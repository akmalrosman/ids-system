<x-layouts.app title="Flights" :breadcrumbs="['Flights' => route('flights.index'), 'All' => null]">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <x-ui.search placeholder="Search aircraft, flight no., pilot, location..." />
        @can(\App\Support\Permission::FLIGHTS_MANAGE)
            <x-ui.button :href="route('flights.create')">Add Flight</x-ui.button>
        @endcan
    </div>

    @include('partials.flights-table', ['flights' => $flights, 'actions' => ['view', 'edit']])
    {{ $flights->links('partials.pagination') }}
</x-layouts.app>
