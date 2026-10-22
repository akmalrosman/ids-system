@php
    use App\Enums\FdrStatus;
    use App\Support\Duration;
    use App\Support\Permission;

    $stats = $flight->stats ?? [];
@endphp

<x-layouts.app :title="'Flight – '.$flight->flight_number" :breadcrumbs="['Flights' => route('flights.index'), 'Show' => null]">
    <x-ui.page-header :title="'Flight – '.$flight->flight_number">
        <x-slot:actions>
            @if ($flight->hasTelemetry())
                <x-ui.button variant="outline" :href="route('flights.present', $flight)" icon="play">Present Flight</x-ui.button>
            @endif
            @can(Permission::FLIGHTS_MANAGE)
                <x-ui.button :href="route('flights.edit', $flight)">Edit Flight</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <dl class="grid gap-x-8 text-sm md:grid-cols-2">
        <div>
            <x-ui.info-item icon="circle-user" label="Pilot">{{ $flight->pilot?->name ?? '—' }}</x-ui.info-item>
            <x-ui.info-item icon="circle-user" label="WSO">{{ $flight->wso?->name ?? '—' }}</x-ui.info-item>
            <x-ui.info-item icon="calendar" label="Date">{{ $flight->flown_at->format('Y-m-d H:i:s') }}</x-ui.info-item>
            <x-ui.info-item icon="timer" label="Duration">{{ Duration::human($flight->duration_seconds) }}</x-ui.info-item>
            <x-ui.info-item icon="plane" label="Aircraft">
                <a href="{{ route('aircraft.show', $flight->aircraft) }}" class="hover:text-neutral-100">{{ $flight->aircraft->label() }}</a>
            </x-ui.info-item>
        </div>
        <div>
            <x-ui.info-item icon="plane-takeoff" label="Flight Number">{{ $flight->flight_number }}</x-ui.info-item>
            <x-ui.info-item icon="hash" label="Shift Number">{{ $flight->shift_number ?? '—' }}</x-ui.info-item>
            <x-ui.info-item icon="map-pin" label="Location">{{ $flight->location ?? '—' }}</x-ui.info-item>
            <x-ui.info-item icon="radar" label="Group / Formation">{{ $flight->groups->pluck('title')->join(', ') ?: '—' }}</x-ui.info-item>
            @if ($flight->remarks)
                <x-ui.info-item icon="info" label="Remarks">{{ $flight->remarks }}</x-ui.info-item>
            @endif
        </div>
    </dl>

    {{-- FDR status --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <x-ui.card title="FDR" class="lg:col-span-2">
            @if (! $flight->hasFdr())
                <p class="text-sm text-neutral-400">No FDR file uploaded.
                    @can(Permission::FLIGHTS_MANAGE)
                        <a href="{{ route('flights.edit', $flight) }}" class="underline underline-offset-4">Upload one</a>.
                    @endcan
                </p>
            @else
                <div x-data="fdrStatus(@js(route('flights.status', $flight)), @js($flight->fdr_status->value))" class="space-y-3 text-sm">
                    <div class="flex flex-wrap items-center gap-3">
                        <x-icon name="file-text" class="size-4 text-neutral-400" />
                        <a href="{{ route('flights.fdr', $flight) }}" class="underline-offset-4 hover:underline">{{ $flight->fdr_original_name }}</a>
                        <x-ui.badge :variant="$flight->fdr_status->variant()">{{ $flight->fdr_status->label() }}</x-ui.badge>
                        @if ($flight->fdr_status->isBusy())
                            <x-icon name="loader-circle" class="size-4 animate-spin text-amber-400" />
                            <span class="text-neutral-500">The page will refresh when processing finishes.</span>
                        @endif
                        @can(Permission::FLIGHTS_MANAGE)
                            <form method="POST" action="{{ route('flights.reprocess', $flight) }}" class="ml-auto">
                                @csrf
                                <x-ui.button type="submit" variant="outline" size="sm" icon="refresh-cw">Re-process</x-ui.button>
                            </form>
                        @endcan
                    </div>

                    @if ($flight->fdr_status === FdrStatus::Failed)
                        <div class="rounded-md border border-red-900 bg-red-950/50 p-3 text-red-200">
                            <p class="font-medium">Conversion failed</p>
                            <p class="mt-1 font-mono text-xs break-words">{{ $flight->fdr_error }}</p>
                        </div>
                    @endif

                    @if ($flight->hasTelemetry())
                        <dl class="grid grid-cols-2 gap-3 pt-2 sm:grid-cols-4">
                            @foreach ([
                                'Max altitude' => isset($stats['maxAltitude']) ? number_format($stats['maxAltitude'] * 3.28084).' ft' : '—',
                                'Max speed' => isset($stats['maxSpeed']) ? number_format($stats['maxSpeed'] * 1.94384, 1).' kt' : '—',
                                'Distance' => isset($stats['distance']) ? number_format($stats['distance'] / 1000, 2).' km' : '—',
                                'Samples' => number_format($stats['samples'] ?? 0),
                            ] as $label => $value)
                                <div class="rounded-lg border border-neutral-800 p-3">
                                    <dt class="text-xs text-neutral-500">{{ $label }}</dt>
                                    <dd class="mt-1 font-medium">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </div>
            @endif
        </x-ui.card>

        <x-ui.card title="BVR">
            @if ($flight->hasBvr())
                <video src="{{ route('flights.bvr', $flight) }}" controls preload="metadata" class="w-full rounded-md bg-black"></video>
                <div class="mt-3 flex items-center justify-between text-xs text-neutral-500">
                    <span class="truncate">{{ $flight->bvr_original_name }} · offset {{ $flight->bvr_offset_seconds }} s</span>
                    @can(Permission::FLIGHTS_MANAGE)
                        <form method="POST" action="{{ route('flights.bvr.destroy', $flight) }}" onsubmit="return confirm('Remove this video?')">
                            @csrf @method('DELETE')
                            <button class="text-red-400 hover:text-red-300">Remove</button>
                        </form>
                    @endcan
                </div>
            @else
                <p class="text-sm text-neutral-400">No video attached.</p>
            @endif
        </x-ui.card>
    </div>

    {{-- Groups --}}
    <x-ui.card title="Groups / Formations" class="mt-4">
        @if ($flight->groups->isEmpty())
            <p class="text-sm text-neutral-400">No groups / formations attached to this flight.</p>
        @else
            @include('partials.groups-table', ['groups' => $flight->groups, 'actions' => ['present', 'view']])
        @endif
    </x-ui.card>

    {{-- Telemetry charts (resources/js/pages/flight-charts.js) --}}
    @if ($flight->hasTelemetry())
        <div data-flight-charts data-url="{{ route('flights.telemetry', $flight) }}" x-data="{ view: 'separate' }" class="mt-6">
            <div class="mb-4 flex items-center gap-2 text-sm">
                <span class="text-neutral-400">View:</span>
                <x-ui.button size="sm" ::class="view === 'separate' ? '' : '!bg-neutral-950 !text-neutral-100 border border-neutral-800'"
                             @click="view = 'separate'; $dispatch('charts-view', 'separate')">Separate</x-ui.button>
                <x-ui.button size="sm" ::class="view === 'combined' ? '' : '!bg-neutral-950 !text-neutral-100 border border-neutral-800'"
                             @click="view = 'combined'; $dispatch('charts-view', 'combined')">Combined</x-ui.button>
            </div>
            <div data-charts-container class="grid gap-4">
                <p class="text-sm text-neutral-500">Loading telemetry…</p>
            </div>
        </div>
    @endif

    <script>
        // Polls the FDR status while it is queued/processing, then reloads the page.
        function fdrStatus(url, initial) {
            return {
                init() {
                    if (!['pending', 'processing'].includes(initial)) return;
                    const timer = setInterval(async () => {
                        const res = await fetch(url, { headers: { Accept: 'application/json' } });
                        if (!res.ok) return;
                        const { status } = await res.json();
                        if (status !== initial) {
                            clearInterval(timer);
                            window.location.reload();
                        }
                    }, 3000);
                },
            };
        }
    </script>
</x-layouts.app>
