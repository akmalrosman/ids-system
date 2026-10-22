<x-layouts.app title="Dashboard" :breadcrumbs="['Dashboard' => null]">
    <div class="space-y-4">
        <div class="grid gap-4 md:grid-cols-3">
            @foreach ([
                'Total Flights' => $totalFlights,
                'Total Groups / Formations' => $totalGroups,
                'Total Flight Durations' => \App\Support\Duration::human($totalDuration, short: true),
            ] as $label => $value)
                <div class="rounded-xl border border-neutral-800 bg-neutral-950 p-6">
                    <p class="text-sm text-neutral-400">{{ $label }}</p>
                    <p class="mt-5 text-3xl font-semibold tracking-tight">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        @can(\App\Support\Permission::FLIGHTS_VIEW)
            <x-ui.card title="Latest Flights">
                @include('partials.flights-table', [
                    'flights' => $latestFlights,
                    'actions' => ['present', 'view'],
                    'showLocation' => true,
                    'withSeconds' => false,
                    'caption' => 'A list of recent flights.',
                ])
            </x-ui.card>
        @endcan

        @can(\App\Support\Permission::GROUPS_VIEW)
            <x-ui.card title="Latest Groups / Formations">
                @include('partials.groups-table', ['groups' => $latestGroups, 'actions' => ['present', 'view']])
            </x-ui.card>
        @endcan
    </div>
</x-layouts.app>
