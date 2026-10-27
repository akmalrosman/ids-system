/**
 * Entry point of the 3D flight / group presentation (presentation/show.blade.php).
 *
 *   PresentationApp.js  scene, renderer, loading, render loop
 *   TileMap.js          offline satellite map (public/tiles)
 *   FlightTrack.js      one aircraft: telemetry interpolation, model, path, label
 *   AircraftModels.js   .glb loading and normalising
 *   CameraRig.js        camera + mouse controls, limited to Malaysia
 *   Playback.js         shared timeline clock
 *   ui.js               buttons, sliders, aircraft cards, video sync
 */
import { PresentationApp } from './PresentationApp.js';
import { bindUi } from './ui.js';

const root = document.querySelector('[data-presentation]');
const config = JSON.parse(document.getElementById('presentation-config').textContent);
const loading = root.querySelector('[data-loading]');
const loadingText = root.querySelector('[data-loading-text]');

// Opened from a "Present" button (its own window): Close shuts the window.
// Opened by URL / in a tab: the link goes back to the flight or group page.
root.querySelector('[data-action="close"]').addEventListener('click', (event) => {
    if (window.opener) {
        event.preventDefault();
        window.close();
    }
});

const app = new PresentationApp(root.querySelector('[data-scene]'), config);
window.idsPresentation = app; // handy for debugging in the browser console

try {
    if (!config.flights.length) throw new Error('None of these flights has processed FDR telemetry yet.');

    await app.load((done, total) => {
        loadingText.textContent = `Loading flight data… ${done}/${total}`;
    });

    if (!app.tracks.length) throw new Error('Telemetry could not be loaded. Check the browser console for details.');

    bindUi(app, root);
    app.start();
    loading.remove();
} catch (error) {
    console.error(error);
    loadingText.textContent = error.message;
    loading.querySelector('[data-spinner]')?.remove();
}
