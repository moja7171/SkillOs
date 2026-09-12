# SkillOS — Product Requirements (v0.3)

> v0.3 replaces v0.2 after a product pivot on 2026-09-12 (DECISIONS.md §12). v0.1 docx files are archived originals. SkillOS is built by one person for personal use plus one or two friends.

**Goal:** Any course I want — built quickly from videos/docs/books I already have — turned into a daily loop of learn → practice → feedback → spaced review, so I actually retain it.

## 1. What changed from v0.2 (one paragraph)

The learner no longer types a topic for AI to design. There is a **fixed catalog of courses** authored at development time (the product owner hands materials — videos with subtitles, documents, books — to Claude, who writes the lesson texts and practices into content files, and an import command loads them). The learner only **enrolls** in a course from the catalog and learns. **AI at runtime is used for one thing: evaluating practice answers and giving feedback.** The "Skill" concept (a cross-course competency) is deliberately dropped for now; courses are independent.

## 2. Non-Goals (MVP)

- In-app authoring UI; user-created topics; runtime AI content generation
- A Skill/competency layer across courses; course-to-course prerequisites
- Admin panel, audit trails, gamification, notes/bookmarks, social features
- Full calendar/history UI, weekly review reports, direct learner ↔ AI chat
- Code execution for evaluation

## 3. Product Principles

| Principle | Meaning |
|---|---|
| Course first | The catalog is the product. A course is a curated, ordered set of lessons from one source. |
| Lesson is the unit of mastery | Practice, feedback, mastery and spaced review attach to a lesson (a topic), never to the course as a whole. |
| Authoring is fast and outside the app | Materials → content files → import. Optimize for "a new course in an afternoon". |
| Two paths to the same lesson | Every lesson offers its video(s) and a rewritten text that matches the video, side by side as tabs. |
| AI evaluates, never authors at runtime | Gemini grades open answers with a stored rubric and returns feedback only. |
| Evidence-only mastery, no raw scores | Mastery is computed from attempts; the learner sees qualitative levels and feedback. |
| Guidance with freedom | One Next Best Action is offered; any available lesson/practice can still be started. |
| Recovery, not backlog | Today's plan is computed from current state; nothing is replayed after a gap. |

## 4. Core User Journey

1. Open **All courses**, enroll in one (sets it Active; Daily Time may stay empty)
2. Set Priority and Daily Time per enrolled course
3. **Continue Learning** → one recommended activity (+ alternatives) across active courses
4. Lesson session: watch/read → practice → evaluate → hint/retry → feedback
5. Attempt stored → lesson mastery updated → next review scheduled
6. Repeat daily; the course shows progress as lessons move up in level

## 5. Course

| Field | Rule |
|---|---|
| Title, description | Authored. |
| Outcome | Authored: what the learner can do after the course. Visible. |
| Source note | Where the materials came from (video series, book, docs). Visible as "منابع". |
| Lessons | Ordered list. |

Courses are shared by all users; enrollment and progress are per user.

## 6. Lesson

A lesson teaches **one topic** (e.g. "decorators"). Usually one video = one lesson; a topic split across two videos is one lesson with two videos.

| Field | Rule |
|---|---|
| Title, summary | Authored. |
| Text | Rewritten lesson text (Markdown) that follows the video's content — not the subtitle verbatim — supplemented from official references when the owner points to them. |
| Videos | 0..n URLs (local file served by the app or external link). Shown as the "ویدیو" tab. |
| Practices | 2–3 authored tasks per lesson, each with form, prompt, two hints, expected outcome, rubric, difficulty, minutes. |
| Prerequisites | Other lessons in the same course; default: the previous lesson. |
| Estimated minutes | For the learn step. |

Recommended resource: the video when present, otherwise the text; the learner may use either and the choice does not affect mastery.

## 7. Enrollment

| Field | Rule |
|---|---|
| Priority | 1–5, orders courses in Continue Learning. |
| Daily Time | Minutes/day, may be empty → not planned, still freely practicable. |
| Preferred time | Optional display hint. |
| Status | Active / Paused / Archived / Maintenance. Unenrolling is not in MVP; archive keeps everything. |

## 8. Activity Model

| Type | Purpose |
|---|---|
| Learn | Consume the lesson (video tab or text tab). |
| Practice | Apply the lesson's topic. Forms: mcq, short_answer, coding, explanation, scenario. |
| Review | A practice of the lesson executed after a delay (`plan_items.source = review`), not a separate activity. |

## 9. Evaluation

