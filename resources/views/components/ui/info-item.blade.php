{{-- Icon + label + value row used on the "show" pages. --}}
@props(['icon', 'label'])

<div class="flex items-start gap-3 py-1.5">
    <x-icon :name="$icon" class="mt-0.5 size-4 text-neutral-400" />
    <dt class="w-40 shrink-0 font-medium text-neutral-200">{{ $label }}</dt>
    <dd {{ $attributes->class('min-w-0 text-neutral-400') }}>{{ $slot }}</dd>
</div>
