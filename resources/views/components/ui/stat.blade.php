{{--
    Number tile for the dashboard.
    <x-ui.stat label="Total Flights" :value="8" icon="plane-takeoff" hint="8 with telemetry" />
    Put extra content (e.g. badges) in the slot to show it under the value instead of the hint.
--}}
@props(['label', 'value', 'icon', 'hint' => null])

<div {{ $attributes->class('rounded-xl border border-neutral-800 bg-neutral-950 p-6') }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-sm text-neutral-400">{{ $label }}</p>
        <span class="flex size-8 items-center justify-center rounded-md bg-neutral-900 text-neutral-400">
            <x-icon :name="$icon" class="size-4" />
        </span>
    </div>
    <p class="mt-3 text-2xl font-semibold tracking-tight whitespace-nowrap">{{ $value }}</p>
    @if ($slot->isNotEmpty())
        <div class="mt-2">{{ $slot }}</div>
    @elseif ($hint)
        <p class="mt-2 text-xs text-neutral-500">{{ $hint }}</p>
    @endif
</div>