| Form | Evaluator |
|---|---|
| mcq | Rule-based (`correct_option`). |
| short_answer, coding, explanation, scenario | Gemini with the stored rubric + expected outcome → `verdict` (correct / partial / incorrect) + Persian feedback. |

## 10. Feedback Loop

Wrong → Hint 1 → Retry → Hint 2 → Retry → Answer. Max two hints; correct-after-hint is weaker evidence; the learner may give up (→ incorrect).

## 11. Mastery

Per (user, lesson): internal 0–1000, learner-facing level Not started / Learning / Familiar / Proficient / Mastered. Deltas, thresholds, review intervals: DECISIONS.md §3. Course progress = distribution of its lessons' levels (e.g. "۱۲ از ۴۰ درس ماهر یا بالاتر").

## 12. Adaptive Remediation

| Signal | Action |
|---|---|
| Review failed | Review again soon (interval reset). |
| Practice failed, lesson already learned | Another practice of the same lesson. |
| 3 consecutive failures on a lesson | Re-learn (open the lesson again), then practice. |
| Prerequisite lesson dropped below Familiar | Review the prerequisite first. |
| Independent success | Advance; schedule spaced review. |

## 13. Planning

Per enrolled course: Priority and Daily Time. Today's plan is **computed** from current state (due reviews → remediation → next lesson in order) within each course's Daily Time and materialized only for today. Weekly view is read-only. **Skip** replaces reschedule. Overdue reviews are capped per course per day. Outside-plan activities count for mastery but never complete a plan item.

## 14. Continue Learning

One recommended activity plus up to two alternatives across active, scheduled courses, ordered by review urgency then Priority.

## 15. Pause / Archive / Reactivation

Archive keeps everything. Reactivation after >14 days queues a review for every lesson at Familiar or above.

## 16. Screens

| Screen | Content |
|---|---|
| Home | Continue Learning, Today per course, My courses with progress |
| All courses | Catalog cards, enroll |
| Course | Outcome, sources, lessons table (order, title, level, minutes, locked/open), enrollment config |
| Lesson | Tabs ویدیو / متن, key points, practices list, prerequisites |
| Session | Learn tab(s) → practice → hints → feedback → next |
| Week | Read-only: today + due reviews for 6 days |

## 17. Functional Requirements

| ID | Requirement |
|---|---|
| FR-01 | Courses and lessons are imported from content files by a command; re-import updates content without losing learner progress. |
| FR-02 | Catalog lists courses; a user can enroll; enrollments are per user, content shared. |
| FR-03 | Per-course Priority, Daily Time, Preferred time, Status. |
| FR-04 | Lesson shows video(s) and rewritten text as tabs; one marked Recommended. |
| FR-05 | Authored practices per lesson with hints, expected outcome, rubric. |
| FR-06 | Rule-based evaluation for mcq; Gemini rubric evaluation otherwise, feedback in Persian, never a score. |
| FR-07 | Hint → Retry → Hint → Retry → Answer. |
| FR-08 | Per-lesson mastery from evidence; qualitative levels only. |
| FR-09 | Deterministic remediation. |
| FR-10 | Computed daily plan; weekly read-only view; Skip. |
| FR-11 | Continue Learning: one recommendation + alternatives. |
| FR-12 | Any lesson/practice can be started outside the plan without completing it. |
| FR-13 | Archive keeps data; reactivation after >14 days queues reviews. |
| FR-14 | Runs on shared hosting: SQLite, no queue worker, built assets committed. |

## 18. Definition of Done

- Import the first course (Advanced Python) from its content files; it appears in the catalog with all lessons, videos and practices.
- Enroll, set Daily Time, open Continue Learning, complete a full learn → practice → feedback session.
- The lesson's level changes; a review appears on its due date.
- A wrong answer shows a hint before the answer.
- Two courses with different Daily Time get proportionally different plans.
- After 10 days away, the plan on return is bounded.
- Re-importing the course after editing a lesson text keeps existing mastery and attempts.

## 19. Technical Direction

Laravel 13 + SQLite, Blade + Tailwind, Persian RTL (DESIGN.md §2.3). Gemini `gemini-3.6-flash` for evaluation only, synchronous. Content lives in `content/<course-slug>/` in the repo; `php artisan content:import` upserts by slug. Deployable to shared hosting (no Node, no workers).

## 20. Future Scope

Skill/competency layer across courses; course prerequisites; in-app authoring; weekly review; gamification; notes; code execution; history UI.
