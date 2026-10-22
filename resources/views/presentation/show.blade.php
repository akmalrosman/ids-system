{{--
    Full-screen 3D debrief for one flight or a group.
    $config comes from PresentationController::buildConfig and is read by resources/js/presentation/main.js.
--}}
@php
    $flights = $config['flights'];
    $rightCount = count($flights) <= 4 ? count($flights) : (int) ceil(count($flights) / 2);
    $right = array_slice($flights, 0, $rightCount);
    $left = array_slice($flights, $rightCount);
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full bg-black">
<head>
    @include('partials.head', ['title' => $title])
    @vite(['resources/css/app.css', 'resources/js/presentation/main.js'])
</head>
<body class="h-full overflow-hidden bg-black font-sans text-neutral-100 antialiased">
<div data-presentation class="flex h-full flex-col">

    {{-- Top bar --}}
    <header class="flex flex-wrap items-center gap-x-5 gap-y-2 border-b border-neutral-800 bg-neutral-950 px-3 py-2 text-sm">
        <div class="min-w-0 max-w-64">
            <p class="truncate font-medium">{{ $config['title'] }}</p>
            <p class="truncate text-xs text-neutral-400">{{ $config['subtitle'] }}</p>
        </div>

        <button type="button" data-action="play" class="inline-flex h-8 items-center gap-2 rounded-md bg-neutral-100 px-3 font-medium text-neutral-900 hover:bg-white">
            <span data-icon="play"><x-icon name="play" class="size-4" /></span>
            <span data-icon="pause" class="hidden"><x-icon name="pause" class="size-4" /></span>
            <span data-label>Play</span>
        </button>

        <label class="flex items-center gap-2 text-neutral-300">Aircraft Scale:
            <select data-control="scale" class="form-control h-8 w-24 py-0" title="Auto keeps aircraft visible when zoomed out and real-size up close">
                <option value="auto">Auto</option>
                @foreach ([1, 2, 5, 10, 25, 50, 100] as $s)<option value="{{ $s }}">{{ $s }}x</option>@endforeach
            </select>
        </label>

        <label class="flex items-center gap-2 text-neutral-300">Speed:
            <select data-control="speed" class="form-control h-8 w-20 py-0">
                @foreach ([0.5, 1, 2, 4, 8, 16, 32, 64] as $s)<option value="{{ $s }}" @selected($s == 1)>{{ $s }}x</option>@endforeach
            </select>
        </label>

        <div class="font-mono text-neutral-300">
            Time (sec): <span data-readout="time" class="text-neutral-100">0:00:00.000</span>
            @if ($config['timelineStart'])
                <span class="ml-2 text-neutral-500">Clock:</span> <span data-readout="clock" class="text-neutral-100">—</span>
            @endif
        </div>

        <input type="range" data-control="timeline" min="0" max="1" step="0.1" value="0" class="min-w-40 flex-1 accent-neutral-100" aria-label="Timeline">

        <a href="{{ $config['closeUrl'] }}" class="inline-flex h-8 items-center gap-1.5 rounded-md bg-red-700 px-3 font-medium text-white hover:bg-red-600">
            Close <x-icon name="x" class="size-4" />
        </a>
    </header>

    <div class="flex min-h-0 flex-1">
        @if ($left)
            <aside class="w-72 shrink-0 space-y-2 overflow-y-auto bg-black p-2">
                @foreach ($left as $flight) @include('presentation._card') @endforeach
            </aside>
        @endif

        {{-- 3D view --}}
        <div data-scene class="relative min-w-0 flex-1 overflow-hidden">
            @if ($missing->isNotEmpty())
                <div class="absolute top-3 left-1/2 z-10 -translate-x-1/2 rounded-md bg-amber-500/15 px-3 py-1.5 text-xs text-amber-200 ring-1 ring-amber-500/30">
                    Not shown (no processed FDR): {{ $missing->map(fn ($f) => $f->aircraft->number.' / '.$f->flight_number)->join(', ') }}
                </div>
            @endif

            <div class="absolute right-3 top-3 z-10 flex flex-col overflow-hidden rounded-md bg-neutral-900/90 text-lg leading-none">
                <button type="button" data-action="zoom-in" title="Zoom in (+)" aria-label="Zoom in" class="size-9 hover:bg-neutral-800">+</button>
                <button type="button" data-action="zoom-out" title="Zoom out (-)" aria-label="Zoom out" class="size-9 border-t border-neutral-700 hover:bg-neutral-800">−</button>
            </div>

            <div class="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 items-center gap-2 text-sm">
                <button type="button" data-action="reset" class="h-9 rounded-md bg-neutral-900/90 px-4 whitespace-nowrap hover:bg-neutral-800">Reset Camera</button>
                <button type="button" data-action="fit" class="h-9 rounded-md bg-neutral-900/90 px-4 whitespace-nowrap hover:bg-neutral-800">Fit All</button>
                <button type="button" data-action="paths" class="h-9 rounded-md bg-neutral-900/90 px-4 whitespace-nowrap hover:bg-neutral-800">All Paths</button>
                <select data-control="linewidth" class="form-control h-9 w-36 border-0 bg-neutral-900/90">
                    <option value="2">Thin (2px)</option>
                    <option value="5" selected>Normal (5px)</option>
                    <option value="8">Thick (8px)</option>
                </select>
            </div>

            <p class="pointer-events-none absolute bottom-1 left-2 z-10 text-[10px] text-white/50">{{ $config['map']['attribution'] }}</p>
            <p class="pointer-events-none absolute right-2 bottom-1 z-10 text-[10px] text-white/40">
                Drag: pan · Right-drag: rotate · Wheel / +/−: zoom · Double-click aircraft: zoom &amp; follow · Space: play/pause · ←/→: ±10 s
            </p>

            <div data-loading class="absolute inset-0 z-20 grid place-items-center bg-black">
                <div class="flex items-center gap-3 text-sm text-neutral-300">
                    <span data-spinner><x-icon name="loader-circle" class="size-5 animate-spin" /></span>
                    <span data-loading-text>Loading flight data…</span>
                </div>
            </div>
        </div>

        @if ($right)
            <aside class="w-72 shrink-0 space-y-2 overflow-y-auto bg-black p-2">
                @foreach ($right as $flight) @include('presentation._card') @endforeach
            </aside>
        @endif
    </div>
</div>

{{-- JSON_HEX_TAG keeps a "</script>" inside a title from ending the tag early. --}}
<script type="application/json" id="presentation-config">{!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) !!}</script>
</body>
</html>
