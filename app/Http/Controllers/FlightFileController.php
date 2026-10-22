<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Services\FlightFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a flight's files. Everything goes through here (never a public URL)
 * so the permission middleware on the routes always applies.
 */
class FlightFileController extends Controller
{
    public function __construct(private FlightFileService $files) {}

    /** Normalised telemetry JSON used by the charts and the 3D presentation. */
    public function telemetry(Flight $flight): Response
    {
        $json = $this->files->telemetryJson($flight);
        abort_if($json === null, 404, 'Telemetry is not available for this flight yet.');

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    /** Small JSON polled by the flight page while the FDR is being processed. */
    public function status(Flight $flight): JsonResponse
    {
        return response()->json([
            'status' => $flight->fdr_status->value,
            'label' => $flight->fdr_status->label(),
            'error' => $flight->fdr_error,
        ]);
    }

    /** Download the original FDR upload. */
    public function fdr(Flight $flight): StreamedResponse
    {
        abort_unless($flight->hasFdr(), 404);

        return $this->files->disk()->download($flight->fdr_path, $flight->fdr_original_name);
    }

    /** Video stream; BinaryFileResponse supports Range requests so seeking works. */
    public function bvr(Flight $flight): BinaryFileResponse
    {
        abort_unless($flight->hasBvr() && $this->files->disk()->exists($flight->bvr_path), 404);

        return response()->file($this->files->disk()->path($flight->bvr_path));
    }

    public function reprocess(Flight $flight): RedirectResponse
    {
        abort_unless($flight->hasFdr(), 404);

        $this->files->queueProcessing($flight);

        return back()->with('status', 'FDR queued for processing.');
    }

    public function destroyBvr(Flight $flight): RedirectResponse
    {
        $this->files->deleteBvr($flight);

        return back()->with('status', 'Video removed.');
    }
}
