## What a Mission Need Statement (MNS) is

Before anything else, we need to document the problem — this gets the whole team on the same page and sets the direction for everything that follows. This document is called the **Mission Need Statement (MNS)**.

## First: what a real need looks like

A real problem/need that's worth using requirements engineering on has these four traits:

1. **It's complex** — the need requires examining several processes, missions, and root causes.
2. **It's recurring** — it doesn't happen once and go away; it's systemic.
3. It affects one or more **stakeholder missions** (the user is one stakeholder, not the only one).
4. The organization has the **experience and control** needed to solve it.

## What a need *isn't*

- An **individual** need unrelated to the organization's goals.
- Caused by a **lack of resources or bad process** (e.g. understaffing, insufficient budget, low team morale).
- A **non-engineering** issue (e.g. a management or political problem that a system-development effort can't fix).
- A problem with **only one possible solution** — since then you don't need the full requirements-engineering toolbox.
- A problem solvable by **a single subject-matter expert** (again, no need for the full process).

## Writing the need statement

An MNS is usually a single paragraph (sometimes more) that describes the current problem, its impact, and what the solution needs to address — **with absolutely no mention of a physical or software solution.**

Three sentences to write it:

1. **Current-state sentence:** what's the current state of the problem?
2. **Impact sentence:** what impact does that state have on users, their missions, and their goals?
3. **Need sentence:** now turn the first sentence into what the solution needs to fix.

### Example: the tree-seed drone

> **Current state:** Reforestation efforts are limited by the availability of human resources and the ability to access certain areas.
>
> **Impact:** Traditional reforestation methods are often slow and labor-intensive, requiring significant resources and time to achieve meaningful results.
>
> **Need:** There is a need for a system that supports reforestation efforts in remote or hard-to-reach areas — a system that can rapidly and efficiently plant trees in these areas, helping to combat deforestation, promote biodiversity, and mitigate the impacts of deforestation.

Notice: nowhere does it mention "drone," "quadcopter," or any other physical solution — just the problem, its impact, and what the solution needs to do.

## Traits of a good need statement

- **Use few adjectives** — adjectives are usually qualitative, not quantitative, and don't help define a measure of effectiveness.
- Don't include unnecessary background — that belongs in the "operational concept" document later, not here.
- No unnecessary words.
- Be as **quantitative** as possible — state "how bad" the current problem is and "how good" the solution needs to be with numbers. These numbers later become operational requirements, and come back up during system-level testing.
- Be short — a few sentences is enough (rule of thumb: 3 to 5 sentences).
- Must be understandable by all stakeholders, including potential users.
- **Must absolutely not dictate the solution.** Don't say, for example, "we need small four-bladed quadcopters that drop seeds" — that sentence eliminates every non-quadcopter solution from the realm of possibility.

## Reviewing the need statement

After writing it, check:

- Does the problem allow for multiple alternative solutions (i.e. we haven't boxed ourselves into one)?
- Is it 3 to 5 sentences?
- Does it have a quantitative measure/target that can later be used to judge success?
- Does the "need" sentence mirror the "current state" sentence, as if it's now solved?
- Are the sentences simple, factual statements — no opinions, no root-cause analysis, no solutions?
- Does it avoid background explanation?

## Where the need statement sits in the hierarchy

The need statement sits **at the top of the requirements hierarchy** — like an umbrella requirement for the whole project. Every lower-level requirement should eventually trace back to it. Rule of thumb: every requirement we write should be shown to help achieve this need — except constraint requirements (which are imposed from outside, like airspace restrictions the end user doesn't care about at all, but the developer still has to comply with).

## Rules for writing goals

After the need, it's goals' turn — generating them uses requirements-elicitation tools (we'll get there in a few lessons), but a few general rules right now:

1. **Be concise**, but not too concise; avoid assuming a solution.
2. **Be precise** — write it in as measurable terms as possible.
3. **Don't lock a solution into the goal.** If a stakeholder insists on a specific solution, ask "why" to get at the underlying capability.
4. Use **active voice** — passive voice confuses the reader.
5. You can decompose high-level, abstract goals into sub-goals.
