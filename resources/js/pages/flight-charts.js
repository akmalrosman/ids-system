/**
 * Telemetry charts on the flight details page (flights/show.blade.php).
 * Data: GET /flights/{id}/telemetry  (ids-telemetry JSON, SI units)
 */
import {
    Chart,
    CategoryScale,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import { formatClock } from '../presentation/format.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip, Legend);
Chart.defaults.color = '#a3a3a3';
Chart.defaults.borderColor = '#262626';
Chart.defaults.font.family = "'Inter Variable', ui-sans-serif, system-ui, sans-serif";

const M_TO_FT = 3.28084;
const MS_TO_KT = 1.94384;
const MAX_POINTS = 1500;

/** One entry per chart in the "Separate" view. */
const SERIES = [
    { key: 'alt', title: 'Height (ft)', unit: 'Feet', color: '#2563eb', map: (v) => v * M_TO_FT },
    { key: 'speed', title: 'Ground speed (kt)', unit: 'Knots', color: '#16a34a', map: (v) => v * MS_TO_KT },
    { key: 'vs', title: 'Vertical speed (ft/min)', unit: 'ft/min', color: '#ea580c', map: (v) => v * M_TO_FT * 60, fill: false },
    { key: 'yaw', title: 'Heading (°)', unit: 'Degrees', color: '#9333ea', fill: false },
    { key: 'pitch', title: 'Pitch (°)', unit: 'Degrees', color: '#db2777', fill: false },
    { key: 'roll', title: 'Roll (°)', unit: 'Degrees', color: '#0891b2', fill: false },
    { key: 'battery', title: 'Battery (%)', unit: '%', color: '#ca8a04' },
];

/**
 * Charts in the "Combined" view. Each group is one chart; each axis gets its own
 * y scale, and series listed on the same axis share it (e.g. pitch and roll).
 */
const COMBINED_GROUPS = [
    {
        title: 'Altitude & speed',
        axes: [
            { title: 'Height (ft)', keys: ['alt'] },
            { title: 'Ground speed (kt)', keys: ['speed'] },
            { title: 'Vertical speed (ft/min)', keys: ['vs'] },
        ],
    },
    {
        title: 'Attitude & battery',
        axes: [
            { title: 'Pitch / Roll (°)', keys: ['pitch', 'roll'] },
            { title: 'Heading (°)', keys: ['yaw'], min: 0, max: 360 },
            { title: 'Battery (%)', keys: ['battery'], min: 0, max: 100 },
        ],
    },
];

export async function mountFlightCharts(root) {
    const container = root.querySelector('[data-charts-container]');

    let telemetry;
    try {
        const res = await fetch(root.dataset.url, { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        telemetry = await res.json();
    } catch (e) {
        container.innerHTML = `<p class="text-sm text-red-400">Could not load telemetry: ${e.message}</p>`;
        return;
    }

    const step = Math.max(1, Math.ceil(telemetry.channels.t.length / MAX_POINTS));
    const pick = (arr) => arr.filter((_, i) => i % step === 0);
    const t = pick(telemetry.channels.t);
    const available = SERIES.filter((s) => Array.isArray(telemetry.channels[s.key]));

    let charts = [];
    const render = (view) => {
        charts.forEach((c) => c.destroy());
        container.innerHTML = '';
        charts = view === 'combined' ? renderCombined(container, t, available, pick, telemetry) : renderSeparate(container, t, available, pick, telemetry);
    };

    render('separate');
    window.addEventListener('charts-view', (e) => render(e.detail));
}

function renderSeparate(container, t, series, pick, telemetry) {
    return series.map((s) => {
        const canvas = addPanel(container, s.title);
        const values = pick(telemetry.channels[s.key]).map((v) => (v === null ? null : s.map ? s.map(v) : v));
        return new Chart(canvas, {
            type: 'line',
            data: { datasets: [dataset(s.title, t, values, s.color, s.fill !== false)] },
            options: baseOptions({ y: { title: { display: true, text: s.unit } } }, false),
        });
    });
}

/** One chart per COMBINED_GROUPS entry, several lines each with their own y axes. */
function renderCombined(container, t, series, pick, telemetry) {
    const byKey = Object.fromEntries(series.map((s) => [s.key, s]));

    return COMBINED_GROUPS.map((group) => {
        // Drop axes whose data is missing in this log (e.g. no battery column).
        const axes = group.axes
            .map((axis) => ({ ...axis, series: axis.keys.map((k) => byKey[k]).filter(Boolean) }))
            .filter((axis) => axis.series.length);
        if (!axes.length) return null;

        const scales = {};
        const datasets = axes.flatMap((axis, i) => {
            const id = `y${i}`;
            // One line on the axis: colour the axis like the line. Shared axis: neutral grey.
            const color = axis.series.length === 1 ? axis.series[0].color : '#a3a3a3';
            scales[id] = {
                position: i % 2 === 0 ? 'left' : 'right',
                min: axis.min,
                max: axis.max,
                grid: { drawOnChartArea: i === 0 },
                title: { display: true, text: axis.title, color },
                ticks: { color },
            };
            return axis.series.map((s) => {
                const values = pick(telemetry.channels[s.key]).map((v) => (v === null ? null : s.map ? s.map(v) : v));
                return { ...dataset(s.title, t, values, s.color, false), yAxisID: id };
            });
        });

        const canvas = addPanel(container, group.title, 360);
        return new Chart(canvas, { type: 'line', data: { datasets }, options: baseOptions(scales, true) });
    }).filter(Boolean);
}

function addPanel(container, title, height = 280) {
    const panel = document.createElement('section');
    panel.className = 'rounded-xl border border-neutral-800 bg-neutral-950 p-6';
    panel.innerHTML = `<h3 class="mb-4 text-base font-medium"></h3><div style="height:${height}px"><canvas></canvas></div>`;
    panel.querySelector('h3').textContent = title;
    container.appendChild(panel);
    return panel.querySelector('canvas');
}

function dataset(label, t, values, color, fill) {
    return {
        label,
        data: t.map((x, i) => ({ x, y: values[i] })),
        borderColor: color,
        backgroundColor: `${color}33`,
        fill: fill ? 'origin' : false,
        borderWidth: 2,
        pointRadius: 0,
        tension: 0.25,
    };
}

function baseOptions(yScales, showLegend) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        parsing: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: showLegend },
            tooltip: {
                callbacks: {
                    title: (items) => formatClock(items[0].parsed.x),
                    label: (item) => `${item.dataset.label}: ${item.parsed.y?.toFixed(1)}`,
                },
            },
        },
        scales: {
            x: { type: 'linear', ticks: { callback: (v) => formatClock(v, false), maxTicksLimit: 10 } },
            ...yScales,
        },
    };
}
