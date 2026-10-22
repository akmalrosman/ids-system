<x-layouts.app title="Users" :breadcrumbs="['Users' => route('users.index'), 'All' => null]">
    <x-ui.page-header title="Users" description="People who can log in, and the role that decides what they can do.">
        <x-slot:actions>
            <x-ui.button :href="route('users.create')" icon="users">Create User</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.search placeholder="Search name, email, phone..." class="mb-4" />

    <x-ui.table-card class="overflow-x-auto">
        <table class="data-table">
            <thead>
            <tr>
                <th class="w-10">#</th>
                <th>Name</th>
                <th>Role</th>
                <th>Email</th>
                <th>Phone</th>
                <th class="text-right">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td class="text-neutral-400">{{ $users->firstItem() + $loop->index }}</td>
                    <td class="font-semibold">{{ $user->name }}</td>
                    <td><x-ui.badge>{{ $user->role?->name ?? 'No role' }}</x-ui.badge></td>
                    <td class="text-neutral-300">{{ $user->email }}</td>
                    <td class="text-neutral-300">{{ $user->phone }}</td>
                    <td>
                        <div class="flex justify-end gap-2">
                            <x-ui.button variant="outline" :href="route('users.show', $user)">View</x-ui.button>
                            <x-ui.button variant="outline" :href="route('users.edit', $user)">Edit</x-ui.button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-10 text-center text-neutral-500">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </x-ui.table-card>
    {{ $users->links('partials.pagination') }}
</x-layouts.app>
