<x-layouts.app title="Roles" :breadcrumbs="['Roles' => route('roles.index'), 'All' => null]">
    <x-ui.page-header title="Roles" description="Sets of permissions given to users.">
        <x-slot:actions>
            <x-ui.button :href="route('roles.create')" icon="id-card">Create Role</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.search placeholder="Search roles..." class="mb-4" />

    <x-ui.table-card class="overflow-x-auto">
        <table class="data-table">
            <thead>
            <tr>
                <th class="w-10">#</th>
                <th>Name</th>
                <th>In Use?</th>
                <th class="text-center">No. of User</th>
                <th class="text-right">Created At</th>
                <th class="text-right">Updated At</th>
                <th class="text-right">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($roles as $role)
                <tr>
                    <td class="text-neutral-400">{{ $loop->iteration }}</td>
                    <td class="font-semibold">{{ $role->name }}</td>
                    <td>
                        <x-ui.badge :variant="$role->users_count > 0 ? 'solid' : 'danger'">{{ $role->users_count > 0 ? 'Yes' : 'No' }}</x-ui.badge>
                    </td>
                    <td class="text-center">{{ $role->users_count }}</td>
                    <td class="text-right">{{ $role->created_at->format('j M Y, H:i:s') }}</td>
                    <td class="text-right">{{ $role->updated_at->format('j M Y, H:i:s') }}</td>
                    <td class="text-right">
                        <x-ui.button variant="outline" :href="route('roles.edit', $role)">Edit</x-ui.button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-10 text-center text-neutral-500">No roles found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </x-ui.table-card>
</x-layouts.app>
