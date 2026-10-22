<?php

namespace App\Http\Controllers;

use App\Enums\FdrStatus;
use App\Models\Flight;
use App\Models\FlightGroup;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        /** @var array<string, int> $fdrCounts flights per FDR status value, e.g. ['ready' => 8] */
        $fdrCounts = Flight::query()->toBase()
            ->selectRaw('fdr_status, count(*) as total')
            ->groupBy('fdr_status')
            ->pluck('total', 'fdr_status')
            ->map(fn ($total) => (int) $total)
            ->all();

        $totalDuration = (float) Flight::sum('duration_seconds');
        $flightsWithDuration = Flight::whereNotNull('duration_seconds')->count();

        return view('dashboard', [
            'totalFlights' => Flight::count(),
            'totalGroups' => FlightGroup::count(),
            'groupedFlights' => Flight::has('groups')->count(),
            'totalDuration' => $totalDuration,
            'averageDuration' => $flightsWithDuration > 0 ? $totalDuration / $flightsWithDuration : null,
            'fdrReady' => $fdrCounts[FdrStatus::Ready->value] ?? 0,
            'fdrBusy' => ($fdrCounts[FdrStatus::Pending->value] ?? 0) + ($fdrCounts[FdrStatus::Processing->value] ?? 0),
            'fdrFailed' => $fdrCounts[FdrStatus::Failed->value] ?? 0,
            'latestFlights' => Flight::withListRelations()->latest('flown_at')->limit(5)->get(),
            'latestGroups' => FlightGroup::withCount('flights')->latest('flown_at')->limit(5)->get(),
        ]);
    }
}
