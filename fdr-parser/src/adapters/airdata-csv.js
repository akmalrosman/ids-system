import { rowsToSamples } from './tabular.js';

/**
 * CSV exported from AirData UAV (airdata.com) for DJI flights:
 * "time(millisecond)", "datetime(utc)", "latitude", "height_above_takeoff(feet)", ...
 */
export default {
    id: 'airdata-csv',
    kind: 'drone',

    detect({ head }) {
        return /time\(millisecond\)/i.test(head) && /latitude/i.test(head);
    },

    parse({ text, options }) {
        return rowsToSamples(text, profile, options);
    },
};

export const profile = {
    timeUnitDefault: 'ms',
    // AirData's zSpeed sign is not documented, so vertical speed is derived from altitude instead.
    verticalSpeedIsDown: false,
    dateTimeIsUtc: true,
    columns: {
        t: ['time'],
        dateTime: ['datetime'],
        lat: ['latitude'],
        lng: ['longitude'],
        alt: ['height_above_takeoff'],
        altMsl: ['altitude_above_seaLevel'],
        speed: ['speed'],
        pitch: ['pitch'],
        roll: ['roll'],
        yaw: ['compass_heading'],
        battery: ['battery_percent'],
        sats: ['satellites'],
        mode: ['flycState'],
    },
};
