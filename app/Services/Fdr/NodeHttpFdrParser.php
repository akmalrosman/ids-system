<?php

namespace App\Services\Fdr;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Sends the file to the fdr-parser HTTP service (fdr-parser/bin/fdr-server.js).
 * Health check: curl http://127.0.0.1:3100/health
 */
class NodeHttpFdrParser implements FdrParser
{
    public function parse(string $path, ?string $originalName = null): Telemetry
    {
        if (! is_file($path)) {
            throw new FdrParseException("FDR file not found on disk: {$path}", 'FILE_NOT_FOUND');
        }

        $url = rtrim(config('ids.fdr.http_url'), '/').'/parse?'.http_build_query([
            'tz' => config('ids.fdr.log_timezone_offset'),
            'maxRate' => config('ids.fdr.max_sample_rate'),
        ]);

        try {
            $response = Http::timeout(config('ids.fdr.timeout'))
                ->withHeaders(['X-File-Name' => rawurlencode($originalName ?? basename($path))])
                ->withBody(fopen($path, 'r'), 'application/octet-stream')
                ->post($url);
        } catch (ConnectionException $e) {
            throw new FdrParseException(
                'Could not reach the FDR parser service at '.config('ids.fdr.http_url').'. Is `npm run fdr:server` running?',
                'PARSER_UNREACHABLE',
            );
        }

        if ($response->failed()) {
            throw FdrParseException::fromParserOutput($response->body(), 'The FDR parser service returned HTTP '.$response->status().'.');
        }

        return Telemetry::fromJson($response->body());
    }
}
