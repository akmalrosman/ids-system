import { spawnSync } from 'node:child_process';
import { FdrError } from '../errors.js';
import djiLogJson from './dji-log-json.js';

/**
 * Real DJI flight records (DJIFlightRecord_*.txt) are a *binary*, encrypted
 * format. Since log version 13 the records can only be decrypted with a DJI
 * developer App Key (https://developer.dji.com).
 *
 * We do not decrypt them ourselves; we hand the file to the open-source
 * `dji-log` CLI (github.com/lvauvillier/dji-log-parser), which prints the
 * frames as JSON, and then reuse the dji-log-json adapter.
 *
 * Configure in Laravel .env (passed through to this process):
 *   DJI_LOG_PARSER_CMD="dji-log --api-key {apiKey} {input}"
 *   DJI_API_KEY=your-dji-app-key
 */
export default {
    id: 'dji-binary',
    kind: 'drone',

    detect({ buffer }) {
        return looksBinary(buffer);
    },

    parse({ options }) {
        const template = options.djiLogCmd;
        if (!template) {
            throw new FdrError(
                'DJI_BINARY_NOT_CONFIGURED',
                'This is an encrypted DJI binary flight record. Set DJI_LOG_PARSER_CMD and DJI_API_KEY in .env ' +
                    'to decode it with the dji-log CLI, or upload a CSV export of the flight instead.',
            );
        }
        if (!options.inputPath) {
            throw new FdrError('DJI_BINARY_NEEDS_FILE', 'Binary DJI logs must be parsed from a file path.');
        }

        // Split first, substitute after: no shell is involved, so file names can't inject commands.
        const [command, ...args] = template
            .trim()
            .split(/\s+/)
            .map((part) => part.replace('{input}', options.inputPath).replace('{apiKey}', options.djiApiKey ?? ''));

        const result = spawnSync(command, args, { encoding: 'utf8', maxBuffer: 1024 * 1024 * 1024 });

        if (result.error) {
            throw new FdrError('DJI_DECODER_MISSING', `Could not run "${command}": ${result.error.message}`);
        }
        if (result.status !== 0) {
            throw new FdrError('DJI_DECODER_FAILED', `dji-log failed: ${(result.stderr || '').trim().slice(0, 500)}`);
        }

        return djiLogJson.parse({ text: result.stdout });
    },
};

/** True when the first bytes contain control characters that never appear in text logs. */
export function looksBinary(buffer) {
    const sample = buffer.subarray(0, Math.min(buffer.length, 512));
    let control = 0;
    for (const byte of sample) {
        if (byte === 0 || (byte < 32 && ![9, 10, 13].includes(byte))) control++;
    }
    return sample.length > 0 && control / sample.length > 0.05;
}
