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

---

## 32. Home: streak/progress merged into one weighted card with the quick-review shortcut (S-38, S-39; 2026-09-15)

**Decision.** `home.blade.php`'s streak count, today's-minutes progress bar, and the "فقط یه مرور سریع" button were three separate, thin, low-weight elements stacked above the main "ادامه‌ی یادگیری" card — a plain line of text plus an unrelated floating button. Merged into a single `.card`: the streak gets an icon badge (rounded warn-tinted square) and a bold two-line number/label instead of one small inline sentence; the progress bar is thicker (`h-2.5` vs `h-2`); and the quick-review button now sits inside the same card as a third flex item instead of floating alone in its own row. Verified with real screenshots (CDP, desktop 1440px and mobile 390px, using a temporary enrollment + streak set via tinker and removed afterward) rather than just reading the classes.

**Why.** Both were flagged in the UI/UX review: the streak/progress row was meant to be the motivational centerpiece (§21) but read as an afterthought, and the quick-review button felt disconnected from the flow it's actually part of. Putting all three in one card fixes both at once since they're the same "today at a glance" concept.

**Consequences.** The card is conditionally rendered only when at least one of the three has content (same as before, just OR'd across all three instead of two), so it still disappears cleanly for a user with no streak, no plan, and no due review. `sm:flex-wrap` was added so the row degrades gracefully if a future addition makes three items too wide for a mid-size viewport.

---

## 33. Course page: collapsible lesson sections + jump-to-section nav (S-41; 2026-09-15)

**Decision.** `courses/show.blade.php`'s lesson table used to render every lesson flat, with a plain (non-interactive) divider row wherever `section` changed — for the two real courses (100+ lessons each) that's a very long undifferentiated scroll. Reworked to:

- Group lessons by `section` in PHP (`groupBy`) instead of detecting boundaries mid-loop, one `<tbody>` per section.
- Each section's header row is now clickable (`@click="open[i] = !open[i]"`) and its lesson rows carry `x-show="open[i]"`, mirroring the exact pattern `lessons/show.blade.php`'s curriculum drawer already uses for its own collapsible sections (chevron rotation via `::class`, same rotate-90/-rotate-90 convention) rather than inventing a new one.
- A row of section-name badges above the table jumps to any section: clicking one forces that section open (`open[i] = true`) and scrolls its `<tbody>` (`x-ref="section-{i}"`) into view — so jumping to a currently-collapsed section always works, never lands on a hidden row.
- Default state: the section containing the learner's next not-yet-familiar, unlocked lesson starts open (falls back to the first section if everything is done or nothing is enrolled); every other section starts collapsed. A course with only one (or zero) named sections skips both the badge row and the collapse affordance entirely — nothing to jump to.
- Verified interactively, not just visually: a CDP script (login, click a section header, click a jump badge) confirmed section 1 toggled from 0/9 to 9/9 visible rows, the target jump section went from 0/10 to 10/10 visible, and `window.scrollY` moved from 0 to 1513 — actual Alpine reactivity and scroll behavior, not just a static screenshot.

**Why.** Flagged in the UI/UX review and judged the most valuable finding of that batch since it affects daily use (returning to a long course) directly, not just first impressions.

**Consequences.** The default-open section is now the "next lesson" section rather than always the first — correct for a learner resuming partway through a course, but means a brand-new user with no progress sees section 1 open (same as before) while a returning user on section 7 sees section 7 open on page load, not section 1. Any future per-section metadata (e.g. a "done" count in the header) should read from the same grouped `$sections` collection rather than re-deriving boundaries from the flat list.

---

## 34. Badge/pill color cleanup: kind badges go neutral, one palette per meaning (S-42; 2026-09-15)

**Decision.** Found a real, confirmed collision, not just a subjective "too many colors": `x-plan-item-badge` (learn/review/practice "kind") reused the `badge-l0..l4` mastery-level palette (l1=blue for یادگیری, l2=green for مرور, l3=orange for تمرین) — the *same* palette `x-level-badge` uses for mastery. `week.blade.php`'s review list puts both in one row (`badge-l2` "مرور" next to a level badge that can *also* render `badge-l2` for "آشنا"), so two adjacent pills could show the identical green for two unrelated meanings. Fixed by:

- `x-plan-item-badge` and the three raw `badge-l1/l2/l3` "kind" badges (`week.blade.php`, `session/show.blade.php`) now render as neutral `badge-ghost` + a small icon (▶ یادگیری, ⟳ مرور, 💡 تمرین) instead of a colored pill. `badge-l0..l4` is now used *only* by `x-level-badge`, so a colored pill from that palette unambiguously means mastery level anywhere in the app.
- `session/show.blade.php`'s nav (the worst stacking case — up to 4 badges: kind + kind + form + difficulty) dropped the standalone "تمرین" pill entirely: `isLearn === false` already implies practice, and the form badge right next to it (e.g. "چندگزینه‌ای") says which kind of practice, so the plain "تمرین" label was pure repetition. Verified with a real attempt (practice, review-sourced, "intro" difficulty): nav now shows مرور (neutral) + چندگزینه‌ای (neutral) + مقدماتی (the one colored pill, badge-ok) — three badges, one of them carrying color, instead of four with three fighting for attention.

**Why.** Flagged in the UI/UX review ("badge/pill overload... each with its own color, stacking up, worst on mobile"); the week-view color collision confirmed it wasn't just visual noise but an actual meaning conflict.

**Consequences.** `ok`/`warn`/`bad` still colors difficulty and result badges (unchanged — those two scales don't co-occur with each other in a way that collides, and both genuinely benefit from standing out). Any new "kind" or "category" indicator added later should default to `badge-ghost` + icon rather than reaching for `l0-4`/`ok`/`warn`/`bad`, which are now reserved for mastery level and outcome-quality respectively.

---

## 35. Course catalog cards get a per-course identity and a footer stat row (S-36; 2026-09-15)

**Decision.** `courses/index.blade.php`'s cards were title + description + a lonely lesson count, all plain text — nothing distinguished one course from another, and there was a lot of unused vertical space. Added, without new dependencies or per-course authoring:

- A monogram avatar (first character of the title) in a rounded square, colored by a deterministic per-course hue (`(id * 137) % 360` — golden-angle spacing keeps adjacent course IDs visually distinct even though nothing is hand-picked per course).
- A footer stat row (lesson count + total estimated hours, separated by a top border from the description) instead of one faint lesson-count line floating at the bottom. `CourseController@index` now also pulls `withSum('lessons as lessons_minutes_sum', 'estimated_minutes')` — one extra aggregate column, no N+1 (still no per-lesson data loaded on this page, unlike the course show page's fuller progress bar, which needs `lessons.masteryRecords` and is too expensive to duplicate here for a browse view).

**Why.** Flagged in the UI/UX review: sparse cards, no per-course visual identity, gets worse as more courses are added — the monogram scales to any number of future courses without needing a color/icon assigned by hand each time.

**Consequences.** The hue is derived purely from `id`, so it's stable for a given course's lifetime but has no relation to its topic/subject — purely a scan-ability aid, not a taxonomy. A future "course category" concept, if added, should replace this hash rather than layer on top of it.

---

## 36. Empty states get an icon, a reason, and a next action (S-37; 2026-09-15)

**Decision.** Two empty states read as dead ends rather than a designed state:

- `friends/index.blade.php` always lists at least one row (the user themself), so it never showed a true "empty" message at all — a 2-3 person install just quietly shows a list of one, no acknowledgement or next step. Now, when `$users->count() <= 1`, a card above the list explicitly names the situation ("فعلاً فقط خودتی این‌جا") and gives the one meaningful next action: a link to `/register`, plus the `REGISTRATION_CODE` itself (when the install has one configured — `config('app.registration_code')`, now passed from `FriendsController`) in a copyable-looking monospace chip, so inviting someone doesn't require the owner to go dig the code out of `.env` themselves.
- `weak-spots/index.blade.php`'s empty state was one muted sentence in a plain card, no icon, no action — despite being *good* news (nothing to fix). Gave it the same treatment as the friends card: an icon (trophy, `--ok`-tinted) reframing it positively, and a button back to `/home` ("ادامه‌ی یادگیری") since continuing to learn is the actual next action, not a dead end.

**Why.** Flagged in the UI/UX review: empty states feel abandoned, with no next action; friends specifically called out as a place that should prompt an invite via the existing `REGISTRATION_CODE` mechanism.

**Consequences.** Both empty states now depend on a "next action" route (`register`, `home`) already existing and being appropriate — if registration is ever closed off entirely (no code and registration disabled outright), the friends prompt would need a different message than "here's the register page," but that's not the current state of the app.

---

## 37. Sample course removed from the live catalog and excluded from `/_ops` auto-import (2026-09-15)

**Decision.** `content/sample-course/` (the 3-lesson fixture used by `CourseImporterTest`/`DeployTest` and mentioned in README's local-setup snippet) had also ended up imported into the real course catalog — visible to actual users right next to the two real courses. Deleted the `Course` row (cascades to its lessons/activities via the existing FK constraints). To stop it from silently coming back on the next deploy: `OpsController::courseSlugs()` — the "every course under `content/`" sweep used by a bare `/_ops/import` (and shown by `/_ops/status`) — now excludes `sample-course` by name. It's still importable on purpose with an explicit slug (`/_ops/import?slug=sample-course`, or `php artisan content:import sample-course` locally), which is exactly how the tests and the README's demo step already use it.

**Why.** The sample course is a fixture for exercising the importer/deploy pipeline, not real content — it shouldn't appear in the catalog a real user browses, and a full `/_ops/import` (which every deploy runs) would have kept re-adding it after every manual deletion.

**Consequences.** Anyone relying on the old README step (`php artisan content:import sample-course # demo course`) for a from-scratch local setup still gets it, since that's an explicit slug, not the sweep — only the *implicit*, no-slug sweep changed. A future real course added under `content/` is picked up automatically as before; only this one specific fixture slug is special-cased.

---

## 38. `/_ops/delete-course` — the only way to remove a course without shell access (2026-09-15)

**Decision.** §37 stopped `sample-course` from being *re*-imported, but a host that had already run a bare `/_ops/import` before that fix still has the old `Course` row sitting in its database forever — `content:import` only adds/updates, it never deletes a course wholesale (`--prune` only removes lessons/practices *within* a course still being imported). Added `/_ops/delete-course?token=…&slug=…` (`OpsController::deleteCourse`): deletes the `Course` row (cascades to lessons/activities/mastery records/attempts/plan items via the existing FK constraints, same as any other `Course::delete()`). `slug` is required — hitting it with no slug returns an error instead of doing anything; there is no "delete everything" form.

**Why.** The deploy story is deliberately shell-free (README "Deploying to shared hosting" — no SSH, no cron, no queue worker; every maintenance action goes through `/_ops/…`). Without this, the only way to remove a wrongly-imported course from a live host would have been asking the owner to somehow run raw SQL or tinker, which shared hosting doesn't offer.

**Consequences.** Like every other `/_ops` action, this is guarded only by `OPS_TOKEN` in the query string — same trust model as `import --prune` (which was already destructive). Keep the token long and treat it as a secret; rotate it if it ever leaks into a shared log or browser history.

---

## 39. Course avatars: curated hex palette instead of a generated hue, and a title alignment bug fixed (2026-09-15)

**Decision.** Two follow-up fixes to the S-36 catalog cards, from the owner looking at the real deployed page:

- The per-course monogram's `hsl($hue, 65%, 55%)` formula (§35) could land on a muddy or, worse, semantically-confusing color — course id 1 happened to hash to almost exactly `--accent`/`--warn`'s orange, making that course's avatar look like a highlighted/primary element for no reason. Replaced the formula with `course_color()` (`app/Support/helpers.php`), a small curated array of 8 hand-picked hex colors (no orange/amber in it, on purpose) cycled by `id % 8` — same determinism, no risk of collision with a token that already carries meaning. Reused on Home's "دوره‌های من" list too (previously text-only), which doubles as more visual separation between entries.
- A course title starting with a Latin word (`dir="auto"`, e.g. "Python 3 Deep Dive — …") was computing its CSS `direction` as `ltr`, which also flips the browser's default `text-align: start` to the *left* — so the title text hugged the left edge of its box while the monogram avatar sat at the right edge (RTL flex order), leaving a large, awkward gap between them for every English-first title. Fixed with an explicit `text-right` (a physical value, not the logical `text-start`, which would still resolve left for an `ltr`-computed element) on both the catalog card and the Home list — `dir="auto"` still gets correct bidi *word* ordering, only the block's own alignment is pinned.
- Home's enrolled-course rows were flat, hairline-separated text stacked tightly with little breathing room. Restyled each as its own `card bg-surface2` chip (the same nesting pattern the primary card's "یا به‌جاش" alternatives list already uses) with the course avatar, giving each entry a clear visual boundary instead of one dense list.

**Why.** Direct owner feedback on the live catalog page: the icon/title pairing looked broken, and the Home course list felt cramped with no separation.

**Consequences.** `course_color()` is now the one place course-identity colors are defined — any future page showing a course badge/avatar should call it rather than re-deriving a color, to keep the "no orange, no semantic collision" guarantee in one place. The `text-right` override means a rare fully-Latin course title always right-aligns even though that reads slightly against a native LTR reader's instinct — acceptable since the app's layout (avatar position, RTL nav) already assumes right-anchored content throughout.

---

## 40. Enroll directly from the catalog page; every enrollment lands on the schedule form (2026-09-15)

**Decision.** Two related gaps: enrolling in a course was only possible from the course's own show page (the catalog just linked to it), and a fresh enrollment redirected back to `courses.show` with a flash message *suggesting* the learner go set a priority/daily-time — easy to miss, and the course sits outside the daily plan until that's done (DESIGN §4).

- `courses/index.blade.php`'s card is no longer one big `<a>`; the clickable title/description region is an inner `<a>`, and a footer row (sibling, not nested — a `<form>` can't legally sit inside an `<a>`) holds the stats plus a "برداشتن" button + form for courses the learner hasn't enrolled in yet, posting to the same `courses.enroll` route the show page already uses.
- `EnrollmentController::store()` now redirects a **new** enrollment straight to `enrollments.edit` (the priority/daily-time/status form) instead of back to the course page — same landing spot regardless of whether "برداشتن" was clicked from the catalog or the course page. An **already-enrolled** click still redirects to `courses.show` with "قبلاً این دوره رو برداشتی." — no reason to force them back into the schedule form for a no-op.

**Why.** Direct owner request: enroll from the catalog too, and always land on the schedule form right after so the course actually enters the plan instead of silently sitting unconfigured.

**Consequences.** Any future "enroll" entry point (there are currently two: catalog, course show) should redirect through the same controller action rather than duplicating the "new vs already-enrolled" branch, so the landing behavior stays consistent by construction.

---

## 41. Video player seeks 5s instead of 10s (2026-09-15)

**Decision.** `resources/js/player.js`'s Plyr instance had `seekTime: 10`; changed to `5`. This single option drives the rewind/fast-forward buttons and the ←/→ and J/L keyboard shortcuts uniformly (Plyr's own behavior, not something this app wires up separately) — updated the i18n comment documenting the keyboard shortcuts to match.

**Why.** Direct owner request.

**Consequences.** None beyond the seek granularity itself — no other code reads or depends on the seek amount.

---

## 42. Default database switched from SQLite to MySQL (2026-09-15)

**Decision.** `DB_CONNECTION` defaults to `mysql` now (`.env.example`, `.env.production.example`), not `sqlite` — §12 originally picked SQLite specifically for the zero-dependency shared-hosting story, but the owner wants MySQL instead. Nothing in the schema or app code was SQLite-specific (checked: no raw SQL, no SQLite-only column types; `php artisan migrate:fresh` ran clean against MySQL 8 on the first try), so this was a config-only switch plus two small generalizations:

- `OpsController::status()`/`migrate()` and `deploy.php` (deploy branch) hardcoded `config('database.connections.sqlite.database')` and touched a SQLite file into existence before migrating. Both now branch on `config('database.default')`: the SQLite file-creation path only runs when that's still the active connection; `status` reports a file path/size for SQLite or attempts a real `DB::connection()->getPdo()` connection and reports host/database/reachability for anything else.
- README's local setup now leads with a one-line `docker run` for a local MySQL 8 container (the fastest zero-install path) and the shared-hosting deploy doc adds "create a database in cPanel's MySQL Databases tool first" as a step before creating `.env`.

SQLite is **not removed** — every doc still documents it as a valid alternative (comment out the `DB_*` block, or a single request-param note in `.env.production.example`), and `OpsController`/`deploy.php` both still handle it correctly. Test suite still runs on an isolated SQLite `:memory:` database (`phpunit.xml`), unrelated to and unaffected by the app's runtime connection — kept as-is since it's faster and needs no server.

**Why.** Direct owner request.

**Consequences.** Local dev (and any future host) now needs a reachable MySQL/MariaDB server rather than "nothing, SQLite is just a file" — a real but small trade, and Docker makes the local side a one-liner. Anyone deploying fresh to shared hosting now has one extra one-time step (create the database in cPanel) before the usual `.env` + `/_ops/migrate` flow.

---

## 43. "انجام دادم" (mark done): a sticky, learner-declared completion signal separate from mastery level (2026-09-16)

**Decision.** Real use surfaced four related problems, reported by the owner and root-caused by reading the code rather than guessing:

1. **Persian captions never actually applied.** `resources/js/player.js` had `storage: { enabled: false }` on the Plyr config. Plyr re-runs its internal `captions.setup()` after every language switch, which falls back to the configured default language whenever it can't read back what it just tried to persist — so `storage: false` meant every click on the Persian subtitle option was silently reverted on the next tick (confirmed live via CDP: the menu's checkmark flipped, the caption overlay kept showing English). Fixed by removing that override (Plyr's own `'plyr'` localStorage key doesn't collide with this app's own `skillos.player.*` keys) and, as a defensive complement, forcing every non-default `<track>` to fetch its cues right after mount (browsers only eagerly load the default track).
2. **Enrolling felt like it hadn't "started."** `Planner`'s `isScheduled()` gate (and therefore anything showing on Home) requires `daily_time_minutes`, but nothing surfaced that requirement as an action — Home's "دوره‌های من" card and the course page both only had a passive, easy-to-miss muted line. Both now carry a real CTA straight to `enrollments.edit`.
3. **No "finish" affordance on the lesson page, and the sidebar circle never filled from watching alone** — deliberately, per §28 (finishing the learn activity alone is a 0-mastery-delta event), but there was no learner-facing way to *declare* a lesson done and have that stick.
4. **Tension between "I said I learned it" and the review system always resurfacing it.** The owner's own resolution, after discussion: keep spaced review running forever (that's correct, not a bug) but add one explicit, learner-controlled action whose completion is sticky — independent of any later dip in numeric mastery from a failed review.

