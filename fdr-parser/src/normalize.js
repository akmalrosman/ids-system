import { haversine, isValidCoordinate, wrap360 } from './lib/geo.js';
import { toIsoWithOffset } from './lib/time.js';
import { FdrError } from './errors.js';

export const OUTPUT_FORMAT = 'ids-telemetry';
export const OUTPUT_VERSION = 1;

/**
 * Turns raw samples from any adapter into the IDS telemetry JSON.
 * The output schema is documented in fdr-parser/README.md.
 *
 * @param {object[]} rawSamples
 * @param {object} meta     file-level metadata from the adapter
 * @param {{id: string, kind: string}} adapter
 * @param {{ fileName?: string, offsetMinutes: number, maxRate: number }} options
 */
export function normalize(rawSamples, meta, adapter, options) {
    let samples = rawSamples.filter((s) => isValidCoordinate(s.lat, s.lng));

    if (samples.length < 2) {
        throw new FdrError('NO_VALID_SAMPLES', 'The file has fewer than 2 samples with a valid GPS position.');
    }

    samples = fillTime(samples);
    samples = sortAndDedupe(samples);
    samples = resample(samples, options.maxRate);

    if (samples.length < 2) {
        throw new FdrError('NO_VALID_SAMPLES', 'Not enough distinct samples after cleaning the data.');
    }

    // Time relative to the first valid sample.
    const t0 = samples[0].t;
    for (const s of samples) s.t -= t0;

    deriveMissing(samples);

    const startTimeMs = findStartTime(samples);

    return {
        format: OUTPUT_FORMAT,
        version: OUTPUT_VERSION,
        source: {
            adapter: adapter.id,
            kind: adapter.kind,
            fileName: options.fileName ?? null,
            aircraftName: meta.aircraftName ?? null,
            aircraftSerial: meta.aircraftSn ?? null,
            droneType: meta.droneType ?? null,
        },
        startTime: startTimeMs === null ? null : toIsoWithOffset(startTimeMs, options.offsetMinutes),
        duration: round(samples[samples.length - 1].t, 3),
        // Effective rate after resampling (the source may be slower than maxRate).
        sampleRate: round((samples.length - 1) / Math.max(samples[samples.length - 1].t, 1e-9), 2),
        stats: computeStats(samples, meta),
        channels: toChannels(samples),
        events: modeEvents(samples),
    };
}

/** Uses wall-clock time when the elapsed-time column is missing. */
function fillTime(samples) {
    const haveT = samples.some((s) => Number.isFinite(s.t));
    const firstTime = samples.find((s) => Number.isFinite(s.time))?.time;

    if (haveT) return samples.filter((s) => Number.isFinite(s.t));

    if (firstTime !== undefined) {
        return samples.filter((s) => Number.isFinite(s.time)).map((s) => ({ ...s, t: (s.time - firstTime) / 1000 }));
    }

    throw new FdrError('NO_TIME', 'The file has no time column, so the samples cannot be placed on a timeline.');
}

function sortAndDedupe(samples) {
    samples.sort((a, b) => a.t - b.t);
    return samples.filter((s, i) => i === 0 || s.t > samples[i - 1].t);
}

/** Keeps at most `maxRate` samples per second (simple decimation). */
function resample(samples, maxRate) {
    if (!maxRate || maxRate <= 0) return samples;

    const step = 1 / maxRate - 1e-6;
    const out = [samples[0]];
    for (const s of samples) {
        if (s.t - out[out.length - 1].t >= step) out.push(s);
    }
    const last = samples[samples.length - 1];
    if (out[out.length - 1] !== last) out.push(last);

    return out;
}

/** Fills altitude, speed, vertical speed, heading and attitude when the source lacks them. */
function deriveMissing(samples) {
    const altMsl0 = samples.find((s) => Number.isFinite(s.altMsl))?.altMsl;

    for (let i = 0; i < samples.length; i++) {
        const s = samples[i];
        const prev = samples[Math.max(0, i - 1)];
        const next = samples[Math.min(samples.length - 1, i + 1)];
        const dt = next.t - prev.t;

        if (!Number.isFinite(s.alt)) {
            s.alt = Number.isFinite(s.altMsl) && altMsl0 !== undefined ? s.altMsl - altMsl0 : 0;
        }
        if (!Number.isFinite(s.speed)) {
            s.speed = dt > 0 ? haversine(prev.lat, prev.lng, next.lat, next.lng) / dt : 0;
        }
        if (!Number.isFinite(s.yaw)) {
            s.yaw = bearing(prev.lat, prev.lng, next.lat, next.lng);
        }
        s.yaw = wrap360(s.yaw);
        if (!Number.isFinite(s.pitch)) s.pitch = 0;
        if (!Number.isFinite(s.roll)) s.roll = 0;
    }

    // Vertical speed needs the final altitudes, so it is a second pass.
    for (let i = 0; i < samples.length; i++) {
        const s = samples[i];
        if (Number.isFinite(s.vs)) continue;
        const prev = samples[Math.max(0, i - 1)];
        const next = samples[Math.min(samples.length - 1, i + 1)];
        const dt = next.t - prev.t;
        s.vs = dt > 0 ? (next.alt - prev.alt) / dt : 0;
    }
}

