#!/usr/bin/env node
/**
 * Downloads satellite map tiles ONCE so the 3D presentation works offline.
 * Tiles are saved to public/tiles/{z}/{x}/{y}.jpg and listed in public/tiles/manifest.json
 * (the presentation only requests tiles that the manifest says exist).
 *
 *   npm run tiles:download -- --preset malaysia          whole country, zoom 5-10  (~1,600 tiles, ~15 MB)
 *   npm run tiles:download -- --preset airbases          every airbase in config/ids.php, zoom 11-15
 *   npm run tiles:download -- --bbox 99.6,6.2,99.9,6.45 --zoom 11-16    any area (west,south,east,north)
 *
 * Options:
 *   --source eox|custom   eox = Sentinel-2 cloudless 2024 by EOX (CC BY-NC-SA 4.0, fine for non-commercial/FYP use)
 *   --url <template>      custom source, e.g. https://example.com/{z}/{x}/{y}.jpg ({y} = XYZ row)
 *   --out <dir>           default public/tiles
 *   --concurrency <n>     parallel downloads, default 6
 *
 * Check the licence / terms of any tile source before bulk downloading from it.
 */
import { mkdir, readFile, stat, writeFile } from 'node:fs/promises';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { parseArgs } from 'node:util';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../..');

const SOURCES = {
    eox: {
        url: 'https://tiles.maps.eox.at/wmts/1.0.0/s2cloudless-2024_3857/default/g/{z}/{y}/{x}.jpg',
        attribution: 'Sentinel-2 cloudless 2024 by EOX IT Services GmbH (contains modified Copernicus Sentinel data 2024)',
    },
};

// Keep in sync with config/ids.php ('map.bounds' and 'locations').
const MALAYSIA = [99.5, 0.75, 119.4, 7.6];
const AIRBASES = {
    Langkawi: [6.3297, 99.7287],
    'Gong Kedak': [5.7973, 102.4894],
    Subang: [3.1306, 101.549],
    Butterworth: [5.4659, 100.391],
    Kuantan: [3.7754, 103.2094],
    Labuan: [5.3007, 115.25],
};
const AIRBASE_RADIUS_DEG = 0.06; // ≈ 6.5 km each side

const { values } = parseArgs({
    options: {
        preset: { type: 'string' },
        bbox: { type: 'string' },
        zoom: { type: 'string' },
        source: { type: 'string', default: 'eox' },
        url: { type: 'string' },
        out: { type: 'string', default: join(root, 'public', 'tiles') },
        concurrency: { type: 'string', default: '6' },
        help: { type: 'boolean', short: 'h', default: false },
    },
});

if (values.help || (!values.preset && !values.bbox)) {
    console.log(await readFile(fileURLToPath(import.meta.url), 'utf8').then((s) => s.split('*/')[0]));
    process.exit(values.help ? 0 : 1);
}

const source = values.url ? { url: values.url, attribution: 'Custom source' } : SOURCES[values.source];
if (!source) throw new Error(`Unknown --source ${values.source}`);

/** @type {{ name: string, bbox: number[], zooms: number[] }[]} */
const jobs = [];
if (values.preset === 'malaysia') {
    jobs.push({ name: 'Malaysia', bbox: MALAYSIA, zooms: range(values.zoom ?? '5-10') });
} else if (values.preset === 'airbases') {
    for (const [name, [lat, lng]] of Object.entries(AIRBASES)) {
        const r = AIRBASE_RADIUS_DEG;
        jobs.push({ name, bbox: [lng - r, lat - r, lng + r, lat + r], zooms: range(values.zoom ?? '11-15') });
    }
} else if (values.bbox) {
    jobs.push({ name: 'Custom area', bbox: values.bbox.split(',').map(Number), zooms: range(values.zoom ?? '11-15') });
} else {
    throw new Error(`Unknown --preset ${values.preset} (use malaysia or airbases)`);
}

const manifestPath = join(values.out, 'manifest.json');
const manifest = await readJson(manifestPath, { attribution: source.attribution, levels: [] });
manifest.attribution = source.attribution;

for (const job of jobs) {
    for (const z of job.zooms) {
        const tiles = tilesInBbox(job.bbox, z);
        process.stdout.write(`${job.name} z${z}: ${tiles.length} tiles `);
        const result = await downloadAll(tiles, z);
        console.log(`(${result.downloaded} new, ${result.skipped} cached, ${result.failed} failed)`);

        if (!manifest.levels.some((l) => l.zoom === z && sameBbox(l.bbox, job.bbox))) {
            manifest.levels.push({ zoom: z, bbox: job.bbox.map((v) => round(v, 5)) });
        }
        await writeFile(manifestPath, JSON.stringify(manifest, null, 2));
    }
}

console.log(`\nDone. Manifest: ${manifestPath}`);

// ---------------------------------------------------------------------------

async function downloadAll(tiles, z) {
    const result = { downloaded: 0, skipped: 0, failed: 0 };
    let next = 0;

    const worker = async () => {
        while (next < tiles.length) {
            const [x, y] = tiles[next++];
            const file = join(values.out, String(z), String(x), `${y}.jpg`);
            if (await exists(file)) {
                result.skipped++;
                continue;
            }
            const url = source.url.replace('{z}', z).replace('{x}', x).replace('{y}', y);
            try {
                const res = await fetchWithRetry(url);
                await mkdir(dirname(file), { recursive: true });
                await writeFile(file, Buffer.from(await res.arrayBuffer()));
                result.downloaded++;
            } catch (e) {
                result.failed++;
                if (result.failed <= 3) console.warn(`\n  ${url}: ${e.message}`);
            }
        }
    };

    await Promise.all(Array.from({ length: Number(values.concurrency) }, worker));
    return result;
}

async function fetchWithRetry(url, attempts = 3) {
    for (let i = 1; ; i++) {
        try {
            const res = await fetch(url, { headers: { 'User-Agent': 'IDS-FYP-tile-downloader/1.0' } });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res;
        } catch (e) {
            if (i >= attempts) throw e;
            await new Promise((r) => setTimeout(r, 500 * i));
        }
    }
}

function tilesInBbox([west, south, east, north], z) {
    const [x0, y0] = lngLatToTile(west, north, z);
    const [x1, y1] = lngLatToTile(east, south, z);
    const tiles = [];
    for (let x = x0; x <= x1; x++) for (let y = y0; y <= y1; y++) tiles.push([x, y]);
    return tiles;
}

function lngLatToTile(lng, lat, z) {
    const n = 2 ** z;
    const r = (lat * Math.PI) / 180;
    return [
        Math.floor(((lng + 180) / 360) * n),
        Math.floor(((1 - Math.log(Math.tan(r) + 1 / Math.cos(r)) / Math.PI) / 2) * n),
    ];
}

function range(spec) {
    const [a, b = a] = spec.split('-').map(Number);
    return Array.from({ length: b - a + 1 }, (_, i) => a + i);
}

async function exists(file) {
    try {
        return (await stat(file)).size > 0;
    } catch {
        return false;
    }
}

async function readJson(file, fallback) {
    try {
        return JSON.parse(await readFile(file, 'utf8'));
    } catch {
        return fallback;
    }
}

function sameBbox(a, b) {
    return a.every((v, i) => Math.abs(v - b[i]) < 1e-4);
}

function round(v, d) {
    return Math.round(v * 10 ** d) / 10 ** d;
}
