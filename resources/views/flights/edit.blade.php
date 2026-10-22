<x-layouts.app title="Edit Flight" :breadcrumbs="['Flights' => route('flights.index'), 'Flight – '.$flight->flight_number => route('flights.show', $flight), 'Edit' => null]">
    <x-ui.page-header :title="'Edit Flight – '.$flight->flight_number">
        <x-slot:actions>
            <x-ui.delete-button :action="route('flights.destroy', $flight)"
                                :confirm="'Delete flight '.$flight->flight_number.' and its FDR / video files?'" />
        </x-slot:actions>
    </x-ui.page-header>

    <form method="POST" action="{{ route('flights.update', $flight) }}" enctype="multipart/form-data">
        @method('PUT')
        @include('flights._form')

        <div class="mt-6 flex justify-end gap-2">
            <x-ui.button variant="outline" :href="route('flights.show', $flight)">Cancel</x-ui.button>
            <x-ui.button type="submit">Save Changes</x-ui.button>
        </div>
    </form>
</x-layouts.app>
