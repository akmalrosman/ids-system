#!/usr/bin/env node
/**
 * Command line converter used by Laravel (App\Services\Fdr\NodeCliFdrParser).
 *
 *   node fdr-parser/bin/fdr-parse.js <input> [--output out.json] [--tz +08:00] [--max-rate 10]
 *                                            [--file-name original.txt] [--keychains-dir dir] [--pretty]
 *
 * Without --output the telemetry JSON is printed to stdout.
 * With --output a one-line summary is printed instead.
 * Errors are printed to stderr as {"error": "...", "code": "..."}; exit code 2 = bad file, 1 = crash.
 *
 * Encrypted DJI logs need the env var DJI_API_KEY (once per file; with --keychains-dir
 * the decryption keys are kept there and later runs work offline).
 */
import { writeFile } from 'node:fs/promises';
import { parseArgs } from 'node:util';
import { parseFile, FdrError } from '../src/index.js';

const { values, positionals } = parseArgs({
    allowPositionals: true,
    options: {
        output: { type: 'string', short: 'o' },
        tz: { type: 'string', default: '+08:00' },
        'max-rate': { type: 'string', default: '10' },
        'file-name': { type: 'string' },
        'keychains-dir': { type: 'string' },
        pretty: { type: 'boolean', default: false },
        help: { type: 'boolean', short: 'h', default: false },
    },
});

if (values.help || positionals.length !== 1) {
    console.log('Usage: fdr-parse <input> [--output out.json] [--tz +08:00] [--max-rate 10] [--file-name name] [--keychains-dir dir] [--pretty]');
    process.exit(values.help ? 0 : 1);
}

try {
    const telemetry = await parseFile(positionals[0], {
        fileName: values['file-name'],
        tz: values.tz,
        maxRate: Number(values['max-rate']),
        djiApiKey: process.env.DJI_API_KEY || undefined,
        keychainsDir: values['keychains-dir'],
    });

    const json = JSON.stringify(telemetry, null, values.pretty ? 2 : undefined);

    if (values.output) {
        await writeFile(values.output, json);
        console.log(
            JSON.stringify({
                ok: true,
                adapter: telemetry.source.adapter,
                samples: telemetry.stats.samples,
                duration: telemetry.duration,
                startTime: telemetry.startTime,
            }),
        );
    } else {
        process.stdout.write(json);
    }
} catch (error) {
    if (error instanceof FdrError) {
        console.error(JSON.stringify(error.toJSON()));
        process.exit(2);
    }
    if (error.code === 'ENOENT') {
        console.error(JSON.stringify({ error: `File not found: ${positionals[0]}`, code: 'FILE_NOT_FOUND' }));
        process.exit(2);
    }
    console.error(JSON.stringify({ error: error.stack ?? String(error), code: 'INTERNAL_ERROR' }));
    process.exit(1);
}
