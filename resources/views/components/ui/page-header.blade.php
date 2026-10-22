{{-- Title row at the top of show / form pages, with optional action buttons. --}}
@props(['title', 'description' => null])

<div {{ $attributes->class('mb-6 flex flex-wrap items-start justify-between gap-4 border-b border-neutral-800 pb-6') }}>
    <div>
        <h1 class="text-xl font-semibold tracking-tight">{{ $title }}</h1>
        @if ($description)<p class="mt-1 text-sm text-neutral-400">{{ $description }}</p>@endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
