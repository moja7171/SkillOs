# SkillOS — MVP Product Requirements (v0.2)

> v0.2 supersedes `SkillOS_MVP_PRD_v0.1_EN.docx`. It keeps the product identity of v0.1 and removes everything that only exists because a *team* would build and operate the product. SkillOS is built by one person for personal use plus one or two friends. See `DECISIONS.md` for the scope rationale and the concrete algorithms v0.1 left open.

**Goal:** Turn "the things I want to learn" into a real, executable, adaptive learning system.

## 1. Product Vision

A goal-driven personal learning OS. The learner adds a topic; AI acts as the learning architect and content engine; the system turns the approved design into a daily loop of Learn → Practice → Evidence → Mastery → Next Action.

## 2. Non-Goals (MVP)

- Path / multi-item curriculum
- Admin / curation panel, reviewer roles, audit trails
- Gamification (XP, streaks, achievements), leaderboards, social features
- Notes, bookmarks
- Full calendar / history UI, weekly review reports
- Direct learner ↔ AI chat
- Automatic video discovery
- Code execution sandbox for evaluation

## 3. Product Principles

| Principle | Meaning |
|---|---|
| Learning Item first | The unit of intent is a topic, no Path required. Core models never depend on Path. |
| Skill-centric | Skill is the unit of Mastery. Progress is derived from Skill state. |
| AI designs, human approves | AI proposes Outcome + Skill structure; nothing downstream exists until the learner approves. |
| System-driven mastery | Mastery is computed from recorded evidence only. Neither the learner nor the AI sets it directly. |
| No raw scores | The learner sees feedback and a qualitative level, never a number. |
| Guidance with freedom | One Next Best Action is always offered; any available activity can still be started. |
| Recovery, not backlog | After missed days the plan is rebuilt from current state; nothing is "replayed". |
| Simple, explainable adaptivity | Every adaptive decision follows a deterministic rule that can be stated in one sentence. |

## 4. Core User Journey

1. Add Learning Item (title, optional "where I am now")
2. AI generates Outcome + Skill structure → learner **Approves** or **Regenerates**
3. Learner sets Priority and Daily Time (may stay empty → item exists but is unscheduled)
4. Learner opens **Continue Learning** → gets one recommended Activity + alternatives
5. Session: Learn → Practice → Evaluate → Hint/Retry → Feedback
6. Attempt is stored → Mastery updated → next Review scheduled
7. Repeat. Missed days change nothing except which reviews are due.

## 5. Learning Item

| Field | Rule |
|---|---|
| Title | The topic. |
| Starting point | Optional free text ("I know basic syntax, never used classes"). Fed to the AI design prompt. |
| Outcome | AI-proposed, human-approved, visible to the learner. |
| Priority | 1–5, set by the learner. Used to order items in Continue Learning. |
| Daily Time | Minutes/day, set by the learner, may be empty. Independent per item. |
| Preferred Time | Optional, display/sort hint only. |
| Status | Active / Paused / Archived / Maintenance. |
| Progress, Mastery | Computed by the system only. |

Item states: `draft` (no approved design) → `active` → `paused` / `archived` → `active`. Deletion is not part of the MVP; archive retains all data.

## 6. AI Learning Design

| Stage | Output | Gate |
|---|---|---|
| Generate design | Outcome statement + 4–10 Skills with prerequisites and order | Staged as a draft, not live |
| Human review | Learner reads the draft | **Approve** → Skills become real. **Regenerate** → new draft. No manual editing in MVP. |
| Generate skill content | Learn text + 2–3 Practice activities + evaluation rubric, **per Skill, lazily** (first time the Skill is needed) | None — content references approved Skills only |

Critical rule: no Skill, Activity, or Plan exists for an item until its design is approved.

## 7. Resources

- **AI Text** — generated per Skill. Always present once the Skill has content.
- **Video** — a URL the learner pastes manually, per item or per Skill. Optional.
- The system marks one as Recommended (AI Text by default; Video if the learner attached one to that Skill). The learner may use either; the choice does not affect Mastery.

## 8. Activity Model

| Type | Purpose |
|---|---|
| Learn | Consume the Skill's AI Text (or Video). |
| Practice | Apply the Skill. Form is chosen by AI per Skill: MCQ, short answer, coding task, explanation, scenario/design decision. |
| Review | A Practice of the Skill executed after a delay, to test retention. Not a separately generated activity. |

Assessment as a separate type is dropped: a Practice attempted without hints *is* the assessment evidence.

## 9. Evaluation

| Practice form | Evaluator |
|---|---|
| MCQ / true-false | Rule-based, deterministic. |
| Everything else (short answer, coding, explanation, scenario) | AI with the stored rubric. Output is limited to `verdict` (correct / partial / incorrect) + feedback text. |

No code execution, no human review queue. Raw verdicts feed Mastery; the learner sees feedback only.

