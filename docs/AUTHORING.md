# Authoring guide — how lessons are made (read this before touching `content/`)

This is the operating manual for any model or person who writes, extends or reviews course content in SkillOS. It is the source of truth for **how** content is produced; `docs/DESIGN.md` §4 describes the data model, `docs/DECISIONS.md` §12–13 explain why the product works this way. Everything below was learned while authoring the first course (106 lessons, 258 practices); follow it unless the owner says otherwise.

## 0. The one-paragraph version

The owner drops course materials into `course/<slug>/` (videos, `.srt`/`.vtt` subtitles, slides, notebooks, example code — git-ignored). You turn each lesson into two files under `content/<slug>/lessons/` — `<lesson>.md` (a Persian rewrite of what the video teaches, **written from the English subtitles**, code in English) and `<lesson>.practices.json` (2–3 practices with English rubrics) — plus `key_points` / `common_mistakes` in `content/<slug>/course.json`. Then: `python3 tools/authoring/validate.py <slug>` → `php artisan content:import <slug>` → `php artisan test --compact tests/Feature/CourseImporterTest.php` → update `docs/STORIES.md` → one commit per section. On the host, `/_ops/import` re-imports (README).

## 1. Ground rules (the owner's standing instructions)

1. **Write lesson text from the English subtitles.** The Persian subtitles are machine-translated and unreliable; never use them as the source. Slides (`files/*.pdf`), notebooks (`files/*.ipynb`) and example `.py` files supplement the transcript. Official Python docs may add detail — especially version notes, since the course was recorded on Python 3.6.
2. **The text is a rewrite that follows the video, not a transcript.** Same topics, same order, same examples (learners switch between the video tab and the text tab), but structured as a readable lesson: headings, short paragraphs, code blocks with outputs, a table where the video compares things.
3. **Learner-visible text is Persian; English technical terms stay English** (closure, decorator, `namedtuple`, scope, iterable…). Never transliterate or invent Persian jargon. Code, identifiers, outputs and code comments are English.
4. **AI prompts and rubrics stay English** — `rubric` is consumed by Gemini, not shown to learners. `prompt`, `expected_outcome`, `hints`, `title` are shown to learners → Persian.
5. **No Skill layer, no learner-created topics.** A course is a fixed catalog authored here; the learner only enrolls. Don't design content that assumes otherwise.
6. **Practices are authored by us** (not generated at runtime). Gemini only grades open answers against the rubric.
7. Log important process/format changes in `docs/DECISIONS.md`; tick progress in `docs/STORIES.md` (C-01 line); keep `docs/DESIGN.md` §4 in sync if the file format changes.

## 2. Where everything lives

```
course/<slug>/                       owner's media (git-ignored, ~GBs) — symlinked at public/media/<slug>
  NNN-lesson-name.mp4                lecture number prefix, kebab-case; NNN is the global lecture number
  NNN-lesson-name.en.vtt / .fa.vtt   converted from .srt with tools/authoring/srt2vtt.py (en first, fa second)
  NNN-lesson-name.en.srt / .fa.srt   originals
  files/NNN-lesson-name.pdf|.ipynb|.py   slides, notebooks, examples (same NNN as the video)
  broken-subs/                       subtitle files that were empty upstream (watermark only) — never attach these

content/<slug>/
  course.json                        course meta + lessons[] (order = array order)
  lessons/<lesson-slug>.md           lesson text (Markdown)
  lessons/<lesson-slug>.en.md        optional English text — same lesson, see §3.1a
  lessons/<lesson-slug>.practices.json

tools/authoring/                     vtt2txt.py, srt2vtt.py, nbdump.py, validate.py (see §4)
```

Lesson slugs are kebab-case English topic names (`parameter-defaults-beware`), not lecture numbers; several videos can belong to one lesson (lecture + coding video, or an intro + lecture).

## 3. The file formats

### 3.1 `course.json`

