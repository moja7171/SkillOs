# SkillOS — User Stories & Build Tracker

Stories are grouped by milestone (DESIGN.md §8). Each one is small enough to implement and verify in one sitting. Acceptance criteria are what we actually check before ticking the box. "Learner" is the user; "system" is the app.

Status: `[ ]` todo · `[~]` in progress · `[x]` done · `[-]` dropped (note why in DECISIONS.md)

---

## M0 — Foundation: AI is live, schema matches DESIGN v0.2

### [ ] S-01 Live design generation
As a learner, I want "Generate design" to produce a real Outcome and Skill list for my topic, so the pipeline is proven end-to-end.
- Given `GEMINI_API_KEY` is set and I have an item "Python for data scripts"
- When I click Generate design
- Then within ~30s the item is `pending_review` with an outcome statement and 4–10 skills, each with a description, and at least one skill has a prerequisite.
- And a failed call (bad key, timeout) shows an error message and leaves the item unchanged.
- **Blocker:** needs the key from the learner (Google AI Studio, free tier).

### [ ] S-02 Starting point
As a learner, I want to tell the system where I currently stand, so the design is not aimed at a total beginner when I'm not one.
- Given the create-item form
- When I fill the optional "Where are you now?" textarea
- Then it is stored on the item and included in the design prompt, and shown on the item page.
- Schema: `learning_items.starting_point` text nullable.

### [ ] S-03 Regenerate design
As a learner, I want to reject a bad design by asking for a new one, without editing it by hand.
- Given an item in `pending_review`
- When I click Regenerate
- Then a new draft replaces the old one and the item stays `pending_review`.
- And Regenerate is **not** offered once the design is `approved` (skills already carry mastery).

### [ ] S-04 Schema alignment
As the system, I need the schema to match DESIGN v0.2 before content and planning are built on it.
- `skills.content_generated_at` timestamp nullable
- `plan_items.reason` string nullable
- `activities.status` dropped
- `activities.type` enum → `learn, practice`
- `attempts.result_status` enum gains `completed` (used by Learn attempts)
- Fresh `migrate:fresh` works; existing models/casts updated; nothing else breaks.

---

## M1 — Skill content

### [ ] S-05 Generate skill content
As the system, I want to produce learn text and practices for one Skill on demand, so content exists only for skills that are actually reached.
- Given an approved skill with `content_generated_at = null`
- When `SkillContentGenerator::generate(skill)` runs
- Then one `resources` row (type text, markdown content, `is_recommended = true`), one `learn` activity, and 2–3 `practice` activities exist, each practice payload matching DECISIONS.md §7, and `content_generated_at` is set — all in one transaction.
- And calling it again is a no-op (idempotent).
- And at least one practice has difficulty `intro`.

### [ ] S-06 Skill page
As a learner, I want to open a Skill and see what it is, my level, its learn text, and its practices.
- Shows name, description, qualitative level (never a number), prerequisites with their levels.
- If content is missing, shows a "Prepare this skill" button that runs S-05 and reloads.
- Renders learn text as markdown.
- Lists practices with form + difficulty + estimated minutes and a Start button (free exploration, FR-14).

### [ ] S-07 Paste a video
As a learner, I want to attach a video link to a Skill so I can learn from it instead of the text.
- Given a Skill page
- When I paste a URL and save
- Then a `resources` row (type video) exists for that skill and becomes Recommended; the text stays available. Removing the link restores text as Recommended.

---

## M2 — Session: Learn → Practice → Evaluate → Hint → Feedback

### [ ] S-08 Learn activity
As a learner, I want to read the Skill's text as a session step and mark it done.
- Starting a learn activity creates an `attempts` row (`started`), shows text (and video link if any).
- "I'm ready" sets `result_status = completed`, `completed_at`, updates `learning_items.last_activity_at`, and goes to the Next step (S-13).

### [ ] S-09 Practice rendering
As a learner, I want each practice rendered in a form that fits it.
- `mcq` → radio options; `short_answer` / `explanation` / `scenario` → textarea; `coding` → monospace textarea.
- Shows prompt, difficulty, estimated minutes. Never shows expected_outcome, rubric, or hints up-front.
- Starting creates an `attempts` row (`started`, `hint_level = 0`).

### [ ] S-10 Rule evaluation (MCQ)
As the system, I want MCQ answers judged deterministically.
- Given an mcq practice
- When I submit an option
- Then verdict is `correct` iff it equals `correct_option`; no AI call is made.

### [ ] S-11 AI evaluation (open forms)
As the system, I want open answers judged against the stored rubric, returning feedback only.
- Given a non-mcq practice
- When I submit a response
- Then `evaluate_response` returns `{verdict, feedback}` (DECISIONS.md §7) and the feedback contains no score/grade and, when incorrect, does not reveal the answer.
- And an AI failure shows "couldn't evaluate, try again" and keeps the attempt `started`.