function bearing(lat1, lng1, lat2, lng2) {
    const r = Math.PI / 180;
    const y = Math.sin((lng2 - lng1) * r) * Math.cos(lat2 * r);
    const x = Math.cos(lat1 * r) * Math.sin(lat2 * r) - Math.sin(lat1 * r) * Math.cos(lat2 * r) * Math.cos((lng2 - lng1) * r);
    return Math.atan2(y, x) / r;
}

/** Wall-clock time of t = 0, taken from the first sample that has a timestamp. */
function findStartTime(samples) {
    const s = samples.find((x) => Number.isFinite(x.time));
    return s ? Math.round(s.time - s.t * 1000) : null;
}

function computeStats(samples, meta) {
    let distance = 0;
    let maxAltitude = -Infinity;
    let maxSpeed = 0;
    let maxClimb = 0;
    let maxDescent = 0;
    let minBattery = null;
    const bounds = { minLat: Infinity, maxLat: -Infinity, minLng: Infinity, maxLng: -Infinity };
    const home = {
        lat: Number.isFinite(meta.homeLat) && meta.homeLat !== 0 ? meta.homeLat : samples[0].lat,
        lng: Number.isFinite(meta.homeLng) && meta.homeLng !== 0 ? meta.homeLng : samples[0].lng,
    };
    let maxDistanceFromHome = 0;

    samples.forEach((s, i) => {
        if (i > 0) distance += haversine(samples[i - 1].lat, samples[i - 1].lng, s.lat, s.lng);
        maxAltitude = Math.max(maxAltitude, s.alt);
        maxSpeed = Math.max(maxSpeed, s.speed);
        maxClimb = Math.max(maxClimb, s.vs);
        maxDescent = Math.min(maxDescent, s.vs);
        if (Number.isFinite(s.battery)) minBattery = minBattery === null ? s.battery : Math.min(minBattery, s.battery);
        bounds.minLat = Math.min(bounds.minLat, s.lat);
        bounds.maxLat = Math.max(bounds.maxLat, s.lat);
        bounds.minLng = Math.min(bounds.minLng, s.lng);
        bounds.maxLng = Math.max(bounds.maxLng, s.lng);
        maxDistanceFromHome = Math.max(maxDistanceFromHome, haversine(home.lat, home.lng, s.lat, s.lng));
    });

    return {
        samples: samples.length,
        maxAltitude: round(maxAltitude, 2),
        maxSpeed: round(maxSpeed, 2),
        maxClimbRate: round(maxClimb, 2),
        maxDescentRate: round(-maxDescent, 2),
        distance: round(distance, 1),
        maxDistanceFromHome: round(maxDistanceFromHome, 1),
        minBattery,
        home: { lat: round(home.lat, 7), lng: round(home.lng, 7) },
        bounds: {
            minLat: round(bounds.minLat, 7),
            maxLat: round(bounds.maxLat, 7),
            minLng: round(bounds.minLng, 7),
            maxLng: round(bounds.maxLng, 7),
        },
    };
}

/** Column-oriented output: much smaller than an array of objects. */
function toChannels(samples) {
    const hasBattery = samples.some((s) => Number.isFinite(s.battery));

    const channels = {
        t: samples.map((s) => round(s.t, 3)),
        lat: samples.map((s) => round(s.lat, 7)),
        lng: samples.map((s) => round(s.lng, 7)),
        alt: samples.map((s) => round(s.alt, 2)),
        speed: samples.map((s) => round(s.speed, 2)),
        vs: samples.map((s) => round(s.vs, 2)),
        pitch: samples.map((s) => round(s.pitch, 1)),
        roll: samples.map((s) => round(s.roll, 1)),
        yaw: samples.map((s) => round(s.yaw, 1)),
    };

    if (hasBattery) channels.battery = samples.map((s) => (Number.isFinite(s.battery) ? round(s.battery, 0) : null));

    return channels;
}

/** One event per flight-mode change, e.g. AutoTakeoff -> P-GPS -> GoHome. */
function modeEvents(samples) {
    const events = [];
    let last = null;
    for (const s of samples) {
        if (s.mode && s.mode !== last) {
            events.push({ t: round(s.t, 3), type: 'mode', label: s.mode });
            last = s.mode;
        }
    }
    return events;
}

function round(value, digits) {
    const f = 10 ** digits;
    return Math.round(value * f) / f;
}
