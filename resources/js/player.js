import Plyr from 'plyr';

// Persian UI strings for the player. Keyboard: space/K play, ←/→ or J/L ±5s,
// ↑/↓ volume, M mute, F fullscreen, C captions, 0–9 jump to %.
const i18n = {
    restart: 'از اول', rewind: '{seektime} ثانیه عقب', play: 'پخش', pause: 'توقف',
    fastForward: '{seektime} ثانیه جلو', seek: 'جست‌وجو', seekLabel: '{currentTime} از {duration}',
    played: 'پخش‌شده', buffered: 'بارگذاری‌شده', currentTime: 'زمان فعلی', duration: 'مدت',
    volume: 'صدا', mute: 'بی‌صدا', unmute: 'باصدا', enableCaptions: 'زیرنویس روشن', disableCaptions: 'زیرنویس خاموش',
    download: 'دانلود', enterFullscreen: 'تمام‌صفحه', exitFullscreen: 'خروج از تمام‌صفحه', frameTitle: 'پخش‌کننده',
    captions: 'زیرنویس', settings: 'تنظیمات', pip: 'تصویر در تصویر', menuBack: 'بازگشت', speed: 'سرعت',
    normal: 'عادی', quality: 'کیفیت', loop: 'تکرار', start: 'شروع', end: 'پایان', all: 'همه', reset: 'بازنشانی',
    disabled: 'غیرفعال', enabled: 'فعال', advertisement: 'تبلیغ',
    qualityBadge: { 2160: '4K', 1440: 'HD', 1080: 'HD', 720: 'HD', 576: 'SD', 480: 'SD' },
};

const SPEED_KEY = 'skillos.player.speed';
const positionKey = (id) => `skillos.player.pos.${id}`;

// Same ladder as the settings-menu speed options and the keyboard fallback.
const SPEED_STEPS = [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2];

function read(key) { try { return localStorage.getItem(key); } catch { return null; } }
function write(key, value) { try { localStorage.setItem(key, value); } catch { /* private mode etc. */ } }

function formatSpeed(value) {
    return `${Math.round(value * 100) / 100}×`;
}

// Compact speed-up / slow-down control in the control bar (a pro-player feel):
// [−] shows the current speed as a pill [+] — the pill itself resets to 1×.
// The settings menu always stays in sync via the shared speed option ladder.
function mountSpeedControl(player) {
    const controls = player.elements.controls;
    if (!controls || controls.querySelector('.plyr-speed')) return;

    const wrap = document.createElement('div');
    wrap.className = 'plyr__controls__item plyr-speed';

    // Same tooltip bubble as the built-in controls: a hidden .plyr__tooltip span
    // that the shared Plyr CSS reveals on hover (no browser-native title).
    const withTip = (btn, label, tipLabel) => {
        btn.setAttribute('aria-label', label);
        const tip = document.createElement('span');
        tip.className = 'plyr__tooltip';
        tip.textContent = tipLabel;
        btn.appendChild(tip);
    };

    const key = document.createElement('button');
    key.type = 'button';
    key.className = 'plyr__control plyr-speed-btn';
    key.dataset.plyrSpeed = 'slower';
    key.innerHTML = '<svg class="plyr-speed-glyph" viewBox="0 0 18 18" fill="currentColor" aria-hidden="true"><rect x="4.25" y="8" width="9.5" height="2" rx="1"/></svg>';
    withTip(key, 'کندتر پخش کن', 'کندتر');

    const value = document.createElement('span');
    value.className = 'plyr-speed-value';

    const pill = document.createElement('button');
    pill.type = 'button';
    pill.className = 'plyr__control plyr-speed-pill';
    pill.dataset.plyrSpeed = 'reset';
    withTip(pill, 'سرعت پخش — برای حالت عادی بزن', 'برگرد به حالت عادی');
    pill.appendChild(value);

    const rest = document.createElement('button');
    rest.type = 'button';
    rest.className = 'plyr__control plyr-speed-btn';
    rest.dataset.plyrSpeed = 'faster';
    rest.innerHTML = '<svg class="plyr-speed-glyph" viewBox="0 0 18 18" fill="currentColor" aria-hidden="true"><rect x="8" y="4.25" width="2" height="9.5" rx="1"/><rect x="4.25" y="8" width="9.5" height="2" rx="1"/></svg>';
    withTip(rest, 'سریع‌تر پخش کن', 'سریع‌تر');

    const render = () => {
        const offNormal = player.speed !== 1;
        value.textContent = formatSpeed(player.speed);
        pill.dataset.active = String(offNormal);
        pill.setAttribute('aria-label', `سرعت: ${formatSpeed(player.speed)} — برای حالت عادی بزن`);
    };

    wrap.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-plyr-speed]');
        if (!btn) return;
        const idx = SPEED_STEPS.indexOf(player.speed);
        switch (btn.dataset.plyrSpeed) {
            case 'slower': player.speed = SPEED_STEPS[Math.max(0, idx - 1)]; break;
            case 'reset': player.speed = 1; break;
            case 'faster': player.speed = SPEED_STEPS[Math.min(SPEED_STEPS.length - 1, idx === -1 ? 1 : idx + 1)]; break;
        }
        render();
    });

    wrap.append(key, pill, rest);
    // The settings button lives inside Plyr's nested menu, not as a direct child of
    // the controls bar — anchor on the menu item so insertBefore always has a real sibling.
    const anchor = controls.querySelector('.plyr__menu') || controls.querySelector('[data-plyr="settings"]') || null;
    controls.insertBefore(wrap, anchor);

    render();
    player.on('ratechange', render);
}

