import { rowsToSamples } from './tabular.js';

/**
 * DJI flight record in CSV layout ("OSD.latitude", "OSD.height [ft]", ...).
 *
 * This is what you get when a DJI .txt flight record is decoded by the
 * official DJI FlightRecord tool / PhantomHelp log viewer, and it is the
 * layout of the dummy logs in /sample-data/dji.
 */
export default {
    id: 'dji-csv',
    kind: 'drone',

    detect({ head }) {
        return /OSD\.latitude/i.test(head) && /OSD\.longitude/i.test(head);
    },

    parse({ text, options }) {
        return rowsToSamples(text, profile, options);
    },
};

export const profile = {
    timeUnitDefault: 's',
    verticalSpeedIsDown: true,
    dateTimeIsUtc: true,
    columns: {
        t: ['OSD.flyTime'],
        date: ['CUSTOM.date'],
        clock: ['CUSTOM.updateTime'],
        dateTime: ['CUSTOM.dateTime'],
        lat: ['OSD.latitude'],
        lng: ['OSD.longitude'],
        alt: ['OSD.height'],
        altMsl: ['OSD.altitude'],
        speed: ['OSD.hSpeed'],
        vx: ['OSD.xSpeed'],
        vy: ['OSD.ySpeed'],
        vz: ['OSD.zSpeed'],
        pitch: ['OSD.pitch'],
        roll: ['OSD.roll'],
        yaw: ['OSD.yaw'],
        battery: ['BATTERY.chargeLevel', 'CENTER_BATTERY.relativeCapacity'],
        sats: ['OSD.gpsNum'],
        mode: ['OSD.flycState'],
        aircraftName: ['RECOVER.aircraftName', 'DETAILS.aircraftName'],
        aircraftSn: ['RECOVER.aircraftSn', 'DETAILS.aircraftSn'],
        droneType: ['RECOVER.droneType', 'OSD.droneType'],
        homeLat: ['HOME.latitude'],
        homeLng: ['HOME.longitude'],
    },
};
