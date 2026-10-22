<x-layouts.app title="Edit Role" :breadcrumbs="['Roles' => route('roles.index'), $role->name => null, 'Edit' => null]">
    <x-ui.page-header :title="'Edit Role – '.$role->name">
        <x-slot:actions>
            @unless ($role->isSuperAdmin())
                <x-ui.delete-button :action="route('roles.destroy', $role)" :confirm="'Delete role '.$role->name.'?'" />
            @endunless
        </x-slot:actions>
    </x-ui.page-header>
    <form method="POST" action="{{ route('roles.update', $role) }}">
        @method('PUT')
        @include('roles._form')
        <div class="mt-6 flex max-w-4xl justify-end gap-2">
            <x-ui.button variant="outline" :href="route('roles.index')">Cancel</x-ui.button>
            <x-ui.button type="submit">Save Changes</x-ui.button>
        </div>
    </form>
</x-layouts.app>
