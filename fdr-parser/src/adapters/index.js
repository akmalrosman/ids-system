import djiBinary from './dji-binary.js';
import djiLogJson from './dji-log-json.js';
import djiCsv from './dji-csv.js';
import airdataCsv from './airdata-csv.js';
import genericCsv from './generic-csv.js';
import { FdrError } from '../errors.js';

/**
 * Registered input formats, checked in this order.
 *
 * To support a new recorder (e.g. the aircraft .tkd files) create
 * `src/adapters/tkd.js` with { id, kind, detect(), parse() } and add it here.
 */
export const adapters = [djiBinary, djiLogJson, djiCsv, airdataCsv, genericCsv];

/**
 * @param {Buffer} buffer
 * @returns {typeof adapters[number]}
 */
export function detectAdapter(buffer) {
    const head = buffer.subarray(0, 8192).toString('utf8');
    const adapter = adapters.find((a) => a.detect({ buffer, head }));

    if (!adapter) {
        throw new FdrError(
            'UNKNOWN_FORMAT',
            'Unrecognised FDR file. Supported: DJI flight record CSV (OSD.* columns), DJI binary .txt (needs dji-log), ' +
                'dji-log JSON, AirData CSV, or a CSV with lat/lon columns.',
        );
    }

    return adapter;
}
