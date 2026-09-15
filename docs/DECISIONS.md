# SkillOS — Implementation Decisions

Short, numbered, and expected to change. Each entry states the decision, why, and what would make us revisit it. This closes the "open decisions" of PRD v0.1 §32.

---

## 1. Scale and audience

**Decision.** SkillOS is built for its author plus one or two friends. It is not a product.

**Why.** Stated explicitly at project start (2026-09-11).

**Consequences.**
- Reviewer = learner = operator. Anything whose only purpose is coordinating multiple people (audit tables, reviewer roles, validation stages, job dashboards) is cut.
- Kept regardless of scale, because they protect the *learner*: human approval before publish, evidence-only mastery, no raw scores.
- Cut criterion for future features: *"Would this exist if only one person ever used it?"*

---

## 2. Planning is computed, not stored

**Decision.** There is no stored weekly plan. `Planner::today(user)` computes today's activities from current state and materializes them as `plan_items` for today only.

**Algorithm.**

```
today(user):
  for each item in user.items where status == active and daily_time_minutes > 0
        ordered by priority desc:
    budget = item.daily_time_minutes
    candidates = []

    # 1. due reviews (capped, see recovery)
    for each skill in item.skills with mastery.next_review_due_at <= today
        ordered by next_review_due_at asc, limit 3:
      candidates << (practice_of(skill), source=review, reason="Review due")

    # 2. remediation on the current skill
    current = first skill by order where level < proficient
              and all prerequisites have level >= familiar
    if current:
      last3 = last 3 attempts on current
      if last3 all incorrect:
        candidates << (learn_of(current), reason="Re-learn after 3 misses")
      elif no attempt on learn_of(current):
        candidates << (learn_of(current), reason="Start skill")
      candidates << (next unattempted-or-least-recent practice_of(current), reason="Practice current skill")

    # 3. maintenance / nothing left
    if no candidates:
      weakest = skill with lowest numeric_mastery
      candidates << (practice_of(weakest), reason="Keep sharp")

    # fill budget, never split an activity
    for c in candidates:
      if c.estimated_minutes <= budget or budget >= c.estimated_minutes / 2:
        plan_items << c ; budget -= c.estimated_minutes
      if budget <= 0: break
```

- Content for `current` is generated lazily if `skills.content_generated_at` is null (see §6).
- **Continue Learning** = first uncompleted plan item (reviews first, then by item priority). Alternatives = next plan item + a free practice of the current skill of the top item.
- **Skip** = plan item status `skipped`. No reschedule.
- **Outside-plan activity**: an attempt whose activity has no scheduled plan item today does not mark anything completed.

**Recovery.** Nothing special. Overdue reviews are simply due; the per-item cap of 3 reviews/day bounds the return-after-absence plan. Overdue reviews that don't fit today are picked up on following days, oldest first.

**Week view.** Today's plan items + reviews due per day for the next 6 days (from `next_review_due_at`). No simulation of progression.

Implemented 2026-09-13 in `App\Services\Planning\Planner`. Three details settled in code:
- If the current lesson already has a due review today, its practice candidate is dropped — the review *is* the practice (avoids planning the same lesson twice).
- Minutes already on today's plan for a course (completed or open) are deducted from the budget on recompute, so raising/lowering Daily Time mid-day behaves sensibly.
- `pickPractice` returns the least-recently-attempted practice of a lesson (unattempted first), so consecutive reviews rotate through a lesson's practices.

**Why.** Stored plans need reschedule, expiry, recovery-plan generation, and conflict handling — four features replaced by one function. It also matches the PRD's real intent: "don't replay the backlog".

**Revisit if.** We want to plan around fixed calendar commitments (e.g. "exam on the 20th").

---

## 3. Mastery numbers

Scale 0–1000. `not_started` until the first attempt.

**Deltas per attempt (applied to the target skill):**

| Evidence | Delta |
|---|---|
| Practice correct, no hint | +150 |
| Practice correct with hint | +70 |
| Practice partial (AI verdict) | +30 |
| Practice incorrect | −100 |
| Review correct, no hint | +120 |
| Review correct with hint | +50 |
| Review incorrect | −150 |
| Learn completed | 0 (resets the consecutive-failure counter only) |
| Abandoned | 0 |

**Levels:**

| numeric | level |
|---|---|
| 0–249 | learning |
| 250–499 | familiar |
| 500–799 | proficient |
| 800–1000 | mastered |

**Next review** is set after every evaluated attempt from the *new* level:

| level | next_review_due_at |
|---|---|
| learning | +1 day |
| familiar | +3 days |
| proficient | +7 days |
| mastered | +21 days |

A failed review therefore drops the level (or at least the number) and pulls the next review closer automatically.

Implemented 2026-09-13 in `MasteryService` (constants `DELTAS`, `THRESHOLDS`, `REVIEW_INTERVAL_DAYS`). Two details settled in code: a learn completion creates the record at 0 → level «learning» with a review due tomorrow (so a lesson that was only read gets practised the next day); `next_review_due_at` is set to the start of the due day.

**Why.** Leitner-style, no parameters to fit, every change explainable. Thresholds are guesses; tune after a month of real use.

**Revisit if.** Levels feel too fast (mastered after 6 clean practices) or too sticky. Adjust deltas first, thresholds second.

---

## 4. Remediation rules

Derived at planning time from attempts; nothing stored.

| Condition | Planner action |
|---|---|
| Last review on a skill was incorrect | That skill's review is due now (already true via §3 intervals) |
| Last practice incorrect, learn already done | Next practice of the same skill (§2 step 2) |
| Last 3 attempts on the skill all incorrect | Learn activity again, then practice |
| A prerequisite of the current skill dropped below familiar | Prerequisite is selected as `current` instead (§2 unlock rule) |

