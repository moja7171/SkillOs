// Attachment downloads may be requested from a download host first (config/media.php's
// download_base_url), same as video/captions in player.js — but an <a download> has no
// load-failure event to hook like <video>/<track> do, so this checks reachability with a
// HEAD request before navigating, falling back to the owner's own machine on a miss.
export function mountDownloadFallbacks(root = document) {
    root.querySelectorAll('a[data-fallback-src]').forEach((a) => {
        if (a.dataset.mounted) return;
        a.dataset.mounted = '1';

        a.addEventListener('click', (e) => {
            if (a.dataset.checked) return; // second click after we already resolved the URL
            e.preventDefault();

            fetch(a.href, { method: 'HEAD' })
                .then((res) => { if (!res.ok) a.href = a.dataset.fallbackSrc; })
                .catch(() => { a.href = a.dataset.fallbackSrc; })
                .finally(() => {
                    a.dataset.checked = '1';
                    a.click();
                });
        });
    });
}
