{{-- GET search box used on every list page; controllers read request('search'). --}}
@props(['placeholder' => 'Search...'])

<form method="GET" {{ $attributes->class('relative w-full max-w-md') }}>
    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-500" />
    <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ $placeholder }}"
           class="form-control h-10 pl-9">
</form>