```json
{
  "title": "Python 3 Deep Dive — بخش ۱: برنامه‌نویسی تابعی",
  "description": "…",                     // 1–2 sentences, catalog card
  "outcome_statement": "…",               // what the learner can DO at the end (shown on the course page)
  "source_note": "…",                     // where the material comes from
  "video_base_url": "/media/<slug>/",     // prefix for every `file` below; production rebases it (MEDIA_BASE_URL)
  "lessons": [
    {
      "slug": "parameter-defaults-beware",
      "title": "مراقب مقدارهای پیش‌فرض باش",
      "summary": "مقدار پیش‌فرض یک بار ساخته می‌شود؛ دام‌های mutable و datetime.",   // one line, shown under the title
      "section": "پارامترهای تابع",        // consecutive lessons with the same section form a sidebar group
      "estimated_minutes": 24,             // sum of the videos' minutes, rounded
      "videos": [
        { "file": "077-parameter-defaults-beware.mp4",
          "title": "بخش نظری",                                       // optional; shown as a chip when >1 video
          "subtitles": [{ "file": "077-parameter-defaults-beware.en.vtt", "lang": "en" }],   // English FIRST = default track
          "subtitle": "077-parameter-defaults-beware.fa.vtt" },      // shorthand for a Persian track, appended last
        { "file": "078-parameter-defaults-beware-again.mp4",
          "subtitle": "078-parameter-defaults-beware-again.fa.vtt" } // no `subtitles` when the English file is empty
      ],
      "attachments": [
        { "title": "اسلایدهای درس (PDF) — 077-parameter-defaults-beware", "file": "files/077-parameter-defaults-beware.pdf" },
        { "title": "نوت‌بوک Jupyter — 077-parameter-defaults-beware", "file": "files/077-parameter-defaults-beware.ipynb" }
      ],
      "key_points": ["…", "…"],            // 3–6 bullets, inline Markdown allowed (`code`, **bold**)
      "common_mistakes": ["…"],            // 1–3 bullets; [] is fine
      "prerequisites": []                  // optional; default = the previous lesson. [] = none (use for standalone sections)
    }
  ]
}
```

`url` may replace `file` for external videos (YouTube/Aparat are embedded, other URLs become links). `title` for the lesson is Persian with English terms kept; the app renders titles RTL, so a title may start with a Latin word (`Closureها`).

### 3.2 `lessons/<slug>.md`

Plain Markdown (GitHub flavored: tables, fenced code, blockquotes). The page shows it in the «متن» tab, and `key_points` / `common_mistakes` are appended automatically — don't repeat them at the end of the text. Rules and style in §5.

#### Optional English text (`<slug>.en.md`)

As of C-03 (2026-09-16, owner request), courses may carry an English version of the lesson body alongside the Persian one. It's per-course, not per-lesson: C-01 and C-02 stay Persian-only (owner explicitly said not to backfill them); every lesson of C-03 onward (the «مسیر رهبری فنی» bundle) gets both.

