import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { mergeGeometries } from 'three/examples/jsm/utils/BufferGeometryUtils.js';

const loader = new GLTFLoader();
const cache = new Map();

/**
 * Loads a .glb aircraft once and returns a normalised template:
 * centred on the origin, nose pointing to -Z (north), up = +Y,
 * longest side = `lengthM` metres. Clone it for each aircraft.
 *
 * If the file can't be loaded a simple arrow-shaped aircraft is used, so the
 * presentation still works (check the browser console for the reason).
 *
 * @param {{url: string, lengthM: number, yawOffsetDeg: number}} model
 * @returns {Promise<THREE.Object3D>}
 */
export function loadAircraftTemplate(model) {
    const key = `${model.url}|${model.lengthM}|${model.yawOffsetDeg}`;
    if (!cache.has(key)) {
        cache.set(
            key,
            loader
                .loadAsync(model.url)
                .then((gltf) => normalise(mergeByMaterial(gltf.scene), model))
                .catch((error) => {
                    console.error(`[AircraftModels] Could not load ${model.url}`, error);
                    return normalise(fallbackAircraft(), { ...model, yawOffsetDeg: 0 });
                }),
        );
    }
    return cache.get(key);
}

function normalise(object, { lengthM, yawOffsetDeg }) {
    const inner = new THREE.Group();
    inner.add(object);
    inner.rotation.y = THREE.MathUtils.degToRad(yawOffsetDeg);
    inner.updateMatrixWorld(true);

    const box = new THREE.Box3().setFromObject(inner);
    const size = box.getSize(new THREE.Vector3());
    const centre = box.getCenter(new THREE.Vector3());
    const scale = lengthM / Math.max(size.x, size.y, size.z, 1e-6);

    inner.position.sub(centre.multiplyScalar(scale));
    inner.scale.setScalar(scale);

    const template = new THREE.Group();
    template.add(inner);
    return template;
}

/**
 * Models exported from CAD/SketchUp often have hundreds of small meshes.
 * Merging meshes that share a material cuts draw calls (≈240 -> ≈65 for the Su-30).
 */
function mergeByMaterial(root) {
    root.updateMatrixWorld(true);
    const groups = new Map();
    const keep = [];

    root.traverse((child) => {
        if (!child.isMesh) return;
        if (Array.isArray(child.material) || child.isSkinnedMesh) {
            keep.push(child);
            return;
        }
        const geometry = child.geometry.clone().applyMatrix4(child.matrixWorld);
        const attrs = Object.keys(geometry.attributes).sort().join(',');
        const key = `${child.material.uuid}|${attrs}|${geometry.index ? 'i' : 'n'}`;
        if (!groups.has(key)) groups.set(key, { material: child.material, geometries: [] });
        groups.get(key).geometries.push(geometry);
    });

    const merged = new THREE.Group();
    for (const { material, geometries } of groups.values()) {
        const geometry = mergeGeometries(geometries, false);
        if (geometry) merged.add(new THREE.Mesh(geometry, material));
        geometries.forEach((g) => g.dispose());
    }
    for (const mesh of keep) {
        const clone = mesh.clone();
        mesh.matrixWorld.decompose(clone.position, clone.quaternion, clone.scale);
        merged.add(clone);
    }
    return merged.children.length ? merged : root;
}

/** Simple jet silhouette (nose to -Z) used when the .glb is missing. */
function fallbackAircraft() {
    const material = new THREE.MeshStandardMaterial({ color: 0xb0b8c0, metalness: 0.3, roughness: 0.6 });
    const group = new THREE.Group();

    const body = new THREE.Mesh(new THREE.ConeGeometry(0.8, 14, 12), material);
    body.rotation.x = -Math.PI / 2;
    group.add(body);

    const wings = new THREE.Mesh(new THREE.BoxGeometry(12, 0.3, 4), material);
    wings.position.z = 1;
    group.add(wings);

    const tail = new THREE.Mesh(new THREE.BoxGeometry(0.3, 3, 2), material);
    tail.position.set(0, 1.5, 6);
    group.add(tail);

    return group;
}
