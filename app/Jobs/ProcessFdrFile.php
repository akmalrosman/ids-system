<?php

namespace App\Jobs;

use App\Enums\FdrStatus;
use App\Models\Flight;
use App\Services\Fdr\FdrParseException;
use App\Services\Fdr\FdrParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Converts a flight's FDR file into telemetry JSON (runs on the queue).
 *
 * Logs go to storage/logs/fdr-*.log. To run it again from the terminal:
 *   php artisan fdr:process {flight_id}
 */
class ProcessFdrFile implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    /**
     * @param  string  $fdrPath  the file this job was queued for; if the user uploads
     *                           a newer file before the job runs, this job does nothing.
     */
    public function __construct(public Flight $flight, public string $fdrPath) {}

    public function handle(FdrParser $parser): void
    {
        $flight = $this->flight->fresh();

        if (! $flight || $flight->fdr_path !== $this->fdrPath) {
            Log::channel('fdr')->info('Skipped: FDR was replaced or flight deleted.', ['flight_id' => $this->flight->id]);

            return;
        }

        $flight->update(['fdr_status' => FdrStatus::Processing, 'fdr_error' => null]);
        Log::channel('fdr')->info('Processing FDR.', ['flight_id' => $flight->id, 'file' => $flight->fdr_original_name]);

        $disk = Storage::disk(config('ids.disk'));

        try {
            $telemetry = $parser->parse($disk->path($flight->fdr_path), $flight->fdr_original_name);
        } catch (FdrParseException $e) {
            $this->markFailed($flight, $e->getMessage(), $e->reason);

            return;
        }

        $telemetryPath = "telemetry/flight-{$flight->id}.json";
        $disk->put($telemetryPath, $telemetry->toJson());

        $flight->update([
            'fdr_status' => FdrStatus::Ready,
            'fdr_error' => null,
            'fdr_processed_at' => now(),
            'telemetry_path' => $telemetryPath,
            'duration_seconds' => $telemetry->duration(),
            'stats' => $telemetry->summary(),
        ]);

        Log::channel('fdr')->info('FDR ready.', [
            'flight_id' => $flight->id,
            'duration' => $telemetry->duration(),
            'samples' => $telemetry->summary()['samples'] ?? null,
        ]);
    }

    /** Called by Laravel for unexpected errors (crash, timeout). */
    public function failed(?Throwable $exception): void
    {
        $this->markFailed($this->flight, $exception?->getMessage() ?? 'Unknown error', 'INTERNAL_ERROR');
    }

    private function markFailed(Flight $flight, string $message, string $reason): void
    {
        $flight->update([
            'fdr_status' => FdrStatus::Failed,
            'fdr_error' => "[{$reason}] {$message}",
            'fdr_processed_at' => now(),
        ]);

        Log::channel('fdr')->warning('FDR failed.', ['flight_id' => $flight->id, 'reason' => $reason, 'message' => $message]);
    }
}
