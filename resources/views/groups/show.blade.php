<x-layouts.app :title="$group->title" :breadcrumbs="['Groups' => route('groups.index'), 'Show' => null]">
    <x-ui.page-header title="Group / Formation Details" :description="$group->description">
        <x-slot:actions>
            @if ($group->flights->contains(fn ($f) => $f->hasTelemetry()))
                <x-ui.button :href="route('groups.present', $group)" target="_blank" data-present-window icon="play">Present</x-ui.button>
            @endif
            @can(\App\Support\Permission::GROUPS_MANAGE)
                <x-ui.button variant="outline" :href="route('groups.edit', $group)">Edit Group</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <dl class="grid gap-x-8 text-sm md:grid-cols-2">
            <div>
                <x-ui.info-item icon="radar" label="Title">{{ $group->title }}</x-ui.info-item>
                <x-ui.info-item icon="calendar" label="Date">{{ $group->flown_at->format('Y-m-d H:i:s') }}</x-ui.info-item>
                <x-ui.info-item icon="timer" label="Duration">{{ \App\Support\Duration::human($group->durationSeconds()) }}</x-ui.info-item>
            </div>
            <div>
                <x-ui.info-item icon="plane" label="Aircrafts">{{ $group->flights->pluck('aircraft_id')->unique()->count() }}</x-ui.info-item>
                <x-ui.info-item icon="map-pin" label="Location">{{ $group->location ?? '—' }}</x-ui.info-item>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Flights" class="mt-6">
        @include('partials.flights-table', [
            'flights' => $group->flights,
            'actions' => ['present', 'view', 'edit'],
            'showWso' => false,
        ])
    </x-ui.card>
</x-layouts.app>
