import * as THREE from 'three';
import { Line2 } from 'three/examples/jsm/lines/Line2.js';
import { LineGeometry } from 'three/examples/jsm/lines/LineGeometry.js';
import { LineMaterial } from 'three/examples/jsm/lines/LineMaterial.js';

const G = 9.81;
const DEG = Math.PI / 180;

/**
 * One aircraft in the presentation: its telemetry, 3D model and flight path.
 *
 * Times: the presentation has one global timeline (seconds). This flight's
 * telemetry starts at `config.startOffset` on that timeline.
 */
export class FlightTrack {
    /**
     * @param {object} config      one entry of presentation config.flights
     * @param {object} telemetry   ids-telemetry JSON (see fdr-parser/README.md)
     * @param {import('./geo.js').LocalProjection} projection
     * @param {THREE.Object3D} template  normalised aircraft model
     */
    constructor(config, telemetry, projection, template) {
        this.config = config;
        this.color = new THREE.Color(config.color);
        this.startOffset = config.startOffset ?? 0;

        const ch = telemetry.channels;
        this.n = ch.t.length;
        this.t = Float64Array.from(ch.t);
        this.alt = Float32Array.from(ch.alt);
        this.speed = Float32Array.from(ch.speed);
        this.vs = Float32Array.from(ch.vs);
        this.battery = ch.battery ?? null;
        this.duration = this.t[this.n - 1];
        this.endTime = this.startOffset + this.duration;

        this.positions = new Float32Array(this.n * 3);
        for (let i = 0; i < this.n; i++) {
            const p = projection.toScene(ch.lat[i], ch.lng[i], Math.max(0, ch.alt[i]));
            this.positions.set([p.x, p.y, p.z], i * 3);
        }

        // Drone attitude (nose-down to fly forward) looks wrong on a jet model,
        // so for drone logs the attitude is derived from the flight path instead.
        const fromPath = telemetry.source?.kind === 'drone';
        const attitude = fromPath ? this.attitudeFromPath(ch) : { heading: ch.yaw, pitch: ch.pitch, roll: ch.roll };
        this.heading = Float32Array.from(attitude.heading);
        this.pitch = Float32Array.from(attitude.pitch);
        this.roll = Float32Array.from(attitude.roll);

        this.object = new THREE.Group();
        this.object.name = `flight-${config.id}`;
        this.buildPath();
        this.buildAircraft(template);
        this.buildDropLine();

        this.state = null;
    }

    // ---- building ---------------------------------------------------------

    buildPath() {
        const geometry = new LineGeometry();
        geometry.setPositions(this.positions);

        this.pathMaterial = new LineMaterial({ color: this.color, linewidth: 5, transparent: true, opacity: 0.3, depthWrite: false });
        this.fullPath = new Line2(geometry, this.pathMaterial);
        this.fullPath.computeLineDistances();

        const trailGeometry = new LineGeometry();
        trailGeometry.setPositions(this.positions);
        this.trailMaterial = new LineMaterial({ color: this.color, linewidth: 5 });
        this.trail = new Line2(trailGeometry, this.trailMaterial);

        // Bounds checks use the full path; skip per-frame culling of the trail.
        this.fullPath.frustumCulled = false;
        this.trail.frustumCulled = false;

        this.object.add(this.fullPath, this.trail);
    }

    buildAircraft(template) {
        this.pivot = new THREE.Group();
        this.model = template.clone();
        this.pivot.add(this.model);
        this.object.add(this.pivot);

        // The model is centred on its position, so on the ground half of it would sit below
        // the map. Keep at least this (unscaled) height under the centre; see update().
        this.modelHalfHeight = new THREE.Box3().setFromObject(template).getSize(new THREE.Vector3()).y / 2;

        // Clones share the template's materials; remember them so colouring can be undone.
        this.meshes = [];
        this.model.traverse((child) => {
            if (child.isMesh) this.meshes.push({ mesh: child, original: child.material, colored: null });
        });
    }

    /** Vertical line from the aircraft to the ground: helps judge height. */
    buildDropLine() {
        const geometry = new THREE.BufferGeometry().setAttribute('position', new THREE.Float32BufferAttribute(new Float32Array(6), 3));
        this.dropLine = new THREE.Line(geometry, new THREE.LineBasicMaterial({ color: this.color, transparent: true, opacity: 0.6 }));
        this.dropLine.frustumCulled = false;
        this.object.add(this.dropLine);
    }


    /** Heading from the ground track, pitch from climb angle, bank from turn rate. */
    attitudeFromPath(ch) {
        const n = this.n;
        const heading = new Float32Array(n);
        const pitch = new Float32Array(n);
        const roll = new Float32Array(n);
        const w = Math.max(1, Math.round(1 / Math.max(this.duration / n, 1e-3))); // ~1 s window

        let unwrapped = 0;
        let prevRaw = null;
        const unwrappedHeading = new Float32Array(n);

        for (let i = 0; i < n; i++) {
            const a = Math.max(0, i - w);
            const b = Math.min(n - 1, i + w);
            const dx = this.positions[b * 3] - this.positions[a * 3];
            const dz = this.positions[b * 3 + 2] - this.positions[a * 3 + 2];
            const moving = this.speed[i] > 1.5 && Math.hypot(dx, dz) > 0.5;
            const raw = moving ? Math.atan2(dx, -dz) / DEG : ch.yaw[i];

            if (prevRaw === null) unwrapped = raw;
            else unwrapped += ((((raw - prevRaw + 540) % 360) + 360) % 360) - 180;
            prevRaw = raw;
            unwrappedHeading[i] = unwrapped;
            heading[i] = raw;

            pitch[i] = this.speed[i] > 1.5 ? THREE.MathUtils.clamp(Math.atan2(this.vs[i], this.speed[i]) / DEG, -45, 45) : 0;
        }

        for (let i = 0; i < n; i++) {
            const a = Math.max(0, i - w);
            const b = Math.min(n - 1, i + w);
            const dt = this.t[b] - this.t[a];
            const turnRate = dt > 0 ? ((unwrappedHeading[b] - unwrappedHeading[a]) / dt) * DEG : 0;
            roll[i] = THREE.MathUtils.clamp(Math.atan((this.speed[i] * turnRate) / G) / DEG, -60, 60);
        }

        return { heading, pitch: smooth(pitch, w), roll: smooth(roll, w) };
    }