### [ ] S-12 Hint → Retry → Answer loop
As a learner, when I'm wrong I want a hint before the answer.
- Wrong with `hint_level < 2` → show `hints[hint_level]`, increment, let me retry the same attempt.
- Wrong with `hint_level == 2` → show expected_outcome/explanation; attempt = `incorrect`.
- Correct with `hint_level == 0` → `correct`; with `hint_level > 0` → `correct_with_hint`.
- `partial` verdict → feedback + one retry allowed; a subsequent correct counts as `correct_with_hint`.
- "Give up" at any point → `incorrect`, answer shown.
- `evidence` json stores `{response, verdict, feedback, hints_shown, source}` for the final state.

### [ ] S-13 Feedback + Next
As a learner, after an attempt I want to understand how it went and be told what's next, without seeing numbers.
- Shows feedback text, and a sentence if the level changed ("Loops: Learning → Familiar").
- One primary Next button (from Planner once M4 exists; until then: next practice of the same skill or back to the Skill page).

---

## M3 — Mastery

### [ ] S-14 MasteryService
As the system, I want every evaluated attempt to update the target skill's mastery by a fixed rule.
- `MasteryService::applyAttempt(attempt)` is the **only** writer of `numeric_mastery`.
- Applies the delta table in DECISIONS.md §3 (practice vs review chosen from `evidence.source`), clamps 0–1000, recomputes `level`, sets `last_evaluated_at` and `next_review_due_at` from the new level.
- `completed` (learn) and `abandoned` apply delta 0 but still touch `last_evaluated_at`.
- Unit-tested with a table of (start, evidence) → (end, level, review offset).

### [ ] S-15 Level display
As a learner, I want to see my state per Skill and per item, qualitatively.
- Skill page and item page show level badges; item page shows a summary like "2 mastered · 3 proficient · 1 learning · 4 not started".
- No route, view, or JSON exposed to the browser contains `numeric_mastery` or a verdict.

---

## M4 — Planning, Continue Learning, Home

### [ ] S-16 Today's plan
As a learner, I want the system to decide what I do today within each item's Daily Time.
- `Planner::today(user)` implements DECISIONS.md §2 exactly: due reviews (max 3/item) → remediation → current skill → keep-sharp; never splits an activity; respects the half-budget overflow rule.
- First call of the day materializes `plan_items` with `reason`; later calls read them.
- Items with no Daily Time, or not `active`, produce no plan items.
- Selecting a skill with no content triggers S-05.
- Unit-tested with fixtures: fresh item; item with one due review; item with 3 consecutive failures; item all-mastered.

### [ ] S-17 Continue Learning
As a learner, I want one button that starts the right thing.
- Returns first uncompleted plan item (reviews first, then by item priority) + up to 2 alternatives, each with reason and ~minutes.
- Starting from a plan item links the attempt to it; completing the attempt marks the plan item `completed`.
- Starting the same activity from a Skill page (outside plan) does **not** complete the plan item.

### [ ] S-18 Home
As a learner, I want the home page to be the daily entry point.
- Continue Learning CTA; Today section per item (plan items with status); active items with level summary; draft items with "Review design"; items with no Daily Time flagged "unscheduled".

### [ ] S-19 Recompute on config change
As the system, when Priority / Daily Time / Status changes I should rebuild today's plan for that item.
- Uncompleted plan items for that item today are deleted and recomputed; completed ones stay.

---

## M5 — Week, Skip, Lifecycle

### [ ] S-20 Week view
As a learner, I want to see what's coming this week.
- Today's plan items + reviews due per day for the next 6 days, grouped by day and item. Read-only.

### [ ] S-21 Skip
As a learner, I want to say "not today" to a planned activity.
- Plan item → `skipped`; Continue Learning moves on; tomorrow's plan is computed fresh (a skipped review is simply still due).

### [ ] S-22 Pause / Archive / Maintenance
As a learner, I want to take an item out of focus without losing anything.
- `paused` / `archived`: no plan items, today's uncompleted ones deleted; skills, mastery, attempts intact; item still viewable.
- `maintenance`: planner schedules only due reviews for it.

### [ ] S-23 Reactivation
As the system, when an item returns after a long gap I want to check retention first.
- On transition to `active`, if `last_activity_at` is older than 14 days → every skill at Familiar+ gets `next_review_due_at = today`. Otherwise nothing.

---

## M6 — Real use

### [ ] S-24 One week of use
As the product owner, I want to use SkillOS daily for a week on a real topic before tuning anything.
- Log friction and wrong-feeling mastery changes; adjust DECISIONS.md §3 numbers with a dated note; only then consider anything from the deferred list.