## 10. Feedback Loop

Wrong → Hint 1 → Retry → Hint 2 → Retry → Explanation/Answer.

- Max two hints, then the answer is shown and the attempt counts as incorrect.
- Correct after a hint is stored as weaker evidence than independent success.
- The learner may "give up" at any point (→ incorrect).

## 11. Mastery

- Internal numeric value per (user, Skill), 0–1000.
- Learner-facing level: Not started / Learning / Familiar / Proficient / Mastered.
- Changes only from evidence (attempts). No time-based decay; failed reviews lower it.
- Exact deltas, thresholds and review intervals: `DECISIONS.md` §3.

## 12. Adaptive Remediation

Deterministic rules, evaluated when the next activity is chosen:

| Signal | Action |
|---|---|
| Review failed | Review again soon (interval reset). |
| Practice failed, Learn already done | Another Practice of the same Skill. |
| 3 consecutive failures on a Skill | Re-learn (Learn activity again), then Practice. |
| Skill's prerequisite dropped below Familiar | Review the prerequisite first. |
| Independent success | Advance; schedule spaced Review. |

## 13. Planning

The learner sets Priority and Daily Time per item; the system decides what fills that time.

- **Today's plan** is *computed* from current state each day (due Reviews → remediation → next Skill in order), within each item's Daily Time. It is materialized only for today so completion can be tracked.
- **Weekly view** is read-only: today's plan plus known due Reviews for the next 6 days. Nothing is dragged or rescheduled.
- **Skip** replaces Reschedule: a planned activity can be marked "not today"; tomorrow's plan is computed fresh.
- **Recovery** is the default behaviour, not a feature: after any gap the plan is computed from current state. Overdue Reviews are capped per item per day so a long absence never produces a wall of reviews.
- Activities started outside the plan count for Mastery but do not complete a plan item.

## 14. Continue Learning

The primary entry point. Returns exactly one recommended Activity (with approximate duration) plus up to two alternatives, across all active scheduled items, ordered by Priority and review urgency. Choosing an alternative is not remembered.

## 15. Pause / Archive / Reactivation

- Pause / Archive: item leaves planning; Skills, Mastery, Attempts are kept.
- Reactivation: if the item has been inactive for more than 14 days, every Skill at Familiar or above gets a Review due today. No reactivation assessment.

## 16. Home

- Continue Learning (primary CTA)
- Today: planned activities per item, with completion state
- Active items: status, Mastery summary
- Items in draft (design not yet approved) with a clear "Review design" action

## 17. Functional Requirements

| ID | Requirement |
|---|---|
| FR-01 | Create a Learning Item without a Path; it may remain unscheduled. |
| FR-02 | AI proposes Outcome + Skill structure; nothing is live until the learner approves. |
| FR-03 | Approve or Regenerate the design. |
| FR-04 | Per-Skill content (text, practices, rubric) is generated lazily on first need. |
| FR-05 | AI Text and an optional pasted Video per Skill; one marked Recommended. |
| FR-06 | Practice form chosen by AI per Skill. |
| FR-07 | Rule-based evaluation for MCQ; AI rubric evaluation otherwise. |
| FR-08 | Hint → Retry → Hint → Retry → Answer. |
| FR-09 | Numeric Mastery + qualitative level per Skill, from evidence only. |
| FR-10 | Deterministic remediation (Review / Practice / Re-learn). |
| FR-11 | Priority and Daily Time per item. |
| FR-12 | Today's plan computed from state; weekly read-only preview; Skip. |
| FR-13 | Continue Learning: one recommendation + alternatives. |
| FR-14 | Any available Activity can be started outside the plan without completing the plan. |
| FR-15 | Archive retains data; reactivation after >14 days queues reviews. |
| FR-16 | No raw score is ever shown. |

## 18. Acceptance — Definition of Done

- Create an item, get a design, approve it, see real Skills.
- Open Continue Learning and complete a full Learn → Practice → Feedback session.
- Mastery level of the Skill changes after the session and a Review appears in the plan on its due date.
- A wrong answer shows a hint before the answer.
- Two items with different Daily Time get proportionally different plans.
- After 10 days without use, the plan on return is bounded (no backlog wall).
- Archiving hides the item from the plan and keeps its Mastery; reactivation queues reviews.

## 19. Technical Direction

- Laravel 13 + SQLite, single server, Blade + Tailwind. No SPA.
- AI: Google Gemini (`gemini-2.5-flash`) with structured JSON output. Calls are synchronous in the request; a queue is added only if timeouts appear.
- No audit tables: the current design draft and its approval timestamp are the only history kept.

## 20. Future Scope

Everything in §2, plus: Path Builder, curation panel, personalization from repeated choices, cohort analytics, code execution, AI validation step, human review queues, weekly review report, gamification.
