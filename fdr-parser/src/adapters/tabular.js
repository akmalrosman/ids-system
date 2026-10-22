import { parseCsv, splitHeader } from '../lib/csv.js';
import { toSI } from '../lib/units.js';
import { parseDurationText, parseLocalDateTime } from '../lib/time.js';
import { FdrError } from '../errors.js';

/**
 * Shared logic for CSV-style flight logs. Each format only describes which
 * column names map to which telemetry field (a "profile"), the rest is here.
 *
 * Profile shape:
 * {
 *   columns: { lat: ['OSD.latitude'], alt: ['OSD.height'], ... },  // candidate names, first match wins
 *   verticalSpeedIsDown: true,      // DJI zSpeed is NED (positive = descending)
 *   dateTimeIsUtc: false,           // true when the `dateTime` column is UTC
 *   timeUnitDefault: 's',           // unit of `t` when the header has none
 * }
 *
 * Fields: t, date, clock, dateTime, lat, lng, alt, altMsl, speed, vx, vy, vz,
 *         pitch, roll, yaw, battery, sats, mode,
 *         aircraftName, aircraftSn, droneType, homeLat, homeLng
 */

const LENGTH_FIELDS = new Set(['alt', 'altMsl']);
const SPEED_FIELDS = new Set(['speed', 'vx', 'vy', 'vz']);
const TEXT_FIELDS = new Set(['date', 'clock', 'dateTime', 'mode', 'aircraftName', 'aircraftSn', 'droneType']);

/**
 * Finds the header row (some exports start with "sep=," or a title line).
 * @returns {{ headerIndex: number, rows: string[][] }}
 */
export function readTable(text) {
    const rows = parseCsv(text.replace(/^﻿/, ''));
    const headerIndex = rows.findIndex((r) => r.length > 3);

    if (headerIndex === -1) {
        throw new FdrError('EMPTY_FILE', 'The file has no data rows.');
    }

    return { headerIndex, rows };
}

/**
 * Resolves profile column names to header indexes.
 * When a name appears twice (e.g. "OSD.flyTime" and "OSD.flyTime [s]") the one with a unit wins.
 *
 * @returns {Record<string, {index: number, unit: string|null}>}
 */
export function resolveColumns(headers, profile) {
    const byName = new Map();
    headers.forEach((raw, index) => {
        const { name, unit } = splitHeader(raw);
        const key = name.toLowerCase();
        const existing = byName.get(key);
        if (!existing || (!existing.unit && unit)) byName.set(key, { index, unit });
    });

    const resolved = {};
    for (const [field, candidates] of Object.entries(profile.columns)) {
        for (const candidate of candidates) {
            const hit = byName.get(candidate.toLowerCase());
            if (hit) {
                resolved[field] = hit;
                break;
            }
        }
    }

    return resolved;
}

/**
 * Converts table rows into raw samples (SI units, angles in degrees).
 *
 * @returns {{ samples: object[], meta: object }}
 */
export function rowsToSamples(text, profile, options) {
    const { headerIndex, rows } = readTable(text);
    const cols = resolveColumns(rows[headerIndex], profile);

    if (!cols.lat || !cols.lng) {
        throw new FdrError('MISSING_COLUMNS', 'Could not find latitude/longitude columns in the file.');
    }

    const meta = {};
    const samples = [];

    for (let r = headerIndex + 1; r < rows.length; r++) {
        const row = rows[r];
        const v = {};

        for (const [field, col] of Object.entries(cols)) {
            const raw = row[col.index];
            if (raw === undefined || raw === '') continue;

            if (TEXT_FIELDS.has(field)) {
                v[field] = raw.trim();
            } else if (field === 't') {
                const unit = col.unit ?? profile.timeUnitDefault ?? 's';
                v.t = toSI(parseDurationText(raw), unit, 'time');
            } else {
                const num = Number(raw);
                if (!Number.isFinite(num)) continue;
                if (LENGTH_FIELDS.has(field)) v[field] = toSI(num, col.unit ?? 'm', 'length');
                else if (SPEED_FIELDS.has(field)) v[field] = toSI(num, col.unit ?? 'm/s', 'speed');
                else v[field] = num;
            }
        }

        // File-level metadata: keep the first non-empty value.
        for (const key of ['aircraftName', 'aircraftSn', 'droneType', 'homeLat', 'homeLng']) {
            if (meta[key] === undefined && v[key] !== undefined && v[key] !== 0) meta[key] = v[key];
        }

        samples.push(buildSample(v, profile, options));
    }

    return { samples, meta };
}

function buildSample(v, profile, options) {
    let time = null;
    if (v.dateTime) {
        time = parseDateTime(v.dateTime, profile.dateTimeIsUtc, options.offsetMinutes);
    } else if (v.date && v.clock) {
        time = parseLocalDateTime(v.date, v.clock, options.offsetMinutes);
    }

    let speed = v.speed;
    if (speed === undefined && v.vx !== undefined && v.vy !== undefined) {
        speed = Math.hypot(v.vx, v.vy);
    }

    let vs = v.vz;
    if (vs !== undefined && profile.verticalSpeedIsDown) vs = -vs;

    return {
        t: v.t,
        time,
        lat: v.lat,
        lng: v.lng,
        alt: v.alt,
        altMsl: v.altMsl,
        speed,
        vs,
        pitch: v.pitch,
        roll: v.roll,
        yaw: v.yaw,
        battery: v.battery,
        sats: v.sats,
        mode: v.mode,
    };
}

/** ISO strings, or "2025-05-18 08:00:00" which is UTC or local depending on the format. */
function parseDateTime(str, isUtc, offsetMinutes) {
    const s = str.trim().replace(' ', 'T');
    const hasZone = /(Z|[+-]\d{2}:?\d{2})$/.test(s);
    const ms = Date.parse(hasZone ? s : `${s}Z`);
    if (!Number.isFinite(ms)) return null;

    return hasZone || isUtc ? ms : ms - offsetMinutes * 60_000;
}
