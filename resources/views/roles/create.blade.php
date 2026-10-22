<x-layouts.app title="Create Role" :breadcrumbs="['Roles' => route('roles.index'), 'Create' => null]">
    <x-ui.page-header title="Create Role" />
    <form method="POST" action="{{ route('roles.store') }}">
        @include('roles._form')
        <div class="mt-6 flex max-w-4xl justify-end gap-2">
            <x-ui.button variant="outline" :href="route('roles.index')">Cancel</x-ui.button>
            <x-ui.button type="submit">Save Role</x-ui.button>
        </div>
    </form>
</x-layouts.app>
