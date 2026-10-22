/** Seconds -> "1:02:03.456" (or "1:02:03" without milliseconds). */
export function formatClock(seconds, withMs = true) {
    const total = Math.max(0, seconds);
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = Math.floor(total % 60);
    const ms = Math.floor((total % 1) * 1000);
    const base = `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    return withMs ? `${base}.${String(ms).padStart(3, '0')}` : base;
}

/** Epoch ms -> "16:05:12" in the app's timezone (config/app.php), not the viewer's. */
export function formatWallClock(epochMs, timeZone = undefined) {
    return new Date(epochMs).toLocaleTimeString('en-GB', { hour12: false, timeZone });
}
