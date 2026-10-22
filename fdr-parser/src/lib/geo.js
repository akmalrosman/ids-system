const EARTH_RADIUS_M = 6371008.8;
const RAD = Math.PI / 180;

/** Great-circle distance in metres. */
export function haversine(lat1, lng1, lat2, lng2) {
    const dLat = (lat2 - lat1) * RAD;
    const dLng = (lng2 - lng1) * RAD;
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * RAD) * Math.cos(lat2 * RAD) * Math.sin(dLng / 2) ** 2;

    return 2 * EARTH_RADIUS_M * Math.asin(Math.min(1, Math.sqrt(a)));
}

/** Normalises an angle to 0..360 degrees. */
export function wrap360(deg) {
    return ((deg % 360) + 360) % 360;
}

export function isValidCoordinate(lat, lng) {
    return (
        Number.isFinite(lat) &&
        Number.isFinite(lng) &&
        Math.abs(lat) <= 90 &&
        Math.abs(lng) <= 180 &&
        // DJI writes 0,0 before it has a GPS fix.
        !(Math.abs(lat) < 1e-6 && Math.abs(lng) < 1e-6)
    );
}
