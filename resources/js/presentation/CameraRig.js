import * as THREE from 'three';
import { MapControls } from 'three/examples/jsm/controls/MapControls.js';

/**
 * Camera + mouse controls.
 *   left drag = pan, right drag / ctrl+drag = rotate, wheel = zoom (towards cursor)
 *
 * The camera target is kept inside the map bounds (Malaysia) and the camera
 * can't go below the horizon. `follow(track)` keeps an aircraft centred.
 */
export class CameraRig {
    /**
     * @param {HTMLElement} domElement
     * @param {{minX:number, maxX:number, minZ:number, maxZ:number}} limits scene-space bounds for the target
     */
    constructor(domElement, limits) {
        this.camera = new THREE.PerspectiveCamera(45, 1, 1, 2e7);
        this.limits = limits;

        this.controls = new MapControls(this.camera, domElement);
        this.controls.enableDamping = true;
        this.controls.dampingFactor = 0.12;
        this.controls.screenSpacePanning = false;
        this.controls.zoomToCursor = true;
        this.controls.zoomSpeed = 2.5; // default 1 feels stuck on a map this big
        this.controls.minDistance = 20;
        this.controls.maxDistance = 2_500_000;
        this.controls.maxPolarAngle = THREE.MathUtils.degToRad(85);

        /** @type {import('./FlightTrack.js').FlightTrack|null} */
        this.following = null;

        // Manual panning stops following an aircraft.
        this.controls.addEventListener('start', () => {
            this.panStart = this.controls.target.clone();
        });
        this.controls.addEventListener('end', () => {
            if (this.following && this.panStart && this.panStart.distanceTo(this.controls.target) > 1) this.follow(null);
        });
    }

    get target() {
        return this.controls.target;
    }

    setAspect(aspect) {
        this.camera.aspect = aspect;
        this.camera.updateProjectionMatrix();
    }

    /** Frames a whole box (e.g. every flight path). */
    fitBox(box) {
        const centre = box.getCenter(new THREE.Vector3());
        const size = box.getSize(new THREE.Vector3());
        const radius = Math.max(size.x, size.z, size.y * 2, 400) / 2;
        // Use the narrower of the vertical and horizontal field of view so it fits on tall screens too.
        const vFov = THREE.MathUtils.degToRad(this.camera.fov);
        const hFov = 2 * Math.atan(Math.tan(vFov / 2) * this.camera.aspect);
        this.lookAtFrom(centre.setY(0), (radius / Math.sin(Math.min(vFov, hFov) / 2)) * 1.1);
    }

    /** Points the camera at `target` from the south-east, looking down at ~50°, `distance` metres away. */
    lookAtFrom(target, distance) {
        const direction = new THREE.Vector3(0.35, 0.95, 0.85).normalize();
        this.controls.target.copy(target);
        this.camera.position.copy(target).addScaledVector(direction, distance);
        this.camera.near = Math.max(0.5, distance / 5000);
        this.camera.updateProjectionMatrix();
        this.controls.update();
    }

    /** factor < 1 zooms in, > 1 zooms out (used by the +/- buttons). */
    zoomBy(factor) {
        const offset = this.camera.position.clone().sub(this.controls.target);
        const distance = THREE.MathUtils.clamp(offset.length() * factor, this.controls.minDistance, this.controls.maxDistance);
        this.camera.position.copy(this.controls.target).add(offset.setLength(distance));
    }

    /** Moves the view centre to `point` (keeping the viewing angle) and sets the distance. */
    focusOn(point, distance) {
        const offset = this.camera.position.clone().sub(this.controls.target);
        this.controls.target.copy(point);
        offset.setLength(THREE.MathUtils.clamp(distance, this.controls.minDistance, this.controls.maxDistance));
        this.camera.position.copy(point).add(offset);
    }

    /** Keep `track` in the centre of the view (null = stop). */
    follow(track) {
        this.following = track;
        this.onFollowChange?.(track);
    }

    update() {
        if (this.following) {
            const delta = this.following.position.clone().sub(this.controls.target);
            this.controls.target.add(delta);
            this.camera.position.add(delta);
        }

        this.clampToBounds();
        this.controls.update();

        // Keep the near plane proportional to the zoom for good depth precision.
        const distance = this.camera.position.distanceTo(this.controls.target);
        const near = THREE.MathUtils.clamp(distance / 5000, 0.5, 500);
        if (Math.abs(near - this.camera.near) / this.camera.near > 0.2) {
            this.camera.near = near;
            this.camera.updateProjectionMatrix();
        }
    }

    clampToBounds() {
        const t = this.controls.target;
        const clamped = new THREE.Vector3(
            THREE.MathUtils.clamp(t.x, this.limits.minX, this.limits.maxX),
            Math.max(0, t.y),
            THREE.MathUtils.clamp(t.z, this.limits.minZ, this.limits.maxZ),
        );
        if (!clamped.equals(t)) {
            const delta = clamped.clone().sub(t);
            t.add(delta);
            this.camera.position.add(delta);
        }
        if (this.camera.position.y < 5) this.camera.position.y = 5;
    }
}
