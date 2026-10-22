/**
 * Entry for every normal page. Page-specific code is loaded only when the
 * page has the matching element, so the dashboard doesn't download Chart.js.
 *
 * The 3D presentation has its own entry: resources/js/presentation/main.js
 */
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

const chartsRoot = document.querySelector('[data-flight-charts]');
if (chartsRoot) {
    import('./pages/flight-charts.js').then(({ mountFlightCharts }) => mountFlightCharts(chartsRoot));
}
