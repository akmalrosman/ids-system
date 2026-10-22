{{-- Bordered card around a .data-table on the list pages. --}}
<div {{ $attributes->class('rounded-xl border border-neutral-800 bg-neutral-950 px-3') }}>
    {{ $slot }}
</div>
