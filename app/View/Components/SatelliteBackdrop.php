<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Background made of the offline satellite tiles in public/tiles, centred on a
 * known location (config('ids.locations')). Renders nothing if the tiles were
 * not downloaded, so pages fall back to their plain background.
 *
 *   <x-satellite-backdrop location="Langkawi" class="opacity-60" />
 */
class SatelliteBackdrop extends Component
{
    /** Tiles each side of the centre tile (2 = a 5 x 5 grid). */
    private const RADIUS = 2;

    /** @var list<list<string|null>> rows of tile URLs (null = tile not downloaded) */
    public array $rows = [];

    public int $columns = 0;

    public function __construct(public string $location = 'Langkawi', public int $zoom = 14)
    {
        $centre = config("ids.locations.{$location}");

        if ($centre) {
            $this->rows = $this->tileRows($centre['lat'], $centre['lng']);
            $this->columns = count($this->rows[0] ?? []);
        }
    }

    /** Only show the backdrop when most of the grid exists on disk. */
    public function shouldRender(): bool
    {
        $tiles = array_merge(...$this->rows ?: [[]]);

        return $tiles !== [] && count(array_filter($tiles)) >= count($tiles) * 0.75;
    }

    public function render(): View
    {
        return view('components.satellite-backdrop');
    }

    /**
     * @return list<list<string|null>>
     */
    private function tileRows(float $lat, float $lng): array
    {
        $n = 2 ** $this->zoom;
        $centreX = (int) floor(($lng + 180) / 360 * $n);
        $latRad = deg2rad($lat);
        $centreY = (int) floor((1 - log(tan($latRad) + 1 / cos($latRad)) / M_PI) / 2 * $n);

        $rows = [];
        foreach (range($centreY - self::RADIUS, $centreY + self::RADIUS) as $y) {
            $row = [];
            foreach (range($centreX - self::RADIUS, $centreX + self::RADIUS) as $x) {
                $path = "tiles/{$this->zoom}/{$x}/{$y}.jpg";
                $row[] = is_file(public_path($path)) ? asset($path) : null;
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
