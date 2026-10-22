<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlightGroupRequest;
use App\Models\Flight;
use App\Models\FlightGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FlightGroupController extends Controller
{
    public function index(Request $request): View
    {
        $groups = FlightGroup::withCount('flights')
            ->search($request->query('search'))
            ->latest('flown_at')
            ->paginate(15)
            ->withQueryString();

        return view('groups.index', compact('groups'));
    }

    public function create(): View
    {
        return view('groups.create', [
            'group' => new FlightGroup(['flown_at' => now()->startOfHour()]),
            'flights' => $this->selectableFlights(),
            'selectedFlights' => [],
            'locations' => array_keys(config('ids.locations')),
        ]);
    }

    public function store(FlightGroupRequest $request): RedirectResponse
    {
        $group = DB::transaction(function () use ($request) {
            $group = FlightGroup::create([...$request->safe()->except('flight_ids'), 'created_by' => $request->user()->id]);
            $group->flights()->sync($request->validated('flight_ids', []));

            return $group;
        });

        return redirect()->route('groups.show', $group)->with('status', 'Group created.');
    }

    public function show(FlightGroup $group): View
    {
        $group->load(['flights' => fn ($q) => $q->withListRelations()]);

        return view('groups.show', compact('group'));
    }

    public function edit(FlightGroup $group): View
    {
        return view('groups.edit', [
            'group' => $group,
            'flights' => $this->selectableFlights(),
            'selectedFlights' => $group->flights()->pluck('flights.id')->all(),
            'locations' => array_keys(config('ids.locations')),
        ]);
    }

    public function update(FlightGroupRequest $request, FlightGroup $group): RedirectResponse
    {
        DB::transaction(function () use ($request, $group) {
            $group->update($request->safe()->except('flight_ids'));
            $group->flights()->sync($request->validated('flight_ids', []));
        });

        return redirect()->route('groups.show', $group)->with('status', 'Group updated.');
    }

    public function destroy(FlightGroup $group): RedirectResponse
    {
        // Only the group is deleted; its flights stay.
        $group->delete();

        return redirect()->route('groups.index')->with('status', "Group \"{$group->title}\" deleted.");
    }

    private function selectableFlights()
    {
        return Flight::with('aircraft.type', 'pilot')->latest('flown_at')->get();
    }
}