The shipped design is a single button, "انجام دادم", on the lesson page (`lessons/show.blade.php`):

- **Gate** (`Lesson::allPracticesPassedBy()`): disabled until every practice activity has at least one `correct`/`correct_with_hint` attempt from that learner (a lesson with no practices has nothing to gate on). Re-checked server-side in `LessonController::markDone()`, not just hidden client-side.
- **Action** (`AttemptSession::markLessonDone()`): records a normal finalized `Attempt` on the lesson's `learnActivity`, tagged `evidence.source = 'lesson_done'` — reuses the entire existing attempt/finalize/mastery pipeline (no schema change; `evidence` was already a free-form JSON column).
- **Stickiness** (`Lesson::isMarkedDoneBy()`): the sidebar circle, the course-page checkmark, and the top progress counter now fill on *either* signal — real mastery ≥ «آشنا», **or** a `lesson_done` attempt existing for that lesson — computed via `max()` of the two, so a later failed review that knocks numeric mastery back down never un-fills a circle the learner explicitly earned. The mastery level *badge* itself is untouched by this — it keeps showing the honest, fluctuating number, since that's what should drive weak-spot detection and review calibration.
- A secondary, smaller bug fixed alongside this: `Planner::today()` only ever called `materialize()` once per enrollment per day (`! $existing->contains('enrollment_id', ...)`), even though `materialize()`/`candidatesFor()` were already written to be safely re-callable (budget computed from what's already scheduled, activities excluded once planned). Removed that redundant gate so the plan's own "امروز" list also tops up with a freshly-eligible next lesson the same day, instead of waiting for tomorrow — though the primary way to move at one's own pace through many lessons in a day remains free lesson-page navigation (never hard-locked) plus this new button, not the planner's suggested batch.

**Why.** Direct owner request, refined over a short back-and-forth: the owner explicitly rejected gating forward progress on a daily lesson-count or time budget ("مبنا نباید روز باشه"), and explicitly wanted one unified action rather than a separate "self-report you already know this" fast path.

**Consequences.** `mastery_records`/`attempts` schemas unchanged. `evidence.source` gains a fourth meaningful value (`lesson_done`, alongside `free`/`plan`/`review`). The «انجام دادم» gate is strict — a lesson with several practices requires all of them individually correct at least once, not just "attempted" — matching the owner's explicit answer over an initially-offered looser alternative.

---

## 44. «دوستان» becomes an admin-only progress dashboard; first `is_admin` flag on the install (2026-09-16)

**Decision.** Added `users.is_admin` (boolean, default `false`) via a migration that also sets it `true` for `moja@skillos.local` in the same `up()` — so the flag is correct immediately after `migrate --force` on any environment (local or production) without a separate manual step. `FriendsController` now does `abort_unless($request->user()->is_admin, 403)`, and both the desktop nav and the mobile dropdown menu (`layouts/navigation.blade.php`) hide the «دوستان» link entirely for non-admins.

The page itself changed from a lightweight "streak + this week's practice count" list (§27) to a per-user **all-time** progress dashboard, per the owner's explicit answer over "just this week" or "everyone gets this, not just me": three new numbers per learner, computed in `ActivityStats`:

- **زمان صرف‌شده**: `allTime()` — same shape as the existing `since()` used on Home, just without the date floor (sum of `estimated_minutes` across every finalized attempt ever, learn + practice alike — an approximation via authored durations, not tracked wall-clock time, consistent with how Home already reports time).
- **درس‌های انجام‌داده**: `lessonsDoneCount()` — distinct lessons at "آشنا"+ mastery **or** with a `lesson_done` attempt (§43's OR logic), reused here as a cross-user count rather than a per-lesson boolean.
- **تمرین‌های حل‌شده**: `practicesSolvedCount()` — distinct practice *activities* ever answered `correct`/`correct_with_hint`, not a count of attempts, so a practice reviewed and re-solved five times still counts once.

**Why.** Direct owner request: create an admin account (the owner's own — no separate account needed) and restrict cross-learner progress visibility to it.

**Consequences.** This is the first permission distinction on the install (previously every authenticated user could do everything). No admin UI to manage the flag yet — toggling anyone else's `is_admin` is a manual DB update, acceptable at this install's scale (owner + 1-2 friends). `FriendsTest` updated: the happy-path test now acts as an admin, plus two new tests for the 403 and the hidden nav link.

---

## 45. Pre-existing bug found while debugging a production 500: both nav dropdowns opened off-screen (2026-09-16)

**Decision.** The owner reported a 500 on lesson pages and the account/logout dropdown menu rendering outside the visible page after a production deploy. Investigated both live against `skillos.growwise.ir` via `/_ops/status` and `/_ops/migrate`:

- The **500** traced to the `is_admin` migration (§44) never having run on production — `/_ops/migrate` had not been hit since that deploy, so it was still pending; running it now applies cleanly. (Reasoned through, but not fully confirmed as *the* cause: a missing `is_admin` column with a `boolean` cast wouldn't itself throw — `getAttribute` finds the key in `$casts`, but `getAttributeFromArray` returns `null` for the missing column, and casting `null` through the boolean cast is `false`, not an exception. Ran `/_ops/optimize` afterward too, in case a stale bootstrap cache was compounding it. Asked the owner to reload and confirm; if it recurs, the real fix needs the actual PHP error from cPanel's error log, not further guessing.)
- The **dropdown-off-screen bug** was real, reproducible, and unrelated to any recent change — measured via CDP (`getBoundingClientRect()`) before touching anything: the account-menu dropdown sat at `left: -128px` in a 1400px viewport, and the mobile hamburger menu at `left: -10px` in a 390px one. Root cause: `resources/views/components/dropdown.blade.php`'s `align="left"` maps (in this RTL app) to `rtl:origin-top-right start-0`, which in RTL means `start-0` = `right: 0` — anchoring the menu's *right* edge at the trigger and expanding *further left*. Both of `navigation.blade.php`'s dropdowns (account menu, mobile hamburger) sit at the visual **left** edge of the RTL nav bar (inside the `ms-auto` group), so anchoring rightward-and-expanding-left pushes them straight off the viewport. Both now use `align="right"` instead, which maps to `end-0` (`left: 0` in RTL) — anchors the menu's *left* edge at the trigger and expands *rightward*, into the page. Re-measured after the fix: both dropdowns fully within the viewport at both 1400px and 390px widths.

**Why.** Owner bug report; the dropdown bug was found as a side effect of investigating the 500 (checked every interactive nav element on the page while diagnosing), not something separately requested.

**Consequences.** No visual regression expected — `align="right"` is this component's own default, so the fix is really "stop overriding it incorrectly" rather than a new code path. No test coverage added (this is a pure CSS/Tailwind logical-property positioning issue with no meaningful way to assert it via Laravel's HTTP test client; caught and verified via live CDP measurement instead).

---

## 46. Every `<x-icon>` size override was silently losing to the component's own default (2026-09-16)

**Decision.** Owner reported several badges/icons looking wrong: the sidebar mastery-level checkmark looked oversized, the top-nav search icon looked off-center. Measured both live via CDP rather than guessing:

- `resources/views/components/icon.blade.php` rendered `<svg {{ $attributes->merge(['class' => 'w-[18px] h-[18px']) }} ...>`. Blade's `merge()` *prepends* the component's default class string ahead of whatever the caller passed, so every call site that set its own size (`<x-icon name="check" class="w-2.5 h-2.5" />`, etc.) actually produced `class="w-[18px] h-[18px] w-2.5 h-2.5"` — two conflicting width/height declarations. Because Tailwind's generated stylesheet places arbitrary-value utilities (`w-[18px]`) after the fixed-scale ones (`w-2.5`, `w-4`, ...) it had scanned, `w-[18px]` won the cascade regardless of HTML class order. Confirmed via `getComputedStyle`: the search icon (wanted 16px) and the theme toggle icon (wanted 16px) both computed to 18px; the sidebar level-badge's check icon (wanted 10px, inside a 16px circle) also computed to 18px — bigger than its own circle, which is exactly the "تیک بزرگ" the owner saw. Every single `<x-icon>` call site in the app passes its own `class`, so this silently affected every icon everywhere, not just the two reported. Fixed by switching to `$attributes->class(['w-[18px] h-[18px]' => ! $attributes->has('class')])`, which only contributes the default when the caller passed no class at all, so a real size override no longer has anything to conflict with.
- The search icon's off-center look was a second, unrelated bug: `layouts/navigation.blade.php` had it on `class="iconbtn hidden sm:flex"`. `.iconbtn` itself is `grid place-items-center`; `sm:flex` overrides `display` to `flex` at that breakpoint, which drops `justify-items`'s horizontal centering (grid-only) while `align-items: center` (shared by both layout modes, so it survives) keeps vertical centering intact — net effect: the icon hugged the flex `start` edge (the visual *right* edge, in this RTL app) horizontally while staying vertically centered. Measured before: button center `x=139` vs. icon center `x=146`. Changed to `sm:grid` (keeping `.iconbtn`'s own display type instead of switching to flex) — re-measured after: both centers at `x=139`.

**Why.** Owner bug report with a screenshot; root-caused by inspecting computed styles and generated CSS rather than adjusting sizes by trial and error.

**Consequences.** Every icon in the app now renders at the exact size its call site requests, which very slightly shrinks or grows icons that had been silently stuck at 18px their whole lifetime (search: 18→16, theme toggle: 18→16, sidebar check: 18→10, etc.) — a visual-only change, no test added (`php artisan test --compact` unaffected: 112/112 still pass). If any future `<x-icon>` call site is added *without* a `class`, it now correctly falls back to the 18px default instead of always being overridden by nothing.

---

## 47. Swept `python-deep-dive-1`'s remaining Persian-in-code-fence warnings (2026-09-16)

**Decision.** `docs/AUTHORING.md`'s house rule (code comments in English, established partway through C-02's authoring after `validate.py` started flagging it) had never been retroactively applied to C-01 (`python-deep-dive-1`), which was authored earlier (2026-09-14) and finished before the rule existed. `python3 tools/authoring/validate.py python-deep-dive-1` reported 41 lessons with at least one Persian-language inline comment inside a fenced code block. Extracted every flagged line across all 41 lessons (187 lines total) and translated each Persian comment to English in place — code itself untouched, only the comment text — verifying each line's surrounding context first for the handful that were ambiguous out of context (e.g. `integers-constructors-and-bases`'s `number *= sign  # قدر مطلق` really is computing an absolute value, confirmed by reading the surrounding `rebase_from_base10` function).

**Why.** Direct continuation of content-authoring work ("ادامه بده ساخت محتوا رو"); this was genuinely unfinished quality debt, not new scope — the same fix had already been applied reactively many times during C-02 (see the `C-02` entry in `docs/STORIES.md` for the running count), just never swept retroactively over C-01.

**Consequences.** `validate.py python-deep-dive-1` now reports 0 warnings (same as `complete-python-mastery`). Re-imported via `php artisan content:import python-deep-dive-1` to confirm the edited `.md` files still parse cleanly — same counts as before (106 lessons, 258 practices). No code/schema changes, so no new tests; `php artisan test --compact` still 112/112.

---

## 48. The auto-deploy hook was silently failing; made its failures visible instead of guessing why (2026-09-16)

**Decision.** The production DB was checked after a deploy and both the newly-authored course content and the just-imported one from S-56/§47's fix were missing — `/_ops/status` showed `courses on disk: complete-python-mastery, python-deep-dive-1` (the files were there, copied by the deploy), but the database had none of it. This is the second time a step that `deploy.php` is supposed to run automatically on every deploy (§45's pending `is_admin` migration was the first) turned out to have silently never run.

