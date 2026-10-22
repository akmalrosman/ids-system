<?php

namespace App\Http\Controllers;

use App\Http\Requests\AircraftRequest;
use App\Models\Aircraft;
use App\Models\AircraftType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AircraftController extends Controller
{
    public function index(Request $request): View
    {
        $aircraft = Aircraft::with('type')
            ->search($request->query('search'))
            ->orderBy('number')
            ->paginate(20)
            ->withQueryString();

        return view('aircraft.index', compact('aircraft'));
    }

    public function create(): View
    {
        return view('aircraft.create', ['aircraft' => new Aircraft, 'types' => AircraftType::orderBy('name')->get()]);
    }

    public function store(AircraftRequest $request): RedirectResponse
    {
        $aircraft = Aircraft::create($request->validated());

        return redirect()->route('aircraft.show', $aircraft)->with('status', 'Aircraft added.');
    }

    public function show(Aircraft $aircraft): View
    {
        $aircraft->load('type');
        $flights = $aircraft->flights()->withListRelations()->latest('flown_at')->limit(20)->get();

        return view('aircraft.show', compact('aircraft', 'flights'));
    }

    public function edit(Aircraft $aircraft): View
    {
        return view('aircraft.edit', ['aircraft' => $aircraft, 'types' => AircraftType::orderBy('name')->get()]);
    }

    public function update(AircraftRequest $request, Aircraft $aircraft): RedirectResponse
    {
        $aircraft->update($request->validated());

        return redirect()->route('aircraft.show', $aircraft)->with('status', 'Aircraft updated.');
    }

    public function destroy(Aircraft $aircraft): RedirectResponse
    {
        if ($aircraft->flights()->exists()) {
            return back()->with('error', "Aircraft {$aircraft->number} has flights and cannot be deleted.");
        }

        $aircraft->delete();

        return redirect()->route('aircraft.index')->with('status', "Aircraft {$aircraft->number} deleted.");
    }
}
