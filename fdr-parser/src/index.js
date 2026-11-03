import { readFile } from 'node:fs/promises';
import { basename } from 'node:path';
import { detectAdapter } from './adapters/index.js';
import { normalize } from './normalize.js';
import { parseOffset } from './lib/time.js';

export { FdrError } from './errors.js';

/**
 * @typedef {object} ParseOptions
 * @property {string} [fileName]   original upload name, stored in the output
 * @property {string} [tz]         offset of local timestamps in the log, e.g. "+08:00"
 * @property {number} [maxRate]    max samples per second in the output (default 10)
 * @property {string} [djiApiKey]  DJI App Key, to fetch the keys of encrypted DJI logs
 * @property {string} [keychainsDir]  folder where those keys are saved / read back (offline reprocessing)
 */

/**
 * Parses an FDR file already in memory.
 *
 * @param {Buffer} buffer
 * @param {ParseOptions} [opts]
 */
export async function parseBuffer(buffer, opts = {}) {
    const options = {
        ...opts,
        offsetMinutes: parseOffset(opts.tz ?? '+08:00'),
        maxRate: Number(opts.maxRate ?? 10),
    };

    const adapter = detectAdapter(buffer);
    const text = adapter.id === 'dji-binary' ? '' : buffer.toString('utf8');
    const { samples, meta } = await adapter.parse({ buffer, text, options });

    return normalize(samples, meta ?? {}, adapter, options);
}

/**
 * Parses an FDR file from disk.
 *
 * @param {string} path
 * @param {ParseOptions} [opts]
 */
export async function parseFile(path, opts = {}) {
    const buffer = await readFile(path);
    return parseBuffer(buffer, { ...opts, fileName: opts.fileName ?? basename(path) });
}