    // ---- per frame --------------------------------------------------------

    /**
     * Moves the aircraft to the given global time.
     * @returns {{localTime:number, alt:number, speed:number, vs:number, heading:number, battery:number|null, active:boolean}}
     */
    update(globalTime) {
        const local = globalTime - this.startOffset;
        const active = local >= 0 && local <= this.duration;
        const t = THREE.MathUtils.clamp(local, 0, this.duration);

        const i = this.indexAt(t);
        const j = Math.min(i + 1, this.n - 1);
        const span = this.t[j] - this.t[i];
        const f = span > 0 ? (t - this.t[i]) / span : 0;

        const x = lerp(this.positions[i * 3], this.positions[j * 3], f);
        // Never draw the model lower than half its (scaled) height, so it rests on top of the
        // map instead of sinking into it while taxiing. In the air the real altitude is higher anyway.
        const groundClearance = this.modelHalfHeight * this.model.scale.y;
        const y = Math.max(lerp(this.positions[i * 3 + 1], this.positions[j * 3 + 1], f), groundClearance);
        const z = lerp(this.positions[i * 3 + 2], this.positions[j * 3 + 2], f);
        const heading = lerpAngle(this.heading[i], this.heading[j], f);
        const pitch = lerp(this.pitch[i], this.pitch[j], f);
        const roll = lerp(this.roll[i], this.roll[j], f);

        this.pivot.position.set(x, y, z);
        // Euler YXZ = yaw, then pitch, then roll. Heading is clockwise from north.
        this.pivot.rotation.set(pitch * DEG, -heading * DEG, -roll * DEG, 'YXZ');

        this.trail.geometry.instanceCount = Math.max(0, i + (f > 0 ? 1 : 0));

        const drop = this.dropLine.geometry.attributes.position;
        drop.setXYZ(0, x, y, z);
        drop.setXYZ(1, x, 0, z);
        drop.needsUpdate = true;

        this.state = {
            localTime: local,
            alt: lerp(this.alt[i], this.alt[j], f),
            speed: lerp(this.speed[i], this.speed[j], f),
            vs: lerp(this.vs[i], this.vs[j], f),
            heading: ((heading % 360) + 360) % 360,
            battery: this.battery ? this.battery[i] : null,
            active,
        };

        return this.state;
    }

    /** Largest i with t[i] <= time (binary search). */
    indexAt(time) {
        let lo = 0;
        let hi = this.n - 1;
        while (lo < hi) {
            const mid = (lo + hi + 1) >> 1;
            if (this.t[mid] <= time) lo = mid;
            else hi = mid - 1;
        }
        return lo;
    }

    // ---- options ------------------------------------------------------------

    setModelScale(scale) {
        this.model.scale.setScalar(scale);
    }

    setPathVisible(visible) {
        this.fullPath.visible = visible;
        this.trail.visible = visible;
    }

    setModelVisible(visible) {
        this.model.visible = visible;
    }

    /** Paints the whole model in this flight's colour (true) or restores its own colours (false). */
    setModelColored(colored) {
        for (const part of this.meshes) {
            if (colored && !part.colored) {
                part.colored = part.original.clone();
                part.colored.map = null;
                part.colored.color?.copy(this.color);
            }
            part.mesh.material = colored ? part.colored : part.original;
        }
    }

    setLineWidth(px) {
        this.pathMaterial.linewidth = px;
        this.trailMaterial.linewidth = px;
    }

    setResolution(width, height) {
        this.pathMaterial.resolution.set(width, height);
        this.trailMaterial.resolution.set(width, height);
    }

    /** World-space box of the whole path (for "Fit All"). */
    getBounds() {
        const box = new THREE.Box3();
        box.setFromArray(this.positions);
        return box;
    }

    get position() {
        return this.pivot.position;
    }
}

function lerp(a, b, f) {
    return a + (b - a) * f;
}

/** Interpolates degrees the short way round (350° -> 10° goes through 0°). */
function lerpAngle(a, b, f) {
    const d = ((((b - a) % 360) + 540) % 360) - 180;
    return a + d * f;
}

/** Centred moving average. */
function smooth(values, w) {
    const out = new Float32Array(values.length);
    for (let i = 0; i < values.length; i++) {
        const a = Math.max(0, i - w);
        const b = Math.min(values.length - 1, i + w);
        let sum = 0;
        for (let k = a; k <= b; k++) sum += values[k];
        out[i] = sum / (b - a + 1);
    }
    return out;
}