---

## 5. Evaluation

| Form | Evaluator |
|---|---|
| `mcq` | `correct_option === response` |
| `short_answer`, `coding`, `explanation`, `scenario` | Gemini with rubric + expected_outcome → `{verdict: correct|partial|incorrect, feedback}` |

- No code execution. Coding tasks are judged on the code as text against the rubric. Accepted loss: a syntactically wrong but conceptually right answer may pass.
- Hint policy: up to 2 hints (from `payload.hints`), then answer shown → `incorrect`.
- **MCQ consistency guard (2026-09-12):** generation must also return `correct_option_text`; an MCQ is kept only if it has exactly 4 options and `options[correct_option] === correct_option_text`, otherwise the same prompt is stored as `short_answer` (rubric-graded). Reason: the first live run produced a 5th option literally named "correct_option" with an index that contradicted the model's own expected outcome — rule-based grading would have marked right answers wrong.
- `partial` counts as +30 and does not trigger a hint; the learner may retry once for a full-credit verdict (a retry after partial is treated as `with hint`). Implemented 2026-09-12 in `AttemptSession`: first partial sets `evidence.partial_retry`; a second non-correct answer enters the normal hint path; a later `correct` is stored as `correct_with_hint`. If the learner gives up after a partial, `result_status` is `incorrect` but `evidence.verdict` stays `partial` — MasteryService reads the verdict for the +30.

**Revisit if.** Coding is the dominant use and the AI verdicts feel unreliable — then add a minimal PHP `proc_open` runner for Python with a timeout, single-file only.

---

## 6. Content generation: lazy, per skill, synchronous

- Trigger: planner selects a skill whose `content_generated_at` is null, or learner opens the skill page.
- One Gemini call produces learn text + 2–3 practices; stored as one `resources` row (text) and 3–4 `activities` rows (1 learn, 2–3 practice). `content_generated_at` set inside the same transaction.
- Synchronous in the request (60s timeout). If real-world latency makes this painful, switch to Laravel queue with the `database` driver — the service call is already isolated so the change is mechanical.

**Why.** Free-tier rate limits, and most items never reach their last skills.

---

## 7. AI output schemas

### generate_design
```json
{
  "outcome_statement": "string",
  "skills": [
    { "key": "s1", "name": "string", "description": "string", "prerequisite_keys": ["s0"] }
  ]
}
```
Prompt inputs: title, starting_point. 4–10 skills, prerequisites before dependents.

### generate_skill_content
```json
{
  "learn": {
    "explanation": "markdown", "examples": ["..."], "key_points": ["..."], "common_mistakes": ["..."]
  },
  "practices": [
    {
      "form": "mcq|short_answer|coding|explanation|scenario",
      "prompt": "string",
      "options": ["..."], "correct_option": 0,
      "expected_outcome": "string",
      "hints": ["gentle", "stronger"],
      "rubric": "string",
      "difficulty": "intro|core|stretch",
      "estimated_minutes": 10
    }
  ]
}
```
Prompt inputs: item title, outcome, skill name+description, starting_point, names of the skill's prerequisites. Instruct: 2–3 practices, at least one `intro`; choose forms that fit the topic (coding for programming, scenario for architecture, etc.).

### evaluate_response
```json
{ "verdict": "correct|partial|incorrect", "feedback": "string" }
```
Prompt inputs: prompt, expected_outcome, rubric, response, hint_level. Instruct: never output a score or grade; feedback ≤ 120 words; if incorrect, do not reveal the answer.

---

## 8. Deferred, with reasons

| Item | Why deferred |
|---|---|
| Gamification / streaks | No evidence yet that motivation is the bottleneck for 2 users. |
| Notes / bookmarks | Text editor is enough; revisit if we miss it in practice. |
| Weekly review report | Needs a few weeks of data to be meaningful. |
| AI validation stage | Reviewer is the learner; a 30-second read + Regenerate covers it. |
| Video discovery | Manual URL paste. |
| Code execution | See §5. |
| Async jobs | See §6. |
| Reactivation assessment | Reviews-due-today on reactivation (>14 days) is enough. |
| Edit skill structure before approve | Regenerate is cheaper than building a tree editor. Revisit if regenerate keeps producing one wrong skill. |

---

## 9. Pre-launch schema changes edit the original migrations (2026-09-12)

**Decision.** Until the app holds real learning data, schema changes are made by editing the `2026_09_11_*` create-migrations and running `migrate:fresh`, not by adding alter-migrations.

**Why.** SQLite makes enum/drop-column alters awkward, and the only data so far is smoke-test data. A pile of alter-migrations for a schema nobody has used yet is noise.

**Revisit when.** The first real week of use (S-24) starts — from then on, every schema change is a new migration.

Also recorded: the Breeze tests for removed features (email verification, password confirmation, password reset) are stale and fail; they are pending deletion. The profile page referenced the removed `verification.send` route and 500'd — fixed by removing that block.

---

## 10. Gemini model: `gemini-3.6-flash` (2026-09-12)

**Decision.** `GEMINI_MODEL` defaults to `gemini-3.6-flash`.

**Why.** The first live call with `gemini-2.5-flash` returned 404 "no longer available to new users"; Google's error message named `gemini-3.6-flash` as the replacement, and it works with the same `responseSchema` structured output (9s for a design). Newer `3.7`/`3.8` flash models are also listed for this key; not tried, no reason yet.

