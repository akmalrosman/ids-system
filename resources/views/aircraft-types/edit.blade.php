<x-layouts.app title="Edit Aircraft Type" :breadcrumbs="['Aircraft Types' => route('aircraft-types.index'), $type->name => route('aircraft-types.show', $type), 'Edit' => null]">
    <x-ui.page-header :title="'Edit '.$type->name">
        <x-slot:actions>
            <x-ui.delete-button :action="route('aircraft-types.destroy', $type)" :confirm="'Delete aircraft type '.$type->name.'?'" />
        </x-slot:actions>
    </x-ui.page-header>
    <form method="POST" action="{{ route('aircraft-types.update', $type) }}" enctype="multipart/form-data">
        @method('PUT')
        @include('aircraft-types._form')
        <div class="mt-6 flex max-w-4xl justify-end gap-2">
            <x-ui.button variant="outline" :href="route('aircraft-types.show', $type)">Cancel</x-ui.button>
            <x-ui.button type="submit">Save Changes</x-ui.button>
        </div>
    </form>
</x-layouts.app>
