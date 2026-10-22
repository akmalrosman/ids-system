<x-layouts.app title="Add Flight" :breadcrumbs="['Flights' => route('flights.index'), 'Add' => null]">
    <x-ui.page-header title="Add Flight" description="Upload the FDR file now or later from the edit page." />

    <form method="POST" action="{{ route('flights.store') }}" enctype="multipart/form-data">
        @include('flights._form')

        <div class="mt-6 flex justify-end gap-2">
            <x-ui.button variant="outline" :href="route('flights.index')">Cancel</x-ui.button>
            <x-ui.button type="submit">Save Flight</x-ui.button>
        </div>
    </form>
</x-layouts.app>
