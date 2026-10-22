<x-layouts.app :title="'Aircraft '.$aircraft->number" :breadcrumbs="['Aircraft' => route('aircraft.index'), 'Show' => null]">
    <x-ui.page-header :title="'Aircraft '.$aircraft->number">
        <x-slot:actions>
            @can(\App\Support\Permission::AIRCRAFT_MANAGE)
                <x-ui.button :href="route('aircraft.edit', $aircraft)">Edit Aircraft</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <dl class="grid gap-x-8 text-sm md:grid-cols-2">
            <div>
                <x-ui.info-item icon="hash" label="Number">{{ $aircraft->number }}</x-ui.info-item>
                <x-ui.info-item icon="layers" label="Type">
                    <a href="{{ route('aircraft-types.show', $aircraft->type) }}" class="hover:text-neutral-100">{{ $aircraft->type->name }}</a>
                </x-ui.info-item>
                <x-ui.info-item icon="info" label="Manufacturer">{{ $aircraft->type->manufacturer }}</x-ui.info-item>
            </div>
            <div>
                <x-ui.info-item icon="id-card" label="Registration">{{ $aircraft->registration ?? '—' }}</x-ui.info-item>
                <x-ui.info-item icon="hash" label="Serial">{{ $aircraft->serial ?? '—' }}</x-ui.info-item>
                <x-ui.info-item icon="info" label="Notes">{{ $aircraft->notes ?? '—' }}</x-ui.info-item>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Latest flights" class="mt-6">
        @include('partials.flights-table', ['flights' => $flights, 'actions' => ['present', 'view'], 'showLocation' => true])
    </x-ui.card>
</x-layouts.app>