**Revisit if.** Latency or quality of skill content / evaluation (M1–M2) is poor — try `gemini-3.8-flash` first, then a `-lite` variant for `evaluate_response` only, which is the call that runs most often.

---

## 11. UI: Persian, RTL, Jalali, dark-first (2026-09-12)

**Decision.** The whole UI is Persian (RTL, Vazirmatn); AI-generated content is **always Persian** too; dates are shown in the Jalali calendar; dark theme is the default with a light toggle. Visual system and tokens: DESIGN.md §2.3. Mockup approved by the user before implementation.

**Why.** The user's request. "Always Persian" content was chosen over per-item language for simplicity; accepted risk is weaker technical terminology for programming topics.

**Consequences.**
- Prompts stay in **English**; only what the learner sees must be Persian. Each prompt instructs the model to write its output fields in Persian, keeping English technical terms where natural (e.g. «virtual environment», `try/except`). The existing Python item was generated before this rule and should be regenerated.
- Persian digits via `fa_num()`, Jalali via `fa_date()` (helpers in `app/Support/helpers.php`). Numbers inside code and durations stay Latin.
- Validation/auth messages: `lang/fa/*.php` (only the rules in use).
- Authored text (titles, outcomes, skill names) gets `dir="auto"` so any Latin content still reads correctly.

**Revisit if.** Technical content in Persian reads badly in practice — then add a per-item content language.

---

## 12. Pivot: fixed catalog, lesson as the unit, AI evaluates only (2026-09-12)

**Decision.** Learners do not create topics. Courses are authored at development time from the owner's materials (videos with subtitles, documents, books) by Claude, with Gemini as an optional drafting assistant, written to `content/<course>/` and loaded by `php artisan content:import`. The learner enrolls from a catalog. The unit of mastery, practice and review is the **lesson** (one topic; usually one video). There is **no Skill layer**: courses are independent. Gemini at runtime does exactly one thing: `evaluate_response`.

**Why.** The owner's stated goal: "build any course I want in a short time in this app, review it and practice it; the courses may be unrelated." A Skill/competency layer only pays off when several courses are combined, which is not planned. Runtime AI design/content generation was solving a problem the owner does not have (they already own the materials) and would have produced lower-quality, unreviewed text.

**Consequences.**
- Removed: `learning_items`, `skills`, `skill_dependencies`, `resources`, `LearningDesignGenerator`, `SkillContentGenerator`, the create/generate/approve flow, PRD §"human approval" (there is nothing to approve at runtime — review happens on the content files before import).
- Added: `courses`, `lessons`, `lesson_videos`, `lesson_prerequisites`, `enrollments`; `activities.lesson_id` + stable `key`; `mastery_records.lesson_id`; `plan_items.enrollment_id`.
- Sections §2 (planner), §3 (mastery), §4 (remediation), §5 (evaluation) stay valid with "skill" → "lesson", "item" → "enrollment". §5's MCQ guard now applies to authored content: the importer rejects an mcq without exactly 4 options and a valid `correct_option`.
- §6 (lazy generation) and §7 (design/content schemas) are obsolete; only the `evaluate_response` schema remains in force.
- Lesson text is a rewrite that follows the video, not the subtitle verbatim; may be supplemented from official references the owner names.
- Deployment target includes shared hosting: SQLite, synchronous AI call, `public/build` committed, no Node on the server.
- Videos are URLs: either a file the app serves or an external link. The lesson page embeds `<video>` for direct media URLs and an iframe for YouTube/Aparat links.

**Deferred with reasons.** Skill layer (needs multiple related courses); course prerequisites (no second course yet); in-app authoring (Claude + files is faster and reviewed).

---

## 13. Course media: local files under `course/`, served through `public/media` (2026-09-13)

**Decision.** Owner-provided media lives in `course/<course-slug>/` (gitignored, 12 GB for the first course), exposed as `public/media/<course-slug>` via a symlink. `course.json` sets `video_base_url` and each video names a `file`; the importer builds the URL. Subtitles are WebVTT files: `subtitle` (shorthand, Persian) and/or `subtitles: [{file, lang, label}]` per video, stored as `lesson_videos.subtitles` json and rendered as `<track>` elements — the player's CC menu switches fa/en/off. The first course has Persian (95) and English (96) tracks; English transcripts are the source for lesson texts.

**Why.** The owner may host videos elsewhere later; changing `video_base_url` is then the only edit. Native `<track>` gives on/off subtitles without any JS. Filenames were normalized (`001-course-overview.mp4`) because the originals had a colon, spaces and download-site tags.

**Consequences.**
- `php artisan serve` ignores HTTP Range requests, so seeking in dev is sluggish; Apache/nginx in production handle it.
- `.srt` subtitles must be converted to `.vtt` (`ffmpeg -i x.srt x.vtt`) before referencing.
- Owner also provided the course's official exercise files (slide PDFs + Jupyter notebooks per lecture). They live in `course/<slug>/files/NNN-slug.{pdf,ipynb}` and are attached to lessons via `attachments: [{title, file}]` → `lessons.attachments` json, shown as «فایل‌های درس». Files for lectures without videos are parked in `files/extra/<section>/`. Slides + notebooks are now primary sources for lesson texts alongside the English transcripts.
- Subtitles arrived later the same day (fa + en, machine-translated fa). Video 085 has no Persian track. Lesson texts for 50 of 60 lessons are still outlines; they are written from the English transcripts next.

---

## 14. Video player: Plyr, self-hosted, with resume and speed memory (2026-09-13)

