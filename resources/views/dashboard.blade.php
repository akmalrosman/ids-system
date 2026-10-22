@php
    use App\Support\Duration;
    use App\Support\Permission;
@endphp

<x-layouts.app title="Dashboard" :breadcrumbs="['Dashboard' => null]">
    <div class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-4 pb-2">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Welcome back, {{ auth()->user()->name }}</h1>
                <p class="mt-1 text-sm text-neutral-400">{{ now()->format('l, j F Y') }} · Overview of recorded flights and debriefs</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can(Permission::GROUPS_MANAGE)
                    <x-ui.button variant="outline" :href="route('groups.create')" icon="radar">Create Group</x-ui.button>
                @endcan
                @can(Permission::FLIGHTS_MANAGE)
                    <x-ui.button :href="route('flights.create')" icon="upload">Add Flight</x-ui.button>
                @endcan
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat label="Total Flights" :value="$totalFlights" icon="plane-takeoff"
                       :hint="$fdrReady.' ready to present'" />
            <x-ui.stat label="Total Groups / Formations" :value="$totalGroups" icon="radar"
                       :hint="$groupedFlights.' '.str('flight')->plural($groupedFlights).' in groups'" />
            <x-ui.stat label="Total Flight Durations" :value="Duration::human($totalDuration, short: true)" icon="timer"
                       :hint="$averageDuration ? 'Average '.Duration::human($averageDuration, short: true).' per flight' : null" />
            <x-ui.stat label="FDR Processing" :value="$fdrReady.' / '.$totalFlights" icon="activity">
                <div class="flex flex-wrap gap-1.5">
                    <x-ui.badge variant="success">{{ $fdrReady }} ready</x-ui.badge>
                    @if ($fdrBusy)<x-ui.badge variant="warning">{{ $fdrBusy }} processing</x-ui.badge>@endif
                    @if ($fdrFailed)<x-ui.badge variant="danger">{{ $fdrFailed }} failed</x-ui.badge>@endif
                </div>
            </x-ui.stat>
        </div>

        @can(Permission::FLIGHTS_VIEW)
            <x-ui.card title="Latest Flights" description="The five most recent flights.">
                <x-slot:actions>
                    <x-ui.button variant="ghost" size="sm" :href="route('flights.index')">
                        View all <x-icon name="arrow-right" class="size-3.5" />
                    </x-ui.button>
                </x-slot:actions>
                @include('partials.flights-table', [
                    'flights' => $latestFlights,
                    'actions' => ['present', 'view'],
                    'showLocation' => true,
                    'withSeconds' => false,
                ])
            </x-ui.card>
        @endcan

        @can(Permission::GROUPS_VIEW)
            <x-ui.card title="Latest Groups / Formations" description="Groups of flights presented together on one timeline.">
                <x-slot:actions>
                    <x-ui.button variant="ghost" size="sm" :href="route('groups.index')">
                        View all <x-icon name="arrow-right" class="size-3.5" />
                    </x-ui.button>
                </x-slot:actions>
                @include('partials.groups-table', ['groups' => $latestGroups, 'actions' => ['present', 'view']])
            </x-ui.card>
        @endcan
    </div>
</x-layouts.app>
