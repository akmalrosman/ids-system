import * as THREE from 'three';
import { lngLatToTile, tileBounds, tileIntersectsBbox, WORLD_SIZE } from './geo.js';

const MAX_CACHED_TILES = 600;
const MAX_PARALLEL_LOADS = 8;
const BASE_LAYER_MAX_TILES = 100;
const UPDATE_INTERVAL_MS = 150;
const WATER_COLOR = 0x0b2233;

/**
 * Flat satellite map built from locally hosted XYZ tiles (public/tiles).
 *
 * - A "base" layer covers all of Malaysia at a low zoom and is always shown.
 * - Around the camera target, sharper tiles are loaded in rings (zoom z, z-2, z-4)
 *   depending on how far the camera is.
 * - public/tiles/manifest.json (written by the download script) says which zoom
 *   levels exist for which area, so we never request tiles that were not downloaded.
 * - Nothing outside config('ids.map.bounds') is ever requested or drawn.
 */
export class TileMap {
    /**
     * @param {object} opts
     * @param {import('./geo.js').LocalProjection} opts.projection
     * @param {string} opts.tileUrl   e.g. "/tiles/{z}/{x}/{y}.jpg"
     * @param {number[]} opts.bounds  [west, south, east, north]
     * @param {number} opts.minZoom
     * @param {number} opts.maxZoom
     * @param {number} opts.anisotropy
     */
    constructor({ projection, tileUrl, bounds, minZoom, maxZoom, anisotropy = 1 }) {
        this.projection = projection;
        this.tileUrl = tileUrl;
        this.bounds = bounds;
        this.minZoom = minZoom;
        this.maxZoom = maxZoom;
        this.anisotropy = anisotropy;

        this.group = new THREE.Group();
        this.group.name = 'map';

        /** @type {Map<string, {mesh: THREE.Mesh|null, state: 'loading'|'ready'|'missing', z: number, usedAt: number}>} */
        this.tiles = new Map();
        this.queue = [];
        this.active = 0;
        this.lastUpdate = 0;
        this.levels = null; // from manifest; null = assume every zoom exists inside bounds
        this.attribution = null;
        this.loader = new THREE.TextureLoader();
    }

    async init() {
        await this.loadManifest();
        this.addWater();

        this.baseZoom = this.pickBaseZoom();
        for (const [x, y] of this.tilesInBbox(this.bounds, this.baseZoom)) {
            this.request(this.baseZoom, x, y, true);
        }
    }

    /** Call every frame; it throttles itself. */
    update(camera, target) {
        const now = performance.now();
        if (now - this.lastUpdate < UPDATE_INTERVAL_MS) return;
        this.lastUpdate = now;

        const distance = camera.position.distanceTo(target);
        const { lat, lng } = this.projection.toLngLat(target.x, target.z);

        // Aim for tiles roughly a quarter of the camera distance wide.
        const tileMetres = Math.max(distance * 0.25, 1);
        let z = Math.floor(Math.log2((WORLD_SIZE * this.projection.k) / tileMetres));
        z = Math.min(z, this.maxZoomAt(lng, lat));

        const wanted = new Set();
        for (const [ringZ, radius] of [[z, 3], [z - 2, 2], [z - 4, 2]]) {
            if (ringZ <= this.baseZoom) continue;
            const c = lngLatToTile(lng, lat, ringZ);
            const cx = Math.floor(c.x);
            const cy = Math.floor(c.y);
            for (let dx = -radius; dx <= radius; dx++) {
                for (let dy = -radius; dy <= radius; dy++) {
                    const key = this.request(ringZ, cx + dx, cy + dy, false);
                    if (key) wanted.add(key);
                }
            }
        }

        for (const [key, tile] of this.tiles) {
            if (tile.z === this.baseZoom || !tile.mesh) continue;
            tile.mesh.visible = wanted.has(key);
            if (tile.mesh.visible) tile.usedAt = now;
        }

        this.prune();
    }

    // ------------------------------------------------------------------

    async loadManifest() {
        if (/^https?:/.test(this.tileUrl)) return; // external source: no manifest

        const url = this.tileUrl.split('{z}')[0] + 'manifest.json';
        try {
            const res = await fetch(url);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const manifest = await res.json();
            this.levels = manifest.levels ?? [];
            this.attribution = manifest.attribution ?? null;
        } catch {
            console.warn(`[TileMap] ${url} not found. Run "npm run tiles:download -- --preset malaysia". Showing plain background.`);
            this.levels = [];
        }
    }

    /** Dark "sea" plane under the tiles so missing tiles don't show the void. */
    addWater() {
        const [w, s, e, n] = this.bounds;
        const a = this.projection.toScene(n, w);
        const b = this.projection.toScene(s, e);
        const geometry = new THREE.PlaneGeometry(b.x - a.x, b.z - a.z);
        geometry.rotateX(-Math.PI / 2);
        const water = new THREE.Mesh(geometry, new THREE.MeshBasicMaterial({ color: WATER_COLOR, depthTest: false, depthWrite: false }));
        water.position.set((a.x + b.x) / 2, 0, (a.z + b.z) / 2);
        water.renderOrder = -1000;
        this.group.add(water);
    }

