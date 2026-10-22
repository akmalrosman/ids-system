<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Models\FlightGroup;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Full-screen Three.js debrief for one flight or a whole group.
 *
 * The page receives a JSON config (see buildConfig) and the JS in
 * resources/js/presentation fetches each flight's telemetry from
 * route('flights.telemetry').
 */
class PresentationController extends Controller
{
    /** Track colours, in the order aircraft are listed. */
    private const COLORS = ['#2563eb', '#dc2626', '#16a34a', '#ea580c', '#9333ea', '#db2777', '#0891b2', '#ca8a04'];

    public function flight(Flight $flight): View
    {
        $flight->load('aircraft.type', 'pilot');

        return $this->render(
            title: 'Single Flight Presentation',
            subtitle: "Flight {$flight->flight_number} · {$flight->aircraft->label()}",
            flights: collect([$flight]),
            location: $flight->location,
            closeUrl: route('flights.show', $flight),
        );
    }

    public function group(FlightGroup $group): View
    {
        $group->load('flights.aircraft.type', 'flights.pilot');

        return $this->render(
            title: $group->title,
            subtitle: 'Flight Group Presentation - '.$group->flown_at->format('Y-m-d H:i:s'),
            flights: $group->flights,
            location: $group->location,
            closeUrl: route('groups.show', $group),
        );
    }

    /** @param  Collection<int, Flight>  $flights */
    private function render(string $title, string $subtitle, Collection $flights, ?string $location, string $closeUrl): View
    {
        $ready = $flights->filter(fn (Flight $f) => $f->hasTelemetry())->values();

        return view('presentation.show', [
            'title' => $title,
            'config' => $this->buildConfig($title, $subtitle, $ready, $location, $closeUrl),
            'missing' => $flights->reject(fn (Flight $f) => $f->hasTelemetry()),
        ]);
    }

    /** @param  Collection<int, Flight>  $flights */
    private function buildConfig(string $title, string $subtitle, Collection $flights, ?string $location, string $closeUrl): array
    {
        // Every aircraft is placed on one shared timeline that starts with the earliest recording.
        $timelineStart = $flights->min(fn (Flight $f) => $f->dataStartsAt()->getTimestampMs());

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'closeUrl' => $closeUrl,
            'timelineStart' => $timelineStart ? (int) $timelineStart : null,
            'timezone' => config('app.timezone'),
            'map' => [
                'tileUrl' => config('ids.map.tile_url'),
                'minZoom' => config('ids.map.min_zoom'),
                'maxZoom' => config('ids.map.max_zoom'),
                'bounds' => config('ids.map.bounds'),
                'attribution' => config('ids.map.attribution'),
                'sourceResolution' => config('ids.map.source_resolution_m'),
                'fallbackCenter' => config("ids.locations.{$location}") ?? ['lat' => 4.2, 'lng' => 108.0],
            ],
            'flights' => $flights->values()->map(fn (Flight $f, int $i) => [
                'id' => $f->id,
                'label' => $f->aircraft->label(),
                'flightNumber' => $f->flight_number,
                'pilot' => $f->pilot?->name,
                'color' => self::COLORS[$i % count(self::COLORS)],
                'telemetryUrl' => route('flights.telemetry', $f),
                'startOffset' => ($f->dataStartsAt()->getTimestampMs() - $timelineStart) / 1000,
                'model' => [
                    'url' => $f->aircraft->type?->modelUrl() ?? config('ids.default_model.url'),
                    'lengthM' => $f->aircraft->type?->model_length_m ?? config('ids.default_model.length_m'),
                    'yawOffsetDeg' => $f->aircraft->type?->model_yaw_offset_deg ?? config('ids.default_model.yaw_offset_deg'),
                ],
                'video' => $f->hasBvr() ? ['url' => route('flights.bvr', $f), 'offset' => $f->bvr_offset_seconds] : null,
            ])->all(),
        ];
    }
}
