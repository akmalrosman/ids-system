<?php

namespace App\Http\Controllers;

use App\Http\Requests\AircraftTypeRequest;
use App\Models\AircraftType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AircraftTypeController extends Controller
{
    public function index(Request $request): View
    {
        $types = AircraftType::withCount('aircraft')
            ->search($request->query('search'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('aircraft-types.index', compact('types'));
    }

    public function create(): View
    {
        return view('aircraft-types.create', ['type' => new AircraftType([
            'model_length_m' => config('ids.default_model.length_m'),
            'model_yaw_offset_deg' => config('ids.default_model.yaw_offset_deg'),
        ])]);
    }

    public function store(AircraftTypeRequest $request): RedirectResponse
    {
        $type = AircraftType::create($request->safe()->except(['model_file', 'remove_model']));
        $this->saveModel($request, $type);

        return redirect()->route('aircraft-types.show', $type)->with('status', 'Aircraft type added.');
    }

    public function show(AircraftType $aircraftType): View
    {
        $aircraftType->load(['aircraft' => fn ($q) => $q->orderBy('number')]);

        return view('aircraft-types.show', ['type' => $aircraftType]);
    }

    public function edit(AircraftType $aircraftType): View
    {
        return view('aircraft-types.edit', ['type' => $aircraftType]);
    }

    public function update(AircraftTypeRequest $request, AircraftType $aircraftType): RedirectResponse
    {
        $aircraftType->update($request->safe()->except(['model_file', 'remove_model']));
        $this->saveModel($request, $aircraftType);

        return redirect()->route('aircraft-types.show', $aircraftType)->with('status', 'Aircraft type updated.');
    }

    public function destroy(AircraftType $aircraftType): RedirectResponse
    {
        if ($aircraftType->aircraft()->exists()) {
            return back()->with('error', "\"{$aircraftType->name}\" is used by aircraft and cannot be deleted.");
        }

        if ($aircraftType->model_path) {
            Storage::disk(config('ids.disk'))->delete($aircraftType->model_path);
        }
        $aircraftType->delete();

        return redirect()->route('aircraft-types.index')->with('status', "Aircraft type \"{$aircraftType->name}\" deleted.");
    }

    /** Streams the uploaded .glb to the presentation. */
    public function model(AircraftType $aircraftType): BinaryFileResponse
    {
        $disk = Storage::disk(config('ids.disk'));
        abort_unless($aircraftType->model_path && $disk->exists($aircraftType->model_path), 404);

        return response()->file($disk->path($aircraftType->model_path), [
            'Content-Type' => 'model/gltf-binary',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    private function saveModel(AircraftTypeRequest $request, AircraftType $type): void
    {
        $disk = Storage::disk(config('ids.disk'));

        if ($request->boolean('remove_model') || $request->hasFile('model_file')) {
            if ($type->model_path) {
                $disk->delete($type->model_path);
            }
            $type->update(['model_path' => null]);
        }

        if ($request->hasFile('model_file')) {
            $type->update(['model_path' => $request->file('model_file')->store("models/{$type->id}", config('ids.disk'))]);
        }
    }
}
