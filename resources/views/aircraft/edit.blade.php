<x-layouts.app title="Edit Aircraft" :breadcrumbs="['Aircraft' => route('aircraft.index'), $aircraft->number => route('aircraft.show', $aircraft), 'Edit' => null]">
    <x-ui.page-header :title="'Edit Aircraft '.$aircraft->number">
        <x-slot:actions>
            <x-ui.delete-button :action="route('aircraft.destroy', $aircraft)" :confirm="'Delete aircraft '.$aircraft->number.'?'" />
        </x-slot:actions>
    </x-ui.page-header>
    <form method="POST" action="{{ route('aircraft.update', $aircraft) }}">
        @method('PUT')
        @include('aircraft._form')
        <div class="mt-6 flex max-w-2xl justify-end gap-2">
            <x-ui.button variant="outline" :href="route('aircraft.show', $aircraft)">Cancel</x-ui.button>
            <x-ui.button type="submit">Save Changes</x-ui.button>
        </div>
    </form>
</x-layouts.app>
