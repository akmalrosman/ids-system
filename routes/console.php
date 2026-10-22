<?php

use App\Jobs\ProcessFdrFile;
use App\Models\Flight;
use Illuminate\Support\Facades\Artisan;

/*
| (Re)convert FDR files without the queue, showing the result in the terminal.
|   php artisan fdr:process 12        one flight
|   php artisan fdr:process --all     every flight that has an FDR
*/
Artisan::command('fdr:process {flight? : Flight ID} {--all : Process every flight with an FDR file}', function () {
    $flights = $this->option('all')
        ? Flight::whereNotNull('fdr_path')->get()
        : Flight::whereKey($this->argument('flight'))->get();

    if ($flights->isEmpty()) {
        $this->error('No matching flight with an FDR file. Pass a flight ID or --all.');

        return 1;
    }

    foreach ($flights as $flight) {
        if (! $flight->fdr_path) {
            $this->warn("Flight {$flight->id} has no FDR file.");

            continue;
        }

        ProcessFdrFile::dispatchSync($flight, $flight->fdr_path);
        $flight->refresh();

        $flight->fdr_error
            ? $this->error("Flight {$flight->id}: {$flight->fdr_error}")
            : $this->info("Flight {$flight->id}: {$flight->fdr_status->label()} ({$flight->duration_seconds} s)");
    }

    return 0;
})->purpose('Convert flight FDR files to telemetry JSON');