**Decision.** Local/YouTube videos render through Plyr 3.7.8 (bundled via Vite, icon sprite self-hosted at `public/plyr.svg`) instead of the bare `<video controls>`. Persian UI strings; controls: big play, ±10s, progress with seek tooltip, time, volume, captions (fa/en/off), settings (captions, speed 0.5–2×), PiP, fullscreen. Keyboard: space/K, ←→/J/L, ↑↓, M, F, C, 0–9. Two additions in `resources/js/player.js`: the playback position is remembered per video (`localStorage`, resume unless within 5s of the start or 10s of the end) and the chosen speed is remembered globally. Aparat stays an iframe.

**Why.** The owner asked for a professional player with speed, seeking, keyboard and subtitle selection. Plyr gives all of that accessibly in ~30 KB and honours `<track>` elements, so the content model didn't change.

**Consequences.** `php artisan serve` cannot seek (no HTTP Range) — test seeking/resume against a Range-capable server (Apache/nginx in production; a throwaway Python range server in dev). Per-viewer state lives in the browser only.

---

---

## 15. Lesson page = course player: curriculum sidebar, nested URLs, «ادامه» (2026-09-13)

**Decision.** The lesson page is laid out like a course player (Udemy-style): the lesson (video/text tabs, practices, status/files/prerequisites, prev/next) on the reading side and a **persistent curriculum sidebar on the left** — course title + progress, sections collapsible (the current one open), every lesson with number, minutes, video/text icon and a level dot (check ≥ «آشنا»), the current lesson highlighted and scrolled into view. Sticky on desktop (the main nav is now sticky too), a drawer on phones. Lesson URLs moved from `/lessons/{id}` to **`/courses/{course}/lessons/{lesson-slug}`** (scoped binding; old ids 301-redirect). New `GET /courses/{course}/learn` («ادامه بده» on the course page and Home) opens the first lesson below «آشنا», else the first lesson. `Lesson::url()` builds the link; `Course::lessons()` uses `chaperone()` so children know their course without extra queries.

**Why.** With 100+ lessons per course, going back to the course page to pick the next lesson is the wrong loop; the owner asked for the list to be always visible while watching. Slug URLs are readable, stable across re-imports and match the content folder names.

**Consequences.** `route('lessons.show')` needs `[$course, $lesson]` — use `$lesson->url()`. The lesson controller loads the whole curriculum with the learner's mastery (2 queries). Key points / common mistakes now render inline Markdown (backticked code).

---

## 16. Deploy to shared hosting without SSH; media served from the owner's machine (2026-09-14)

**Decision.** Releases are zips built locally by `tools/build-release.sh` from a committed tree (`git archive` + `composer install --no-dev`), laid out as `skillos/` (app) + `public_html/` (web root with a front controller that finds `../skillos` or `./skillos` and calls `usePublicPath`). All post-upload steps run over HTTP: `GET /_ops/{status|migrate|import|optimize|clear}?token=OPS_TOKEN` (`OpsController`; 404 unless the token matches; `hash_equals`). The zip never contains `.env`, `database/`, `storage/` or `course/`, so an update is "extract over the old release, hit the three ops URLs". Registration can require `REGISTRATION_CODE`. Media stays on the owner's machine: `MEDIA_BASE_URL` (config/media.php + `media_url()` helper) rebases every stored `/media/...` URL onto a local origin served by `tools/media-server.py` (stdlib Python, HTTP Range + CORS so Plyr can seek and load `<track>` subtitles).

**Why.** The owner's host has no shell and no Composer/Node; a deploy path that only needs a file manager and a browser is the only workable one. The 20 GB of course video is personal and large, and the browser is always on the owner's machine, so serving it from `localhost` (a "potentially trustworthy" origin, exempt from mixed-content blocking) costs nothing and keeps the host tiny.

**Consequences.** Friends who use the site do not see videos unless they run the media server with their own copy, or media is uploaded to `public_html/media/` and `MEDIA_BASE_URL` is cleared; text + practices work regardless. `OPS_TOKEN` travels in the query string — keep it long, and rotate it if it ever ends up in a shared log. Config caching (`/_ops/optimize`) must be re-run after editing `.env`.

---

## 17. Authoring manual is the entry point for every session (2026-09-14)

**Decision.** `docs/AUTHORING.md` holds the complete lesson-authoring process (sources, formats, style, practices, workflow, gotchas, definition of done) and `CLAUDE.md`/`AGENTS.md` open with a pointer to it plus the owner's standing rules, so every model session — content or code — reads it first. Reusable helpers live in `tools/authoring/` (`vtt2txt.py`, `srt2vtt.py`, `nbdump.py`, `validate.py`) instead of throwaway scratch scripts.

**Why.** Remaining courses will be authored with smaller models across many sessions; the process knowledge was only in one conversation's memory and in a scratchpad that gets wiped.

**Consequences.** Changes to the content format must update `AUTHORING.md` (and `DESIGN.md` §4). `validate.py` is the gate before `content:import`.

---

## 18. Reviews grow their own practice pool via AI once the authored ones are exhausted (2026-09-14)

**Decision.** A review is still "the least-recently-attempted practice of the lesson" (`Planner::pickPractice`), unchanged. What's new: before picking, `Planner::pickReviewPractice()` checks whether the learner has already attempted *every* practice in the lesson's pool at least once; if so, `ReviewPracticeGenerator` asks Gemini for one new practice (never `mcq`, to avoid the option-consistency failure in §5), validates it the same way `CourseImporter` validates authored ones, and adds it to the lesson permanently as `activities.generated = true`. The normal rotation then picks it — freshest first, since it has no attempts yet. Nothing else about spaced repetition changed: intervals, the 3-reviews/day cap and the mastery deltas (§3) are untouched, and there is still no pre-review recap screen — a review opens straight on the question. `content:import --prune` skips `generated` rows so re-importing a course never deletes them. A generation failure (no `GEMINI_API_KEY`, network, bad output) is caught and reported, and the learner silently gets the normal authored rotation instead — never blocked.

