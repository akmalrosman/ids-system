#!/usr/bin/env node
/**
 * Generates realistic *dummy* DJI flight records so the system can be built
 * and demonstrated before a real drone is available.
 *
 *   node fdr-parser/tools/generate-dummy-dji.js [--out database/sample-data/dji] [--rate 5]
 *
 * Output layout = a decoded DJI flight record in CSV form (same column names as
 * the DJI FlightRecord / PhantomHelp CSV export: "OSD.latitude", "OSD.height [ft]", ...).
 * Real DJIFlightRecord_*.txt files are binary + encrypted; see fdr-parser/README.md.
 *
 * Every flight is simulated with a simple drone flight model:
 * motors on -> auto take-off -> climb -> waypoint mission -> return home -> auto landing.
 * The random generator is seeded, so running this again produces identical files.
 */
import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { parseArgs } from 'node:util';

const here = dirname(fileURLToPath(import.meta.url));
const { values } = parseArgs({
    options: {
        out: { type: 'string', default: resolve(here, '../../database/sample-data/dji') },
        rate: { type: 'string', default: '5' },
    },
});

const OUT_DIR = resolve(values.out);
const RATE_HZ = Number(values.rate);
const SIM_DT = 0.05; // physics step (s)
const G = 9.81;
const M_TO_FT = 3.28084;
const MS_TO_MPH = 2.23694;
const LOCAL_UTC_OFFSET_H = 8; // Malaysia

// ---------------------------------------------------------------------------
// Scenarios
// ---------------------------------------------------------------------------

/** Langkawi: 6 drones flying a V-formation loop around the airport (group "LIMA 2025"). */
const LANGKAWI_HOME = { lat: 6.3297, lng: 99.7287, elevation: 6 };
const LANGKAWI_ROUTE = [
    [600, 1200],
    [1500, 2500],
    [3000, 2800],
    [3800, 1800],
    [3200, 300],
    [1800, -500],
    [400, -400],
];
const V_FORMATION = [
    { aircraft: '5201', right: 0, back: 0 },
    { aircraft: '5205', right: -45, back: 35 },
    { aircraft: '5207', right: 45, back: 35 },
    { aircraft: '5210', right: -90, back: 70 },
    { aircraft: '5211', right: 90, back: 70 },
    { aircraft: '5216', right: 0, back: 110 },
];

/** Gong Kedak: lawnmower survey pattern. */
function lawnmower(width, lanes, spacing, origin = [200, 200]) {
    const pts = [];
    for (let i = 0; i < lanes; i++) {
        const n = origin[1] + i * spacing;
        const [a, b] = i % 2 === 0 ? [origin[0], origin[0] + width] : [origin[0] + width, origin[0]];
        pts.push([a, n], [b, n]);
    }
    return pts;
}

/** Subang: orbits around a point, then a figure of eight. */
function orbitAndEight(center, radius, laps) {
    const pts = [];
    for (let i = 0; i <= laps * 24; i++) {
        const a = (i / 24) * Math.PI * 2;
        pts.push([center[0] + radius * Math.sin(a), center[1] + radius * Math.cos(a)]);
    }
    for (let i = 0; i <= 48; i++) {
        const a = (i / 48) * Math.PI * 2;
        pts.push([center[0] + radius * 1.6 * Math.sin(a), center[1] + radius * 0.8 * Math.sin(2 * a)]);
    }
    return pts;
}

