<x-layouts.app :title="$type->name" :breadcrumbs="['Aircraft Types' => route('aircraft-types.index'), 'Show' => null]">
    <x-ui.page-header :title="$type->name" :description="$type->description">
        <x-slot:actions>
            @can(\App\Support\Permission::AIRCRAFT_MANAGE)
                <x-ui.button :href="route('aircraft-types.edit', $type)">Edit Aircraft Type</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <dl class="grid gap-x-8 text-sm md:grid-cols-2">
            <div>
                <x-ui.info-item icon="info" label="Manufacturer">{{ $type->manufacturer }}</x-ui.info-item>
                <x-ui.info-item icon="plane" label="Model">{{ $type->model }}</x-ui.info-item>
            </div>
            <div>
                <x-ui.info-item icon="box" label="3D model">{{ $type->model_path ? 'Uploaded' : 'Default (Su-30)' }}</x-ui.info-item>
                <x-ui.info-item icon="arrow-up-down" label="Length / offset">{{ $type->model_length_m }} m · {{ $type->model_yaw_offset_deg }}°</x-ui.info-item>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Aircraft of this type" class="mt-6">
        <div class="flex flex-wrap gap-2">
            @forelse ($type->aircraft as $a)
                <a href="{{ route('aircraft.show', $a) }}" class="rounded-md border border-neutral-800 px-3 py-1.5 text-sm hover:bg-neutral-900">{{ $a->number }}</a>
            @empty
                <p class="text-sm text-neutral-400">None yet.</p>
            @endforelse
        </div>
    </x-ui.card>
</x-layouts.app>
