{{--
    @include('partials.groups-table', ['groups' => $groups, 'actions' => ['present', 'view', 'edit']])
    $groups needs flights_count (withCount('flights')).
--}}
@php
    $actions ??= ['present', 'view', 'edit'];
    $offset = method_exists($groups, 'firstItem') ? ($groups->firstItem() ?? 1) - 1 : 0;
    $canManage = auth()->user()->can(\App\Support\Permission::GROUPS_MANAGE);
@endphp

<div class="overflow-x-auto">
    <table class="data-table">
        <thead>
        <tr>
            <th class="w-10">#</th>
            <th>Title</th>
            <th>Location</th>
            <th class="text-center">No. of Flights</th>
            <th class="text-right">Date</th>
            <th class="text-right">Action</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($groups as $group)
            <tr>
                <td class="text-neutral-400">{{ $offset + $loop->iteration }}</td>
                <td class="font-medium">{{ $group->title }}</td>
                <td>{{ $group->location ?? '—' }}</td>
                <td class="text-center">{{ $group->flights_count }}</td>
                <td class="text-right">{{ $group->flown_at->format('j M Y, H:i:s') }}</td>
                <td>
                    <div class="flex justify-end gap-2">
                        @if (in_array('present', $actions) && $group->flights_count > 0)
                            <x-ui.button variant="outline" size="icon" :href="route('groups.present', $group)" title="Present group" aria-label="Present group">
                                <x-icon name="play" class="size-4" />
                            </x-ui.button>
                        @endif
                        @if (in_array('view', $actions))
                            <x-ui.button variant="outline" :href="route('groups.show', $group)">View</x-ui.button>
                        @endif
                        @if (in_array('edit', $actions) && $canManage)
                            <x-ui.button variant="outline" :href="route('groups.edit', $group)">Edit</x-ui.button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="py-10 text-center text-neutral-500">No groups / formations found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