const scenarios = [
    ...V_FORMATION.map((slot, i) => ({
        aircraft: slot.aircraft,
        start: { date: '2025-05-18', time: '16:00:00' },
        startDelay: i * 4, // take-off one after another, 4 s apart
        home: { ...LANGKAWI_HOME, lat: LANGKAWI_HOME.lat - 0.00012 * i, lng: LANGKAWI_HOME.lng + 0.00008 * i },
        route: offsetRoute(LANGKAWI_ROUTE, slot.right, slot.back),
        cruiseAlt: 120 + (i % 2 === 0 ? 0 : 6),
        cruiseSpeed: 13,
        seed: 100 + i,
    })),
    {
        aircraft: '5210',
        start: { date: '2025-11-25', time: '08:00:00' },
        home: { lat: 5.7973, lng: 102.4894, elevation: 4 },
        route: lawnmower(1400, 7, 110),
        cruiseAlt: 100,
        cruiseSpeed: 11,
        seed: 300,
    },
    {
        aircraft: '5203',
        start: { date: '2025-09-23', time: '10:00:00' },
        home: { lat: 3.1306, lng: 101.549, elevation: 27 },
        route: orbitAndEight([0, 700], 450, 2),
        cruiseAlt: 115,
        cruiseSpeed: 12,
        seed: 400,
    },
];

// ---------------------------------------------------------------------------
// Flight simulation
// ---------------------------------------------------------------------------

