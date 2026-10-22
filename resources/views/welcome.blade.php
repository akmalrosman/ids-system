<x-layouts.guest title="Welcome">
    <div class="relative flex min-h-full flex-col overflow-hidden">
        <div aria-hidden="true" class="bg-grid pointer-events-none absolute inset-0"></div>

        <header class="relative mx-auto flex h-20 w-full max-w-5xl items-center justify-between px-6">
            <span class="flex items-center gap-2.5 text-sm font-medium">
                <x-icon name="tower-control" class="size-5" />
                {{ config('app.name') }}
            </span>

            @auth
                <x-ui.button variant="outline" size="sm" :href="route('dashboard')">Dashboard</x-ui.button>
            @else
                <x-ui.button variant="outline" size="sm" :href="route('login')">Log in</x-ui.button>
            @endauth
        </header>

        <main class="relative flex flex-1 flex-col items-center justify-center px-6 text-center">
            <span class="mb-8 flex size-14 items-center justify-center rounded-2xl border border-neutral-800 bg-neutral-950">
                <x-icon name="tower-control" class="size-7" />
            </span>

            <h1 class="text-4xl font-semibold tracking-tight md:text-6xl">Integrated Debrief System</h1>
            <p class="mt-4 max-w-md text-neutral-400">Flight data analysis and 3D flight debrief.</p>

            <ul class="mt-10 flex flex-col items-center gap-3 text-sm text-neutral-500 sm:flex-row sm:gap-0">
                @foreach (['file-text' => 'Flight data processing', 'plane' => '3D flight replay', 'radar' => 'Formation debrief'] as $icon => $label)
                    <li class="flex items-center gap-2">
                        @unless ($loop->first)
                            <span aria-hidden="true" class="mx-5 hidden size-1 rounded-full bg-neutral-700 sm:block"></span>
                        @endunless
                        <x-icon :name="$icon" class="size-4 text-neutral-400" />
                        {{ $label }}
                    </li>
                @endforeach
            </ul>
        </main>

        <footer class="relative py-8 text-center text-xs text-neutral-600">
            &copy; {{ now()->year }} {{ config('app.name') }}
        </footer>
    </div>
</x-layouts.guest>
