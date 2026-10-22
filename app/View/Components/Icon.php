<?php

namespace App\View\Components;

use Illuminate\View\Component;
use InvalidArgumentException;

/**
 * Inline SVG icon from resources/icons (Lucide, ISC licence).
 *
 *   <x-icon name="plane" class="size-4" />
 *
 * To add an icon, copy its .svg from https://lucide.dev into resources/icons.
 */
class Icon extends Component
{
    /** @var array<string, string> */
    private static array $cache = [];

    public string $svg;

    public function __construct(public string $name)
    {
        $this->svg = self::$cache[$name] ??= self::load($name);
    }

    public function render(): string
    {
        return <<<'BLADE'
            {!! str_replace('<svg', '<svg '.$attributes->merge(['class' => 'shrink-0', 'aria-hidden' => 'true'])->toHtml(), $svg) !!}
            BLADE;
    }

    private static function load(string $name): string
    {
        $path = resource_path("icons/{$name}.svg");

        if (! preg_match('/^[a-z0-9-]+$/', $name) || ! is_file($path)) {
            throw new InvalidArgumentException("Icon [{$name}] not found in resources/icons.");
        }

        $svg = file_get_contents($path);
        $svg = preg_replace('/<!--.*?-->/s', '', $svg);
        // Size comes from the class (e.g. size-4); drop the fixed size and Lucide's own class.
        // Only on the outer <svg> tag: inner shapes like <rect> need their own width/height.
        $svg = preg_replace_callback(
            '/<svg\b[^>]*>/',
            fn (array $tag) => preg_replace('/\s(width|height|class)="[^"]*"/', '', $tag[0]),
            $svg,
            1,
        );

        return trim(preg_replace('/\s+/', ' ', $svg));
    }
}
