/**
 * Coordinate conversion between lat/lng and the Three.js scene.
 *
 * Scene axes: +X = east, +Y = up (altitude), -Z = north. 1 unit = 1 metre.
 * The map uses Web Mercator (same as the tiles). Mercator stretches distances by
 * 1/cos(lat); we scale by cos(originLat) so distances near the origin are true metres,
 * which keeps the altitude (also in metres) in proportion. In Malaysia (lat 1°–7°)
 * the remaining error across the whole country is under 1%.
 */

const R = 6378137; // WGS84 / Web Mercator radius
export const WORLD_SIZE = 2 * Math.PI * R; // mercator width in metres
const DEG = Math.PI / 180;

export function lngLatToMercator(lng, lat) {
    return {
        x: R * lng * DEG,
        y: R * Math.log(Math.tan(Math.PI / 4 + (lat * DEG) / 2)),
    };
}

export function mercatorToLngLat(x, y) {
    return {
        lng: x / R / DEG,
        lat: (2 * Math.atan(Math.exp(y / R)) - Math.PI / 2) / DEG,
    };
}

/** Converts between lat/lng and scene metres around a fixed origin. */
export class LocalProjection {
    constructor(originLat, originLng) {
        this.origin = lngLatToMercator(originLng, originLat);
        this.k = Math.cos(originLat * DEG);
    }

    /** Mercator metres -> scene x/z */
    mercToScene(mx, my) {
        return { x: (mx - this.origin.x) * this.k, z: -(my - this.origin.y) * this.k };
    }

    toScene(lat, lng, alt = 0) {
        const m = lngLatToMercator(lng, lat);
        const { x, z } = this.mercToScene(m.x, m.y);
        return { x, y: alt, z };
    }

    toLngLat(x, z) {
        return mercatorToLngLat(x / this.k + this.origin.x, -z / this.k + this.origin.y);
    }
}

/** XYZ tile -> mercator bounds in metres. */
export function tileBounds(z, x, y) {
    const size = WORLD_SIZE / 2 ** z;
    const minX = -WORLD_SIZE / 2 + x * size;
    const maxY = WORLD_SIZE / 2 - y * size;
    return { minX, maxX: minX + size, minY: maxY - size, maxY, size };
}

/** lng/lat -> fractional tile coordinates at zoom z. */
export function lngLatToTile(lng, lat, z) {
    const n = 2 ** z;
    const r = lat * DEG;
    return {
        x: ((lng + 180) / 360) * n,
        y: ((1 - Math.log(Math.tan(r) + 1 / Math.cos(r)) / Math.PI) / 2) * n,
    };
}

/** True when tile (z,x,y) overlaps bbox [west, south, east, north]. */
export function tileIntersectsBbox(z, x, y, [west, south, east, north]) {
    const a = lngLatToTile(west, north, z);
    const b = lngLatToTile(east, south, z);
    return x >= Math.floor(a.x) && x <= Math.floor(b.x) && y >= Math.floor(a.y) && y <= Math.floor(b.y);
}
