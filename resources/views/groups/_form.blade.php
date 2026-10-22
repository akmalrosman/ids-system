{{-- Shared by groups/create and groups/edit. Expects $group, $flights, $selectedFlights, $locations. --}}
@php $selected = array_map('intval', old('flight_ids', $selectedFlights)); @endphp

@csrf

<div class="grid gap-6 lg:grid-cols-3">
    <x-ui.card title="Details">
        <div class="space-y-5">
            <x-ui.field label="Title" name="title" required>
                <input id="title" name="title" required maxlength="150" class="form-control" value="{{ old('title', $group->title) }}">
            </x-ui.field>
            <x-ui.field label="Date & time" name="flown_at" required>
                <input id="flown_at" name="flown_at" type="datetime-local" step="1" required class="form-control"
                       value="{{ old('flown_at', $group->flown_at?->format('Y-m-d\TH:i:s')) }}">
            </x-ui.field>
            <x-ui.field label="Location" name="location">
                <input id="location" name="location" list="locations" class="form-control" value="{{ old('location', $group->location) }}">
                <datalist id="locations">@foreach ($locations as $loc)<option value="{{ $loc }}">@endforeach</datalist>
            </x-ui.field>
            <x-ui.field label="Description" name="description">
                <textarea id="description" name="description" rows="3" class="form-control">{{ old('description', $group->description) }}</textarea>
            </x-ui.field>
        </div>
    </x-ui.card>

    <x-ui.card title="Flights" description="Pick the flights to present together. They are synced by their FDR timestamps." class="lg:col-span-2">
        <div x-data="{ q: '' }">
            <input type="search" x-model="q" placeholder="Filter flights..." class="form-control mb-3">
            @error('flight_ids')<p class="mb-2 text-xs text-red-400">{{ $message }}</p>@enderror
            <div class="max-h-[28rem] overflow-y-auto rounded-md border border-neutral-800">
                @forelse ($flights as $flight)
                    @php $text = strtolower($flight->aircraft->number.' '.$flight->flight_number.' '.$flight->location.' '.$flight->pilot?->name.' '.$flight->flown_at->format('Y-m-d')); @endphp
                    <label x-show="!q || @js($text).includes(q.toLowerCase())"
                           class="flex cursor-pointer items-center gap-3 border-b border-neutral-800/70 px-3 py-2.5 text-sm last:border-0 hover:bg-neutral-900">
                        <input type="checkbox" name="flight_ids[]" value="{{ $flight->id }}" @checked(in_array($flight->id, $selected))
                               class="size-4 accent-neutral-100">
                        <span class="w-14 font-medium">{{ $flight->aircraft->number }}</span>
                        <span class="w-24 text-neutral-400">Flight {{ $flight->flight_number }}</span>
                        <span class="flex-1 truncate text-neutral-400">{{ $flight->pilot?->name }} · {{ $flight->location }}</span>
                        @unless ($flight->hasTelemetry())
                            <x-ui.badge variant="muted">no telemetry</x-ui.badge>
                        @endunless
                        <span class="text-neutral-500">{{ $flight->flown_at->format('j M Y, H:i') }}</span>
                    </label>
                @empty
                    <p class="p-4 text-sm text-neutral-500">No flights yet.</p>
                @endforelse
            </div>
        </div>
    </x-ui.card>
</div>
