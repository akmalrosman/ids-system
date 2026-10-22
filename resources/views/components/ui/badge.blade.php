{{-- Variants: outline | solid | success | warning | danger | muted --}}
@props(['variant' => 'outline'])

<span {{ $attributes->class([
    'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium whitespace-nowrap',
    match ($variant) {
        'outline' => 'border border-neutral-700 bg-neutral-900 text-neutral-200',
        'solid' => 'bg-neutral-100 text-neutral-900',
        'success' => 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/30',
        'warning' => 'bg-amber-500/15 text-amber-300 ring-1 ring-amber-500/30',
        'danger' => 'bg-red-700 text-white',
        'muted' => 'bg-neutral-800 text-neutral-400',
    },
]) }}>{{ $slot }}</span>
