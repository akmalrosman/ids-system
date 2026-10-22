<x-layouts.app title="Add Aircraft Type" :breadcrumbs="['Aircraft Types' => route('aircraft-types.index'), 'Add' => null]">
    <x-ui.page-header title="Add Aircraft Type" />
    <form method="POST" action="{{ route('aircraft-types.store') }}" enctype="multipart/form-data">
        @include('aircraft-types._form')
        <div class="mt-6 flex max-w-4xl justify-end gap-2">
            <x-ui.button variant="outline" :href="route('aircraft-types.index')">Cancel</x-ui.button>
            <x-ui.button type="submit">Save Aircraft Type</x-ui.button>
        </div>
    </form>
</x-layouts.app>
