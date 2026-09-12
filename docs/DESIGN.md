# SkillOS — MVP Product Design (v0.2)

> v0.2 supersedes `SkillOS_MVP_Product_Design_v0.1_EN.docx`. Aligned with `PRD.md` v0.2 and with the schema actually implemented in `database/migrations`. Algorithms and numbers live in `DECISIONS.md`.

## 1. Product Rules → Implementation Implications

| Rule | Implication in code |
|---|---|
| Learning Item first, no Path | No table references a path. `skills.learning_item_id` is the only grouping. |
| Human approval boundary | `learning_items.design_status` enum `draft → pending_review → approved`. `skills` rows are created only in the approve transition. |
| Skill-centric mastery | `mastery_records` is per (user, skill). Item progress = aggregate of its skills' levels. |
| Mastery from evidence only | The only writer of `mastery_records.numeric_mastery` is `MasteryService::applyAttempt()`. |
| No raw scores | Numeric mastery and evaluation verdicts never reach a Blade view. |
| Recovery not backlog | The planner is a pure function of current state; there is no stored future plan to replay. |
| Explainable adaptivity | Every planner choice carries a short `reason` string shown in the UI ("Review due", "Next skill", "Retry after 3 misses"). |

## 2. Surfaces

| Surface | Content | Primary action |
|---|---|---|
| Home | Continue Learning CTA, Today per item, active items with mastery summary, draft items | Continue Learning |
| Learning Item | Title, Outcome, status, Skills with level, resources, schedule config | Continue Learning (scoped to item) |
| Design review | Proposed Outcome + Skill list with prerequisites | Approve / Regenerate |
| Session | Learn content → Practice → feedback → next | Submit / Next |
| Skill | Level, learn text, video link, available practices | Practice / Re-learn |
| Week | Today's plan + due reviews for 6 more days (read-only) | Open activity |

### 2.1 Session flow

| Step | System | Learner sees |
|---|---|---|
| Start | Load activity + target skill; ensure skill content exists (generate lazily if not) | Title, goal, ~minutes |
| Learn (if Learn activity or first practice of skill) | Show AI Text; show pasted video link if any | Content, "I'm ready" |
| Practice | Render practice by form (MCQ options / textarea / code textarea) | The task |
| Evaluate | MCQ → rule; otherwise Gemini with rubric → verdict + feedback | Feedback text only |
| Wrong | Show hint[hint_level], increment, allow retry (max 2 hints) | Hint, retry box |
| Exhausted | Show explanation/expected outcome; attempt = incorrect | Answer + explanation |
| Update | Store Attempt, apply mastery delta, set next review | New qualitative level if changed |
| Next | Planner picks next activity | One Next Best Action |

### 2.2 Feedback state machine

```
submitted ──correct──▶ correct (hint_level==0) / correct_with_hint (hint_level>0)
    │
  wrong, hint_level<2 ──▶ show hint[hint_level]; hint_level++ ──▶ retry ──▶ submitted
    │
  wrong, hint_level==2 ──▶ show answer ──▶ incorrect
    │
  give up (any time) ──▶ incorrect
```

## 3. Data Model (as implemented)

| Table | Purpose | Notes |
|---|---|---|
| `learning_items` | Topic + config + design state | `outcome_statement`, `design_status`, `design_draft` (json staging), `design_approved_at`, `status`, `priority`, `daily_time_minutes`, `preferred_time`, `last_activity_at`. Add: `starting_point` (text, nullable). |
| `skills` | Approved decomposition | `order` gives suggested sequence. Add: `content_generated_at` (nullable) — null means content is lazily pending. |
| `skill_dependencies` | Prerequisite edges | Directed; created from the draft's `prerequisite_keys`. Self-edges and unknown keys are dropped at approve time. |
| `resources` | AI text / pasted video | `type` video|text, `content` for text, `url` for video, `is_recommended`. |
| `activities` | Reusable activity templates | `type` learn|practice, `payload` json (see §4.3), `estimated_minutes`. **Drop `status` column** — state belongs to attempts and plan items. **Drop `review`/`assessment` from `type`**: review is a practice executed with `plan_items.source = review`. |
| `attempts` | One execution of an activity | `result_status` started|correct|correct_with_hint|incorrect|abandoned, `hint_level`, `evidence` json `{response, verdict, feedback, hints_shown, source}`. |
| `mastery_records` | Current state per (user, skill) | `numeric_mastery` 0–1000, `level`, `last_evaluated_at`, `next_review_due_at`. |
| `plan_items` | Today's materialized plan | `scheduled_for` (today), `duration_minutes`, `status` scheduled|completed|skipped, `source` plan|review|recovery. Add: `reason` (string). |

Removed relative to v0.1: `outcomes`, `mastery_events`, `review_items`, `notes`, `bookmarks`, `generation_runs`, `approval_records`.

