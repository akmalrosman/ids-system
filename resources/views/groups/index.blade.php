<x-layouts.app title="Groups" :breadcrumbs="['Groups' => route('groups.index'), 'All' => null]">
    <x-ui.page-header title="Groups / Formations" description="Flights presented together on one synced timeline.">
        @can(\App\Support\Permission::GROUPS_MANAGE)
            <x-slot:actions>
                <x-ui.button :href="route('groups.create')" icon="radar">Create Group</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-ui.search placeholder="Search title or location..." class="mb-4" />

    <x-ui.table-card>
        @include('partials.groups-table', ['groups' => $groups])
    </x-ui.table-card>
    {{ $groups->links('partials.pagination') }}
</x-layouts.app>
