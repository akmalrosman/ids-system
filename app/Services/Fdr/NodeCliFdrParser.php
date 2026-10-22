<?php

namespace App\Services\Fdr;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Runs `node fdr-parser/bin/fdr-parse.js` as a child process.
 *
 * To debug a file by hand, run the same command in a terminal:
 *   node fdr-parser/bin/fdr-parse.js path/to/file.txt --pretty
 */
class NodeCliFdrParser implements FdrParser
{
    public function parse(string $path, ?string $originalName = null): Telemetry
    {
        if (! is_file($path)) {
            throw new FdrParseException("FDR file not found on disk: {$path}", 'FILE_NOT_FOUND');
        }

        File::ensureDirectoryExists($tmpDir = storage_path('app/tmp'));
        $output = $tmpDir.DIRECTORY_SEPARATOR.'fdr-'.Str::uuid().'.json';

        $command = [
            config('ids.fdr.node_binary'),
            config('ids.fdr.cli_script'),
            $path,
            '--output', $output,
            '--tz', config('ids.fdr.log_timezone_offset'),
            '--max-rate', (string) config('ids.fdr.max_sample_rate'),
        ];
        if ($originalName) {
            array_push($command, '--file-name', $originalName);
        }

        try {
            $result = Process::timeout(config('ids.fdr.timeout'))
                ->env(array_filter([
                    'DJI_LOG_PARSER_CMD' => config('ids.fdr.dji_log_cmd'),
                    'DJI_API_KEY' => config('ids.fdr.dji_api_key'),
                ]))
                ->run($command);

            if ($result->failed()) {
                $stderr = $result->errorOutput();

                // 127 = "command not found" on Linux, 9009 / "is not recognized" on Windows.
                if (in_array($result->exitCode(), [127, 9009], true) || str_contains(strtolower($stderr), 'is not recognized')) {
                    throw new FdrParseException(
                        'Node.js was not found. Install Node.js or set NODE_BINARY in .env to its full path.',
                        'NODE_NOT_FOUND',
                    );
                }

                throw FdrParseException::fromParserOutput($stderr, 'The FDR parser exited with code '.$result->exitCode().'.');
            }

            return Telemetry::fromJson(File::get($output));
        } finally {
            File::delete($output);
        }
    }
}
