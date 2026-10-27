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

/**
 * "Present" buttons (data-present-window) open the 3D presentation in its own
 * screen-sized window. Ctrl/Shift/middle-click keep the browser's normal
 * behaviour, and if pop-ups are blocked the link's target="_blank" opens a tab.
 */
document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-present-window]');
    if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;

    const features = `popup,left=0,top=0,width=${screen.availWidth},height=${screen.availHeight}`;
    if (window.open(link.href, '_blank', features)) event.preventDefault();
});