/** Seeded PRNG (mulberry32) so output is reproducible. */
function rng(seed) {
    let a = seed >>> 0;
    return () => {
        a = (a + 0x6d2b79f5) >>> 0;
        let t = a;
        t = Math.imul(t ^ (t >>> 15), t | 1);
        t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

/** Shifts a route sideways / backwards so several drones can fly it in formation. */
function offsetRoute(route, right, back) {
    return route.map(([e, n], i) => {
        const prev = route[Math.max(0, i - 1)];
        const next = route[Math.min(route.length - 1, i + 1)];
        let de = next[0] - prev[0];
        let dn = next[1] - prev[1];
        const len = Math.hypot(de, dn) || 1;
        de /= len;
        dn /= len;
        // right-hand perpendicular of (de, dn) is (dn, -de)
        return [e + right * dn - back * de, n - right * de - back * dn];
    });
}

const wrap180 = (deg) => ((((deg + 180) % 360) + 360) % 360) - 180;
const clamp = (v, lo, hi) => Math.min(hi, Math.max(lo, v));
const approach = (v, target, maxStep) => v + clamp(target - v, -maxStep, maxStep);

function simulate(sc) {
    const rand = rng(sc.seed);
    const noise = (amp) => (rand() * 2 - 1) * amp;

    const s = {
        t: 0,
        e: 0,
        n: 0,
        alt: 0,
        speed: 0,
        vz: 0,
        heading: 20 + noise(15),
        turnRate: 0,
        accel: 0,
        battery: 99 - Math.floor(rand() * 4),
        phase: 'ground',
        phaseT: 0,
        wp: 0,
        mileage: 0,
        heightMax: 0,
        hSpeedMax: 0,
        gpsNum: 17 + Math.floor(rand() * 4),
        gpsErrE: 0,
        gpsErrN: 0,
        temperatureF: 86 + noise(2),
    };

    const startDelay = sc.startDelay ?? 0;
    const frames = [];
    let nextSampleAt = 0;
    let tip = '';

    const setPhase = (phase, message) => {
        s.phase = phase;
        s.phaseT = 0;
        tip = message;
    };

    while (s.phase !== 'done') {
        // --- phase logic --------------------------------------------------
        let targetSpeed = 0;
        let targetAlt = s.alt;
        let target = null;

        switch (s.phase) {
            case 'ground':
                if (s.phaseT > 2 + startDelay) setPhase('motors', 'Motors started.');
                break;
            case 'motors':
                if (s.phaseT > 3) setPhase('takeoff', 'Taking off.');
                break;
            case 'takeoff':
                targetAlt = 1.2;
                if (s.alt >= 1.15) setPhase('climb', '');
                break;
            case 'climb':
                targetAlt = sc.cruiseAlt;
                if (s.alt > 25) {
                    target = sc.route[0];
                    targetSpeed = sc.cruiseSpeed * 0.6;
                }
                if (s.alt >= sc.cruiseAlt - 2) setPhase('mission', 'Waypoint mission started.');
                break;
            case 'mission': {
                targetAlt = sc.cruiseAlt;
                target = sc.route[s.wp];
                targetSpeed = sc.cruiseSpeed;
                const dist = Math.hypot(target[0] - s.e, target[1] - s.n);
                if (dist < Math.max(12, s.speed * 1.4)) {
                    s.wp++;
                    if (s.wp >= sc.route.length) setPhase('gohome', 'Returning to home point.');
                }
                break;
            }
            case 'gohome': {
                targetAlt = sc.cruiseAlt;
                target = [0, 0];
                const dist = Math.hypot(s.e, s.n);
                targetSpeed = Math.min(sc.cruiseSpeed, Math.sqrt(2 * 2.5 * dist));
                if (dist < 1.5 && s.speed < 0.6) setPhase('landing', 'Landing.');
                break;
            }
            case 'landing':
                targetAlt = 0;
                target = [0, 0];
                targetSpeed = Math.min(1, Math.hypot(s.e, s.n));
                if (s.alt <= 0.01) setPhase('landed', 'Motors stopped.');
                break;
            case 'landed':
                if (s.phaseT > 3) s.phase = 'done';
                break;
        }

        // --- horizontal dynamics -------------------------------------------
        const prevSpeed = s.speed;
        if (target) {
            const desired = (Math.atan2(target[0] - s.e, target[1] - s.n) * 180) / Math.PI;
            const diff = wrap180(desired - s.heading);
            const maxRate = 40; // deg/s
            const rate = clamp(diff * 1.2, -maxRate, maxRate);
            s.turnRate = approach(s.turnRate, rate, 90 * SIM_DT);
            s.heading = wrap180(s.heading + s.turnRate * SIM_DT);
            // slow down for sharp turns
            targetSpeed *= Math.max(0.35, Math.cos((Math.min(Math.abs(diff), 90) * Math.PI) / 180));
        } else {
            s.turnRate = approach(s.turnRate, 0, 90 * SIM_DT);
        }
        s.speed = Math.max(0, approach(s.speed, targetSpeed, 3 * SIM_DT));
        s.accel = (s.speed - prevSpeed) / SIM_DT;

        const h = (s.heading * Math.PI) / 180;
        const de = Math.sin(h) * s.speed * SIM_DT;
        const dn = Math.cos(h) * s.speed * SIM_DT;
        s.e += de;
        s.n += dn;
        s.mileage += Math.hypot(de, dn);

        // --- vertical dynamics ---------------------------------------------
        const airborne = ['takeoff', 'climb', 'mission', 'gohome', 'landing'].includes(s.phase);
        let targetVz = 0;
        if (airborne) {
            const maxDown = s.phase === 'landing' ? (s.alt > 6 ? 3 : 0.8) : 3;
            targetVz = clamp((targetAlt - s.alt) * 0.8, -maxDown, s.phase === 'takeoff' ? 1.5 : 5);
        }
        s.vz = approach(s.vz, targetVz, 2.5 * SIM_DT);
        s.alt = Math.max(0, s.alt + s.vz * SIM_DT);
        if (s.alt === 0 && s.vz < 0) s.vz = 0;

        // --- systems ---------------------------------------------------------
        const motorsOn = !['ground', 'landed'].includes(s.phase) || (s.phase === 'landed' && s.phaseT < 1);
        if (motorsOn) {
            const load = airborne ? 0.055 + 0.00012 * s.speed ** 2 + Math.max(0, s.vz) * 0.006 : 0.004;
            s.battery = Math.max(5, s.battery - load * SIM_DT);
            s.temperatureF = Math.min(115, s.temperatureF + 0.006 * SIM_DT);
        }
        s.gpsErrE = clamp(s.gpsErrE + noise(0.05), -0.6, 0.6);
        s.gpsErrN = clamp(s.gpsErrN + noise(0.05), -0.6, 0.6);
        if (rand() < 0.002) s.gpsNum = clamp(s.gpsNum + (rand() < 0.5 ? -1 : 1), 14, 23);
        s.heightMax = Math.max(s.heightMax, s.alt);
        s.hSpeedMax = Math.max(s.hSpeedMax, s.speed);

        // --- record -----------------------------------------------------------
        if (s.t >= nextSampleAt - 1e-9) {
            frames.push(snapshot(s, sc, tip, motorsOn, airborne, noise));
            tip = '';
            nextSampleAt += 1 / RATE_HZ;
        }

        s.t += SIM_DT;
        s.phaseT += SIM_DT;
    }

    return frames;
}

/** Converts simulator state into one CSV row object. */
function snapshot(s, sc, tip, motorsOn, airborne, noise) {
    const mPerDegLat = 111320;
    const mPerDegLng = 111320 * Math.cos((sc.home.lat * Math.PI) / 180);
    const lat = sc.home.lat + (s.n + s.gpsErrN) / mPerDegLat;
    const lng = sc.home.lng + (s.e + s.gpsErrE) / mPerDegLng;

    // Multirotors tilt forward to accelerate and bank into turns.
    const pitch = airborne ? -(s.speed / 21) * 22 - s.accel * 1.5 + noise(0.4) : 0;
    const bank = airborne ? (Math.atan((s.speed * (s.turnRate * Math.PI) / 180) / G) * 180) / Math.PI : 0;
    const roll = clamp(bank + (airborne ? noise(0.4) : 0), -35, 35);

    const h = (s.heading * Math.PI) / 180;
    const vNorth = Math.cos(h) * s.speed;
    const vEast = Math.sin(h) * s.speed;

    const mode = {
        ground: 'P-GPS',
        motors: 'Motors_Started',
        takeoff: 'AutoTakeoff',
        climb: 'P-GPS',
        mission: 'Waypoint',
        gohome: 'GoHome',
        landing: 'AutoLanding',
        landed: 'P-GPS',
    }[s.phase];

    const wall = localClock(sc.start, s.t);
    const voltage = 4 * (3.3 + 0.9 * (s.battery / 100)) - (motorsOn ? 0.4 : 0);
    const current = motorsOn ? (airborne ? 9 + 0.35 * s.speed + Math.max(0, s.vz) * 1.8 : 1.2) : 0.3;
    const sticks = s.phase === 'climb' ? [1024, 1024, 1684, 1024] : [1024, 1024, 1024, 1024];

    return {
        'CUSTOM.date [local]': wall.date,
        'CUSTOM.updateTime [local]': wall.time,
        'OSD.flyTime': `${Math.floor(s.t / 60)}m ${(s.t % 60).toFixed(1)}s`,
        'OSD.flyTime [s]': s.t.toFixed(1),
        'OSD.latitude': lat.toFixed(8),
        'OSD.longitude': lng.toFixed(8),
        'OSD.height [ft]': (s.alt * M_TO_FT).toFixed(1),
        'OSD.heightMax [ft]': (s.heightMax * M_TO_FT).toFixed(1),
        'OSD.vpsHeight [ft]': s.alt < 10 ? (s.alt * M_TO_FT).toFixed(1) : '0.0',
        'OSD.altitude [ft]': ((s.alt + sc.home.elevation) * M_TO_FT).toFixed(1),
        'OSD.mileage [ft]': (s.mileage * M_TO_FT).toFixed(0),
        'OSD.hSpeed [MPH]': (s.speed * MS_TO_MPH).toFixed(1),
        'OSD.hSpeedMax [MPH]': (s.hSpeedMax * MS_TO_MPH).toFixed(1),
        'OSD.xSpeed [MPH]': (vNorth * MS_TO_MPH).toFixed(1),
        'OSD.ySpeed [MPH]': (vEast * MS_TO_MPH).toFixed(1),
        'OSD.zSpeed [MPH]': (-s.vz * MS_TO_MPH).toFixed(1), // NED: positive = descending
        'OSD.pitch': pitch.toFixed(1),
        'OSD.roll': roll.toFixed(1),
        'OSD.yaw': s.heading.toFixed(1),
        'OSD.yaw [360]': (((s.heading % 360) + 360) % 360).toFixed(1),
        'OSD.directionOfTravel [true]': s.speed > 0.5 ? (((s.heading % 360) + 360) % 360).toFixed(1) : '',
        'OSD.flycState': mode,
        'OSD.isGPSUsed': 'True',
        'OSD.gpsNum': s.gpsNum,
        'OSD.gpsLevel': s.gpsNum >= 16 ? 5 : 4,
        'OSD.droneType': 'Mavic3E',
        'OSD.isOnGround': airborne ? 'False' : 'True',
        'OSD.isMotorOn': motorsOn ? 'True' : 'False',
        'OSD.goHomeStatus': s.phase === 'gohome' ? 'Cruise' : s.phase === 'landing' ? 'Landing' : 'Standby',
        'GIMBAL.pitch': s.phase === 'mission' ? '-30.0' : '0.0',
        'GIMBAL.roll': '0.0',
        'GIMBAL.yaw [360]': (((s.heading % 360) + 360) % 360).toFixed(1),
        'RC.aileron': sticks[0],
        'RC.elevator': sticks[1],
        'RC.throttle': sticks[2],
        'RC.rudder': sticks[3],
        'BATTERY.chargeLevel': Math.round(s.battery),
        'BATTERY.voltage [V]': voltage.toFixed(2),
        'BATTERY.current [A]': current.toFixed(1),
        'BATTERY.temperature [F]': s.temperatureF.toFixed(1),
        'HOME.latitude': sc.home.lat.toFixed(8),
        'HOME.longitude': sc.home.lng.toFixed(8),
        'HOME.height [ft]': (sc.home.elevation * M_TO_FT).toFixed(1),
        'RECOVER.aircraftName': `IDS-${sc.aircraft}`,
        'RECOVER.aircraftSn': `1581F5BKD22${sc.aircraft}${String(sc.seed).padStart(3, '0')}`,
        'RECOVER.droneType': 'Mavic3E',
        'APP.tip': tip,
        'APP.warning': s.battery < 30 && airborne ? 'Low battery. Return to home recommended.' : '',
    };
}

/** Local wall clock as written by the DJI app: "5/18/2025" and "4:00:00.20 PM". */
function localClock(start, elapsedS) {
    const base = Date.parse(`${start.date}T${start.time}Z`) + Math.round(elapsedS * 1000);
    const d = new Date(base); // treated as local time, no timezone math needed
    const h24 = d.getUTCHours();
    const h12 = h24 % 12 === 0 ? 12 : h24 % 12;
    const cs = String(Math.floor(d.getUTCMilliseconds() / 10)).padStart(2, '0');
    return {
        date: `${d.getUTCMonth() + 1}/${d.getUTCDate()}/${d.getUTCFullYear()}`,
        time: `${h12}:${String(d.getUTCMinutes()).padStart(2, '0')}:${String(d.getUTCSeconds()).padStart(2, '0')}.${cs} ${h24 < 12 ? 'AM' : 'PM'}`,
    };
}

function toCsv(frames) {
    const headers = Object.keys(frames[0]);
    const esc = (v) => {
        const str = String(v ?? '');
        return /[",\n]/.test(str) ? `"${str.replace(/"/g, '""')}"` : str;
    };
    return [headers.join(','), ...frames.map((f) => headers.map((h) => esc(f[h])).join(','))].join('\r\n') + '\r\n';
}

// ---------------------------------------------------------------------------

mkdirSync(OUT_DIR, { recursive: true });

for (const sc of scenarios) {
    const frames = simulate(sc);
    const takeoff = localClock(sc.start, 0);
    const stamp = sc.start.time.replace(/:/g, '-');
    const file = `DJIFlightRecord_${sc.start.date}_[${stamp}]_${sc.aircraft}.txt`;
    writeFileSync(join(OUT_DIR, file), toCsv(frames));

    const duration = frames.length / RATE_HZ;
    console.log(
        `${file}  ${frames.length} rows  ${Math.floor(duration / 60)}m${Math.round(duration % 60)}s  ` +
            `start ${takeoff.date} ${takeoff.time} (UTC+${LOCAL_UTC_OFFSET_H})`,
    );
}
