<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Models\FlightGroup;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'totalFlights' => Flight::count(),
            'totalGroups' => FlightGroup::count(),
            'totalDuration' => (float) Flight::sum('duration_seconds'),
            'latestFlights' => Flight::withListRelations()->latest('flown_at')->limit(5)->get(),
            'latestGroups' => FlightGroup::withCount('flights')->latest('flown_at')->limit(5)->get(),
        ]);
    }
}
