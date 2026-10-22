<x-layouts.app title="Create User" :breadcrumbs="['Users' => route('users.index'), 'Create' => null]">
    <x-ui.page-header title="Create User" />
    <form method="POST" action="{{ route('users.store') }}">
        @include('users._form')
        <div class="mt-6 flex max-w-2xl justify-end gap-2">
            <x-ui.button variant="outline" :href="route('users.index')">Cancel</x-ui.button>
            <x-ui.button type="submit">Save User</x-ui.button>
        </div>
    </form>
</x-layouts.app>