### 3.1 Enums

| Domain | Values |
|---|---|
| LearningItem.status | active, paused, archived, maintenance |
| LearningItem.design_status | draft, pending_review, approved |
| Skill level | not_started, learning, familiar, proficient, mastered |
| Activity.type | learn, practice |
| Practice form (in payload) | mcq, short_answer, coding, explanation, scenario |
| Attempt.result_status | started, correct, correct_with_hint, incorrect, abandoned |
| PlanItem.status | scheduled, completed, skipped |
| PlanItem.source | plan, review, recovery |

## 4. AI Architecture

AI is a backend service. Two call types only, both synchronous, both structured JSON via Gemini `responseSchema`:

| Call | When | Input | Output | Gate |
|---|---|---|---|---|
| `generate_design` | Learner clicks Generate / Regenerate | title, starting_point | outcome_statement, skills[] with keys, descriptions, prerequisite_keys | Human approve |
| `generate_skill_content` | First time a skill is needed by the planner or opened by the learner | item title, outcome, skill name+description, neighbouring skill names, starting_point | learn text + 2–3 practices each with form, prompt, hints[2], expected_outcome, rubric, difficulty | None |
| `evaluate_response` | Non-MCQ practice submitted | practice prompt, rubric, expected_outcome, learner response, hint_level | verdict (correct/partial/incorrect), feedback | None |

Dropped from v0.1: `validate_learning_design`, `discover_video`, `generate_weekly_plan`, `generate_recovery_plan` (planning is deterministic, not AI).

### 4.1 Reliability rules

- A failed AI call never mutates state; the learner sees an error and can retry.
- `evaluate_response` output is bounded to verdict + feedback; the model is instructed never to output a score.
- Content generation is idempotent per skill (`content_generated_at` guard) so a double-click cannot duplicate practices.
- Prompts and schemas live in `App\Services\Ai\*` classes, one class per call.

### 4.2 Practice payload shape

```json
{
  "form": "short_answer",
  "prompt": "...",
  "options": ["..."],            // mcq only
  "correct_option": 2,           // mcq only
  "expected_outcome": "...",
  "hints": ["...", "..."],
  "rubric": "...",
  "difficulty": "intro|core|stretch"
}
```

Learn payload: `{ "resource_id": <text resource> }`.

## 5. Mastery & Remediation

Contract: `numeric_mastery` changes only inside `MasteryService::applyAttempt(Attempt)`. It applies a delta from a fixed table, clamps to 0–1000, recomputes `level`, and sets `next_review_due_at` from the level. Numbers: `DECISIONS.md` §3–4.

Remediation is not stored; it is re-derived by the planner from the last attempts on each skill.

## 6. Planning

`Planner::today(User)` is a pure function of: active items with `daily_time_minutes`, mastery records, recent attempts, and today's existing plan items. See `DECISIONS.md` §2 for the algorithm.

Materialization: the first call on a given day inserts `plan_items` for today; later calls read them. Changing an item's Priority/Daily Time/Status deletes today's uncompleted plan items for that item and recomputes.

`Planner::continueLearning(User)` returns the first uncompleted plan item across items plus two alternatives (next plan item; a free-choice practice of the current skill).

Week view: today's plan items + `mastery_records.next_review_due_at` grouped by day for the next 6 days.

## 7. Lifecycle

- Pause / Archive: planner ignores the item. Its today plan items are deleted.
- Reactivate: if `last_activity_at` is older than 14 days, set `next_review_due_at = today` for every skill at Familiar or above.
- Maintenance: planner only schedules due reviews for the item, never new skills.

## 8. Build Sequence

1. Gemini key + live test of `generate_design`. Schema tweaks: `starting_point`, `content_generated_at`, `plan_items.reason`, drop `activities.status`.
2. `generate_skill_content` + storage as Resource + Activities.
3. Session UI: Learn → Practice → evaluate (rule + AI) → hints → feedback. Attempts stored.
4. `MasteryService` + level display on Skill and Item pages.
5. `Planner::today` + `continueLearning` + Home page.
6. Week view, Skip, Archive/Reactivate, Maintenance.
7. Use it for a week. Tune numbers in `DECISIONS.md`.

## Appendix — System Map

| Layer | Objects | Question answered |
|---|---|---|
| Intent | LearningItem (+ outcome) | What do I want to learn? |
| Design | Skills + dependencies | What capabilities are required? |
| Assets | Resources + Activities | How is it taught and practised? |
| Execution | Attempts | What did I actually do? |
| State | MasteryRecords | Where am I now? |
| Planning | Planner (function) + today's PlanItems | What next, and when? |

When in doubt, prioritize the loop: Activity → Evidence → Mastery → Next Action.
