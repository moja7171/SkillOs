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
- `partial` counts as +30 and does not trigger a hint; the learner may retry once for a full-credit verdict (a retry after partial is treated as `with hint`).

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