    /** Highest zoom whose tiles over the whole country stay under BASE_LAYER_MAX_TILES. */
    pickBaseZoom() {
        const candidates = this.levels
            ? [...new Set(this.levels.filter((l) => this.covers(l.bbox, this.bounds)).map((l) => l.zoom))]
            : Array.from({ length: this.maxZoom - this.minZoom + 1 }, (_, i) => this.minZoom + i);

        const fitting = candidates.filter((z) => this.tilesInBbox(this.bounds, z).length <= BASE_LAYER_MAX_TILES);
        return fitting.length ? Math.max(...fitting) : this.minZoom;
    }

    maxZoomAt(lng, lat) {
        if (!this.levels) return this.maxZoom;
        const zooms = this.levels
            .filter(({ bbox: [w, s, e, n] }) => lng >= w && lng <= e && lat >= s && lat <= n)
            .map((l) => l.zoom);
        return Math.min(this.maxZoom, zooms.length ? Math.max(...zooms) : this.baseZoom);
    }

    isAvailable(z, x, y) {
        if (z < this.minZoom || z > this.maxZoom) return false;
        if (!tileIntersectsBbox(z, x, y, this.bounds)) return false;
        if (!this.levels) return true;
        return this.levels.some((l) => l.zoom === z && tileIntersectsBbox(z, x, y, l.bbox));
    }

    /** Returns the tile key if the tile exists (or is loading), else null. */
    request(z, x, y, isBase) {
        const key = `${z}/${x}/${y}`;
        const existing = this.tiles.get(key);
        if (existing) return existing.state === 'missing' ? null : key;
        if (!this.isAvailable(z, x, y)) return null;

        this.tiles.set(key, { mesh: null, state: 'loading', z, usedAt: performance.now(), isBase });
        this.queue.push({ key, z, x, y });
        // Coarse tiles first so something appears quickly.
        this.queue.sort((a, b) => a.z - b.z);
        this.pump();
        return key;
    }

    pump() {
        while (this.active < MAX_PARALLEL_LOADS && this.queue.length) {
            const job = this.queue.shift();
            if (!this.tiles.has(job.key)) continue; // pruned while waiting
            this.active++;
            const url = this.tileUrl.replace('{z}', job.z).replace('{x}', job.x).replace('{y}', job.y);
            this.loader.load(
                url,
                (texture) => {
                    this.active--;
                    this.onTileLoaded(job, texture);
                    this.pump();
                },
                undefined,
                () => {
                    this.active--;
                    const tile = this.tiles.get(job.key);
                    if (tile) tile.state = 'missing';
                    this.pump();
                },
            );
        }
    }

    onTileLoaded({ key, z, x, y }, texture) {
        const tile = this.tiles.get(key);
        if (!tile) {
            texture.dispose();
            return;
        }

        texture.colorSpace = THREE.SRGBColorSpace;
        texture.anisotropy = this.anisotropy;

        const b = tileBounds(z, x, y);
        const p0 = this.projection.mercToScene(b.minX, b.maxY);
        const p1 = this.projection.mercToScene(b.maxX, b.minY);
        const geometry = new THREE.PlaneGeometry(p1.x - p0.x, p1.z - p0.z);
        geometry.rotateX(-Math.PI / 2);

        const mesh = new THREE.Mesh(geometry, new THREE.MeshBasicMaterial({ map: texture, depthTest: false, depthWrite: false }));
        mesh.position.set((p0.x + p1.x) / 2, 0, (p0.z + p1.z) / 2);
        // Ground is drawn first, sharper zoom levels on top of coarser ones.
        mesh.renderOrder = -500 + z;
        mesh.matrixAutoUpdate = false;
        mesh.updateMatrix();
        mesh.visible = tile.isBase || false;

        tile.mesh = mesh;
        tile.state = 'ready';
        this.group.add(mesh);
        this.lastUpdate = 0; // re-evaluate visibility on next frame
    }

    prune() {
        if (this.tiles.size <= MAX_CACHED_TILES) return;
        const removable = [...this.tiles.entries()]
            .filter(([, t]) => !t.isBase && t.mesh && !t.mesh.visible)
            .sort((a, b) => a[1].usedAt - b[1].usedAt);

        for (const [key, tile] of removable.slice(0, this.tiles.size - MAX_CACHED_TILES)) {
            this.group.remove(tile.mesh);
            tile.mesh.geometry.dispose();
            tile.mesh.material.map?.dispose();
            tile.mesh.material.dispose();
            this.tiles.delete(key);
        }
    }

    tilesInBbox([w, s, e, n], z) {
        const a = lngLatToTile(w, n, z);
        const b = lngLatToTile(e, s, z);
        const out = [];
        for (let x = Math.floor(a.x); x <= Math.floor(b.x); x++) {
            for (let y = Math.floor(a.y); y <= Math.floor(b.y); y++) out.push([x, y]);
        }
        return out;
    }

    covers([w, s, e, n], [w2, s2, e2, n2]) {
        return w <= w2 + 0.01 && s <= s2 + 0.01 && e >= e2 - 0.01 && n >= n2 - 0.01;
    }
}
