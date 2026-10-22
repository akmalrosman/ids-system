<?php

namespace App\Support;

/**
 * Formats a number of seconds the ways the UI needs.
 */
final class Duration
{
    /** 7849.0 -> "2:10:49.000" */
    public static function clock(?float $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        $ms = (int) round($seconds * 1000);
        $h = intdiv($ms, 3_600_000);
        $m = intdiv($ms % 3_600_000, 60_000);
        $s = intdiv($ms % 60_000, 1000);

        return sprintf('%d:%02d:%02d.%03d', $h, $m, $s, $ms % 1000);
    }

    /** 7849.0 -> "2 hour 10 min 49 sec" (long) or "2 hr 10 min 49 sec" (short) */
    public static function human(?float $seconds, bool $short = false): string
    {
        if ($seconds === null) {
            return '—';
        }

        $total = (int) round($seconds);
        $h = intdiv($total, 3600);
        $m = intdiv($total % 3600, 60);
        $s = $total % 60;

        $parts = [];
        if ($h > 0) {
            $parts[] = $h.($short ? ' hr' : ' hour');
        }
        if ($h > 0 || $m > 0) {
            $parts[] = $m.' min';
        }
        $parts[] = $s.' sec';

        return implode(' ', $parts);
    }
}