Root cause wasn't fully provable without host shell access, but the mechanism that made it *unprovable* was clearly wrong and is now fixed: `.cpanel.yml`'s post-pull task was `curl -sk ... || true`, and `deploy.php` printed its output only to the HTTP response — nothing persisted anywhere else. If the loopback curl failed for any reason (wrong port, TLS/SNI mismatch on that specific host, etc.), `|| true` swallowed the non-zero exit so cPanel's own deployment log showed success regardless, and there was no independent way to check whether `deploy.php` itself had ever actually run.

Fixed on the `deploy` branch (not `main` — `.cpanel.yml`/`deploy.php` only exist there):
- `.cpanel.yml`: dropped `|| true`, added `-f` to curl so a real HTTP-level failure now returns non-zero and shows up as a failed task in cPanel's own log instead of being hidden.
- `deploy.php`: every invocation — rejected (403) or not, and even a fatal error mid-script via a `register_shutdown_function` safety net — now appends its full output to `storage/logs/deploy-hook.log`, independent of whether the triggering curl itself succeeded or cPanel's log shows anything. Added `OpsController::deployLog()` / `/_ops/deploy-log` (main branch, shared code) to read the tail of that file without SSH.

Immediately re-ran the two missing steps by hand via `/_ops/migrate` and `/_ops/import` (both courses) to unblock production right away, independent of this fix.

