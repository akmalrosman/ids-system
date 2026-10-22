/**
 * Time helpers. DJI apps write *local* wall-clock time with no timezone, so
 * the caller tells us the offset (Malaysia = +08:00).
 */

/** "+08:00" -> 480 (minutes) */
export function parseOffset(offset = '+00:00') {
    const match = String(offset).match(/^([+-])(\d{1,2}):?(\d{2})?$/);
    if (!match) return 0;

    const minutes = Number(match[2]) * 60 + Number(match[3] ?? 0);

    return match[1] === '-' ? -minutes : minutes;
}

/**
 * Parses local date + time strings into epoch milliseconds.
 * Accepts "5/18/2025" or "2025-05-18" / "2025/05/18" dates and
 * "4:00:00.10 PM" or "16:00:00.100" times.
 *
 * @returns {number|null}
 */
export function parseLocalDateTime(dateStr, timeStr, offsetMinutes) {
    const date = parseDate(dateStr);
    const time = parseTime(timeStr);
    if (!date || !time) return null;

    const utc = Date.UTC(date.y, date.m - 1, date.d, time.h, time.min, time.s, time.ms);

    return utc - offsetMinutes * 60_000;
}

function parseDate(str) {
    if (!str) return null;
    const s = str.trim();

    let m = s.match(/^(\d{4})[-/](\d{1,2})[-/](\d{1,2})$/);
    if (m) return { y: +m[1], m: +m[2], d: +m[3] };

    // US style used by DJI CSV exports: month/day/year
    m = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (m) return { y: +m[3], m: +m[1], d: +m[2] };

    return null;
}

function parseTime(str) {
    if (!str) return null;
    const m = str.trim().match(/^(\d{1,2}):(\d{2}):(\d{2})(?:\.(\d+))?\s*(AM|PM)?$/i);
    if (!m) return null;

    let h = +m[1];
    const ampm = m[5]?.toUpperCase();
    if (ampm === 'PM' && h < 12) h += 12;
    if (ampm === 'AM' && h === 12) h = 0;

    const frac = m[4] ? Number(`0.${m[4]}`) : 0;

    return { h, min: +m[2], s: +m[3], ms: Math.round(frac * 1000) };
}

/** Epoch ms -> "2025-05-18T16:00:00.000+08:00" */
export function toIsoWithOffset(epochMs, offsetMinutes) {
    const local = new Date(epochMs + offsetMinutes * 60_000).toISOString().replace('Z', '');
    const sign = offsetMinutes < 0 ? '-' : '+';
    const abs = Math.abs(offsetMinutes);
    const hh = String(Math.floor(abs / 60)).padStart(2, '0');
    const mm = String(abs % 60).padStart(2, '0');

    return `${local}${sign}${hh}:${mm}`;
}

/** DJI "OSD.flyTime" text like "12m 3.4s" or "1h 2m 3.4s" -> seconds */
export function parseDurationText(str) {
    if (str === undefined || str === null || str === '') return NaN;
    const asNumber = Number(str);
    if (Number.isFinite(asNumber)) return asNumber;

    const m = String(str).match(/^(?:(\d+)h\s*)?(?:(\d+)m\s*)?(?:([\d.]+)s)?$/);
    if (!m) return NaN;

    return (+(m[1] ?? 0)) * 3600 + (+(m[2] ?? 0)) * 60 + (+(m[3] ?? 0));
}