**Why.** Most lessons ship with only 2-3 authored practices; a learner reviewing the same lesson for months eventually just re-sees the same question, memorizing the answer rather than the skill. Generating a fresh one only when the pool is actually exhausted (rather than every review) keeps the Gemini cost/latency rare instead of paid on every review, while the pool still grows over time so variety compounds. Content stays authored everywhere else (§6, §12) — this is a narrow, explicit exception scoped to the review path only.

**Consequences.** A lesson's `activities` table row count is no longer purely a function of its content files — `generated=true` rows are owner-invisible unless they open the lesson and check. If the owner ever wants to prune or review AI-written practices by hand, filter on `generated`. `ReviewPracticeGenerator::FORMS` intentionally excludes `mcq`.

---

## 19. Video days: per-course weekday gating on new lessons only (2026-09-14)

**Decision.** `Enrollment.video_days` (nullable JSON array of Carbon's `dayOfWeek`, 0=Sunday..6=Saturday) lets a learner say "only start new lessons on these weekdays." `Enrollment::isVideoDay()` checks it against `now()`; empty/null means every day (unchanged default for every existing enrollment). `Planner::candidatesFor()` only gates the two candidates that carry a *new* video — "شروع درس" (first time through a lesson's learn activity) and "یادگیری دوباره بعد از سه اشتباه" (relearn) — behind `$enrollment->isVideoDay()`. Everything else is untouched: due reviews (always practice, never `learn`), and practice of a lesson *already* learned, show up every day regardless. If a lesson isn't learned yet and today isn't a video day, its practice is withheld too (nothing to practice on unwatched material) and the planner falls through to its ordinary "nothing to advance" fallback (weakest-lesson maintenance practice). Configured per enrollment on the existing `/enrollments/{enrollment}/edit` schedule page: a checkbox reveals a 7-day picker (Alpine `x-show`), unioned with `daily_time_minutes` at the same request.

**Why.** The owner wants a routine like "2 days a week for new video, the rest for practice/review" — the planner previously had no notion of weekday at all, mixing a fresh lesson into every day's plan regardless. Gating only the two learn-carrying candidates (not reviews, not already-learned practice) keeps every other planning rule, mastery number and the review-generation path (§18) exactly as they were; this is additive, not a rewrite.

**Consequences.** `Planner::candidatesFor()` now reads `$enrollment` for more than routing (`isReviewsOnly()`); any future candidate type that starts new (unlearned) material should check `isVideoDay()` the same way. A course with `video_days` set and an enrollment stuck below "familiar" on every lesson will show nothing (or only the maintenance fallback) on non-video days — expected, not a bug.

---

## 20. Git-based deploy via cPanel Git Version Control, alongside the zip path (2026-09-14)

