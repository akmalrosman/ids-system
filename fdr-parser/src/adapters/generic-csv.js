import { rowsToSamples } from './tabular.js';

/**
 * Fallback for any CSV with obvious column names (time, lat, lon, alt, ...).
 * Handy for test data or for converting other recorders (e.g. the aircraft
 * .tkd files) to CSV first.
 */
export default {
    id: 'generic-csv',
    kind: 'aircraft',

    detect({ head }) {
        const firstLine = head.split(/\r?\n/).find((l) => l.includes(',')) ?? '';
        const names = firstLine.toLowerCase().split(',').map((h) => h.trim().replace(/\s*[[(].*$/, ''));
        return names.some((n) => ['lat', 'latitude'].includes(n)) && names.some((n) => ['lng', 'lon', 'long', 'longitude'].includes(n));
    },

    parse({ text, options }) {
        return rowsToSamples(text, profile, options);
    },
};

export const profile = {
    timeUnitDefault: 's',
    verticalSpeedIsDown: false,
    dateTimeIsUtc: false,
    columns: {
        t: ['t', 'time', 'seconds', 'elapsed', 'time_s'],
        dateTime: ['timestamp', 'datetime', 'date_time'],
        lat: ['lat', 'latitude'],
        lng: ['lng', 'lon', 'long', 'longitude'],
        alt: ['alt', 'altitude', 'height'],
        speed: ['speed', 'groundspeed', 'gs'],
        vz: ['vs', 'vertical_speed', 'vspeed'],
        pitch: ['pitch'],
        roll: ['roll', 'bank'],
        yaw: ['yaw', 'heading', 'hdg'],
        battery: ['battery'],
        mode: ['mode'],
    },
};
