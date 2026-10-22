import * as THREE from 'three';
import { LocalProjection } from './geo.js';
import { TileMap } from './TileMap.js';
import { CameraRig } from './CameraRig.js';
import { FlightTrack } from './FlightTrack.js';
import { Playback } from './Playback.js';
import { loadAircraftTemplate } from './AircraftModels.js';

/** With aircraft scale "Auto", models are drawn at least this many pixels long. */
const AUTO_SCALE_MIN_PX = 48;

/** How the presentation looks when it first opens (the UI controls start in the same state). */
export const INITIAL_VIEW = {
    modelScale: 10,
    pathsVisible: false,
};

/**
 * Owns the Three.js scene: renderer, map, aircraft, camera and the clock.
 * UI wiring lives in ui.js; this class has no knowledge of the DOM controls.
 */
export class PresentationApp {
    /**
     * @param {HTMLElement} container element the canvas is added to
     * @param {object} config            JSON built by PresentationController::buildConfig
     */
    constructor(container, config) {
        this.container = container;
        this.config = config;
        /** @type {FlightTrack[]} */
        this.tracks = [];
        this.failed = [];
        this.modelScale = INITIAL_VIEW.modelScale;
        this.frameListeners = new Set();
    }

    /**
     * Downloads telemetry + models and builds the scene.
     * @param {(done:number, total:number) => void} onProgress
     */
    async load(onProgress = () => {}) {
        const flights = this.config.flights;
        let done = 0;
        const step = () => onProgress(++done, flights.length * 2);

        const results = await Promise.all(
            flights.map(async (flight) => {
                try {
                    const res = await fetch(flight.telemetryUrl, { headers: { Accept: 'application/json' } });
                    if (!res.ok) throw new Error(`telemetry HTTP ${res.status}`);
                    const telemetry = await res.json();
                    step();
                    const template = await loadAircraftTemplate(flight.model);
                    step();
                    return { flight, telemetry, template };
                } catch (error) {
                    console.error(`[Presentation] Flight ${flight.id} failed to load`, error);
                    this.failed.push({ flight, error });
                    done += 2;
                    onProgress(done, flights.length * 2);
                    return null;
                }
            }),
        );

        const loaded = results.filter(Boolean);
        this.projection = this.createProjection(loaded);
        this.setupRenderer();
        this.setupScene();

        for (const { flight, telemetry, template } of loaded) {
            const track = new FlightTrack(flight, telemetry, this.projection, template);
            this.tracks.push(track);
            this.scene.add(track.object);
        }

        await this.map.init();

        const duration = Math.max(1, ...this.tracks.map((t) => t.endTime));
        this.playback = new Playback(duration);

        this.setModelScale(INITIAL_VIEW.modelScale);
        this.setAllPathsVisible(INITIAL_VIEW.pathsVisible);
        this.resize();
        this.fitAll({ remember: true });
        this.updateTracks();
        this.renderer.domElement.addEventListener('dblclick', (e) => this.onDoubleClick(e));
    }

    /** Scene origin = middle of all flights (or the location of the flight / group). */
    createProjection(loaded) {
        const points = loaded.flatMap(({ telemetry }) => [
            [telemetry.stats.bounds.minLat, telemetry.stats.bounds.minLng],
            [telemetry.stats.bounds.maxLat, telemetry.stats.bounds.maxLng],
        ]);
        if (!points.length) {
            const c = this.config.map.fallbackCenter;
            return new LocalProjection(c.lat, c.lng);
        }
        const lat = (Math.min(...points.map((p) => p[0])) + Math.max(...points.map((p) => p[0]))) / 2;
        const lng = (Math.min(...points.map((p) => p[1])) + Math.max(...points.map((p) => p[1]))) / 2;
        return new LocalProjection(lat, lng);
    }

    setupRenderer() {
        // Logarithmic depth: aircraft metres away and Malaysia 2000 km wide in one view.
        this.renderer = new THREE.WebGLRenderer({ antialias: true, logarithmicDepthBuffer: true });
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.outputColorSpace = THREE.SRGBColorSpace;
        this.renderer.domElement.className = 'absolute inset-0 block';
        this.container.appendChild(this.renderer.domElement);

        new ResizeObserver(() => this.resize()).observe(this.container);
    }

    setupScene() {
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x05080d);

        this.scene.add(new THREE.HemisphereLight(0xdfe9ff, 0x334455, 2.2));
        const sun = new THREE.DirectionalLight(0xffffff, 2.5);
        sun.position.set(0.5, 1, 0.3);
        this.scene.add(sun);

        const [w, s, e, n] = this.config.map.bounds;
        const nw = this.projection.toScene(n, w);
        const se = this.projection.toScene(s, e);
        this.rig = new CameraRig(this.renderer.domElement, { minX: nw.x, maxX: se.x, minZ: nw.z, maxZ: se.z });

