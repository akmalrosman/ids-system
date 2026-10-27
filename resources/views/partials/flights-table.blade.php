{{--
    Flight table shared by the dashboard, flights list, group page, aircraft page and user page.

    @include('partials.flights-table', [
        'flights' => $flights,                  // Collection or paginator (with aircraft.type, pilot, wso loaded)
        'actions' => ['present', 'view', 'edit'],
        'showLocation' => true,
        'showWso' => true,
        'withSeconds' => true,                  // date format 18 May 2025, 16:00:00
    ])
--}}
@php
    use App\Support\Duration;

    $actions ??= ['view', 'edit'];
    $showLocation ??= false;
    $showWso ??= true;
    $withSeconds ??= true;
    $offset = method_exists($flights, 'firstItem') ? ($flights->firstItem() ?? 1) - 1 : 0;
    $canManage = auth()->user()->can(\App\Support\Permission::FLIGHTS_MANAGE);
@endphp

<div class="overflow-x-auto">
    <table class="data-table">
        <thead>
        <tr>
            <th class="w-10">#</th>
            <th>AC Number</th>
            <th>Flight No.</th>
            <th>Shift No.</th>
            <th>Duration</th>
            <th>Pilot</th>
            @if ($showWso)<th>WSO</th>@endif
            @if ($showLocation)<th>Location</th>@endif
            <th class="text-center">FDR</th>
            <th class="text-center">BVR</th>
            <th class="text-right">Date</th>
            <th class="text-right">Action</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($flights as $flight)
            <tr>
                <td class="text-neutral-400">{{ $offset + $loop->iteration }}.</td>
                <td class="font-medium">{{ $flight->aircraft->number }}</td>
                <td>{{ $flight->flight_number }}</td>
                <td>{{ $flight->shift_number ?? '—' }}</td>
                <td class="font-mono text-[13px]">{{ Duration::clock($flight->duration_seconds) }}</td>
                <td>{{ $flight->pilot?->name ?? '—' }}</td>
                @if ($showWso)<td>{{ $flight->wso?->name ?? '—' }}</td>@endif
                @if ($showLocation)<td>{{ $flight->location ?? '—' }}</td>@endif
                <td class="text-center">
                    @if ($flight->hasFdr())
                        <span title="FDR: {{ $flight->fdr_status->label() }}" @class([
                            'inline-flex',
                            'text-neutral-300' => $flight->hasTelemetry(),
                            'text-amber-400' => $flight->fdr_status->isBusy(),
                            'text-red-400' => $flight->fdr_status === \App\Enums\FdrStatus::Failed,
                        ])><x-icon name="file-text" class="size-4" /></span>
                    @endif
                </td>
                <td class="text-center">
                    @if ($flight->hasBvr())
                        <span title="Video attached" class="inline-flex text-neutral-300"><x-icon name="file-video" class="size-4" /></span>
                    @endif
                </td>
                <td class="text-right">{{ $flight->flown_at->format($withSeconds ? 'j M Y, H:i:s' : 'j M Y, H:i') }}</td>
                <td>
                    <div class="flex justify-end gap-2">
                        @if (in_array('present', $actions) && $flight->hasTelemetry())
                            <x-ui.button variant="outline" size="icon" :href="route('flights.present', $flight)" target="_blank" data-present-window title="Present flight" aria-label="Present flight">
                                <x-icon name="play" class="size-4" />
                            </x-ui.button>
                        @endif
                        @if (in_array('view', $actions))
                            <x-ui.button variant="outline" :href="route('flights.show', $flight)">View</x-ui.button>
                        @endif
                        @if (in_array('edit', $actions) && $canManage)
                            <x-ui.button variant="outline" :href="route('flights.edit', $flight)">Edit</x-ui.button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="12" class="py-10 text-center text-neutral-500">No flights found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
