{{--
    One aircraft card beside the 3D view. Wired up by resources/js/presentation/ui.js via data-* attributes.
    data-video="off" (no BVR video, or the video is hidden) switches the flight data to a larger layout.
--}}
<div data-card data-flight-id="{{ $flight['id'] }}" data-video="{{ $flight['video'] ? 'on' : 'off' }}"
     class="group overflow-hidden rounded-md border-2 bg-neutral-950 ring-white transition"
     style="border-color: {{ $flight['color'] }}">
    <div class="flex items-center justify-between px-2 py-1 text-sm font-medium text-white" style="background: {{ $flight['color'] }}">
        <span class="truncate">{{ $flight['label'] }}</span>
        <span class="text-xs opacity-80">#{{ $flight['flightNumber'] }}</span>
    </div>

    <div class="flex gap-2 p-2">
        <div class="flex min-w-0 flex-1 flex-col">
            @if ($flight['video'])
                <div data-card-video class="mb-2">
                    <video data-video src="{{ $flight['video']['url'] }}" muted playsinline preload="metadata"
                           class="aspect-video w-full rounded bg-black object-cover"></video>
                </div>
            @endif

            <dl class="grid grid-cols-3 gap-x-2 gap-y-1 font-mono text-[11px]
                       group-data-[video=off]:flex-1 group-data-[video=off]:grid-cols-2 group-data-[video=off]:content-evenly group-data-[video=off]:gap-x-3">
                @foreach (['alt' => 'ALT', 'speed' => 'SPD', 'heading' => 'HDG', 'vs' => 'V/S', 'battery' => 'BAT', 'time' => 'T+'] as $key => $label)
                    <div class="group-data-[video=off]:rounded group-data-[video=off]:bg-neutral-900 group-data-[video=off]:px-2 group-data-[video=off]:py-1.5">
                        <dt class="text-neutral-500 group-data-[video=off]:text-[10px] group-data-[video=off]:tracking-wider">{{ $label }}</dt>
                        <dd data-field="{{ $key }}" class="truncate text-neutral-100 group-data-[video=off]:text-sm group-data-[video=off]:font-semibold">—</dd>
                    </div>
                @endforeach
            </dl>
            <p data-card-error class="mt-2 hidden text-xs text-red-400">Telemetry failed to load.</p>
        </div>

        <div class="flex flex-col gap-1">
            <button type="button" data-card-action="path" title="Show / hide flight path" class="rounded p-1.5 hover:bg-white/10">
                <x-icon name="route" class="size-4" />
            </button>
            <button type="button" data-card-action="model" title="Show / hide 3D model" class="rounded p-1.5 hover:bg-white/10">
                <x-icon name="box" class="size-4" />
            </button>
            <button type="button" data-card-action="color" title="Colour the model in this flight's colour" class="rounded p-1.5 hover:bg-white/10">
                <x-icon name="palette" class="size-4" style="color: {{ $flight['color'] }}" />
            </button>
            <button type="button" data-card-action="follow" title="Follow this aircraft" class="rounded p-1.5 hover:bg-white/10">
                <x-icon name="crosshair" class="size-4" />
            </button>
            @if ($flight['video'])
                <button type="button" data-card-action="video" title="Show / hide BVR video" class="rounded p-1.5 hover:bg-white/10">
                    <x-icon name="video" class="size-4" />
                </button>
                <button type="button" data-card-action="mute" title="Mute / unmute video" class="rounded p-1.5 hover:bg-white/10">
                    <span data-icon="muted"><x-icon name="volume-x" class="size-4" /></span>
                    <span data-icon="unmuted" class="hidden"><x-icon name="volume-2" class="size-4" /></span>
                </button>
            @endif
        </div>
    </div>
</div>
