{{-- Satellite tile mosaic that covers its parent like background-size: cover. See App\View\Components\SatelliteBackdrop. --}}
<div aria-hidden="true" {{ $attributes->class('pointer-events-none absolute inset-0 overflow-hidden') }}>
    <div class="absolute top-1/2 left-1/2 grid min-h-full min-w-full -translate-x-1/2 -translate-y-1/2"
         style="grid-template-columns: repeat({{ $columns }}, minmax(0, 1fr)); aspect-ratio: {{ $columns }} / {{ count($rows) }};">
        @foreach ($rows as $row)
            @foreach ($row as $url)
                @if ($url)
                    <img src="{{ $url }}" alt="" decoding="async" class="block size-full object-cover">
                @else
                    <div class="bg-neutral-900"></div>
                @endif
            @endforeach
        @endforeach
    </div>
</div>
