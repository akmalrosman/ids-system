<x-layouts.app title="Flights" :breadcrumbs="['Flights' => route('flights.index'), 'All' => null]">
    <x-ui.page-header title="Flights" description="Every recorded flight with its FDR (flight data) and BVR (video) files.">
        @can(\App\Support\Permission::FLIGHTS_MANAGE)
            <x-slot:actions>
                <x-ui.button :href="route('flights.create')" icon="upload">Add Flight</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-ui.search placeholder="Search aircraft, flight no., pilot, location..." class="mb-4" />

    <x-ui.table-card>
        @include('partials.flights-table', ['flights' => $flights, 'actions' => ['view', 'edit'], 'showLocation' => true])
    </x-ui.table-card>
    {{ $flights->links('partials.pagination') }}
</x-layouts.app>
