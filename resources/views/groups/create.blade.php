<x-layouts.app title="Create Group" :breadcrumbs="['Groups' => route('groups.index'), 'Create' => null]">
    <x-ui.page-header title="Create Group / Formation" />

    <form method="POST" action="{{ route('groups.store') }}">
        @include('groups._form')

        <div class="mt-6 flex justify-end gap-2">
            <x-ui.button variant="outline" :href="route('groups.index')">Cancel</x-ui.button>
            <x-ui.button type="submit">Save Group</x-ui.button>
        </div>
    </form>
</x-layouts.app>
