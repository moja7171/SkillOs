## Need and goal: the foundation of everything

Requirements engineering starts by identifying a **need** — an undesirable situation that needs improving. A need describes a market or technology gap we can exploit to gain or hold onto market share. **Goals** say exactly what stakeholders want the system to give them — usually to improve their quality of life or standard of living.

Needs and goals usually come out of comprehensive market analysis — stakeholders and the marketing team working together to review existing systems' performance and find where the gaps are.

## From need to solution: a hierarchical path

A need is abstract. We decompose it into goals. Goals (if too broad) decompose into sub-goals. Sub-goals turn into **missions**, missions into **mission phases** (pre-mission, mission, post-mission), and phases into **scenarios** (which say under what conditions, by whom, and in what environment the mission is carried out). Scenarios become the foundation of our requirements and functions.

We usually write needs and goals very briefly — a sentence, maybe a few. They only say **what** needs to change, not **how**.

## The golden rule: stay implementation-free

The most important point of this lesson, and one of the most important in the whole course: **a need or goal must not make any assumption about the solution.**

### A bad example

> "We need a police car with at least a 350-horsepower engine for fast acceleration."

This sentence makes two bad assumptions: (1) it assumes the solution must be a "car," (2) it assumes it needs a "350+ horsepower engine" — with no analysis backing either assumption up.

### A good example

Instead, we should talk about **capability**:

- The ability to keep pace with sports cars and motorcycles in highway pursuits
- The ability to jump a curb to chase a suspect going the wrong way
- Fast acceleration and braking
- The ability to keep operating even after being shot

None of these are solutions — they're all capabilities. You might picture a police car in your head, and that's fine, but we shouldn't bake that assumption into the need/goal itself — so that later, after real analysis, if it turns out the solution really should be a car with a powerful engine, we write that down then.

## Why this matters so much

The people who actually design and implement the solution (design engineers, subject-matter experts) need maximum freedom to find the most optimal solution. If we lock in the solution in the need/goal/requirement from the start, we've taken that freedom away from them — and because humans naturally look for a quick solution (rather than methodical analysis), this often happens **unintentionally**.

For simple problems (like fixing a light switch at home), this shortcut works fine, but for product development, it can lead to undesirable solutions.

## A couple more parts of this rule

- Goals **must not describe the end state** — only the capability or mission outcome we expect, not how to get there.
- Needs and goals are only a foundation for scenarios, mission descriptions, User Stories and Use Cases — and those become the foundation for requirements and functions. Never jump directly from a need to a solution.
