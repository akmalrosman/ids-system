<?php

namespace App\Services\Fdr;

/**
 * Converts an FDR file into normalised telemetry.
 *
 * Implementations: NodeCliFdrParser (default) and NodeHttpFdrParser,
 * chosen with FDR_PARSER_DRIVER in .env (bound in AppServiceProvider).
 */
interface FdrParser
{
    /**
     * @param  string  $path  absolute path of the uploaded file
     * @param  string|null  $originalName  name the user uploaded, stored in the telemetry
     *
     * @throws FdrParseException when the file can't be converted
     */
    public function parse(string $path, ?string $originalName = null): Telemetry;
}
