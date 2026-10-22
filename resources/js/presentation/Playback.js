/**
 * The presentation clock: one global timeline shared by every aircraft.
 */
export class Playback {
    constructor(duration) {
        this.duration = duration;
        this.time = 0;
        this.speed = 1;
        this.playing = false;
        this.listeners = new Set();
    }

    /** Advances the clock; `dt` is real seconds since the last frame. */
    tick(dt) {
        if (!this.playing) return;
        this.time += dt * this.speed;
        if (this.time >= this.duration) {
            this.time = this.duration;
            this.pause();
        }
    }

    play() {
        if (this.time >= this.duration) this.time = 0;
        this.playing = true;
        this.emit();
    }

    pause() {
        this.playing = false;
        this.emit();
    }

    toggle() {
        this.playing ? this.pause() : this.play();
    }

    seek(time) {
        this.time = Math.min(Math.max(time, 0), this.duration);
        this.emit('seek');
    }

    setSpeed(speed) {
        this.speed = speed;
        this.emit();
    }

    /** fn(type) is called on play/pause/seek/speed changes (type = 'seek' for seeks). */
    onChange(fn) {
        this.listeners.add(fn);
    }

    emit(type = 'state') {
        this.listeners.forEach((fn) => fn(type));
    }
}
