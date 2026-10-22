import { formatClock, formatWallClock } from './format.js';
import { INITIAL_VIEW } from './PresentationApp.js';

const M_TO_FT = 3.28084;
const MS_TO_KT = 1.94384;
const VIDEO_DRIFT_TOLERANCE_S = 0.35;

/**
 * Connects the HTML controls in presentation/show.blade.php to the PresentationApp.
 * Elements are found by data-* attributes, so the markup can be restyled freely.
 *
 * @param {import('./PresentationApp.js').PresentationApp} app
 * @param {HTMLElement} root
 */
export function bindUi(app, root) {
    const $ = (sel) => root.querySelector(sel);
    const $$ = (sel) => [...root.querySelectorAll(sel)];
    const playback = app.playback;

    // ---- top bar ----------------------------------------------------------------
    const playBtn = $('[data-action="play"]');
    const timeline = $('[data-control="timeline"]');
    const timeReadout = $('[data-readout="time"]');
    const clockReadout = $('[data-readout="clock"]');

    timeline.max = playback.duration;
    playBtn.addEventListener('click', () => playback.toggle());
    timeline.addEventListener('input', () => playback.seek(Number(timeline.value)));

    const scaleSelect = $('[data-control="scale"]');
    scaleSelect.value = String(app.modelScale);
    scaleSelect.addEventListener('change', () =>
        app.setModelScale(scaleSelect.value === 'auto' ? 'auto' : Number(scaleSelect.value)),
    );

    const speedSelect = $('[data-control="speed"]');
    speedSelect.addEventListener('change', () => playback.setSpeed(Number(speedSelect.value)));

    // ---- bottom bar -------------------------------------------------------------
    $('[data-action="reset"]').addEventListener('click', () => app.resetCamera());
    $('[data-action="fit"]').addEventListener('click', () => app.fitAll());
    $('[data-action="zoom-in"]').addEventListener('click', () => app.zoomBy(0.5));
    $('[data-action="zoom-out"]').addEventListener('click', () => app.zoomBy(2));

    let allPaths = INITIAL_VIEW.pathsVisible;
    const pathsBtn = $('[data-action="paths"]');
    const syncPathButtons = () => {
        showToggle(pathsBtn, allPaths);
        $$('[data-card-action="path"]').forEach((b) => showToggle(b, allPaths));
    };
    pathsBtn.addEventListener('click', () => {
        allPaths = !allPaths;
        app.setAllPathsVisible(allPaths);
        cards.forEach((c) => (c.pathVisible = allPaths));
        syncPathButtons();
    });

    const widthSelect = $('[data-control="linewidth"]');
    widthSelect.addEventListener('change', () => app.setLineWidth(Number(widthSelect.value)));
    app.setLineWidth(Number(widthSelect.value));

    // ---- aircraft cards ---------------------------------------------------------
    const cards = app.tracks.map((track) => {
        const el = root.querySelector(`[data-card][data-flight-id="${track.config.id}"]`);
        const card = {
            track,
            el,
            video: el?.querySelector('video[data-video]') ?? null,
            fields: Object.fromEntries([...(el?.querySelectorAll('[data-field]') ?? [])].map((f) => [f.dataset.field, f])),
            pathVisible: INITIAL_VIEW.pathsVisible,
            modelVisible: true,
            videoVisible: true,
            colored: false,
        };
        el?.querySelectorAll('[data-card-action]').forEach((btn) =>
            btn.addEventListener('click', () => onCardAction(card, btn.dataset.cardAction, btn)),
        );
        showToggle(el?.querySelector('[data-card-action="color"]'), card.colored);
        return card;
    });
    syncPathButtons();

    // Cards for flights whose telemetry failed to load.
    for (const { flight } of app.failed) {
        const el = root.querySelector(`[data-card][data-flight-id="${flight.id}"]`);
        el?.querySelector('[data-card-error]')?.classList.remove('hidden');
    }

    function onCardAction(card, action, btn) {
        if (action === 'path') {
            card.pathVisible = !card.pathVisible;
            card.track.setPathVisible(card.pathVisible);
            showToggle(btn, card.pathVisible);
        } else if (action === 'model') {
            card.modelVisible = !card.modelVisible;
            card.track.setModelVisible(card.modelVisible);
            showToggle(btn, card.modelVisible);
        } else if (action === 'color') {
            card.colored = !card.colored;
            card.track.setModelColored(card.colored);
            showToggle(btn, card.colored);
        } else if (action === 'video') {
            card.videoVisible = !card.videoVisible;
            card.el.querySelector('[data-card-video]')?.classList.toggle('hidden', !card.videoVisible);
            card.el.dataset.video = card.videoVisible ? 'on' : 'off'; // switches the flight-data layout (_card.blade.php)
            if (!card.videoVisible) card.video?.pause();
            else syncVideos(true);
            showToggle(btn, card.videoVisible);
        } else if (action === 'follow') {
            app.rig.follow(app.rig.following === card.track ? null : card.track);
        } else if (action === 'mute' && card.video) {
            card.video.muted = !card.video.muted;
            btn.querySelector('[data-icon="muted"]')?.classList.toggle('hidden', !card.video.muted);
            btn.querySelector('[data-icon="unmuted"]')?.classList.toggle('hidden', card.video.muted);
        }
    }

    app.rig.onFollowChange = (track) => {
        cards.forEach((c) => {
            c.el?.querySelector('[data-card-action="follow"]')?.classList.toggle('bg-white/20', c.track === track);
            c.el?.classList.toggle('ring-2', c.track === track);
        });
    };

    // ---- keyboard shortcuts -------------------------------------------------------
    window.addEventListener('keydown', (e) => {
        if (['INPUT', 'SELECT', 'TEXTAREA'].includes(e.target.tagName)) return;
        if (e.code === 'Space') {
            e.preventDefault();
            playback.toggle();
        } else if (e.key === 'ArrowRight') playback.seek(playback.time + 10);
        else if (e.key === 'ArrowLeft') playback.seek(playback.time - 10);
        else if (e.key === '+' || e.key === '=') app.zoomBy(0.5);
        else if (e.key === '-') app.zoomBy(2);
        else if (e.key === 'f') app.fitAll();
        else if (e.key === 'r') app.resetCamera();
    });

    // ---- state -> UI ------------------------------------------------------------------
    playback.onChange((type) => {
        playBtn.querySelector('[data-icon="play"]').classList.toggle('hidden', playback.playing);
        playBtn.querySelector('[data-icon="pause"]').classList.toggle('hidden', !playback.playing);
        playBtn.querySelector('[data-label]').textContent = playback.playing ? 'Pause' : 'Play';
        syncVideos(type === 'seek');
    });

    let lastUiUpdate = 0;
    app.onFrame(() => {
        const now = performance.now();
        if (now - lastUiUpdate < 100) return; // 10 Hz is plenty for text
        lastUiUpdate = now;

        timeReadout.textContent = formatClock(playback.time);
        if (clockReadout && app.config.timelineStart) {
            clockReadout.textContent = formatWallClock(app.config.timelineStart + playback.time * 1000, app.config.timezone);
        }
        if (document.activeElement !== timeline) timeline.value = String(playback.time);

        for (const card of cards) updateCardReadout(card);
        syncVideos(false);
    });

    /** Keeps each BVR video at the same moment as its aircraft. */
    function syncVideos(force) {
        for (const { video, track, videoVisible } of cards) {
            if (!video || !videoVisible || !track.state || Number.isNaN(video.duration)) continue;

            const videoTime = track.state.localTime - (track.config.video?.offset ?? 0);
            const inRange = videoTime >= 0 && videoTime <= video.duration;

            if (!inRange) {
                if (!video.paused) video.pause();
                continue;
            }
            if (force || Math.abs(video.currentTime - videoTime) > VIDEO_DRIFT_TOLERANCE_S * Math.max(1, playback.speed)) {
                video.currentTime = videoTime;
            }
            video.playbackRate = Math.min(playback.speed, 16);
            if (playback.playing && video.paused) video.play().catch(() => {});
            if (!playback.playing && !video.paused) video.pause();
        }
    }

    function updateCardReadout({ track, fields }) {
        const s = track.state;
        if (!s) return;
        const set = (key, text) => fields[key] && (fields[key].textContent = text);
        set('alt', `${Math.round(s.alt * M_TO_FT).toLocaleString()} ft`);
        set('speed', `${Math.round(s.speed * MS_TO_KT)} kt`);
        set('vs', `${Math.round(s.vs * M_TO_FT * 60).toLocaleString()} fpm`);
        set('heading', `${String(Math.round(s.heading) % 360).padStart(3, '0')}°`);
        set('battery', s.battery === null ? '—' : `${s.battery}%`);
        set('time', s.active ? formatClock(s.localTime, false) : s.localTime < 0 ? 'not started' : 'finished');
    }
}

/** On/off look for toggle buttons: an "off" button is dimmed. */
function showToggle(button, on) {
    button?.classList.toggle('opacity-40', !on);
}
