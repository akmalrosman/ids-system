/**
 * Unit conversion to SI (metres, metres/second, seconds).
 * Unit strings come from CSV headers, e.g. "[ft]", "(mph)", "[m/s]".
 */

const LENGTH_TO_M = {
    m: 1,
    meter: 1,
    meters: 1,
    metres: 1,
    ft: 0.3048,
    feet: 0.3048,
    foot: 0.3048,
    km: 1000,
    mi: 1609.344,
    nm: 1852,
};

const SPEED_TO_MS = {
    'm/s': 1,
    mps: 1,
    'km/h': 1 / 3.6,
    kmh: 1 / 3.6,
    kph: 1 / 3.6,
    mph: 0.44704,
    kts: 0.514444,
    kt: 0.514444,
    knots: 0.514444,
    'ft/s': 0.3048,
    'ft/min': 0.3048 / 60,
    fpm: 0.3048 / 60,
};

const TIME_TO_S = {
    s: 1,
    sec: 1,
    seconds: 1,
    ms: 0.001,
    millisecond: 0.001,
    milliseconds: 0.001,
};

/**
 * @param {number} value
 * @param {string|null} unit
 * @param {'length'|'speed'|'time'} kind
 * @returns {number}
 */
export function toSI(value, unit, kind) {
    if (!Number.isFinite(value) || !unit) return value;

    const table = kind === 'length' ? LENGTH_TO_M : kind === 'speed' ? SPEED_TO_MS : TIME_TO_S;
    const factor = table[unit.toLowerCase()];

    return factor === undefined ? value : value * factor;
}