        this.map = new TileMap({
            projection: this.projection,
            tileUrl: this.config.map.tileUrl,
            bounds: this.config.map.bounds,
            minZoom: this.config.map.minZoom,
            maxZoom: this.config.map.maxZoom,
            anisotropy: this.renderer.capabilities.getMaxAnisotropy(),
            sourceResolution: this.config.map.sourceResolution,
        });
        this.scene.add(this.map.group);
    }

    start() {
        const clock = new THREE.Clock();
        this.renderer.setAnimationLoop(() => {
            const dt = Math.min(clock.getDelta(), 0.1);
            this.playback.tick(dt);
            this.updateTracks();
            this.rig.update();
            this.applyAutoScale();
            this.map.update(this.rig.camera, this.rig.target);
            this.renderer.render(this.scene, this.rig.camera);
            this.frameListeners.forEach((fn) => fn());
        });
    }

    updateTracks() {
        for (const track of this.tracks) track.update(this.playback.time);
    }

    /** fn() is called after every rendered frame (used by the UI to refresh read-outs). */
    onFrame(fn) {
        this.frameListeners.add(fn);
    }

    resize() {
        if (!this.renderer) return;
        const { clientWidth: w, clientHeight: h } = this.container;
        if (!w || !h) return;
        this.renderer.setSize(w, h);
        this.rig.setAspect(w / h);
        this.tracks.forEach((t) => t.setResolution(w, h));
    }

    // ---- actions used by the UI ------------------------------------------------

    fitAll(options) {
        const box = new THREE.Box3();
        this.tracks.forEach((t) => box.union(t.getBounds()));
        if (box.isEmpty()) {
            const c = this.config.map.fallbackCenter;
            const p = this.projection.toScene(c.lat, c.lng);
            box.setFromCenterAndSize(new THREE.Vector3(p.x, 0, p.z), new THREE.Vector3(20000, 0, 20000));
        }
        this.rig.follow(null);
        this.rig.fitBox(box, options);
    }

    resetCamera() {
        this.rig.reset();
    }

    /** A number (1 = real size) or 'auto' (see applyAutoScale). */
    setModelScale(scale) {
        this.modelScale = scale;
        if (scale !== 'auto') this.tracks.forEach((t) => t.setModelScale(scale));
    }

    /**
     * 'auto' scale: each aircraft is drawn at least AUTO_SCALE_MIN_PX long on screen,
     * so it is visible when zoomed out and shrinks to its real size when you get close.
     */
    applyAutoScale() {
        if (this.modelScale !== 'auto') return;

        const camera = this.rig.camera;
        const viewHeight = this.container.clientHeight || 1;
        const pxToMetres = (2 * Math.tan(THREE.MathUtils.degToRad(camera.fov / 2))) / viewHeight;

        for (const track of this.tracks) {
            const distance = camera.position.distanceTo(track.position);
            const length = track.config.model.lengthM || 20;
            track.setModelScale(Math.max(1, (AUTO_SCALE_MIN_PX * distance * pxToMetres) / length));
        }
    }

    /** factor < 1 zooms in, > 1 zooms out. */
    zoomBy(factor) {
        this.rig.zoomBy(factor);
    }

    /**
     * Double-click an aircraft: zoom in on it and follow it.
     * Double-click anywhere else: centre the view there and zoom in.
     */
    onDoubleClick(event) {
        const rect = this.renderer.domElement.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        const camera = this.rig.camera;

        const hit = this.tracks
            .map((track) => {
                const p = track.position.clone().project(camera);
                const sx = ((p.x + 1) / 2) * rect.width;
                const sy = ((1 - p.y) / 2) * rect.height;
                return { track, pixels: Math.hypot(sx - x, sy - y), inFront: p.z < 1 };
            })
            .filter((h) => h.inFront && h.pixels < 40)
            .sort((a, b) => a.pixels - b.pixels)[0];

        if (hit) {
            this.rig.follow(hit.track);
            this.rig.focusOn(hit.track.position, Math.max(80, (hit.track.config.model.lengthM || 20) * 8));
            return;
        }

        const ndc = new THREE.Vector2((x / rect.width) * 2 - 1, -(y / rect.height) * 2 + 1);
        const raycaster = new THREE.Raycaster();
        raycaster.setFromCamera(ndc, camera);
        const ground = raycaster.ray.intersectPlane(new THREE.Plane(new THREE.Vector3(0, 1, 0), 0), new THREE.Vector3());
        if (ground) {
            this.rig.follow(null);
            this.rig.focusOn(ground, camera.position.distanceTo(this.rig.target) * 0.4);
        }
    }

    setLineWidth(px) {
        this.tracks.forEach((t) => t.setLineWidth(px));
    }

    setAllPathsVisible(visible) {
        this.tracks.forEach((t) => t.setPathVisible(visible));
    }
}