// Multi-video lessons mount every video's Plyr instance up front so switching between
// them is instant, but each instance's `keyboard.global` listener sits on `window` with
// no notion of which player is actually visible — so pressing space toggled play/pause on
// every mounted video at once, not just the one on screen. Keep the global listener live
// on only the active video per group (see setActiveVideo(), called from the video picker).
const mountedPlayers = new Map();

export function setActiveVideo(groupEl, activeIndex) {
    groupEl.querySelectorAll('.js-player').forEach((el, i) => {
        const player = mountedPlayers.get(el);
        if (player) player.listeners.global(i === activeIndex);
    });
}

export function mountPlayers(root = document) {
    root.querySelectorAll('.js-player').forEach((el) => {
        if (el.dataset.mounted) return;
        el.dataset.mounted = '1';

        // Browsers only eagerly fetch the *default* <track>; a non-default caption
        // track's cues stay unloaded until something toggles its mode, and Plyr's own
        // language-switch handler silently does nothing for a track it finds already
        // sitting at 'hidden' with no cues. Fix: flip every non-default track to
        // 'hidden' to force the browser to fetch+parse its cues, then — once loaded —
        // put it back to 'disabled' so Plyr still sees the single-active-track shape
        // it expects (and so has a real mode transition to perform later, instead of
        // finding the track already 'hidden' and no-op'ing). Cues stay cached once
        // fetched, so restoring 'disabled' doesn't lose them.
        el.querySelectorAll('track').forEach((t) => {
            if (t.default) return;
            t.track.mode = 'hidden';
            t.addEventListener('load', () => { t.track.mode = 'disabled'; }, { once: true });
        });

        // Videos/captions may be requested from a download host first (config/media.php's
        // download_base_url), with the owner's own machine as a fallback for anything not
        // migrated there yet. `error` on <video>/<track> fires per failed request (a 404 from
        // the download host counts), so swap to the fallback src once and reload just that piece.
        const source = el.querySelector('source[data-fallback-src]');
        if (source) {
            el.addEventListener('error', () => {
                if (source.src === source.dataset.fallbackSrc) return;
                source.src = source.dataset.fallbackSrc;
                el.load();
            });
        }
        el.querySelectorAll('track[data-fallback-src]').forEach((t) => {
            t.addEventListener('error', () => {
                if (t.src === t.dataset.fallbackSrc) return;
                t.src = t.dataset.fallbackSrc;
            });
        });

        const player = new Plyr(el, {
            iconUrl: '/plyr.svg',
            i18n,
            controls: ['play-large', 'rewind', 'play', 'fast-forward', 'progress', 'current-time', 'duration',
                'mute', 'volume', 'captions', 'settings', 'pip', 'fullscreen'],
            settings: ['captions', 'speed'],
            speed: { selected: parseFloat(read(SPEED_KEY)) || 1, options: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2] },
            seekTime: 5,
            keyboard: { focused: true, global: true },
            tooltips: { controls: true, seek: true },
            captions: { active: true, language: el.dataset.captionsDefault || 'en', update: true },
            invertTime: false,
            // Plyr must be allowed to persist the chosen caption language (and volume) to its
            // own localStorage key ('plyr', separate from ours below) — with storage disabled,
            // every language switch gets silently reverted: Plyr re-reads captions.setup() after
            // each switch, which falls back to the configured default language whenever it can't
            // read back what it just tried to save.
        });

        // Speed +/- control added after the built-ins (near the settings button).
        mountSpeedControl(player);

        // Resume where the learner stopped (skip if near the start or the end).
        // Metadata may already be loaded before Plyr wires up, so try on both events.
        const id = el.dataset.videoId;
        if (id) {
            let restored = false;
            const restore = () => {
                if (restored || !player.duration) return;
                const saved = parseFloat(read(positionKey(id)));
                restored = true;
                if (saved > 5 && saved < player.duration - 10) player.currentTime = saved;
            };
            player.on('ready', restore);
            player.on('loadedmetadata', restore);
            if (el.readyState >= 1) restore();

            const save = () => write(positionKey(id), String(Math.floor(player.currentTime)));
            let last = 0;
            player.on('timeupdate', () => {
                if (Math.abs(player.currentTime - last) >= 3) { last = player.currentTime; save(); }
            });
            player.on('seeked', save);
            player.on('pause', save);
            player.on('ended', () => write(positionKey(id), '0'));
        }

        player.on('ratechange', () => write(SPEED_KEY, String(player.speed)));

        mountedPlayers.set(el, player);
    });

    root.querySelectorAll('[data-video-group]').forEach((groupEl) => setActiveVideo(groupEl, 0));
}
