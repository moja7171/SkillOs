// Interactive subtitles (DECISIONS.md §73): the English caption line on screen is split into
// words/phrases. Hovering one underlines it; clicking pauses the video and opens a popup with
// its Persian meaning (from the authored glossary, else a cached live lookup) and the whole
// line translated. Clicking anywhere else closes the popup and resumes playback — but only if
// the video was playing when the learner clicked. Dragging across words selects a phrase.

const TOKEN = /[A-Za-z0-9]+(?:['’\-][A-Za-z0-9]+)*|\s+|[^\sA-Za-z0-9]+/g;
const isWord = (t) => /^[A-Za-z0-9]/.test(t);
const norm = (s) => s.toLowerCase().replace(/[’]/g, "'").replace(/\s+/g, ' ').trim();
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

function tokenize(text) {
    return (text.match(TOKEN) || []).map((t) => ({ text: t, word: isWord(t) }));
}

function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
}

export function mountInteractiveCaptions(player, video) {
    const glossaryUrl = video.dataset.glossaryUrl;
    const lookupUrl = video.dataset.lookupUrl;
    if (!glossaryUrl || !player.elements?.captions) return;

    const container = player.elements.container;
    const captionsEl = player.elements.captions;
    let cues = {};                 // start ms → { t, w[] }
    let glossaryPromise = null;
    let popup = null;
    let resumeAfter = false;
    let activeUnits = [];
    let dragStart = null;
    const lookups = new Map();

    const loadGlossary = () => {
        glossaryPromise ??= fetch(glossaryUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then((r) => (r.ok ? r.json() : null))
            .then((json) => { cues = json?.cues ?? {}; refresh(); })
            .catch(() => { /* lookups still work live */ });
        return glossaryPromise;
    };

    // ---- finding the cue(s) behind the caption on screen -------------------------------

    const englishTrack = () => [...video.textTracks].find((t) => t.language === 'en' && t.mode !== 'disabled');

    const isEnglishShown = () => !!englishTrack() && String(player.language || 'en').startsWith('en');

    const activeCues = () => {
        const track = englishTrack();
        return track ? [...(track.activeCues || [])] : [];
    };

    const neighbour = (cue, offset) => {
        const track = englishTrack();
        const list = track ? [...(track.cues || [])] : [];
        const i = list.indexOf(cue);

        return i === -1 ? null : (list[i + offset]?.text ?? null);
    };

    const plainText = (cue) => cue.text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    const cueText = (cue) => norm(plainText(cue));

    // ---- rendering ---------------------------------------------------------------------

    // Group word tokens into units: glossary phrases ("load balancer") are one unit, every other
    // word its own — a unit is what underlines together and what a click looks up.
    function unitsFor(tokens, entry) {
        const wordIdx = tokens.map((t, i) => (t.word ? i : -1)).filter((i) => i !== -1);
        const unitOf = new Map();       // token index → unit number
        let unit = 0;
        const items = [...(entry?.w ?? [])].sort((a, b) => b.s.length - a.s.length);

        for (const item of items) {
            const parts = tokenize(item.s).filter((t) => t.word).map((t) => norm(t.text));
            if (!parts.length) continue;
            for (let k = 0; k + parts.length <= wordIdx.length; k++) {
                const slice = wordIdx.slice(k, k + parts.length);
                if (slice.some((i) => unitOf.has(i))) continue;
                if (slice.every((i, j) => norm(tokens[i].text) === parts[j])) {
                    slice.forEach((i) => unitOf.set(i, unit));
                    unit++;
                    break;
                }
            }
        }
        for (const i of wordIdx) {
            if (!unitOf.has(i)) unitOf.set(i, unit++);
        }
        return unitOf;
    }

    function renderCaption(span) {
        if (!span) return;
        const raw = span.dataset.raw ?? span.textContent;
        span.dataset.raw = raw;

        const tokens = tokenize(raw);
        const shown = activeCues();
        let cursor = 0;
        const owner = new Array(tokens.length).fill(null);   // token index → cue
        const flat = norm(raw);
        // Assign each token to the active cue whose text contains it, in order of appearance.
        let tokenChars = 0;
        const charStart = tokens.map((t) => { const s = tokenChars; tokenChars += t.text.length; return s; });
        for (const cue of shown) {
            const text = cueText(cue);
            const at = flat.indexOf(text, cursor);
            if (at === -1 || !text) continue;
            cursor = at + text.length;
            tokens.forEach((t, i) => { if (charStart[i] >= at && charStart[i] < at + text.length) owner[i] = cue; });
        }

        // Units are computed per cue so a phrase never straddles two cues.
        const unitOf = new Map();
        let base = 0;
        const groups = shown.length ? shown : [null];
        for (const cue of groups) {
            const idx = tokens.map((_, i) => i).filter((i) => (cue ? owner[i] === cue : true));
            const sub = idx.map((i) => tokens[i]);
            const map = unitsFor(sub, cue ? cues[String(Math.round(cue.startTime * 1000))] : null);
            let max = -1;
            map.forEach((u, j) => { unitOf.set(idx[j], base + u); max = Math.max(max, u); });
            base += max + 1;
        }

        const frag = document.createDocumentFragment();
        let current = null;
        let currentUnit = -1;
        tokens.forEach((t, i) => {
            const u = unitOf.get(i);
            if (u !== undefined) {
                if (u !== currentUnit) {
                    current = el('span', 'iu');
                    current.dataset.unit = String(u);
                    current.dataset.cue = owner[i] ? String(Math.round(owner[i].startTime * 1000)) : '';
                    frag.appendChild(current);
                    currentUnit = u;
                }
                current.append(t.text);
            } else {
                // Spaces between two words of one unit stay inside it; everything else is outside.
                const next = unitOf.get(i + 1);
                if (current && next !== undefined && next === currentUnit && !t.word) current.append(t.text);
                else { frag.append(t.text); current = null; currentUnit = -1; }
            }
        });
        span.textContent = '';
        span.appendChild(frag);
        span.dataset.iw = '1';
    }

    function refresh() {
        if (popup) return;
        const span = captionsEl.querySelector('.plyr__caption');
        if (span && isEnglishShown()) renderCaption(span);
    }

    new MutationObserver(() => {
        const span = captionsEl.querySelector('.plyr__caption');
        if (!span || span.dataset.iw || !isEnglishShown()) return;
        loadGlossary();
        renderCaption(span);
    }).observe(captionsEl, { childList: true });

    // ---- popup -------------------------------------------------------------------------

    function closePopup(resume) {
        if (!popup) return;
        popup.remove();
        popup = null;
        activeUnits.forEach((u) => u.classList.remove('is-active'));
        activeUnits = [];
        const shouldResume = resume && resumeAfter;
        resumeAfter = false;
        if (shouldResume) player.play();
    }

    function speak(text) {
        if (!('speechSynthesis' in window)) return;
        window.speechSynthesis.cancel();
        const u = new SpeechSynthesisUtterance(text);
        u.lang = 'en-US';
        u.rate = 0.9;
        window.speechSynthesis.speak(u);
    }

    async function liveLookup(phrase, sentence, cue) {
        const key = `${norm(phrase)}|${norm(sentence)}`;
        if (lookups.has(key)) return lookups.get(key);
        const request = fetch(lookupUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            credentials: 'same-origin',
            body: JSON.stringify({
                phrase,
                sentence,
                before: cue ? neighbour(cue, -1) : null,
                after: cue ? neighbour(cue, 1) : null,
            }),
        }).then(async (r) => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(data.message || 'ترجمه در دسترس نیست.');
            return data;
        });
        lookups.set(key, request);
        request.catch(() => lookups.delete(key));
        return request;
    }

    function place(anchor) {
        const box = container.getBoundingClientRect();
        const a = anchor.getBoundingClientRect();
        const w = popup.offsetWidth;
        const h = popup.offsetHeight;
        let left = a.left + a.width / 2 - box.left - w / 2;
        left = Math.max(8, Math.min(left, box.width - w - 8));
        let top = a.top - box.top - h - 10;
        if (top < 8) top = Math.min(a.bottom - box.top + 10, box.height - h - 8);
        popup.style.left = `${left}px`;
        popup.style.top = `${Math.max(8, top)}px`;
    }

    // Pausing reveals Plyr's controls, which slide the caption up for ~0.4 s: follow it.
    function placeAfterControlsSettle(anchor) {
        const mine = popup;
        [120, 480].forEach((ms) => setTimeout(() => { if (popup === mine && anchor.isConnected) place(anchor); }, ms));
    }

    function openPopup(units) {
        const first = units[0];
        const phrase = units.map((u, i) => (i ? ' ' : '') + u.textContent).join('').replace(/\s+/g, ' ').trim();
        const start = first.dataset.cue;
        const cue = activeCues().find((c) => String(Math.round(c.startTime * 1000)) === start) ?? null;
        const entry = start ? cues[start] : null;
        const sentence = cue ? plainText(cue) : first.closest('.plyr__caption').dataset.raw.replace(/\s+/g, ' ').trim();

        if (!popup) resumeAfter = !player.paused;
        player.pause();
        popup?.remove();
        activeUnits.forEach((u) => u.classList.remove('is-active'));
        activeUnits = units;
        units.forEach((u) => u.classList.add('is-active'));

        popup = el('div', 'iw-popup');
        popup.setAttribute('role', 'dialog');
        popup.setAttribute('aria-label', 'ترجمه‌ی کلمه');
        popup.dir = 'rtl';

        const head = el('div', 'iw-head');
        const term = el('span', 'iw-term', phrase);
        term.dir = 'ltr';
        head.appendChild(term);
        if ('speechSynthesis' in window) {
            const say = el('button', 'iw-btn', '🔊');
            say.type = 'button';
            say.title = 'تلفظ';
            say.setAttribute('aria-label', 'تلفظ');
            say.addEventListener('click', () => speak(phrase));
            head.appendChild(say);
        }
        const x = el('button', 'iw-btn', '✕');
        x.type = 'button';
        x.title = 'بستن';
        x.setAttribute('aria-label', 'بستن');
        x.addEventListener('click', () => closePopup(true));
        head.appendChild(x);

        const meaning = el('div', 'iw-meaning');
        const note = el('div', 'iw-note');
        popup.append(head, meaning, note);

        if (entry?.t) {
            const line = el('div', 'iw-line');
            const en = el('div', 'iw-en', sentence);
            en.dir = 'ltr';
            line.append(en, el('div', 'iw-fa', entry.t));
            popup.appendChild(line);
        }

        const fill = (data) => {
            meaning.textContent = data.fa;
            meaning.classList.remove('is-loading');
            note.textContent = data.n || '';
            note.hidden = !data.n;
            place(first);
        };

        const authored = entry?.w?.find((w) => norm(w.s) === norm(phrase));
        container.appendChild(popup);
        placeAfterControlsSettle(first);
        if (authored) {
            fill({ fa: authored.fa, n: authored.n });
        } else {
            meaning.textContent = 'در حال ترجمه…';
            meaning.classList.add('is-loading');
            note.hidden = true;
            place(first);
            const mine = popup;
            liveLookup(phrase, sentence, cue)
                .then((data) => { if (popup === mine) fill(data); })
                .catch((e) => { if (popup === mine) { meaning.textContent = e.message; meaning.classList.remove('is-loading'); meaning.classList.add('is-error'); place(first); } });
        }
    }

    // ---- interaction -------------------------------------------------------------------

    const unitAt = (x, y) => document.elementFromPoint(x, y)?.closest?.('.iu') ?? null;

    captionsEl.addEventListener('pointerdown', (e) => {
        const u = e.target.closest?.('.iu');
        if (!u) return;
        e.preventDefault();
        e.stopPropagation();
        dragStart = u;
    });

    document.addEventListener('pointerup', (e) => {
        if (!dragStart) return;
        const from = dragStart;
        dragStart = null;
        const to = unitAt(e.clientX, e.clientY) ?? from;
        const line = from.closest('.plyr__caption');
        if (!line || to.closest('.plyr__caption') !== line) return openPopup([from]);
        const all = [...line.querySelectorAll('.iu')];
        const [a, b] = [all.indexOf(from), all.indexOf(to)].sort((m, n) => m - n);
        const picked = all.slice(a, b + 1);
        // A phrase never spans two cues: keep the cue of the first unit.
        openPopup(picked.filter((u) => u.dataset.cue === picked[0].dataset.cue));
    });

    // While a popup is open, any other click closes it and resumes. Captured on the player so
    // Plyr's own click-to-play/pause never sees it (it would pause the video we just resumed).
    container.addEventListener('click', (e) => {
        // A drag across words ends in a click whose target is their common parent (the caption).
        if (e.target.closest?.('.plyr__captions')) { e.stopPropagation(); return; }
        if (!popup || popup.contains(e.target)) return;
        e.stopPropagation();
        e.preventDefault();
        closePopup(true);
    }, true);

    document.addEventListener('click', (e) => {
        if (popup && !container.contains(e.target)) closePopup(true);
    });

    window.addEventListener('keydown', (e) => {
        if (!popup) return;
        if (e.key === 'Escape' || e.key === ' ' || e.key === 'k') {
            e.preventDefault();
            e.stopImmediatePropagation();
            closePopup(true);
        }
    }, true);

    // Anything else that moves playback ends the lookup without forcing a resume.
    player.on('seeking', () => closePopup(false));
    player.on('languagechange', () => { closePopup(false); const s = captionsEl.querySelector('.plyr__caption'); if (s) delete s.dataset.iw; });
    player.on('enterfullscreen', () => popup && place(activeUnits[0]));
    player.on('exitfullscreen', () => popup && place(activeUnits[0]));
}
