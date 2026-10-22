<x-layouts.app title="Edit Group" :breadcrumbs="['Groups' => route('groups.index'), $group->title => route('groups.show', $group), 'Edit' => null]">
    <x-ui.page-header :title="'Edit – '.$group->title">
        <x-slot:actions>
            <x-ui.delete-button :action="route('groups.destroy', $group)"
                                :confirm="'Delete group \''.$group->title.'\'? The flights themselves are kept.'" />
        </x-slot:actions>
    </x-ui.page-header>

    <form method="POST" action="{{ route('groups.update', $group) }}">
        @method('PUT')
        @include('groups._form')

        <div class="mt-6 flex justify-end gap-2">
            <x-ui.button variant="outline" :href="route('groups.show', $group)">Cancel</x-ui.button>
            <x-ui.button type="submit">Save Changes</x-ui.button>
        </div>
    </form>
</x-layouts.app>
