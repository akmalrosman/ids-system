<x-layouts.app title="Add Aircraft" :breadcrumbs="['Aircraft' => route('aircraft.index'), 'Add' => null]">
    <x-ui.page-header title="Add Aircraft" />
    <form method="POST" action="{{ route('aircraft.store') }}">
        @include('aircraft._form')
        <div class="mt-6 flex max-w-2xl justify-end gap-2">
            <x-ui.button variant="outline" :href="route('aircraft.index')">Cancel</x-ui.button>
            <x-ui.button type="submit">Save Aircraft</x-ui.button>
        </div>
    </form>
</x-layouts.app>
