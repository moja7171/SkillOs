# SkillOS — User Stories & Build Tracker (v0.3)

Rewritten after the 2026-09-12 pivot (DECISIONS.md §12). Earlier v0.2 stories S-01..S-07 were completed and then superseded; their reusable parts (UI system, Persian infra, page tests) carry over.

Status: `[ ]` todo · `[~]` in progress · `[x]` done · `[-]` dropped

---

## M0 — Catalog foundation

### [x] S-01 Schema v0.3
Tables per DESIGN.md §3: courses, lessons, lesson_videos, lesson_prerequisites, activities(lesson_id, key), enrollments, attempts, mastery_records(lesson_id), plan_items(enrollment_id). Old learning_items/skills/skill_dependencies/resources removed. `migrate:fresh` clean; models, factories, relationships in place.

### [x] S-02 Content importer
As the owner, I want `php artisan content:import <slug>` to load a course from `content/<slug>/` so authoring stays in files.
- Upserts course by slug, lessons by slug, learn activity per lesson, practices by key; videos and prerequisites replaced from the file; default prerequisite = previous lesson.
- Re-import after editing a lesson text keeps existing attempts/mastery (same ids).
- Validates: unique slugs, prerequisite slugs exist, mcq has 4 options and a valid correct_option, hints ≤ 2, difficulty/form enums. Errors name the file and field.
- A sample course in `content/sample-course/` (2–3 short lessons) ships with the repo for tests and demos.

### [x] S-03 Catalog and enrollment
As a learner, I want to see all courses and enroll.
- `/courses` lists courses with lessons count and an "enrolled" badge; `/courses/{slug}` shows outcome, sources, lessons table (level per lesson for me), enroll button.
- Enroll creates an enrollment (active, priority 3, no daily time) and redirects to the course page; enrolling twice is a no-op.

### [x] S-04 Enrollment config
Priority, Daily Time, Preferred time, Status — same form and rules as before (v0.2 S-04/edit page), now on `/enrollments/{id}/edit`.

### [x] S-05 Lesson page
As a learner, I want to open a lesson and switch between video and text.
- Tabs ویدیو / متن (video tab first and marked Recommended when a video exists); markdown text; key points and common mistakes; practices list with form/difficulty/minutes; prerequisites with my levels; my level badge.
- Locked state shown (with reason) when a prerequisite is below Familiar; the lesson can still be opened (free exploration).

### [x] S-06 My courses (interim Home)
`/` for a logged-in user lists enrolled courses with a level-distribution bar and "unscheduled" flag, plus a link to the catalog. Replaced by the real Home in M4.

## M1 — First real course

### [~] C-01 Advanced Python course content
Owner provided 95 videos (no subtitles) as `course/python-deep-dive-1/`, served via `public/media/` symlink. 2026-09-13: `content/python-deep-dive-1/` has all 106 lessons (159 videos, 11 sections; lectures 100–162 arrived later without subtitles yet) (videos grouped, sections), full text + practices for «مرور سریع» (10), «متغیرها و حافظه» (11) and «انواع عددی» (18) — 39 of 106 — written from English transcripts + slides + notebooks; 67 outlines remain. 9 English subtitles are empty upstream (055, 056, 064, 078, 085, 097, 106, 111, 138; parked in `course/.../broken-subs/`). Claude writes (lesson texts following the videos, 2–3 practices per lesson with rubrics), imports, owner reviews in the app, fixes iterate as commits.

## M2 — Session (unchanged from v0.2 S-08..S-13, "skill" → "lesson")

### [x] S-08 Learn activity (tabs video/text, "آماده‌ام" → completed)
### [x] S-09 Practice rendering by form
### [x] S-10 Rule evaluation (mcq)
### [x] S-11 AI evaluation (`evaluate_response`, Persian feedback, no score)
### [x] S-12 Hint → Retry → Answer loop, give up, evidence json
### [x] S-13 Feedback + Next (interim: next practice of the same lesson / retry / back to lesson — planner-driven Next arrives in M4)

## M3 — Mastery

### [x] S-14 MasteryService (per lesson; DECISIONS §3; unit-tested table)
### [x] S-15 Level display on lesson/course pages; course progress summary; no numeric leaks

## M4 — Planning, Continue Learning, Home

### [x] S-16 `Planner::today` (DECISIONS §2, per enrollment; fixtures tested)
### [x] S-17 Continue Learning (recommendation + 2 alternatives; plan item completion rules)
### [x] S-18 Home (CTA, Today per course, My courses)
### [x] S-19 Recompute today's plan on enrollment config change

## M5 — Week, Skip, Lifecycle

### [x] S-20 Week view · [x] S-21 Skip · [x] S-22 Pause/Archive/Maintenance · [x] S-23 Reactivation after >14 days

## M6 — Real use

### [ ] S-24 One week of daily use on the Python course; tune DECISIONS §3 numbers with dated notes.
