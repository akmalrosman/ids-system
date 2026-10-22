<?php

namespace App\Services;

use App\Enums\FdrStatus;
use App\Jobs\ProcessFdrFile;
use App\Models\Flight;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Everything to do with a flight's files, kept in one place:
 *
 *   fdr/{flight_id}/...          original FDR upload
 *   telemetry/flight-{id}.json   JSON generated from the FDR
 *   bvr/{flight_id}/...          video recording
 */
class FlightFileService
{
    public function disk(): Filesystem
    {
        return Storage::disk(config('ids.disk'));
    }

    /** Saves a new FDR file and queues it for conversion. */
    public function storeFdr(Flight $flight, UploadedFile $file): void
    {
        $this->deleteFdr($flight);

        $path = $file->store("fdr/{$flight->id}", config('ids.disk'));

        $flight->update([
            'fdr_path' => $path,
            'fdr_original_name' => $file->getClientOriginalName(),
            'fdr_status' => FdrStatus::Pending,
            'fdr_error' => null,
            'fdr_processed_at' => null,
        ]);

        $this->queueProcessing($flight);
    }

    /** Runs the conversion again for the current FDR file. */
    public function queueProcessing(Flight $flight): void
    {
        if (! $flight->fdr_path) {
            return;
        }

        $flight->update(['fdr_status' => FdrStatus::Pending, 'fdr_error' => null]);

        ProcessFdrFile::dispatch($flight, $flight->fdr_path)->afterCommit();
    }

    public function deleteFdr(Flight $flight): void
    {
        $this->disk()->delete(array_filter([$flight->fdr_path, $flight->telemetry_path]));

        $flight->update([
            'fdr_path' => null,
            'fdr_original_name' => null,
            'fdr_status' => FdrStatus::None,
            'fdr_error' => null,
            'fdr_processed_at' => null,
            'telemetry_path' => null,
            'stats' => null,
            'duration_seconds' => null,
        ]);
    }

    public function storeBvr(Flight $flight, UploadedFile $file): void
    {
        $this->deleteBvr($flight);

        $flight->update([
            'bvr_path' => $file->store("bvr/{$flight->id}", config('ids.disk')),
            'bvr_original_name' => $file->getClientOriginalName(),
        ]);
    }

    public function deleteBvr(Flight $flight): void
    {
        if ($flight->bvr_path) {
            $this->disk()->delete($flight->bvr_path);
        }

        $flight->update(['bvr_path' => null, 'bvr_original_name' => null]);
    }

    /** Removes every file of a flight (used before deleting it). */
    public function deleteAll(Flight $flight): void
    {
        $this->disk()->deleteDirectory("fdr/{$flight->id}");
        $this->disk()->deleteDirectory("bvr/{$flight->id}");

        if ($flight->telemetry_path) {
            $this->disk()->delete($flight->telemetry_path);
        }
    }

    /** Decoded telemetry JSON as a string, or null if not ready. */
    public function telemetryJson(Flight $flight): ?string
    {
        if (! $flight->hasTelemetry() || ! $this->disk()->exists($flight->telemetry_path)) {
            return null;
        }

        return $this->disk()->get($flight->telemetry_path);
    }
}