**Why.** Direct owner report ("انگار مقدار دهی رو هاست انجام نشده") followed by an explicit ask to make future deploys reliably update the database, not just the files.

**Consequences.** README's git-based deploy section updated to match (curl flags, the log, and a new "always check `/_ops/deploy-log` afterward" step). This doesn't *prove* the loopback curl trick itself is reliable now — that still can't be verified without a real deploy-and-check cycle — but it guarantees the next failure (if any) is visible instead of silent, which is the actual gap that let two real bugs go unnoticed until the owner caught them from the outside. New test (`DeployTest::test_ops_deploy_log_reports_missing_or_tails_the_file`) covers the missing-file and tail-the-file cases of the new action; `php artisan test --compact` at 113/113.

---

## 49. A 10-course bundle becomes 10 separate `Course` rows, not one course with 10 sections (2026-09-16)

**Decision.** Owner dropped `course/Management course/` — 10 independent Udemy-style courses (559 lectures, 36G total) spanning requirements engineering, software architecture, product management, scrum, engineering management, and org/AI-era leadership — asking for a better name than the placeholder "Management course" and a discussion of how to structure it before any authoring started.

Recommended, and the owner confirmed, **10 separate `Course` rows** over one mega-course with 10 sections: enrollment, `daily_time_minutes` pacing, streak, the planner's candidate selection, and mastery/review are all scoped *per course* and implicitly assume one coherent subject learned at one pace. These 10 are unrelated professional topics (architecture ≠ product management ≠ people leadership) that the owner should be able to start/pace independently — exactly like the two existing Python courses today. This needed zero schema changes either way; the choice was purely about fitting the existing per-course semantics rather than stretching them.

