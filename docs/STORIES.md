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

### [x] C-01 Advanced Python course content (authoring done 2026-09-14; owner review ongoing)
Owner provided the videos + subtitles + exercise files as `course/python-deep-dive-1/`, served via `public/media/` symlink. `content/python-deep-dive-1/` has all 106 lessons (159 videos, 11 sections), full text + practices for «مرور سریع» (10), «متغیرها و حافظه» (11), «انواع عددی» (18), «پارامترهای تابع» (10), «توابع درجه‌یک» (10) «دامنه‌ها، closureها و decoratorها» (12), «tuple و named tuple» (6), «ماژول‌ها، پکیج‌ها و namespaceها» (12), «به‌روزرسانی‌های پایتون» (8) and «مطالب تکمیلی» (9) — **all 106 lessons** authored (258 practices) from English transcripts + slides + notebooks + example files. 9 English subtitles are empty upstream (055, 056, 064, 078, 085, 097, 106, 111, 138; parked in `course/.../broken-subs/`). Claude writes (lesson texts following the videos, 2–3 practices per lesson with rubrics), imports, owner reviews in the app, fixes iterate as commits.

### [ ] C-02 «پایتون از صفر» beginner course content (skeleton done 2026-09-14; 2 of 11 sections authored)
Owner provided `course/complete-python-mastery/` (Mosh's "Complete Python Course", 181 lectures) — only lectures **001–139** are in scope (fundamentals → OOP → stdlib → packaging); 140–184 (APIs/automation mini-project, Django project, ML intro) were explicitly dropped and their video/subtitle files deleted. Source filenames arrived as download-tool artifacts (`index.html?token=...&filename=...`) — renamed to the `NNN-kebab-title.{mp4,en.srt,fa.srt}` convention (script kept in `tools/authoring/` history, not the repo), then `srt2vtt.py`. `content/complete-python-mastery/course.json` has all **133 lessons** (136 lectures, 3 merged: VSCode-tricks win/mac, functions exercise+solution, managing-dependencies pt1/pt2) across 11 sections — «شروع به کار» (12), «متغیرها و انواع داده» (10), «عبارت‌های شرطی» (7), «حلقه‌ها» (7), «توابع» (11), «ساختارهای داده‌ی درونی» (23), «استثناها» (7), «برنامه‌نویسی شی‌گرا» (22), «ماژول‌ها و پکیج‌ها» (8), «کتابخانه‌ی استاندارد پایتون» (17), «مدیریت پکیج‌ها» (9) — titles/summaries/videos only, one-paragraph placeholder `.md` per lesson, **no practices/key_points yet**. «شروع به کار» (12 lessons, 19 practices) fully authored 2026-09-14 — first lesson renamed to the `course-overview` slug (matches the validator's no-practices exemption used by C-01). «متغیرها و انواع داده» (10 lessons, 21 practices) authored 2026-09-15; also the point where the owner asked for a simpler/casual tone across all lesson text (not just this course) — both sections rewritten to match, see `feedback_lesson_tone` in Claude's memory and `docs/AUTHORING.md` §5. All 40 practices across both sections then rewritten again the same day around real, tangible scenarios instead of "what does this print" drills — section 1 as help-a-friend/code-review moments, section 2 around one running "user profile" scenario (avatar initials, masking a national ID, a signup form, a shopping cart) — per `docs/AUTHORING.md` §6's updated practice-design note. No emoji anywhere in learner-facing text (explicit owner preference). Next: «عبارت‌های شرطی».

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

## M7 — Engagement (habit-formation, cross-course)

### [x] S-25 Streak, daily-goal progress bar, short-gap nudge on Home (DECISIONS §21)
### [x] S-26 Weekly recap widget · [x] S-27 Cross-course monthly stats (shipped together as one card, DECISIONS §22)
### [x] S-28 Level-up celebration polish (only past "learning", DECISIONS §23)
### [ ] S-29 Review-retention stat on the lesson page
### [ ] S-30 "5-minute mode" quick-review button on Home
### [ ] S-31 Global search across lessons
### [ ] S-32 Friends streak/stats view
