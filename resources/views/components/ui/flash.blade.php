{{-- Toast for session('status') / session('error') set by controllers. --}}
@php
    $message = session('error') ?? session('status');
    $isError = session()->has('error');
@endphp

@if ($message)
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
         class="fixed right-4 bottom-4 z-50 flex max-w-sm items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-xl
                {{ $isError ? 'border-red-800 bg-red-950 text-red-100' : 'border-neutral-700 bg-neutral-900 text-neutral-100' }}"
         role="status">
        <x-icon :name="$isError ? 'triangle-alert' : 'circle-check'" class="mt-0.5 size-4" />
        <span class="flex-1">{{ $message }}</span>
        <button type="button" @click="show = false" class="text-neutral-400 hover:text-neutral-100" aria-label="Close">
            <x-icon name="x" class="size-4" />
        </button>
    </div>
@endif