**Decision.** For hosts where we actually have git access (this one does, same cPanel account as another project of the owner's), a persistent `deploy` branch — force-adding the gitignored `vendor/` on top of whatever's already on `main` (`public/build` is already committed on `main`, so unlike a typical Laravel deploy branch this one never needs a Node build step) — is cloned once via cPanel's Git Version Control into `<domain>/`, with the subdomain's Document Root pointed at `<domain>/public` (the standard shared-hosting Laravel layout: the app lives one level above the web root, no custom front controller needed, unlike §16's zip path). `.cpanel.yml` (deploy-branch-only, mirrors an existing setup of the owner's for a sibling project) copies the checkout into `$DEPLOYPATH` on every pull and curls `public/deploy.php` (also deploy-branch-only), which bootstraps Laravel directly — no HTTP routing, no CSRF — and runs `migrate --force`, `storage:link`, `content:import --prune` for every course under `content/`, then the three `*:cache` commands. That curl is gated to `REMOTE_ADDR === 127.0.0.1`, with a `DEPLOY_TOKEN`-guarded query-string fallback for manually confirming it actually ran (mirrors a real problem hit on the sibling project: the internal curl's own success was never directly observable, no SSH/Terminal to check why). `tools/deploy-push.sh` — living on `main`, reaching `deploy` too on its next merge — is the one command a normal update needs: merge `main` into the persistent worktree at `~/skillos-deploy-worktree`, refresh `vendor/`, push; then one click ("Update from Remote") in cPanel's UI.

**Why.** The zip-upload path (§16) exists because most shared hosts give *no* SSH and *no* git — every step has to go through a file manager and HTTP GETs to `/_ops/…`. Where git access *is* available, that whole manual dance collapses to "push, click a button in cPanel" — worth having as the primary path on any host that supports it, since it removes the recurring human steps (build zip, upload, extract, hit four URLs) down to one.

**Consequences.** Two deploy paths now coexist and must both keep working: `OpsController`/`/_ops/…` (§16, still the only option on hosts without git) and this one. Both end up running the same artisan commands (migrate, content:import, the `*:cache` trio) but through separate code paths (`OpsController` vs `public/deploy.php`) since `deploy.php` bootstraps Laravel standalone rather than going through routing/middleware — a change to what "a deploy needs to run" (e.g., a new artisan step) has to be made in both places. `public/deploy.php` and `.cpanel.yml` live only on `deploy`, never `main` — they'd be meaningless (and the DEPLOYPATH would be wrong) anywhere else.

---

## 21. Habit-formation on Home: streak, daily-goal bar, gentle nudge (2026-09-15)

**Decision.** `users.streak_count` / `users.streak_last_date` track a cross-course daily streak, bumped once per calendar day by `User::recordActivityToday()` — called from `AttemptSession::finalize()`, so it fires on every learn-completion or practice attempt across every enrollment, not per course. Same-day calls no-op; a gap of a day or more resets to 1; the day right after `streak_last_date` bumps by one. Home shows it as a small flame pill next to a visual progress bar for today's planned-vs-done minutes (both numbers already existed in `HomeController`, just weren't drawn as a bar before). A short absence (2–13 days since `streak_last_date`) shows a soft one-line nudge card on Home ("N روزه نیومدی…"); this is purely a Home-page notice, independent of `Planner::reactivate()` (§2), which is the heavier >14-day, enrollment-`next_review_due_at`-touching path triggered explicitly on reactivating a paused/archived enrollment.

**Why.** The owner asked for a set of engagement/retention ideas across the whole system (not tied to one course) — a visible streak plus a same-page sense of "today's progress" and a low-pressure comeback nudge are the cheapest, highest-leverage first pieces: no new pages, reuses data already being computed, and the psychology (don't break the chain, visible partial progress, a reminder that isn't guilt-tripping) is well-established.

**Consequences.** `recordActivityToday()` runs inside `AttemptSession::finalize()`'s existing DB transaction, so it's atomic with mastery/plan-item updates but adds a write to every single attempt finalize, learn or practice — negligible cost, but worth knowing if that method's hot-path cost ever matters. The nudge's 2–13 day window is deliberately exclusive of `Planner::REACTIVATION_GAP_DAYS` (14) to avoid the two systems talking about the same gap differently.

---

## 22. Weekly/monthly activity recap on Home (2026-09-15)

**Decision.** `ActivityStats::since(User, Carbon)` is one query — `attempts` joined to `activities`, filtered to `completed_at >= $since`, aggregating `count(*)` and `sum(estimated_minutes)` — called twice by `HomeController` (7 and 30 days back) and rendered as one small "خلاصه‌ی فعالیت" card on Home, right under Today's list. Open attempts (`completed_at IS NULL`) are excluded, matching what "done" already means everywhere else in the app.

**Why.** Originally scoped as two separate stories (S-26 weekly, S-27 monthly) but they're the same query at two different cutoffs — one card with two numbers is simpler than two near-identical cards, so they shipped together.

**Consequences.** `ActivityStats` is intentionally cross-course (no course/enrollment filter) — it's meant to answer "how much have *I* been doing," not per-course reporting; a per-course breakdown would be a different query, not an extension of this one.

---

## 23. Level-up celebration only past "learning" (2026-09-15)

**Decision.** `session/show.blade.php`'s practice-result panel shows a bigger, highlighted banner ("آفرین، رفتی یه سطح بالاتر!") instead of the plain from→to badge row, but only when `evidence.level_change.from !== 'not_started'` and the level actually went up. The `not_started → learning` transition — which fires on every lesson's very first attempt, learn or practice — keeps the plain row.

**Why.** The owner's engagement-ideas batch asked for level-up moments to feel more like a celebration. Doing that for `not_started → learning` too would fire on literally every lesson in every course (100+ times per course) and cheapen it; reserving the banner for `learning → familiar` and beyond — which requires sustained correct answers, not just starting — keeps it meaning something.

**Consequences.** The learn-activity completion panel (a separate, smaller spot earlier in the same file) still shows only the plain "to" badge — it's always a `not_started → learning` transition, so it was never a candidate for the banner treatment.

---

## 24. Review-retention row on the lesson page (2026-09-15)

**Decision.** The «وضعیت من» card on `lessons/show.blade.php` gets a new row — "N از M بار بلد بودی" — counting the learner's finished (`result_status != 'started'`) attempts on this lesson's practices where `evidence.source === 'review'`, and how many of those were `correct`/`correct_with_hint`. Hidden entirely (`$reviewCount === 0`) until the first review has actually happened.

**Why.** Part of the engagement-ideas batch: seeing concrete evidence that spaced repetition is working ("you've reviewed this 3 times and still know it") is motivating in a way an abstract "next review: Tuesday" date isn't — it's the same instinct as §21's streak/nudge and §23's celebration banner, applied to the review loop specifically.

**Consequences.** Computed inline in the Blade file (a plain `Attempt::where(...)` query), matching how `$level`/`$record` are already computed there rather than in `LessonController` — consistent with the existing pattern in that file, not a new one.

---

## 25. "5-minute mode": a shortcut to today's other due review (2026-09-15)

**Decision.** No new data model — `HomeController` picks the first `source === 'review' && status === 'scheduled'` item from the same priority-ordered list `$todayByEnrollment` is built from (`Planner::orderForLearner`), skipping whichever item is already `$primary`. Shown as a small standalone button ("فقط یه مرور سریع") that posts straight to `session.start-planned`, same route the regular Today list and primary CTA already use.

**Why.** `orderForLearner` already sorts due reviews first, so a due review is very often already `$primary` — the one case worth a dedicated shortcut is a *second* review (a different course, once >1 enrollment has something due) that's buried further down the Today list. Reusing the existing sorted list instead of a raw/unsorted lookup matters: the first scheduled review by `PlanItem` id does not reliably match `$primary`, since plan items are created in enrollment-iteration order, not priority order — picking the wrong one would occasionally offer a shortcut to a *different* review than the one already recommended, silently contradicting the main CTA. `HomeTest` covers exactly this ordering trap with two courses whose creation order is deliberately the reverse of their priority.

**Consequences.** The button only ever appears when a second review is genuinely available; nothing new to maintain if none is.

---

## 26. Cross-course lesson search: a plain LIKE scan, no index (2026-09-15)

**Decision.** `SearchController` (`GET /search?q=…`, a search icon in the nav) runs one query — `lessons.title|summary|content LIKE '%q%'`, `with('course')`, capped at 40 — across **every** course, not just the learner's own enrollments, because lesson content is already unrestricted by enrollment everywhere else in the app (`LessonController::show` never checks it). Each result shows the lesson's `summary` if it has one, else a ~140-character plain-text snippet cut from `content` around the first match (markdown syntax stripped with a regex, not a real parser). Query shorter than 2 characters shows a prompt instead of running.

**Why.** Asked for as part of the engagement batch — with three courses (and growing) it's genuinely hard to remember which course covered a given topic. A real search index (SQLite FTS5, ranking, stemming) is meaningfully more infrastructure for a two-person app with a few hundred lessons total; a LIKE scan answers "which lesson was that in" fine at this scale and needs no new tooling, migration, or reindexing step to keep in sync with content edits.

**Consequences.** Revisit if the catalog grows enough that LIKE gets slow or noisy (no relevance ranking — results are alphabetical by title) — SQLite FTS5 is the natural next step, not a rewrite, since it would replace the query inside `SearchController` without touching the route or view.

---

## 27. «دوستان»: everyone on the install, no friend graph (2026-09-15)

**Decision.** `GET /friends` lists every `User` (`orderByDesc('streak_count')`), each row showing name, streak (if any) and this week's practice count via the existing `ActivityStats` (§22). No follow/request model — §1 already scopes this whole app to its author plus one or two friends, so "everyone on the install" and "your friends" are the same small set; a real social graph would be pure overhead here.

**Why.** Last of the engagement-ideas batch — light, low-pressure mutual visibility ("did my friend show up today") without building an actual social feature. Reuses `ActivityStats` rather than a new query.

**Consequences.** This does not scale past a handful of users by design — it lists literally everyone with an account, with no privacy control beyond "who has a login." Revisit (add an actual friend/follow relation) only if the install ever grows past the couple of people it's built for (§1).

---

## 28. Weak spots + seen-vs-mastered: two pedagogy pieces, not the whole list (2026-09-15)

**Decision.** Asked (as an educational-systems-analyst brainstorm) for further learning-science techniques beyond content and engagement, and offered seven; scoped down to the two judged genuinely worth it at this app's size (2-3 learners), the rest explicitly rejected as low value for that scale — not deferred, decided against:

- **Weak spots** (`App\Services\Insights\WeakSpots`, `/weak-spots`): lessons with ≥2 incorrect *finished* attempts (`result_status != 'started'`), most-wrong-first, cross-course. A count links in from Home's activity-recap card when > 0.
- **Seen vs. mastered** (`CourseController::show`'s `$seenLessonIds`, a small check mark next to the level badge in the course lesson table): a lesson's `learning` level currently conflates two different states — watched but never practiced, vs. practiced but not yet succeeding — because finishing the learn activity alone already creates a `MasteryRecord` at that level (§3's DELTAS gives `completed` a 0 delta, but `firstOrCreate` still sets the level). Rather than changing that mastery math (deliberately untouched pre-§9-revisit, pending S-24's real-use tuning), the mark is a separate signal computed straight from `Attempt`, shown regardless of level (a lesson can in principle reach `familiar`+ via free practice before its learn activity is ever finished, which is itself informative).

**Why (the other five, rejected).** *Interleaving* — real research backing, but the benefit shows up in large-cohort studies; a solo learner working through one course mostly sequentially won't perceive it, and it touches `Planner`'s core selection logic for benefit that's hard to verify at n=1. *Confidence calibration* — adds a click to every practice for a statistic more suited to a large, long-running cohort than personal use. *Concept map* — the existing curriculum sidebar (level dots, current lesson highlighted) already does this job for course structures that are mostly linear (`prerequisites` defaults to "the previous lesson"). *Adaptive difficulty* — most lessons have only 2-3 authored practices total, too shallow a pool for an adaptation algorithm to have much to choose between; revisit if a lesson's pool depth grows a lot (e.g. from §18's AI-generated reviews accumulating over a long time). *Pretesting* — genuinely cheap (could reuse an existing practice as a pre-video guess) and not rejected on merit, just not asked for in this round.

**Consequences.** `WeakSpots::MIN_INCORRECT = 2` is a guess, not tuned against real use — a single mistake is normal and shouldn't flag a lesson. Revisit alongside S-24. The seen-mark only appears on the course page's lesson table, not the lesson-page curriculum sidebar (§15) or anywhere else — that sidebar is already visually compact (dots, not badges) and adding it there would clutter it for comparatively little gain over the course-page table, which is the natural whole-course overview.

---

## 29. Home's «امروز» list grouped by course (2026-09-15)

**Decision.** `home.blade.php`'s Today card now renders a small `bg-surface2` section header (course title + "N از M" for that course's items) above each course's items, replacing the old per-row course-name tag that was `hidden` below the `sm` breakpoint. Each course's internal item order is unchanged (`Planner::orderForLearner`'s reviews-first rule still applies within the group); `$primary`'s highlighting and the top "ادامه‌ی یادگیری" card are untouched — this only reshapes the full list underneath.

**Why.** Confirmed with the owner: the spaced-repetition algorithm itself (§2-3, §18, §24) needs no change. The actual scaling problem as course count grows is that the list was flat with the only course indicator invisible on mobile — makes "which course was this again" a real question with 2+ courses, worse with more. A global cross-course daily review cap was discussed and *deferred, not rejected*: `Planner::week()` already gives forward visibility into review load, so it's not clearly needed yet.

**Consequences.** `HomeTest` covers the grouping directly (two courses, both headers visible, per-course counts correct). If a course ever has zero items today it simply has no header — `$todayByEnrollment` only contains enrollments with something scheduled, so there's nothing to suppress.

---

## 30. Mobile pass: nav overflow and a page-wide horizontal-scroll bug (2026-09-15)

**Decision.** Two real bugs found by actually screenshotting the app at phone width (390px, headless Chrome via CDP — no tool for this existed in the repo, so a throwaway script was written and discarded, not committed) rather than reasoning from Tailwind classes alone:

1. **Nav overflow.** `layouts/navigation.blade.php`'s middle link row (خانه/همه‌ی دوره‌ها/هفته/دوستان) had no responsive handling at all and visibly collided with the search icon and avatar below `sm`. Fixed by hiding that row (`hidden sm:flex`) and the standalone search icon (`hidden sm:flex`) below `sm`, replaced by a hamburger button (new `menu` icon) opening the existing `x-dropdown` component with all five destinations (four links + search) as one list — same dropdown primitive the avatar menu already uses, not a new mechanism.
2. **Page-wide horizontal scroll.** `.page`'s `grid` (used by Home, the course page, and others) let a wide-content descendant anywhere inside blow out the *entire page* horizontally, because CSS Grid items default to `min-width: auto` rather than shrinking to their track. Fixed with one rule, `.page > * { min-width: 0; }` — the standard fix for this well-known Grid/Flexbox interaction, and it resolved every instance found (Home's streak/progress row, the Continue-Learning alternatives list, Today's per-item rows) in one place rather than patching each separately.

The course page's lesson table (fixed-width `سطح`/action columns that plainly don't fit 358px of real content) was left as a table, wrapped in `overflow-x-auto` — the sanctioned exception for tables (see artifact/responsive-design conventions) rather than a card-based mobile redesign, which wasn't asked for.

**Why.** Asked directly to make sure the mobile design holds up; reasoning about Tailwind classes without rendering them missed both bugs — the grid bug in particular wouldn't have been found by inspecting any single component in isolation, since no individual element was "wrong," only their interaction with the shared `.page` grid.

**Consequences.** `.page > * { min-width: 0 }` applies globally to every current and future page built on `.page`'s grid — a good default, but means a future wide-content bug inside `.page` will now correctly *scroll within its own element* (if it opts into `overflow-x-auto` itself) instead of silently blowing out the whole page; it won't auto-fix new tables/wide content, just stops them from taking the page down with them. No screenshot tooling was added to the repo — this was verified ad hoc, so a future mobile change should get the same manual check, not an assumption that Tailwind classes alone guarantee correctness.

---

## 31. Accessibility pass (S-44..S-49): labels, keyboard, contrast, progress semantics (2026-09-15)

**Decision.** Six fixes, found by actually grepping/computing rather than assuming:

- Every icon-only `<button>`/`<a>` now carries `aria-label` alongside (not instead of) its existing `title` — `title` alone works as a fallback accessible name but is weaker (inconsistent screen-reader support, tooltip-only visible hint). One button (`lessons/show.blade.php`'s mobile sidebar-close `×`) had neither and is now the one place `title` was added fresh.
- `x-dropdown` (used by the nav's hamburger and account menus) closes on `Escape` now (`@keydown.escape.window`, one line in the shared component covers every instance), and both trigger buttons carry `aria-haspopup="true"` + `:aria-expanded="open.toString()"`. The lesson-page mobile curriculum drawer got the same `Escape`-closes treatment since it's the same disclosure pattern.
- `search/index.blade.php`'s query input has a real (visually-hidden) `<label>` instead of relying on `placeholder` alone.
- `--faint` moved from `#98a0b0`/`#5c6474` (light/dark) to `#616c7a`/`#818999` — measured before and after with the actual WCAG relative-luminance formula (not eyeballed): every background it appears against (`bg`, `surface`, `surface2`, both themes) now clears 4.4:1+, up from as low as 2.43:1. `--muted` was already compliant and untouched.
- `x-level-bar` (the stacked mastery-distribution bar) gets `role="img"` + a computed `aria-label` summarizing the segments in Persian ("۵ درس مسلط، ۳ درس آشنا، …") — a multi-segment bar has no single value, so a text summary is the correct equivalent, not `aria-valuenow`. The two genuinely single-value bars (lesson-page course-completion, Home's daily-minutes) got `role="progressbar"` + `aria-valuemin/max/now` instead.

**Why.** Found on a UI/UX review the owner asked for, then a dedicated accessibility question. Every item was verified against the actual DOM/CSS (grep for `iconbtn`/`aria-label`, read the dropdown component, compute contrast ratios) rather than assumed, matching how §30's mobile bugs were found — reasoning about classes in isolation missed real issues there too.

**Consequences.** `--faint` and `--muted` are now visually closer to each other than before (their contrast ratios differ by less than they used to) since compliance took priority over maximizing the three-tier ink/muted/faint visual hierarchy — still distinguishable, just less dramatically. Any new icon-only control should follow the same `title` + `aria-label` pairing; any new disclosure (dropdown/drawer) should reuse `x-dropdown` or replicate its `Escape` handling rather than inventing a new pattern without it.
