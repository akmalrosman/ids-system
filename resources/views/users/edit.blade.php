<x-layouts.app title="Edit User" :breadcrumbs="['Users' => route('users.index'), $user->name => route('users.show', $user), 'Edit' => null]">
    <x-ui.page-header :title="'Edit '.$user->name">
        <x-slot:actions>
            @unless ($user->is(auth()->user()))
                <x-ui.delete-button :action="route('users.destroy', $user)" :confirm="'Delete user '.$user->name.'?'" />
            @endunless
        </x-slot:actions>
    </x-ui.page-header>
    <form method="POST" action="{{ route('users.update', $user) }}">
        @method('PUT')
        @include('users._form')
        <div class="mt-6 flex max-w-2xl justify-end gap-2">
            <x-ui.button variant="outline" :href="route('users.show', $user)">Cancel</x-ui.button>
            <x-ui.button type="submit">Save Changes</x-ui.button>
        </div>
    </form>
</x-layouts.app>
