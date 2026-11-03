<?php

namespace Database\Seeders;

use App\Enums\FdrStatus;
use App\Jobs\ProcessFdrFile;
use App\Models\Aircraft;
use App\Models\AircraftType;
use App\Models\Flight;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * The real DJI Mavic 3 flight records in /database/sample-data/dji, flown around
 * Batu Pahat, Parit Raja and Pagoh (Johor). The drone stands in for aircraft 5201
 * until .tkd files are supported.
 *
 * The records are encrypted; their decryption keys are in sample-data/dji/keychains,
 * so seeding works offline and without a DJI App Key. Safe to run again: records that
 * are already a flight are skipped. On the server:
 *
 *   php artisan db:seed --class=SampleFlightSeeder --force
 */
class SampleFlightSeeder extends Seeder
{
    private const SAMPLE_DIR = 'database/sample-data/dji';

    private const AIRCRAFT = '5201';

    public function run(): void
    {
        File::copyDirectory(base_path(self::SAMPLE_DIR.'/keychains'), config('ids.fdr.keychains_dir'));

        $aircraft = Aircraft::firstOrCreate(['number' => self::AIRCRAFT], [
            'aircraft_type_id' => AircraftType::where('model', 'Su-30MKM')->value('id'),
            'registration' => 'M52-01',
        ]);
        $admin = User::where('email', config('ids.admin.email'))->first();

        $records = collect(File::files(base_path(self::SAMPLE_DIR)))
            ->filter(fn ($file) => str_starts_with($file->getFilename(), 'FlightRecord_'))
            ->sortBy(fn ($file) => $file->getFilename())
            ->values();

        foreach ($records as $i => $record) {
            $name = $record->getFilename();

            if (Flight::where('fdr_original_name', $name)->exists()) {
                continue;
            }

            $this->seedFlight($aircraft, (string) ($i + 1), $name, $record->getPathname(), $admin);
        }
    }

    private function seedFlight(Aircraft $aircraft, string $flightNumber, string $name, string $source, ?User $admin): void
    {
        $flight = Flight::create([
            'aircraft_id' => $aircraft->id,
            'flight_number' => $flightNumber,
            'flown_at' => $this->timeFromFileName($name),
            'created_by' => $admin?->id,
        ]);

        $path = "fdr/{$flight->id}/{$name}";
        Storage::disk(config('ids.disk'))->put($path, File::get($source));
        $flight->update([
            'fdr_path' => $path,
            'fdr_original_name' => $name,
            'fdr_status' => FdrStatus::Pending,
        ]);

        // Convert now (not on the queue) so the flights can be presented straight away.
        ProcessFdrFile::dispatchSync($flight, $path);
        $flight->refresh();

        if ($flight->fdr_status === FdrStatus::Failed) {
            $this->command?->error("{$name}: {$flight->fdr_error}");

            return;
        }

        // The telemetry knows the exact take-off time and place.
        $flight->update(array_filter([
            'flown_at' => isset($flight->stats['start_time'])
                ? Carbon::parse($flight->stats['start_time'])->timezone(config('app.timezone'))
                : null,
            'location' => $this->nearestLocation($flight->stats['home'] ?? null),
        ]));
    }

    /** Name of the config('ids.locations') entry within ~10 km of the take-off point. */
    private function nearestLocation(?array $home): ?string
    {
        if (! $home) {
            return null;
        }

        return collect(config('ids.locations'))
            ->map(fn (array $p) => hypot($p['lat'] - $home['lat'], $p['lng'] - $home['lng']))
            ->filter(fn (float $degrees) => $degrees < 0.1)
            ->sort()
            ->keys()
            ->first();
    }

    /** FlightRecord_2026-10-08_[13-10-54].txt -> 2026-10-08 13:10:54 (phone's local time). */
    private function timeFromFileName(string $name): Carbon
    {
        return preg_match('/(\d{4}-\d{2}-\d{2})_\[(\d{2})-(\d{2})-(\d{2})\]/', $name, $m)
            ? Carbon::parse("{$m[1]} {$m[2]}:{$m[3]}:{$m[4]}")
            : now();
    }
}
