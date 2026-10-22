{{--
    Minimal centred layout for the login / forgot-password pages (same grid background as the landing page).
    <x-layouts.auth title="Log in"> ...form... </x-layouts.auth>
--}}
@props(['title' => null])

<x-layouts.guest :title="$title">
    <div class="relative flex min-h-full flex-col items-center justify-center overflow-hidden px-6 py-12">
        <div aria-hidden="true" class="bg-grid pointer-events-none absolute inset-0"></div>

        <a href="{{ route('home') }}" class="relative mb-8 flex items-center gap-2.5 text-sm font-medium text-neutral-300 hover:text-neutral-100">
            <x-icon name="tower-control" class="size-5" />
            {{ config('app.name') }}
        </a>

        <div class="relative w-full max-w-sm rounded-2xl border border-neutral-800 bg-neutral-950 p-8">
            {{ $slot }}
        </div>
    </div>
</x-layouts.guest>
