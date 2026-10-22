<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlightRequest;
use App\Models\Aircraft;
use App\Models\Flight;
use App\Models\FlightGroup;
use App\Models\User;
use App\Services\FlightFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FlightController extends Controller
{
    public function __construct(private FlightFileService $files) {}

    public function index(Request $request): View
    {
        $flights = Flight::withListRelations()
            ->search($request->query('search'))
            ->latest('flown_at')
            ->paginate(15)
            ->withQueryString();

        return view('flights.index', compact('flights'));
    }

    public function create(Request $request): View
    {
        $flight = new Flight(['flown_at' => now()->startOfHour()]);
        $selectedGroups = array_filter([(int) $request->query('group')]);

        return view('flights.create', [...$this->formOptions(), 'flight' => $flight, 'selectedGroups' => $selectedGroups]);
    }

    public function store(FlightRequest $request): RedirectResponse
    {
        $flight = DB::transaction(function () use ($request) {
            $flight = Flight::create([...$request->flightData(), 'created_by' => $request->user()->id]);
            $flight->groups()->sync($request->validated('group_ids', []));

            return $flight;
        });

        $this->storeFiles($request, $flight);

        return redirect()->route('flights.show', $flight)->with('status', 'Flight created.');
    }

    public function show(Flight $flight): View
    {
        $flight->load(['aircraft.type', 'pilot', 'wso', 'groups' => fn ($q) => $q->withCount('flights')]);

        return view('flights.show', compact('flight'));
    }

    public function edit(Flight $flight): View
    {
        return view('flights.edit', [
            ...$this->formOptions(),
            'flight' => $flight,
            'selectedGroups' => $flight->groups()->pluck('flight_groups.id')->all(),
        ]);
    }

    public function update(FlightRequest $request, Flight $flight): RedirectResponse
    {
        DB::transaction(function () use ($request, $flight) {
            $flight->update($request->flightData());
            $flight->groups()->sync($request->validated('group_ids', []));
        });

        $this->storeFiles($request, $flight);

        return redirect()->route('flights.show', $flight)->with('status', 'Flight updated.');
    }

    public function destroy(Flight $flight): RedirectResponse
    {
        $this->files->deleteAll($flight);
        $flight->delete();

        return redirect()->route('flights.index')->with('status', "Flight {$flight->flight_number} deleted.");
    }

    private function storeFiles(FlightRequest $request, Flight $flight): void
    {
        if ($request->hasFile('fdr')) {
            $this->files->storeFdr($flight, $request->file('fdr'));
        }

        if ($request->hasFile('bvr')) {
            $this->files->storeBvr($flight, $request->file('bvr'));
        }
    }

    /** Dropdown data shared by create and edit. */
    private function formOptions(): array
    {
        $aircrew = User::aircrew()->orderBy('name')->get(['id', 'name']);

        return [
            'aircraft' => Aircraft::with('type')->orderBy('number')->get(),
            // Fall back to every user when no one has the Pilot role yet.
            'aircrew' => $aircrew->isEmpty() ? User::orderBy('name')->get(['id', 'name']) : $aircrew,
            'groups' => FlightGroup::latest('flown_at')->get(['id', 'title', 'flown_at']),
            'locations' => array_keys(config('ids.locations')),
        ];
    }
}
