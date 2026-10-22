<x-layouts.app title="Aircraft Types" :breadcrumbs="['Aircraft Types' => route('aircraft-types.index'), 'All' => null]">
    <x-ui.page-header title="Aircraft Types" description="Types of aircraft and the 3D model each one uses in the presentation.">
        @can(\App\Support\Permission::AIRCRAFT_MANAGE)
            <x-slot:actions>
                <x-ui.button :href="route('aircraft-types.create')" icon="layers">Add Aircraft Type</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-ui.search placeholder="Search name, manufacturer, model..." class="mb-4" />

    <x-ui.table-card class="overflow-x-auto">
        <table class="data-table">
            <thead>
            <tr>
                <th class="w-10">#</th>
                <th>Name</th>
                <th>Manufacturer</th>
                <th>Model</th>
                <th class="text-center">Aircraft</th>
                <th class="text-center">3D model</th>
                <th class="text-right">Created</th>
                <th class="text-right">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($types as $type)
                <tr>
                    <td class="text-neutral-400">{{ $types->firstItem() + $loop->index }}</td>
                    <td class="font-semibold">{{ $type->name }}</td>
                    <td>{{ $type->manufacturer }}</td>
                    <td>{{ $type->model }}</td>
                    <td class="text-center">{{ $type->aircraft_count }}</td>
                    <td class="text-center">
                        <x-ui.badge :variant="$type->model_path ? 'success' : 'muted'">{{ $type->model_path ? 'Uploaded' : 'Default' }}</x-ui.badge>
                    </td>
                    <td class="text-right">{{ $type->created_at->format('j M Y, H:i') }}</td>
                    <td>
                        <div class="flex justify-end gap-2">
                            <x-ui.button variant="outline" :href="route('aircraft-types.show', $type)">View</x-ui.button>
                            @can(\App\Support\Permission::AIRCRAFT_MANAGE)
                                <x-ui.button variant="outline" :href="route('aircraft-types.edit', $type)">Edit</x-ui.button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-10 text-center text-neutral-500">No aircraft types found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </x-ui.table-card>
    {{ $types->links('partials.pagination') }}
</x-layouts.app>
