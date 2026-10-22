<x-layouts.app title="Aircraft" :breadcrumbs="['Aircraft' => route('aircraft.index'), 'All' => null]">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <x-ui.search placeholder="Search number, registration, serial, type..." />
        @can(\App\Support\Permission::AIRCRAFT_MANAGE)
            <x-ui.button :href="route('aircraft.create')">Add Aircraft</x-ui.button>
        @endcan
    </div>

    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
            <tr>
                <th class="w-10">#</th>
                <th>Number</th>
                <th>Type</th>
                <th>Registration</th>
                <th>Serial</th>
                <th>Manufacturer</th>
                <th class="text-right">Created</th>
                <th class="text-right">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($aircraft as $a)
                <tr>
                    <td class="text-neutral-400">{{ $aircraft->firstItem() + $loop->index }}</td>
                    <td class="font-semibold">{{ $a->number }}</td>
                    <td>{{ $a->type->model }}</td>
                    <td>{{ $a->registration ?? '—' }}</td>
                    <td>{{ $a->serial ?? '—' }}</td>
                    <td>{{ $a->type->manufacturer }}</td>
                    <td class="text-right">{{ $a->created_at->format('j M Y, H:i') }}</td>
                    <td>
                        <div class="flex justify-end gap-2">
                            <x-ui.button variant="outline" :href="route('aircraft.show', $a)">View</x-ui.button>
                            @can(\App\Support\Permission::AIRCRAFT_MANAGE)
                                <x-ui.button variant="outline" :href="route('aircraft.edit', $a)">Edit</x-ui.button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-10 text-center text-neutral-500">No aircraft found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $aircraft->links('partials.pagination') }}
</x-layouts.app>
