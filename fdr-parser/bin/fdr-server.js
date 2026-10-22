#!/usr/bin/env node
/**
 * Optional HTTP version of the parser (Laravel FDR_PARSER_DRIVER=http).
 * Use it when the Node runtime lives on a different server/container than PHP.
 *
 *   PORT=3100 node fdr-parser/bin/fdr-server.js
 *
 *   GET  /health                          -> {"ok": true}
 *   POST /parse?tz=+08:00&maxRate=10      body = raw file bytes, header X-File-Name = original name
 *        200 -> telemetry JSON | 422 -> {"error","code"} | 500 -> {"error","code"}
 *
 * Binds to 127.0.0.1 by default; set HOST=0.0.0.0 only inside a private network (e.g. Docker).
 */
import { createServer } from 'node:http';
import { mkdtemp, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { parseBuffer, FdrError } from '../src/index.js';
import { looksBinary } from '../src/adapters/dji-binary.js';

const PORT = Number(process.env.PORT ?? 3100);
const HOST = process.env.HOST ?? '127.0.0.1';
const MAX_BODY_BYTES = Number(process.env.MAX_BODY_MB ?? 200) * 1024 * 1024;

const server = createServer(async (req, res) => {
    const url = new URL(req.url, 'http://localhost');

    try {
        if (req.method === 'GET' && url.pathname === '/health') {
            return send(res, 200, { ok: true });
        }

        if (req.method !== 'POST' || url.pathname !== '/parse') {
            return send(res, 404, { error: 'Not found', code: 'NOT_FOUND' });
        }

        const buffer = await readBody(req);
        const options = {
            fileName: req.headers['x-file-name'] ? decodeURIComponent(req.headers['x-file-name']) : undefined,
            tz: url.searchParams.get('tz') ?? '+08:00',
            maxRate: Number(url.searchParams.get('maxRate') ?? 10),
            djiLogCmd: process.env.DJI_LOG_PARSER_CMD || undefined,
            djiApiKey: process.env.DJI_API_KEY || undefined,
        };

        // Binary DJI logs are decoded by an external CLI that needs a real file.
        if (looksBinary(buffer)) {
            const dir = await mkdtemp(join(tmpdir(), 'fdr-'));
            try {
                const inputPath = join(dir, 'input.txt');
                await writeFile(inputPath, buffer);
                return send(res, 200, parseBuffer(buffer, { ...options, inputPath }));
            } finally {
                await rm(dir, { recursive: true, force: true });
            }
        }

        return send(res, 200, parseBuffer(buffer, options));
    } catch (error) {
        if (error instanceof FdrError) return send(res, 422, error.toJSON());
        console.error(error);
        return send(res, 500, { error: error.message, code: 'INTERNAL_ERROR' });
    }
});

server.listen(PORT, HOST, () => console.log(`fdr-parser listening on http://${HOST}:${PORT}`));

function readBody(req) {
    return new Promise((resolve, reject) => {
        const chunks = [];
        let size = 0;
        req.on('data', (chunk) => {
            size += chunk.length;
            if (size > MAX_BODY_BYTES) {
                reject(new FdrError('FILE_TOO_LARGE', `File is larger than ${MAX_BODY_BYTES / 1024 / 1024} MB.`));
                req.destroy();
                return;
            }
            chunks.push(chunk);
        });
        req.on('end', () => resolve(Buffer.concat(chunks)));
        req.on('error', reject);
    });
}

function send(res, status, body) {
    res.writeHead(status, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify(body));
}
