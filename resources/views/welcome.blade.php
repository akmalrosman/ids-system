<x-layouts.guest title="Welcome">
    <div class="flex min-h-full flex-col px-6 py-6">
        <header class="mx-auto flex w-full max-w-5xl justify-end">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-md border border-neutral-800 px-4 py-1.5 text-sm hover:bg-neutral-900">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="rounded-md px-4 py-1.5 text-sm hover:bg-neutral-900">Log in</a>
            @endauth
        </header>

        <main class="flex flex-1 items-center justify-center">
            <div class="w-full max-w-4xl rounded-lg border border-neutral-800 bg-neutral-900/60 px-10 py-20 md:px-20 md:py-24">
                <h1 class="text-5xl leading-[1.05] font-semibold tracking-tight md:text-6xl">
                    Integrated<br>Debrief<br>System
                </h1>
            </div>
        </main>
    </div>
</x-layouts.guest>
