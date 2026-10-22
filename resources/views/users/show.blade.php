<x-layouts.app :title="$user->name" :breadcrumbs="['Users' => route('users.index'), 'Show' => null]">
    <x-ui.page-header :title="$user->name">
        <x-slot:actions>
            <x-ui.button :href="route('users.edit', $user)">Edit User</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <dl class="grid gap-x-8 text-sm md:grid-cols-2">
        <div>
            <x-ui.info-item icon="id-card" label="Role">{{ $user->role?->name ?? 'No role' }}</x-ui.info-item>
            <x-ui.info-item icon="info" label="Email">{{ $user->email }}</x-ui.info-item>
        </div>
        <div>
            <x-ui.info-item icon="info" label="Phone">{{ $user->phone ?? '—' }}</x-ui.info-item>
            <x-ui.info-item icon="calendar" label="Created">{{ $user->created_at->format('j M Y, H:i') }}</x-ui.info-item>
        </div>
    </dl>

    <x-ui.card title="Latest flights as pilot" class="mt-6">
        @include('partials.flights-table', ['flights' => $flights, 'actions' => ['present', 'view'], 'showLocation' => true])
    </x-ui.card>
</x-layouts.app>
