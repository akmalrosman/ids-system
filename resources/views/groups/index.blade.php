<x-layouts.app title="Groups" :breadcrumbs="['Groups' => route('groups.index'), 'All' => null]">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <x-ui.search placeholder="Search title or location..." />
        @can(\App\Support\Permission::GROUPS_MANAGE)
            <x-ui.button :href="route('groups.create')">Create Group</x-ui.button>
        @endcan
    </div>

    @include('partials.groups-table', ['groups' => $groups])
    {{ $groups->links('partials.pagination') }}
</x-layouts.app>
