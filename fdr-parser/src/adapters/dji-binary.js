import { createHash } from 'node:crypto';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { DJILog } from 'dji-log-parser-js';
import { FdrError } from '../errors.js';
import { framesToSamples } from './dji-log-json.js';

/**
 * Real DJI flight records (DJIFlightRecord_*.txt) are a *binary* format,
 * decoded here with the open-source dji-log-parser (npm dji-log-parser-js).
 *
 * Since log version 13 the records are encrypted. The keys ("keychains") are
 * fetched once from DJI's API with a free DJI App Key (https://developer.dji.com,
 * env DJI_API_KEY) and need internet. When `keychainsDir` is given, the keys are
 * saved there as <sha256 of the file>.json, so the same file can later be
 * processed again (or uploaded again) offline.
 */
export default {
    id: 'dji-binary',
    kind: 'drone',

    detect({ buffer }) {
        return looksBinary(buffer);
    },

    async parse({ buffer, options }) {
        let log;
        try {
            log = new DJILog(buffer);
        } catch (e) {
            throw new FdrError('DJI_BINARY_INVALID', `This binary file is not a readable DJI flight record: ${errorText(e)}`);
        }

        const keychains = log.version >= 13 ? await loadKeychains(log, buffer, options) : undefined;

        let frames;
        try {
            frames = log.frames(keychains);
        } catch (e) {
            throw new FdrError('DJI_DECODE_FAILED', `The DJI flight record could not be decoded: ${errorText(e)}`);
        }

        const details = log.details ?? {};
        return {
            samples: framesToSamples(frames),
            meta: {
                aircraftName: details.aircraftName,
                aircraftSn: details.aircraftSn,
                droneType: details.productType,
            },
        };
    },
};

/** Saved keychains if there are any, otherwise fetched from DJI (and saved). */
async function loadKeychains(log, buffer, { keychainsDir, djiApiKey }) {
    const keychainsPath = keychainsDir ? join(keychainsDir, `${createHash('sha256').update(buffer).digest('hex')}.json`) : null;

    if (keychainsPath && existsSync(keychainsPath)) {
        return JSON.parse(readFileSync(keychainsPath, 'utf8'));
    }

    if (!djiApiKey) {
        throw new FdrError(
            'DJI_API_KEY_MISSING',
            `This DJI flight record (log version ${log.version}) is encrypted. Set DJI_API_KEY in .env to decode it.`,
        );
    }

    let keychains;
    try {
        keychains = await log.fetchKeychains(djiApiKey);
    } catch (e) {
        throw new FdrError(
            'DJI_KEYCHAIN_FETCH_FAILED',
            `Could not get the decryption keys from DJI (needs internet and a valid DJI_API_KEY): ${errorText(e)}`,
        );
    }

    if (keychainsPath) {
        mkdirSync(keychainsDir, { recursive: true });
        writeFileSync(keychainsPath, JSON.stringify(keychains));
    }
    return keychains;
}

/** The WASM decoder throws plain strings as well as Errors. */
function errorText(e) {
    return String(e?.message ?? e).slice(0, 500);
}

/** True when the first bytes contain control characters that never appear in text logs. */
export function looksBinary(buffer) {
    const sample = buffer.subarray(0, Math.min(buffer.length, 512));
    let control = 0;
    for (const byte of sample) {
        if (byte === 0 || (byte < 32 && ![9, 10, 13].includes(byte))) control++;
    }
    return sample.length > 0 && control / sample.length > 0.05;
}
