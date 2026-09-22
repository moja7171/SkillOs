# SkillOS — Product Design (v0.3)

> v0.3 follows the 2026-09-12 pivot (PRD.md v0.3, DECISIONS.md §12): fixed catalog of courses, lesson as the mastery unit, no Skill layer, AI only for evaluation. Algorithms and numbers live in `DECISIONS.md`.

## 1. Product Rules → Implementation Implications

| Rule | Implication in code |
|---|---|
| Catalog, not user topics | `courses`/`lessons` have no `user_id`; they are written only by `content:import`. |
| Lesson is the mastery unit | `mastery_records` is per (user, lesson). Course progress is an aggregate. |
| Enrollment carries learner config | `enrollments` = user × course with priority, daily time, status. |
| Logo | `<x-application-logo>` — amber rounded tile with a white rising path and a dark dot at the top (the learning path, «you are here»); same mark as `public/favicon.svg`. Wordmark: Skill**OS** with OS in accent. |
| Two paths per lesson | Lesson page and Session show tabs ویدیو / متن from `lesson_videos` + `lessons.content`. |
| AI evaluates only | The only runtime Gemini call is `evaluate_response`. |
| Mastery from evidence only | The only writer of `numeric_mastery` is `MasteryService::applyAttempt()`. |
| No raw scores | Numeric mastery and verdicts never reach a Blade view. |
| Recovery not backlog | `Planner::today` is a pure function of state; nothing is stored ahead. |
| Re-import is safe | Content is upserted by stable slugs/keys so attempts and mastery survive edits. |

## 2. Surfaces

