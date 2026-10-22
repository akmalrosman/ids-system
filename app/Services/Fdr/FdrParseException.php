<?php

namespace App\Services\Fdr;

use RuntimeException;

/**
 * The FDR file could not be converted. `$reason` is the machine-readable code
 * from fdr-parser (UNKNOWN_FORMAT, NO_VALID_SAMPLES, ...).
 */
class FdrParseException extends RuntimeException
{
    public function __construct(string $message, public readonly string $reason = 'PARSER_ERROR')
    {
        parent::__construct($message);
    }

    /** Builds the exception from the parser's {"error": "...", "code": "..."} output. */
    public static function fromParserOutput(string $output, string $fallback): self
    {
        $json = json_decode(trim($output), true);

        if (is_array($json) && isset($json['error'])) {
            return new self($json['error'], $json['code'] ?? 'PARSER_ERROR');
        }

        return new self(trim($output) !== '' ? trim($output) : $fallback);
    }
}
