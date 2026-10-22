<?php

namespace App\Services\Fdr;

use Illuminate\Support\Arr;

/**
 * Normalised telemetry produced by fdr-parser ("ids-telemetry" v1).
 * The full schema is documented in fdr-parser/README.md.
 */
final class Telemetry
{
    public const FORMAT = 'ids-telemetry';

    public function __construct(public readonly array $data)
    {
        if (($data['format'] ?? null) !== self::FORMAT || ! isset($data['channels']['t'])) {
            throw new FdrParseException('The parser returned data in an unexpected format.', 'INVALID_OUTPUT');
        }
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new FdrParseException('The parser returned invalid JSON.', 'INVALID_OUTPUT');
        }

        return new self($data);
    }

    public function duration(): float
    {
        return (float) $this->data['duration'];
    }

    /** ISO 8601 with offset, or null when the log had no timestamps. */
    public function startTime(): ?string
    {
        return $this->data['startTime'] ?? null;
    }

    /**
     * Small summary stored in flights.stats so lists don't need the full file.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return [
            ...Arr::only($this->data['stats'], [
                'samples', 'maxAltitude', 'maxSpeed', 'maxClimbRate', 'maxDescentRate',
                'distance', 'maxDistanceFromHome', 'minBattery', 'home', 'bounds',
            ]),
            'start_time' => $this->startTime(),
            'adapter' => $this->data['source']['adapter'] ?? null,
            'kind' => $this->data['source']['kind'] ?? null,
            'aircraft_name' => $this->data['source']['aircraftName'] ?? null,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
