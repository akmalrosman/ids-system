{{--
    Split layout for the login / forgot-password pages: satellite panel on the left (large screens), form on the right.
    <x-layouts.auth title="Log in"> ...form... </x-layouts.auth>
--}}
@props(['title' => null])

<x-layouts.guest :title="$title">
    <div class="grid min-h-full lg:grid-cols-2">
        <aside class="relative hidden overflow-hidden border-r border-neutral-800 lg:flex lg:flex-col lg:justify-between lg:p-10">
            <x-satellite-backdrop location="Subang" class="opacity-70" />
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-linear-to-t from-neutral-950 via-neutral-950/60 to-neutral-950/40"></div>

            <a href="{{ route('home') }}" class="relative flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-md bg-neutral-100 text-neutral-900">
                    <x-icon name="tower-control" class="size-5" />
                </span>
                <span class="text-sm font-semibold">{{ config('app.name') }}</span>
            </a>

            <div class="relative max-w-md">
                <h2 class="text-3xl font-semibold tracking-tight">Integrated Debrief System</h2>
                <p class="mt-3 text-neutral-300">Review flight data and replay every sortie in 3D, one aircraft or the whole formation.</p>
            </div>
        </aside>

        <main class="flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-sm">
                <a href="{{ route('home') }}" class="mb-8 flex justify-center lg:hidden">
                    <x-icon name="tower-control" class="size-10" />
                </a>
                {{ $slot }}
            </div>
        </main>
    </div>
</x-layouts.guest>
