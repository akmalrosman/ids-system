<x-layouts.guest title="Welcome">
    @php
        $features = [
            ['file-text', 'Flight data processing', 'Upload DJI flight logs (aircraft FDR later). They are converted to telemetry automatically.'],
            ['plane', '3D flight replay', 'Replay every flight on a 3D map with the aircraft model, its path and live flight parameters.'],
            ['radar', 'Formation debrief', 'Play a whole group on one synced timeline, with each aircraft\'s BVR video beside it.'],
            ['wifi-off', 'Works offline', 'Malaysian map imagery, 3D models and data are all served from your own server.'],
        ];
    @endphp

    <div class="relative min-h-full overflow-hidden bg-neutral-950">
        <x-satellite-backdrop class="opacity-80" />
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-neutral-950/75 md:bg-transparent md:bg-linear-to-r md:from-neutral-950 md:via-neutral-950/75 md:to-neutral-950/10"></div>
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 bottom-0 h-1/2 bg-linear-to-t from-neutral-950 to-transparent"></div>

        <div class="relative mx-auto flex min-h-screen max-w-6xl flex-col px-6">
            <header class="flex h-20 items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex size-9 items-center justify-center rounded-md bg-neutral-100 text-neutral-900">
                        <x-icon name="tower-control" class="size-5" />
                    </span>
                    <span class="text-sm font-semibold">{{ config('app.name') }}</span>
                </div>

                @auth
                    <x-ui.button variant="outline" :href="route('dashboard')" class="bg-neutral-950/60">Dashboard</x-ui.button>
                @else
                    <x-ui.button variant="ghost" :href="route('login')">Log in</x-ui.button>
                @endauth
            </header>

            <main class="flex flex-1 flex-col justify-center py-16">
                <p class="mb-6 inline-flex w-fit items-center gap-2 rounded-full border border-white/10 bg-neutral-950/60 px-3 py-1 text-xs text-neutral-300 backdrop-blur">
                    <span class="size-1.5 rounded-full bg-emerald-400"></span>
                    Flight debrief platform
                </p>

                <h1 class="max-w-3xl text-5xl leading-[1.05] font-semibold tracking-tight md:text-7xl">
                    Integrated<br>Debrief System
                </h1>

                <p class="mt-6 max-w-xl text-lg text-neutral-300">
                    From flight data to a full 3D debrief: process recorder files, review every parameter, and replay
                    single flights or whole formations over the map of Malaysia.
                </p>

                <div class="mt-10 flex flex-wrap gap-3">
                    @auth
                        <x-ui.button :href="route('dashboard')" class="h-11 px-6">
                            Go to dashboard <x-icon name="arrow-right" class="size-4" />
                        </x-ui.button>
                    @else
                        <x-ui.button :href="route('login')" icon="log-in" class="h-11 px-6">Log in</x-ui.button>
                    @endauth
                </div>
            </main>

            <section class="grid gap-4 pb-12 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($features as [$icon, $title, $text])
                    <div class="rounded-xl border border-white/10 bg-neutral-950/70 p-5 backdrop-blur">
                        <span class="flex size-9 items-center justify-center rounded-md bg-white/10">
                            <x-icon :name="$icon" class="size-4" />
                        </span>
                        <h2 class="mt-4 text-sm font-medium">{{ $title }}</h2>
                        <p class="mt-1.5 text-sm text-neutral-400">{{ $text }}</p>
                    </div>
                @endforeach
            </section>

            <footer class="flex flex-wrap justify-between gap-2 border-t border-white/10 py-6 text-xs text-neutral-500">
                <span>&copy; {{ now()->year }} {{ config('app.name') }}</span>
                <span>Imagery: {{ config('ids.map.attribution') }}</span>
            </footer>
        </div>
    </div>
</x-layouts.guest>