- File: `lessons/<lesson-slug>.en.md`, same Markdown rules as the Persian file. Imported into `Lesson.content_en` (nullable — importer skips it silently when the file doesn't exist).
- The lesson page only shows a فارسی/English toggle next to the «متن» tab when `content_en` is present; nothing changes for lessons/courses without it.
- **Write the English version from the transcript directly, not as a translation of the Persian.** The structural decisions (headings, which examples to use, what to defer to a later lesson) are already made once you've written the Persian version — reuse them — but render the prose in natural English, not a literal translation. This is meaningfully cheaper than authoring both from scratch, and avoids "translationese."
- `key_points`, `common_mistakes` and practices stay Persian-only for now — this only covers the lesson body (`content`/`content_en`). Revisit if that ever needs to change.

### 3.3 `lessons/<slug>.practices.json`

An array of 2–3 objects (the intro/overview lesson may have none):

```json
[
  {
    "key": "when-evaluated",                       // kebab-case, unique within the lesson; learner progress is keyed on it — don't rename casually
    "title": "پیش‌فرض کِی ارزیابی می‌شود؟",
    "form": "mcq",                                 // mcq | short_answer | coding | explanation | scenario
    "difficulty": "intro",                         // intro | core | stretch
    "estimated_minutes": 2,                        // 1–10
    "prompt": "```python\n…\n```\nدرباره‌ی خروجی چه می‌توان گفت؟",   // Markdown; fenced code is fine
    "options": ["…", "…", "…", "…"],               // mcq only: exactly 4, all distinct, ONE correct
    "correct_option": 1,                           // mcq only: 0–3
    "expected_outcome": "…",                       // Persian model answer shown after submit; for coding, include the reference code
    "hints": ["…", "…"],                           // EXACTLY two, progressively more revealing, never the answer itself
    "rubric": "Correct only if option 1 is selected.",  // English; graded by Gemini for open forms
    "attachments": [{ "title": "قالب کار", "file": "files/077-template.pdf" }]  // optional; same shape as a lesson's attachments (§3.1), shown on the practice page
  }
]
```

Rubric conventions:
- mcq: exactly `Correct only if option N is selected.` with N = `correct_option` (the validator checks this).
- open forms: `Correct: … Partial: … Incorrect: …` — name the specific facts/steps that must appear, and what is an acceptable alternative. The grader returns `correct | correct_with_hint | incorrect`, so "Partial" describes what still counts as correct-with-reservations.

Per-lesson mix that works: one `intro` (usually mcq, 1–2 min), one `core` (coding or short_answer, 4–6 min), one `core`/`stretch` (explanation, 3–4 min). Difficulty drives the planner's choice; keep the distribution roughly 1/1/1 across a section.

## 4. Workflow for a section (do this, in this order)

Work **one section at a time** (6–12 lessons), and commit per section.

```sh
# 0. sources into a scratch folder (the scratchpad dir may be wiped between sessions — re-run when needed)
python3 tools/authoring/srt2vtt.py course/<slug>                     # only when new .srt files arrived
python3 tools/authoring/vtt2txt.py course/<slug> /tmp/skillos/transcripts --only 102 103 104
pdftotext -layout course/<slug>/files/102-x.pdf /tmp/skillos/102.txt # slides, when they exist
python3 tools/authoring/nbdump.py course/<slug>/files/103-*.ipynb    # notebooks
```

1. Read the transcript(s) of the lesson **completely** before writing (a lecture + its coding video are one lesson). 40–60 KB of transcript is normal; read it, don't skim the first third.
2. Write `<slug>.md` (§5) and `<slug>.practices.json` (§3.3). Write one lesson per tool call; keep heredocs under ~150 lines each — very long single commands get cut off by the tooling.
3. Add `key_points` / `common_mistakes` for every lesson of the section to `course.json` (edit the JSON, or a small Python snippet that loads → sets → dumps with `ensure_ascii=False, indent=2` and a trailing newline).
4. `python3 tools/authoring/validate.py <slug>` — fix every ✗ (warnings are advice).
5. `php artisan content:import <slug>` then `php artisan test --compact tests/Feature/CourseImporterTest.php`.
6. Optional visual check: `php artisan serve --port=8123` and look at `/courses/<slug>/lessons/<lesson>` (dev user `moja@skillos.local` / `password`; `artisan serve` can't seek video — that's expected).
7. `docs/STORIES.md`: update the C-01 line (sections done, N of M). If the process changed, `docs/DECISIONS.md`.
8. Commit: message like `C-01: «<section>» section authored (N lessons, M practices)` plus a short body; use a message file (`git commit -F`) — inline `-m` with Persian quotes and double quotes breaks.

Sanity check at the end of a section: every lesson `.md` > 800 bytes, every practices file present, validator OK, importer test green.

## 5. Lesson text: style guide

**Length and shape.** 60–140 lines of Markdown for a 20–30-minute lesson; shorter for short videos, never a one-paragraph stub. `##` sections that mirror the video's flow; `###` for sub-steps. One idea per paragraph, 1–3 sentences each. End with a one-line takeaway ("جمع‌بندی", "قاعده") when the lesson has a rule.

**Opening.** If the lesson has more than one video or a source caveat, start with a blockquote:
`> این درس دو ویدیو دارد: بخش نظری و کدنویسی.` / `> زیرنویس انگلیسی ویدیوی نظری در دسترس نبود؛ متن از اسلایدها و ویدیوی کدنویسی نوشته شده.`

**Code.** Fenced ```` ```python ```` blocks with the real examples from the video, **with the outputs as trailing comments** (`# 10 20 30`) or a following block. Comments in English only — Persian inside a code line breaks bidi rendering (the validator warns). Inline code for every identifier, literal, module or expression mentioned in prose: `` `nonlocal` ``, `` `d1 | d2` ``. Never put Persian inside backticks.

**Persian.** Use نیم‌فاصله (ZWNJ) correctly: می‌کند، آن‌ها، نمی‌شود، closureها (ZWNJ before ها after a Latin word: `closure‌ها`). Persian punctuation «» for quotes, ، for commas in Persian sentences; Latin punctuation inside code. Numbers in prose may be Persian digits (۳٫۶) or Latin when they are literals (`3.6`); be consistent within a lesson.

**Register: simple and casual, not formal/textbook.** Short, plain sentences over dense academic prose. Second person, conversational ("بذار رک بگم…", "یه بازی کوچیک باهاش:"), colloquial contractions where they read naturally (شده‌ی محاوره‌ای خفیف: "می‌شه" به‌جای "می‌شود" جاهایی که لحن اجازه می‌ده) — still correct Persian, just relaxed. Lean on concrete, relatable examples over abstract explanation. No filler, no "در این ویدیو". Images where the course's source material and tooling make it feasible (ask the owner how to source them for a given course if there's no obvious pipeline — e.g. no slides/screenshots in the raw materials).

