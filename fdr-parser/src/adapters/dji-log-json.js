import { FdrError } from '../errors.js';

/**
 * JSON produced by the open-source `dji-log` CLI (github.com/lvauvillier/dji-log-parser):
 *   { "version": 14, "details": {...}, "frames": [ { "custom": {...}, "osd": {...}, "battery": {...} } ] }
 * Units are already metric.
 */
export default {
    id: 'dji-log-json',
    kind: 'drone',

    detect({ head }) {
        return /^\s*[[{]/.test(head) && /"osd"\s*:/.test(head);
    },

    parse({ text }) {
        let json;
        try {
            json = JSON.parse(text);
        } catch (e) {
            throw new FdrError('INVALID_JSON', `The JSON file could not be read: ${e.message}`);
        }

        const frames = Array.isArray(json) ? json : json.frames;
        if (!Array.isArray(frames)) {
            throw new FdrError('INVALID_JSON', 'Expected a "frames" array in the DJI log JSON.');
        }

        const details = json.details ?? {};
        const meta = {
            aircraftName: details.aircraftName,
            aircraftSn: details.aircraftSn,
            droneType: details.productType,
        };

        const samples = frames.map((f) => {
            const osd = f.osd ?? {};
            const dateTime = f.custom?.dateTime;

            return {
                t: osd.flyTime,
                time: dateTime ? Date.parse(dateTime) : null,
                lat: osd.latitude,
                lng: osd.longitude,
                alt: osd.height,
                altMsl: osd.altitude,
                speed: Number.isFinite(osd.xSpeed) && Number.isFinite(osd.ySpeed) ? Math.hypot(osd.xSpeed, osd.ySpeed) : undefined,
                vs: Number.isFinite(osd.zSpeed) ? -osd.zSpeed : undefined,
                pitch: osd.pitch,
                roll: osd.roll,
                yaw: osd.yaw,
                battery: f.battery?.chargeLevel,
                sats: osd.gpsNum,
                mode: osd.flycState,
            };
        });

        return { samples, meta };
    },
};