| Surface | Route | Content | Primary action |
|---|---|---|---|
| Home | `/` | Continue Learning CTA, Today per course, My courses with progress bars | Continue Learning |
| All courses | `/courses` | Catalog cards (title, outcome, lessons count, enrolled badge) | Enroll |
| Course | `/courses/{slug}` | Outcome, sources, lessons table (#, title, level, minutes, lock), enrollment config in sidebar | Continue (scoped) / Enroll |
| Lesson (course player) | `/courses/{slug}/lessons/{slug}` | Persistent curriculum sidebar on the left (sections, levels, current highlighted), tabs ویدیو / متن, key points, practices list, status / files / prerequisites, prev/next | Practice / Next lesson |
| Continue | `/courses/{slug}/learn` | Redirects to the first lesson below «آشنا» | — |
| Session | `/session/{activity}` | Learn tabs → practice → hints → feedback → next | Submit / Next |
| Week | `/week` | Today's plan + due reviews for 6 days (read-only) | Open activity |
| Enrollment config | `/enrollments/{id}/edit` | Priority, Daily Time, Preferred time, Status | Save |

### 2.1 Session flow

| Step | System | Learner sees |
|---|---|---|
| Start | Load activity + lesson | Title, course, ~minutes |
| Learn (learn activity, or first practice of an unlearned lesson) | Tabs: video (recommended if present) / text; key points card | Content, "آماده‌ام" |
| Practice | Render by form (mcq radios / textarea / code textarea) | The task |
| Evaluate | mcq → rule; otherwise Gemini with rubric → verdict + feedback | Feedback text only |
| Wrong | Show `hints[hint_level]`, increment, retry (max 2) | Hint, retry box |
| Exhausted | Show expected outcome; attempt = incorrect | Answer + explanation |
| Update | Store Attempt, apply mastery delta, set next review | New qualitative level if changed |
| Next | Planner picks next activity | One Next Best Action |

### 2.2 Feedback state machine

```
submitted ──correct──▶ correct (hint_level==0) / correct_with_hint (hint_level>0)
    │
  wrong, hint_level<2 ──▶ show hint[hint_level]; hint_level++ ──▶ retry
    │
  wrong, hint_level==2 ──▶ show answer ──▶ incorrect
    │
  give up (any time) ──▶ incorrect
```

### 2.3 Visual system (approved mockup, 2026-09-12)

Persian UI, `dir="rtl"`, desktop-first. LeetCode-inspired density: top nav, main column + 360px side column, 1px-bordered cards (radius 10px, no shadows), badges for levels, a table for lesson lists, split-pane Session (learn content | practice).

| Token | Dark (default) | Light |
|---|---|---|
| bg / surface / surface2 | #0e1014 / #151820 / #1c2029 | #f5f6f8 / #ffffff / #f0f2f5 |
| line / line2 | #262b36 / #323847 | #e2e5ea / #d0d5dd |
| ink / muted / faint | #e7e9ef / #8e96a8 / #5c6474 | #171a21 / #5f6779 / #98a0b0 |
| accent (one, for CTAs and "Recommended") | #f0a020 | #d98a0b |
| ok / warn / bad | #2fbf8f / #f0a020 / #ef5a6f | #1a9f72 / #d98a0b / #d9384f |
| levels l0..l4 (not started → mastered) | #4b5263, #5b8def, #2fbf8f, #f0a020, #b58cff | #a3aab8, #3b6fd6, #1a9f72, #d98a0b, #8a5cf5 |

- Fonts: Vazirmatn (UI) + JetBrains Mono (code, durations). Persian digits in prose (`fa_num()`), Latin in code and `.num` cells.
- Dates: Jalali via `fa_date()` (morilog/jalali); storage stays Gregorian.
- Tokens live in `resources/css/app.css` (`:root` light, `.dark` dark); Tailwind exposes them as `bg-surface`, `text-muted`, `border-line`, `badge-l3`, etc. Components: `.card`, `.card-h`, `.btn(-primary|-ghost|-danger|-sm)`, `.badge-*`, `.input`, `.label`, `.alert-*`, `.table`, `.levelbar`, `.page`.
- Theme toggle in the nav; choice in `localStorage.theme`, applied before first paint; dark is the default.
- Level labels (per lesson): not_started «شروع‌نشده», learning «در حال یادگیری», familiar «آشنا», proficient «ماهر», mastered «مسلط» (`MasteryRecord::LEVEL_LABELS`).

## 3. Data Model

| Table | Purpose | Key columns |
|---|---|---|
| `courses` | Catalog entry | `slug` (unique), `title`, `description`, `outcome_statement`, `source_note`, `category`, `category_order` (display-only catalog grouping, §58 — no gating) |
| `lessons` | Topic unit inside a course | `course_id`, `slug` (unique per course), `order`, `title`, `summary`, `content` (markdown), `key_points` json, `common_mistakes` json, `estimated_minutes` |
| `lesson_videos` | 0..n videos per lesson | `lesson_id`, `order`, `title`, `url` |
| `video_views` | This user watched this video to the end | `user_id`, `lesson_video_id` (unique pair), `watched_at` — gates "انجام دادم" alongside `allPracticesPassedBy` (DECISIONS.md §63) |
| `lesson_prerequisites` | Directed edges inside a course | `lesson_id`, `prerequisite_lesson_id` |
| `activities` | Learn (one per lesson) and practice templates | `lesson_id`, `key` (stable per lesson, from content files), `type` learn|practice, `title`, `estimated_minutes`, `payload` json (§4.2) |
| `enrollments` | User × course config | `user_id`, `course_id` (unique pair), `priority`, `daily_time_minutes`, `preferred_time`, `status`, `last_activity_at` |
| `attempts` | One execution of an activity | `activity_id`, `user_id`, `started_at`, `completed_at`, `result_status`, `hint_level`, `evidence` json |
| `mastery_records` | Current state per (user, lesson) | `numeric_mastery` 0–1000, `level`, `last_evaluated_at`, `next_review_due_at` |
| `plan_items` | Today's materialized plan | `user_id`, `enrollment_id`, `activity_id`, `scheduled_for`, `duration_minutes`, `status`, `source`, `reason` |

Removed in v0.3: `learning_items`, `skills`, `skill_dependencies`, `resources`.

### 3.1 Enums

| Domain | Values |
|---|---|
| Enrollment.status | active, paused, archived, maintenance |
| Lesson level | not_started, learning, familiar, proficient, mastered |
| Activity.type | learn, practice |
| Practice form (payload) | mcq, short_answer, coding, explanation, scenario |
| Attempt.result_status | started, completed, correct, correct_with_hint, incorrect, abandoned |
| PlanItem.status | scheduled, completed, skipped |
| PlanItem.source | plan, review, recovery |

## 4. Content Pipeline & AI

### 4.1 Authoring (development time)

```
content/<course-slug>/
  course.json            title, description, outcome_statement, source_note, lessons[]
  lessons/<lesson-slug>.md             lesson text (markdown, Persian, English terms kept)
  lessons/<lesson-slug>.practices.json practices[] (§4.2 payload + key, title, estimated_minutes)
```

`course.json` lesson entry: `{slug, title, summary, estimated_minutes, key_points[], common_mistakes[], videos: [{title, url}], prerequisites: [slug] (default: previous lesson)}`.

`php artisan content:import {course-slug}` upserts course by slug, lessons by (course, slug), practices by (lesson, key); removes lessons/practices no longer in the files only with `--prune`. Attempts and mastery reference ids that survive re-import.

Authoring workflow: owner drops materials (video + subtitle, docs, book excerpts) → Claude writes the files (Gemini may draft; Claude reviews) → import → commit.

### 4.2 Practice payload

```json
{
  "form": "coding",
  "prompt": "...",
  "options": ["..."], "correct_option": 1,      // mcq only
  "expected_outcome": "...",
  "hints": ["...", "..."],
  "rubric": "...",
  "difficulty": "intro|core|stretch"
}
```

Learn activity payload: `{}` (the lesson itself is the content).

### 4.3 Runtime AI: one call

| Call | When | Input | Output |
|---|---|---|---|
| `evaluate_response` | Non-mcq practice submitted | prompt, rubric, expected_outcome, learner response, hint_level | `{verdict: correct|partial|incorrect, feedback}` (Persian; no score; incorrect → no answer reveal) |

A failed call never mutates state; the learner can retry.

## 5. Mastery & Remediation

Contract unchanged: `MasteryService::applyAttempt(Attempt)` is the only writer; numbers in DECISIONS.md §3–4 apply per lesson. Course progress = count of lessons per level.

## 6. Planning

`Planner::today(User)` — DECISIONS.md §2 with "skill" read as "lesson" and "item" as "enrollment". `current` lesson = first by order with level < proficient whose prerequisites are ≥ familiar.

## 7. Lifecycle

Pause/Archive: enrollment leaves planning, data kept. Reactivate after >14 days: reviews due today for lessons ≥ familiar. Maintenance: reviews only.

## 8. Build Sequence (v0.3)

1. Schema + models for courses/lessons/enrollments; importer; sample course fixture; catalog + enroll + course page + lesson page (tabs).
2. Import the first real course (Advanced Python) from the owner's materials.
3. Session: learn → practice → evaluate (rule + Gemini) → hints → feedback; attempts.
4. MasteryService + level display + course progress.
5. Planner::today + Continue Learning + Home.
6. Week view, Skip, lifecycle.
7. Use it for a week; tune.

## Appendix — System Map

| Layer | Objects | Question answered |
|---|---|---|
| Catalog | Course, Lesson, Video, Practice | What can I learn, and how is it taught? |
| Intent | Enrollment | What am I learning now, how much per day? |
| Execution | Attempts | What did I do? |
| State | MasteryRecords (per lesson) | Where am I? |
| Planning | Planner + today's PlanItems | What next, when? |

When in doubt, prioritize the loop: Activity → Evidence → Mastery → Next Action.
