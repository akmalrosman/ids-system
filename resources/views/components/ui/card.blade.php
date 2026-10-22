@props(['title' => null, 'description' => null, 'padding' => true])

<section {{ $attributes->class('rounded-xl border border-neutral-800 bg-neutral-950') }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-center justify-between gap-3 px-6 pt-6">
            <div>
                @if ($title)<h2 class="text-base font-medium">{{ $title }}</h2>@endif
                @if ($description)<p class="mt-1 text-sm text-neutral-400">{{ $description }}</p>@endif
            </div>
            {{ $actions ?? '' }}
        </header>
    @endif

    <div @class(['p-6' => $padding, 'pt-4' => $padding && $title])>
        {{ $slot }}
    </div>
</section>
