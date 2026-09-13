import Plyr from 'plyr';

// Persian UI strings for the player. Keyboard: space/K play, ←/→ or J/L ±10s,
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

function read(key) { try { return localStorage.getItem(key); } catch { return null; } }
function write(key, value) { try { localStorage.setItem(key, value); } catch { /* private mode etc. */ } }

export function mountPlayers(root = document) {
    root.querySelectorAll('.js-player').forEach((el) => {
        if (el.dataset.mounted) return;
        el.dataset.mounted = '1';

        const player = new Plyr(el, {
            iconUrl: '/plyr.svg',
            i18n,
            controls: ['play-large', 'rewind', 'play', 'fast-forward', 'progress', 'current-time', 'duration',
                'mute', 'volume', 'captions', 'settings', 'pip', 'fullscreen'],
            settings: ['captions', 'speed'],
            speed: { selected: parseFloat(read(SPEED_KEY)) || 1, options: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2] },
            seekTime: 10,
            keyboard: { focused: true, global: true },
            tooltips: { controls: true, seek: true },
            captions: { active: true, language: el.dataset.captionsDefault || 'en', update: true },
            invertTime: false,
            storage: { enabled: false }, // we persist speed ourselves; volume via Plyr default is fine
        });

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
    });
}
