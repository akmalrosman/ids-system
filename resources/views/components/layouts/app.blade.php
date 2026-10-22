{{--
    Main layout with sidebar.
    <x-layouts.app title="Flights" :breadcrumbs="['Flights' => route('flights.index'), 'All' => null]">
--}}
@props(['title' => null, 'breadcrumbs' => []])

<!DOCTYPE html>
<html lang="en" class="h-full bg-neutral-950">
<head>
    @include('partials.head', ['title' => $title])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-neutral-100 antialiased"
      x-data="{
          sidebarOpen: JSON.parse(localStorage.getItem('ids.sidebar') ?? 'true'),
          mobileNav: false,
          toggleNav() { window.innerWidth < 768 ? this.mobileNav = !this.mobileNav : this.sidebarOpen = !this.sidebarOpen },
      }"
      x-init="$watch('sidebarOpen', v => localStorage.setItem('ids.sidebar', v))">

<div class="flex min-h-full">
    {{-- Mobile backdrop --}}
    <div x-cloak x-show="mobileNav" x-transition.opacity @click="mobileNav = false" class="fixed inset-0 z-30 bg-black/60 md:hidden"></div>

    <aside :class="[sidebarOpen ? 'md:w-64' : 'md:w-0', mobileNav ? 'translate-x-0' : '-translate-x-full md:translate-x-0']"
           class="fixed inset-y-0 left-0 z-40 w-64 shrink-0 overflow-hidden bg-neutral-950 transition-all duration-200 md:sticky md:top-0 md:h-screen">
        @include('partials.sidebar')
    </aside>

    <main class="min-w-0 flex-1 md:py-2 md:pr-2">
        <div class="min-h-full rounded-none border-neutral-800 bg-[#0b0b0b] md:min-h-[calc(100vh-1rem)] md:rounded-xl md:border">
            <header class="flex h-14 items-center gap-2 border-b border-neutral-800 px-4">
                <button type="button" @click="toggleNav()" class="rounded-md p-1.5 text-neutral-300 hover:bg-neutral-800" aria-label="Toggle sidebar">
                    <x-icon name="panel-left" class="size-4" />
                </button>

                @if ($breadcrumbs)
                    <nav aria-label="Breadcrumb" class="flex min-w-0 items-center gap-2 text-sm">
                        @foreach ($breadcrumbs as $label => $url)
                            @if (! $loop->first)<x-icon name="chevron-right" class="size-3.5 text-neutral-500" />@endif
                            @if ($url && ! $loop->last)
                                <a href="{{ $url }}" class="truncate text-neutral-400 hover:text-neutral-100">{{ $label }}</a>
                            @else
                                <span class="truncate text-neutral-100">{{ $label }}</span>
                            @endif
                        @endforeach
                    </nav>
                @endif
            </header>

            <div class="p-4 md:p-6">
                {{ $slot }}
            </div>
        </div>
    </main>
</div>

<x-ui.flash />
</body>
</html>