**Tables** when the video compares 2+ things (tuple/list/str, regular vs namespace package, choices vs sample). Keep header cells short.

**Version notes.** The recordings are Python 3.6. When behaviour changed later, say so in a `>` note or a sentence: `> از پایتون 3.7 …`, e.g. `namedtuple(..., defaults=)`, `/` positional-only (3.8), `dict | dict` (3.9), `match` (3.10). Check docs.python.org before claiming a version.

**Don't.** Don't translate the transcript's spoken asides ("so let's go ahead and…"). Don't invent examples the video didn't use when the video's example is fine. Don't add sections the video doesn't cover just to be complete — the text must stay in step with the video tab. Don't repeat key points at the end (they're rendered from `course.json`). Don't leave TODOs.

## 6. Practices: what good ones look like

- **They test the lesson, not trivia.** Each practice maps to a key point. A learner who understood the video should be able to do it without the text.
- **Practical and motivating, not dry drills.** Favor a tangible little task the learner can picture doing over an abstract "what does this print" quiz question whenever the concept allows it — framed so solving it feels worth doing, not just a correctness check. `prompt`/`title` stay simple and casual too (see §5's register note), same as the lesson text. No emoji anywhere. A pattern that works well: thread one running relatable scenario across a whole section's practices (e.g. building a "user profile" — avatar initials, masking a national ID, a signup form) instead of a fresh disconnected mini-story per practice; for a tooling-only section with no code to write, reframe as help-a-friend/code-review moments ("a teammate hit this error, explain why and how to fix it").
- **mcq**: a code snippet + "خروجی چیست؟" with three plausible wrong answers that correspond to real misconceptions (e.g. `list` vs `tuple` for `*args`). Never "all of the above". Put the correct answer at a varying index across the lesson's MCQs.
- **coding**: a small task with a clear target output; `expected_outcome` includes reference code in a fenced block and the expected result; rubric lists the must-haves ("uses `nonlocal`", "returns the result", "forwards `*args/**kwargs`") and acceptable alternatives.
- **explanation**: "در ۳ تا ۴ جمله …" with 2–4 concrete things to cover; rubric = which of those points must be present for Correct / Partial.
- **short_answer**: predict-the-output or fill-in-the-fact; `expected_outcome` is the exact answer.
- Hints: first hint = where to look / what concept; second hint = the decisive detail. Two, always.
- Keep prompts self-contained (include the code being asked about); don't reference "the code above" from the lesson text.

## 7. Adding a new course from scratch

1. Ask the owner for: media folder, subtitle language(s), any docs/books, and whether videos are local (default) or external.
2. Rename media to `NNN-topic.ext` (kebab-case, zero-padded lecture number), convert subtitles (`srt2vtt.py`), move empty subtitle files to `broken-subs/`, put slides/notebooks/examples in `files/` with the same `NNN`. Symlink: `ln -s ../../course/<slug> public/media/<slug>`.
3. Create `content/<slug>/course.json` with **all** lessons as entries (slug, title, section, summary, minutes, videos, attachments) and a one-paragraph `.md` outline per lesson so the course imports and browses immediately; then author section by section (§4). Sections in `course.json` order; standalone sections (updates, extras) get `"prerequisites": []`.
4. Add a `course-overview` first lesson (no practices) written from the intro video.
5. Record the course in `docs/STORIES.md` as a new C-xx story; note anything non-obvious (empty subtitles, offsets between section-local and global lecture numbers) there so the next session doesn't rediscover it.
6. After each section: import, tests, commit. On the host: upload `content/<slug>/` and `/_ops/import?token=…&slug=<slug>` (README, "Deploying").

## 8. Gotchas that cost time before

- Scratch folders (`/tmp/…`, the assistant scratchpad) disappear between sessions. Regenerate transcripts with `vtt2txt.py`; keep nothing important there.
- Nine English subtitle files in the first course were empty (`055 056 064 078 085 097 106 111 138`) — write those lessons from slides/notebooks; say so in the opening blockquote; don't attach an empty track.
- A shell command whose text contains the pattern you `pkill -f` will kill the shell itself (exit 144). Kill by PID, or from a helper script whose invocation line doesn't contain the pattern.
- `php artisan serve` doesn't support HTTP Range → video seeking fails locally; not a bug in the player.
- Very long tool calls (many files in one heredoc) can be interrupted mid-write, leaving stubs. One lesson per call; run `validate.py` afterwards to catch stubs.
- `git commit -m "…"` with quotes in Persian text breaks; write the message to a file and use `-F`.
- `key_points` render inline Markdown — backticks become `<code>`; don't use headings or lists inside them.
- Titles are rendered RTL; a title that starts with a Latin word is fine, but avoid titles that are entirely English when a Persian phrasing exists.
- The importer rejects: practices without exactly 2 hints, MCQs without exactly 4 options or with `correct_option` outside 0–3, lessons referenced in `course.json` without a `.md` file. `--prune` deletes lessons/practices removed from the files (and their learner records) — use only on purpose.

## 9. Definition of done for a section

- [ ] every lesson `.md` written from the English transcript (+ slides/notebooks), follows the video, code in English
- [ ] 2–3 practices per lesson, mixed forms/difficulties, rubrics in English, 2 hints each
- [ ] `key_points` (3–6) and `common_mistakes` in `course.json`
- [ ] `tools/authoring/validate.py <slug>` OK; `content:import` OK; importer test green
- [ ] `docs/STORIES.md` progress line updated
- [ ] one commit, message `C-xx: «section» section authored (N lessons, M practices)`
