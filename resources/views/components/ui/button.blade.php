{{--
    <x-ui.button>Save</x-ui.button>
    <x-ui.button variant="outline" size="sm" :href="route('flights.show', $flight)" icon="eye">View</x-ui.button>
    Variants: primary | secondary | outline | ghost | danger. Sizes: sm | md | icon.
--}}
@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'icon' => null, 'type' => 'button'])

@php
    $classes = [
        'inline-flex items-center justify-center gap-2 rounded-md font-medium whitespace-nowrap transition-colors',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-500 disabled:pointer-events-none disabled:opacity-50',
        match ($variant) {
            'primary' => 'bg-neutral-100 text-neutral-900 hover:bg-white',
            'secondary' => 'bg-neutral-800 text-neutral-100 hover:bg-neutral-700',
            'outline' => 'border border-neutral-800 bg-neutral-950 text-neutral-100 hover:bg-neutral-800/70',
            'ghost' => 'text-neutral-300 hover:bg-neutral-800/70 hover:text-neutral-100',
            'danger' => 'bg-red-700 text-white hover:bg-red-600',
        },
        match ($size) {
            'sm' => 'h-8 px-3 text-xs',
            'md' => 'h-9 px-4 text-sm',
            'icon' => 'size-9',
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
    </button>
@endif