The name **«مسیر رهبری فنی»** (owner's pick from three offered options) is a *conceptual* grouping only — how the 10 courses get introduced/talked about — not a new database entity. No "track"/"program" layer exists in the data model and none was requested; each of the 10 is cataloged as an ordinary standalone course with its own real Persian title (see `docs/STORIES.md` C-03 for the full list and slugs).

Depth: same full authoring bar as C-01/C-02 (practices+rubrics, key_points, common_mistakes, scenario-threading) for all 10 — owner's explicit choice over a lighter format, prioritizing consistent quality over faster throughput across ~559 lessons.

**Why.** Direct owner request, resolved via `AskUserQuestion` before any content work started (structure, name, depth, and whether to include an unrelated `course/scrum course/` folder — set aside, not part of the 10).

**Consequences.** Course count on the catalog page will grow from 2 to potentially 12; no catalog-page changes needed for this (it already lists an arbitrary number of courses). If a genuine cross-course "path" UI is ever wanted later (progress across all 10, a suggested order enforced by the app rather than just narrative framing), that's new scope requiring its own decision — not assumed here.

---

## 50. Fixed Persian text visually scrambling whenever it starts with an English word (2026-09-16)

**Decision.** Owner reported RTL text "falling apart" around English words across quizzes, practices, answers and key points. Root-caused with a live before/after screenshot rather than guessed: `dir="auto"` was applied on ~37 elements holding *authored Persian prose* (key_points, common_mistakes, mcq options, practice prompts/feedback/hints, expected answers, titles, summaries). `dir="auto"` picks an element's base direction from its *first strong-directional character* — so any key point/sentence that happens to start with an English term (`bool زیرکلاس int است؛ True == 1 و...`) got its **entire line** flipped to an LTR base direction, not just that one word. That put the bullet marker on the left, reversed the whole line's flow, and left the embedded Persian segments as the ones being visually reordered — the opposite of what `dir="auto"` was presumably added for. One spot (`courses/index.blade.php`) had already band-aided the symptom with a `text-right` override and a comment describing the exact mechanism, without fixing the cause.

Since every learner-visible string in this app is Persian prose by house rule (English only as embedded technical jargon — never English-first), `dir="auto"`'s "guess the language" heuristic was never the right tool here; the base direction is always known statically. Removed `dir="auto"` from all 37 authored-content spots (they now correctly inherit `dir="rtl"` from `<html>`), removed the `text-right` band-aid alongside it, and kept `dir="auto"` on the 4 spots where the content genuinely isn't authored/known-language — the search box, the practice-answer textarea, the "my last answer" display, and a person's own name.

Separately, embedded English terms (inline `` `code` `` spans, acronyms) still need to render as a stable left-to-right *unit* inside a correctly-RTL-based paragraph, or reordering can still happen at the run level even with the right base direction. `.prose-fa code` already had `unicode-bidi: isolate` for this, but the global `code, pre, .mono` rule (used by key_points/common_mistakes and other non-`.prose-fa` spots) only had `direction: ltr` — insufficient on its own. Added `unicode-bidi: isolate` there too.

**Why.** Owner bug report, reproduced and root-caused via a live CDP screenshot of a real key point (`booleans.practices.json`'s `bool زیرکلاس int است...`) before touching anything, then re-verified after the fix on the same content.

**Consequences.** Purely a markup/CSS fix — `php artisan test --compact` unaffected (113/113). Any future Blade view showing authored Persian text should not reach for `dir="auto"`; only use it for genuinely user-supplied or language-uncertain content (matching the 4 spots kept here).

---

## 51. Practice session overhaul: per-practice status, real attempt history, a true cancel path, and no more native `confirm()` (2026-09-16)

**Decision.** Four related bugs from the same owner report (items 2–5 of the 5-bug list, item 1 was §50):

1. **Only the lesson-level "انجام دادم" card showed any completion signal — nothing per practice.** `lessons/show.blade.php`'s practice list had no status at all; clicking "شروع" on an already-solved practice silently created a brand-new blank attempt every time, with no "you already tried this" framing and no way to see past answers. Fixed with `LessonController::show()` computing each practice's *latest* attempt status (`solved` / `unsolved` / `in_progress` / untouched, batched in one query to avoid N+1) and a new `SessionController::open()` entry point that the practice list now links to (`GET activities/{activity}`) instead of POSTing straight to `start()`: it resumes an open attempt, or shows the most recent finished one (result + a full history list, see below) instead of blindly starting fresh. `start()` itself is untouched and still used by the explicit "دوباره همین تمرین" (try again) button, which must always begin a genuinely new attempt.
2. **No attempt history anywhere.** `session/show.blade.php` only ever showed the *current* attempt's own last response. `SessionController::show()` now also loads every other `Attempt` on the same activity (newest first) and the view lists them — date, result badge, response — in a collapsible "تلاش‌های قبلی من" section.
3. **No real way to back out of a practice.** The only actions were "ارسال پاسخ" (submit) and "بی‌خیال، جواب رو نشون بده" (give up — finalizes as incorrect and reveals the answer, a real penalty). Neither was a true no-consequence exit. Added `SessionController::cancel()` (`DELETE session/{attempt}`): deletes an open attempt outright — nothing recorded, mastery untouched — wired to the nav's exit icon, but only when there's something to lose (an open, non-learn attempt); otherwise it's still a plain link back to the lesson.
4. **The give-up confirmation was a native `window.confirm()`** — the only one in the whole app (checked: `grep -rn "confirm(" resources`), unstyled browser chrome sliding down from the top of the tab. Replaced both the give-up confirmation and the new cancel confirmation with `<x-modal>` (already used once, for account deletion) — same look, same escape/backdrop/focus-trap behavior as the rest of the app, no JS dialog anywhere in the session flow anymore.

A fifth, smaller item bundled in: **practice-level attachments.** `attachments` previously only existed on lessons (`course.json`); `CourseImporter` now also reads them off individual practices (`*.practices.json`, same `{title, file|url}` shape, resolved the same way) and `session/show.blade.php` renders them under the prompt when present. Documented in `docs/AUTHORING.md` §3.3.

**Why.** Direct owner bug report from real use of the platform, fixed as a prerequisite before starting C-03's authoring (the owner's explicit condition).

**Consequences.** New routes: `GET activities/{activity}` (`session.open`) and `DELETE session/{attempt}` (`session.cancel`); `POST activities/{activity}/start` (`session.start`) keeps its exact old behavior for the "try again" case. 7 new tests across `SessionTest`, `LessonPageTest`, `CourseImporterTest` cover the new open/resume/cancel/history/attachments behavior; full suite at 120/120. Verified live via CDP end-to-end: badges on the lesson page, the styled cancel and give-up modals, an actual attempt getting deleted on cancel, and a real two-attempt history rendering correctly.

---

## 52. Optional English lesson text, per-course from C-03 onward (2026-09-16)

**Decision.** Owner wants an English version of the lesson body available starting with C-03 («مسیر رهبری فنی») — C-01/C-02 stay Persian-only, not backfilled. Added `lessons.content_en` (nullable `longText`), a parallel optional `<slug>.en.md` file next to `<slug>.md` (mirrors the existing `.en.vtt`/`.fa.vtt` subtitle-pair convention), and a فارسی/English toggle in `<x-lesson-content>` next to the ویدیو/متن tabs — shown only when `content_en` is present, so every existing course/lesson is completely unaffected. The English pane renders with `dir="ltr"` (everything else in the app assumes `dir="rtl"` from `<html>`, per §50).

Authoring workflow: write the English version **from the transcript**, not as a translation of the already-written Persian — the structural choices (headings, which examples, what to defer to a later lesson) are already settled once the Persian exists, so reuse them, but write natural English prose rather than translating literally. `key_points`/`common_mistakes`/practices stay Persian-only for now; only the lesson body is bilingual.

Retroactive scope, confirmed via `AskUserQuestion`: the 18 lessons of C-03's «مقدمه و مبانی» section (already authored Persian-only before this request) get English text added too, not just lessons written from this point forward — the course itself is the boundary, not the point in time.

**Why.** Direct owner request, alongside an explicit cost/effort tradeoff conversation: producing English text roughly doubles the writing volume per lesson, which cuts against the same conversation's ask to reduce token burn — flagged this directly rather than silently absorbing the cost. The "write English from transcript, not translate from Persian" workflow keeps the incremental cost closer to +30–50% than +100%, since the hard part (structure, example selection, scope boundaries with neighboring lessons) is already decided.

**Consequences.** `CourseImporter` reads `<slug>.en.md` when present (silently skipped otherwise, `content_en` stays `null`) — no `validate.py` requirement added, since this is optional per-course, not a house rule for every course. 2 new tests (`CourseImporterTest`, `LessonPageTest`) cover the import and the toggle's visibility. `php artisan test --compact` at 122/122.

---

## 53. Video streaming from other devices — a permanent Cloudflare Tunnel, replacing `MEDIA_BASE_URL=http://localhost:8765` (2026-09-16, in progress)

**Problem.** Owner reported videos don't play at all from a phone or a second laptop. Root cause: production's `MEDIA_BASE_URL` is `http://localhost:8765` (§25's "media stays on the owner's machine" design, via `tools/media-server.py`) — `localhost` is a deliberate browser-security trick (an `https://` page is allowed to load `http://localhost` media without a mixed-content block), but it only resolves to *the viewer's own device*. On the owner's daily machine (where `media-server.py` actually runs) this works by coincidence; on any other device, `localhost:8765` points at nothing.

**Decision.** Expose `media-server.py` through a **named, permanent Cloudflare Tunnel** at `https://media.growwise.ir`, replacing the `localhost` URL. Two alternatives considered and rejected: Tailscale (private VPN — rejected because the owner wants this reachable from arbitrary devices without installing an app on each one first) and a Cloudflare *quick* tunnel (`trycloudflare.com` — rejected because its URL changes on every restart, and updating `MEDIA_BASE_URL` requires a manual cPanel edit each time, so instability there is a recurring chore, not a one-off).

**DNS approach — corrected 2026-09-16.** Originally planned as a subdomain-only NS delegation (`media.growwise.ir` as its own Cloudflare zone, rest of the domain untouched). That plan turned out to be **Enterprise-plan-only** (verified live against `https://developers.cloudflare.com/dns/zone-setups/subdomain-setup/` — the availability table shows "No/No/No/Yes" for Free/Pro/Business/Enterprise) and is not available on Cloudflare's free tier. Corrected, owner-confirmed (via `AskUserQuestion`, chose the free full-domain option over buying a separate domain) plan: **move all of `growwise.ir`'s DNS to Cloudflare** (free plan), recreating every existing record there, then cut over nameservers at Parspack. `media.growwise.ir` then becomes just another record inside that same zone.

**Existing records at Parspack to recreate on Cloudflare before nameserver cutover** (captured 2026-09-16 via `dig ... @1.1.1.1`):
- `A growwise.ir → 45.159.149.29` (apex — DNS only / grey cloud, since it's cPanel, not typically proxied)
- `A www.growwise.ir → 45.159.149.29`
- `A skillos.growwise.ir → 45.159.149.29`
- `A mail.growwise.ir → 45.159.149.29`
- `A webmail.growwise.ir → 45.159.149.29`
- `A cpanel.growwise.ir → 45.159.149.29`
- `MX growwise.ir → 10 mail.growwise.ir`
- `TXT growwise.ir → "v=spf1 mx a:mailgw-g.getway.biz a:imailgw-g.getway.biz -all"` (SPF)
- `TXT _dmarc.growwise.ir → "v=DMARC1; p=none"`
- `TXT default._domainkey.growwise.ir → "v=DKIM1; k=rsa; p=..."` (DKIM — long key, must be copied verbatim, not retyped)

All mail-related records (`MX`, SPF/DKIM/DMARC `TXT`, and the `mail`/`webmail`/`cpanel` `A` records) must be set to **DNS only** (grey cloud) in Cloudflare, not proxied, or mail and cPanel access will break.

**Status as of 2026-09-16/17, DNS migration done, tunnel itself blocked — pivoted plan, resume here:**
- [x] `growwise.ir` fully migrated to Cloudflare DNS (free plan). All records from the list above recreated and verified resolving correctly (`dig ... @1.1.1.1`/`@8.8.8.8`), including one record missed on the first pass (`englishos.growwise.ir`, another cPanel-hosted subdomain, added after being spotted in cPanel's domain list). Nameservers cut over at Parspack (`ns1.parspack.co`/`ns2.parspack.co` → `cecelia.ns.cloudflare.com`/`nitin.ns.cloudflare.com`, NS3/NS4 left empty), DNSSEC confirmed already off, propagation confirmed complete. `skillos.growwise.ir` and `growwise.ir` both verified still loading correctly over HTTPS post-cutover.
- [x] `cloudflared tunnel login`, `tunnel create skillos-media` (ID `b791c809-63b9-4a31-9e5f-078a984d7b4a`), `tunnel route dns skillos-media media.growwise.ir`, `~/.cloudflared/config.yml`, and `cloudflared service install` (systemd) all completed successfully.
- [x] **Blocker found 2026-09-17: the systemd service never connects — hard-blocked at the network level.** `cloudflared`'s connectivity pre-checks show DNS resolution to `region1/2.v2.argotunnel.com` succeeds, but both **UDP (QUIC) and TCP (HTTP2) connectivity to port 7844 fail** with `i/o timeout` — confirmed identical on the owner's home Wi-Fi *and* on mobile-data hotspot (so it's a carrier/country-level block on this specific port/protocol signature, not a home-router issue). `ngrok` was tried as a fallback (its tunnel endpoint also uses port 443, not 7844) and is **also actively blocked** — `openssl s_client` to `tunnel.us.ngrok.com:443` times out, and plain `curl` to `ngrok.com`/`dashboard.ngrok.com` gets TLS/HTTP2 framing errors (DPI interference, not a simple firewall drop). This matches known DPI blocking of VPN/tunnel-service signatures in Iran.
- [x] **VPN workaround tested and works, but rejected by owner as a standing dependency.** Owner has a local VPN app (`Happ`, xray core + sing-box TUN, SOCKS proxy at `127.0.0.1:10808`) already running for general browsing. Routing `cloudflared` through it via `proxychains4` (config written to `/home/moja/.cloudflared/proxychains.conf`) **does** reach `region1.v2.argotunnel.com:7844` successfully (verified with `openssl s_client` through the proxy chain) — so a working systemd `ExecStart=/usr/bin/proxychains4 -f /home/moja/.cloudflared/proxychains.conf /home/moja/bin/cloudflared ...` is possible. Owner explicitly said no ("نمیخوام با vpn وصل بشم") — doesn't want the media pipeline's uptime tied to a VPN app also staying connected. **This path was not applied to the live systemd unit** (still points at plain `cloudflared`, currently failing/restarting).
- [x] **SSH path closed, 2026-09-18: Parspack refused.** Support's answer (relayed by owner): shared/cPanel hosting doesn't offer SSH access at all — only available on a dedicated server with its own control panel, a different product entirely. Owner decided to drop the SSH/reverse-tunnel idea rather than provision a whole new server just for this. **This closes the loop opened by §53's "chosen next path" below — do not re-suggest requesting SSH from Parspack for this shared-hosting account, it's a known dead end.**
- [x] **Path actually taken instead: the download-host approach from §59.** Owner bought a Parspack download-hosting plan (100GB, `dl.growwise.ir`) and is migrating course videos there; the app already has the download-host-first/local-fallback logic built (§59). Accepted trade-off, explicitly confirmed by the owner: for any video *not yet* uploaded to the download host, the local-machine fallback only actually resolves when the viewer opens the site from the owner's own laptop (same "localhost" constraint as the original problem) — full multi-device reachability for a given video only lands once that video is uploaded to the download host. This is fine as an interim state during gradual migration, not a bug.
- ~~Chosen next path (owner picked via `AskUserQuestion`, 2026-09-17): request SSH access from Parspack support~~ — superseded, see above. Historical detail kept for context: port 22 on `45.159.149.29` was open/reachable (checked via `nc`/`/dev/tcp`), but that only meant the host's firewall allowed it through, not that the hosting *product* included shell access.
- Cleanup once a path is confirmed working: the currently-installed `cloudflared` systemd service (`/etc/systemd/system/cloudflared.service`, presently in an `activating (auto-restart)`/failing loop) should be stopped and disabled (`sudo systemctl disable --now cloudflared.service`) if the SSH-tunnel path is chosen instead, so it's not endlessly retry-looping in the background for no reason.

**Why.** Direct owner report; permanent (not quick-tunnel) requested explicitly once the quick-tunnel's instability was explained. Full-domain DNS migration (not subdomain delegation) chosen once subdomain delegation was found to be Enterprise-only; owner explicitly preferred it (free) over buying a separate domain. Cloudflare Tunnel itself then turned out to be unusable from the owner's network (country-level DPI blocking port 7844), and the owner declined the working VPN-proxied workaround as a permanent dependency, hence the pivot to requesting SSH for a plain reverse-tunnel approach.

**Consequences.** Once live (whichever path), `media-server.py` must keep running on the owner's machine (unchanged from the original design) — the tunnel only fixes *reachability*, not the "media lives on the owner's own machine" architecture itself, unless the SSH-refused fallback (hosting videos on the VPS) is taken, which *would* change that architecture. No app code changes so far; this is pure ops/DNS/network setup, nothing to test with `php artisan test`.

---

## 54. English lesson text (§52) discontinued going forward — Persian-only for the rest of C-03 and all future courses (2026-09-17)

**Decision.** Owner ended the §52 experiment: the two `requirements-engineering` sections already written bilingual — «مقدمه و مبانی» (18 lessons) and «سناریو، User Story و Use Case» (9 lessons) — keep their `.en.md` files as-is, no rollback. But **every lesson from here on, for the rest of `requirements-engineering` and for every other course**, goes back to Persian-only, matching C-01/C-02's original rule. No more `<slug>.en.md`, no more English-pane toggle work, for anything authored after this point.

**Why.** Direct owner instruction, no reason elaborated beyond ending the experiment early. §52 itself already flagged the tradeoff up front — English text roughly doubles per-lesson writing volume even with the "write from transcript, not translate" shortcut — so this reads as the owner deciding that cost isn't worth it past the two sections already committed to.

**Consequences.** `docs/AUTHORING.md` §3.2a updated to mark the English-text workflow as retired (kept only as a description of what the two already-bilingual sections did, not as standing instruction). `lessons.content_en` and the فارسی/English toggle in `<x-lesson-content>` stay in the schema/UI — they still render correctly for the two sections that have `content_en` populated, they just won't gain new data. No `validate.py` or importer change needed (the English file was always optional/silently-skipped). `docs/STORIES.md`'s C-03 "NEXT UP" pointer no longer instructs writing `.en.md` for upcoming sections.

---

## 55. Merged two duplicate-recording "Workshops" lectures into one lesson (2026-09-17)

**Decision.** `requirements-engineering`'s skeleton import (§111 in STORIES.md's C-03 entry) flagged lectures 031 and 036 as both literally titled "Workshops," with different narration, and left it as an open question for authoring time. Having now read both full transcripts: they're the same lecture re-recorded, not two distinct lessons — same definitions, same structure (goal → tools → invitees → location → moderator/note-taker → running the session → RFI/RFA → closeout), same "Tree Seed Dropping Drone … Requirements Workshop" example, word-for-word overlapping sentences in places. 036 has small deltas (explicitly lists "user story" alongside scenario/use case as a workshop technique; states the in-person-over-remote recommendation more directly) but nothing that justifies a second full lesson. Merged: lecture 036's video/subtitles became a second `videos[]` entry (titled "بازبینی و مثال تکمیلی") on the existing `requirements-elicitation-techniques-workshops` lesson, the lesson text gained an opening blockquote noting the two videos and one added mention of user story as a workshop technique, and the standalone `requirements-elicitation-techniques-workshops-ii` lesson was removed from `course.json` and pruned from the database (`content:import --prune`). Section 3 goes from 13 lessons to 12 as a result.

**Why.** Authoring a second lesson that's ~95% the same content as the first wastes effort and gives learners a repetitive, low-value second lesson — the opposite of what `docs/AUTHORING.md`'s "don't add sections the video doesn't cover" and the owner's general move-fast/avoid-low-value-work preference call for. The video pair itself looks like an instructor re-recording or course-revision artifact (GIT.IR platform intro slide identical in both), not intentionally distinct content.

**Consequences.** `content/requirements-engineering/course.json` lesson count for this section: 12, not 13 (13 lecture numbers, 028–040, but 031/036 share one lesson). `docs/STORIES.md`'s course-wide section list and lecture-range notes for this section should be read with that in mind. No app/schema change — this is a one-time content-authoring call, using the existing multi-video-per-lesson mechanism (already used elsewhere, e.g. C-01's lecture+coding-video lessons).

---

## 56. Combined `/_ops/migrate` + `/_ops/import` + `/_ops/optimize` into one `/_ops/update` endpoint (2026-09-17)

**Decision.** Added `/_ops/update?token=T` to `OpsController`, which runs migrate, then `content:import` (defaulting `prune` to **true**, unlike the standalone `import` action which defaults it to false), then the same cache-warming commands `optimize` runs — in one HTTP request instead of three separate browser visits. The three original actions (`migrate`, `import`, `optimize`) are untouched and still work individually, for the narrower case of wanting just one step (e.g. importing a single course without re-migrating or re-caching).

**Why.** Owner asked whether the three manual post-update browser commands (documented in README's "Manual zip upload" section) could be collapsed into one, to make routine host updates less tedious. The git-based deploy path (`public/deploy.php` on the `deploy` branch) already does exactly this automatically on every `git pull`; this brings the same convenience to the manual/content-only update path, which has no such hook.

**Consequences.** README's "Manual zip upload" section rewritten to lead with `/_ops/update` and mention the three individual actions as a fallback. `DeployTest` gained `test_ops_update_runs_migrate_import_and_optimize_in_one_request`. Full suite 123/123. No change to the git-based `deploy.php` path — that already had this covered.

---

## 57. Skeletons for the remaining 9 C-03 courses, all at once, plus a recommended cross-course learning order (2026-09-17)

**Decision.** Before continuing `requirements-engineering`'s content authoring, built skeletons (renamed/converted media, symlinked into `public/media/`, `content/<slug>/course.json` with every lesson placed into a section, one-paragraph placeholder `.md` per lesson) for all 9 remaining C-03 courses in one pass, matching the process already used for `requirements-engineering` itself:

1. `software-architecture-complete-guide` — 113 lessons, 17 sections
2. `system-design-case-studies` — 19 lessons, 7 sections
3. `large-scale-systems-design` — 41 lessons, 11 sections
4. `becoming-a-product-manager` — 109 lessons, 12 sections
5. `advanced-scrum-master` — 59 lessons, 9 sections
6. `practical-guide-for-tech-leaders` — 34 lessons, 7 sections
7. `become-an-engineering-manager` — 66 lessons, 8 sections
8. `organizational-leadership-ai-era` — 15 lessons, 3 sections
9. `generative-ai-in-organizations` — 17 lessons, 5 sections

Sections were inferred from each course's own lecture-title patterns (intro/summary/recap markers, topic clustering) without watching the videos — same "real (if not-yet-deep) Persian titles/summaries from the lecture titles" standard used for `requirements-engineering`'s skeleton. Media prep used a new throwaway script (not committed — one-off, `course/` is gitignored) that unwraps the `index.html?...&filename=X` download-tool artifacts, renames to `NNN-kebab-title.{mp4,en.srt,fa.srt}`, moves empty subtitle files to `broken-subs/`, converts `.srt`→`.vtt`, and reports `ffprobe` durations. Duplicate lesson slugs (multiple lectures titled just "Summary" or "Conclusion" within one course) were caught by a dedup check and disambiguated by section topic (e.g. `summary-application-types`, `conclusion-requirements`) before import — `software-architecture-complete-guide` had 10 such collisions.

**Also added: a recommended cross-course learning order**, since the owner asked how to know which of the 10 courses to take in what sequence. Scoped explicitly to documentation only (no in-app "step N of 10" UI, no DB track/program entity — confirmed with the owner, consistent with §49's "no track/program layer exists and none was requested"). The order is the same as the build order already recorded in `docs/STORIES.md`'s C-03 entry, now with rationale attached: technical foundations first (requirements engineering → software architecture → system design case studies → large-scale systems design), then product/process (product management → scrum mastery), then a leadership progression (practical guide for tech leaders → engineering manager → organizational leadership in the AI era → generative AI in organizations) that ends on the two most forward-looking, least foundational topics. Recorded in `docs/STORIES.md`'s C-03 entry directly below the course list, not as a separate document.

**Why.** Owner's explicit request: build all 9 skeletons in one batch (rather than one at a time as content authoring reaches each course) so the full catalog structure exists up front, and get a documented recommended order across all 10 courses. Owner will handle deploying this to the live host themselves (not asked of this session).

**Consequences.** `content:import` for all 9 new courses loads cleanly (0 practices/key_points yet, expected at skeleton stage — same as `requirements-engineering` was before its own authoring began). `DeployTest::test_ops_status_migrate_import_and_clear_run_with_the_token` was asserting the exact, full `courses on disk:` list — now 12 courses instead of 2, so the assertion was loosened to check for the presence of the two original courses rather than an exact full-list match, since that list will keep growing as more courses are added and shouldn't make this test brittle. Full suite 123/123. `course/Management course/` (the original owner-dropped bulk folder) is now empty and removed; `course/scrum course/` remains untouched and out of scope, as recorded in §49.

---

## 58. Course catalog grouped into three display-only categories (2026-09-17)

**Decision.** Owner asked for a way to browse the growing catalog (now 15 courses) more easily — group the 10-course C-03 leadership bundle together in its build order, group the Scrum courses together, group the Python courses together. Added `courses.category` (string) and `courses.category_order` (unsigned smallint) columns, set per course in `content/<slug>/course.json` and carried through by `CourseImporter`. Three categories, assigned once each course is fully identified with exactly one (asked the owner first — `advanced-scrum-master` is simultaneously course 6/10 of the leadership bundle *and* topically a Scrum course; owner chose single-category over letting a course appear twice, so it stayed under «مسیر رهبری فنی» since that's its already-documented, ordered home):

- «مسیر رهبری فنی» (`category_order` 1–10): the same 10 C-03 courses, same order as §57/STORIES.md.
- «اسکرام و اجایل» (1–2): `agile-scrum-master-product-owner`, then `scrum-master-ai-bootcamp` (C-04).
- «برنامه‌نویسی» (1–2): `complete-python-mastery`, then `python-deep-dive-1`.

`courses/index.blade.php` now renders one section per category (heading + grid), `CourseController::index` groups and orders sections via a fixed `$categoryOrder` array (programming, scrum-agile, leadership-path — courses with no category, e.g. `sample-course` locally, fall into a trailing unlabeled group). Confirmed explicitly with the owner beforehand: **display-only** — no gating, no locking a course until a previous one in its category is finished, no effect on enrollment/planner/streak. Same category-string-as-label pattern already used for `lessons.section` (no separate labels table), consistent with the project's "avoid low-value heavy work" bias.

**Why.** With C-03's 10-course bundle plus the new Scrum pair plus the two Python courses, a flat alphabetical grid stopped being enough to answer "what do I read and in what order" — the same problem `docs/STORIES.md`'s recommended-order note (§57) solved in the docs, now surfaced in the app itself where the owner actually browses.

**Consequences.** New migration `add_category_to_courses_table`; `Course`'s `#[Fillable(...)]` gained the two fields. All 13 real courses' `course.json` updated with `category`/`category_order`; re-imported. Full suite 123/123 (including `PagesRenderTest`'s existing smoke test of `courses.index`). This is a narrower move than a real track/program layer — §49's "no track/program layer exists" stance still holds for gating/sequencing; this only changes how the catalog page groups and orders what's already there.

---

## 59. Video/caption download-host fallback — `MEDIA_DOWNLOAD_BASE_URL` with the owner's machine as backup (2026-09-18)

**Problem.** §53's Cloudflare Tunnel plan turned out to be unworkable (port 7844 blocked at the network/carrier level, see §53's updated status). Owner decided to instead buy ~100GB on a dedicated download host and serve videos from there, migrating courses off the local machine gradually — but wants both sources live at once during the migration: try the download host first, fall back to the local machine (`media-server.py`, via the existing `MEDIA_BASE_URL`) for anything not yet uploaded, with no manual "is this video migrated yet" bookkeeping per video.

**Decision.** New optional config `media.download_base_url` (`MEDIA_DOWNLOAD_BASE_URL`). A new helper `media_download_url()` mirrors `media_url()` but rebases onto this host, returning `null` when unset. `lesson-content.blade.php` renders the download-host URL as the video `<source>`/caption `<track>` `src` when available (with the local `media_url()` result stashed on `data-fallback-src`); `player.js` listens for the element's `error` event (fires on a 404/network failure of the *currently selected* source) and swaps to `data-fallback-src` once, then `el.load()`s the video. Chosen over a server-side existence check (e.g. an HTTP HEAD per video on each page load) because it needs no network round-trip on the Laravel side and requires no per-video state — the browser just retries once, at zero cost when the file *is* on the download host (the common case once migration is done).

**Scope, extended same day.** Initially only video `<source>`/caption `<track>`; owner then asked for exercise-file attachments (`lessons/show.blade.php`, `session/show.blade.php`) to get the same treatment, since those also move to the download host (same course folder as the videos — `CourseImporter::attachments()` resolves them through the same `mediaUrl()`/`video_base_url` as videos, so no separate path convention needed). An `<a download>` has no load-failure event to hook like `<video>`/`<track>` do, so a new `resources/js/download-fallback.js` (`mountDownloadFallbacks()`, wired into `app.js` next to `mountPlayers()`) intercepts the click, does a `fetch(..., {method: 'HEAD'})` against the download-host URL, swaps to `data-fallback-src` on a miss/network error, then re-dispatches the click. Same `data-fallback-src` markup convention as the video component, driven by the same `media_download_url()` helper.

**Why.** Direct owner request, immediately after §53's Cloudflare Tunnel path hit a hard network block; owner explicitly wants a gradual migration (courses moved to the download host one at a time) without either a flag day or manual per-video tracking.

**Consequences.** No DB schema change — `media_download_url()` derives everything from the existing `/media/...`-prefixed URLs already stored on `LessonVideo`/subtitle/`attachments` records, same as `media_url()`. 4 new feature tests in `LessonPageTest` (video/attachment fallback markup present when `download_base_url` is set, video markup absent when it isn't); full suite 126/126. `vendor/bin/pint --dirty` clean. `npm run build` run to compile the `player.js`/`download-fallback.js` changes.

**Owner's actual purchase and progress, 2026-09-18:** bought a Parspack "هاست دانلود" (download-hosting) plan — 100GB storage, 400GB/month traffic, dedicated IP `194.147.142.36`, DirectAdmin panel, FTP at `3264450084.cloudydl.com`. Current local video+caption footprint is ~72GB across all courses (checked via `du`), so there's headroom but not a lot — worth watching as more courses get videos. Added `media.growwise.ir`... correction, added **`dl.growwise.ir`** as the domain for this host (an earlier draft of this note said `media.growwise.ir` per §53's original plan, but the owner actually used `dl` when creating the DNS record) — added as an A record in Cloudflare (`dl` → `194.147.142.36`), then switched to **Proxied** (orange cloud) specifically to get Cloudflare's free edge SSL certificate, since the download host's own default DirectAdmin cert (for its auto-generated `da.direct` hostname) doesn't cover `dl.growwise.ir` and was additionally expired. SSL/TLS mode set to Flexible. Verified working (`openssl s_client` to the Cloudflare proxy IP shows a valid `CN=growwise.ir` cert; `curl` gets a plain 403 on `/`, expected with nothing uploaded yet).

The panel's own file manager only has one real document root — `/domains/pz25416.parspack.net/public_html/` — despite `skillos.growwise.ir`/`dl.growwise.ir` being listed as attached "domains" in Parspack's product dashboard; those are just aliases pointing at the same single root, not separate per-domain folders. Course folders go directly under that root, named exactly after the course slug (matching `video_base_url` in each course's `course.json`, e.g. `/media/requirements-engineering/` → `public_html/requirements-engineering/`), flat (no lesson-slug subfolders — files are numbered, e.g. `001-course-introduction.mp4`), `.mp4`+`.vtt` only (`.srt` not used by the app, skip to save space/time). `lftp` (not preinstalled) was `apt-get install`ed for bulk mirroring; a per-course `lftp mirror -R --continue --include-glob='*.mp4' --include-glob='*.vtt' --exclude-glob='*'` loop was handed to the owner to run themselves (in their own terminal, ideally inside `screen`/`tmux` since ~72GB will take hours) rather than run through Claude's own Bash tool, both because of the size/duration and because a background Bash task started earlier in this same session was already observed to not survive a session boundary cleanly.

Owner still needs to: (1) actually run the upload for all courses (started, in progress as of this writing), (2) set `MEDIA_DOWNLOAD_BASE_URL=https://dl.growwise.ir` in production `.env`, (3) `/_ops/optimize` after the `.env` change. §53's SSH/reverse-tunnel path is still the fallback plan if the download-host approach doesn't pan out (e.g. runs out of space or traffic).
