## Why a requirements engineer needs to know systems engineering

Understanding systems engineering helps you write more accurate, effective requirements — it gives you a holistic view of product development, emphasizes continuous, iterative refinement of requirements, and builds better communication and collaboration with stakeholders.

Formal definition (ISO/IEC/IEEE 15288): systems engineering is an interdisciplinary approach that governs the total technical and managerial effort required to transform stakeholder needs, expectations and constraints into a solution, and to support that solution throughout its life.

That's a mouthful, isn't it? Even this course's own instructor admits that after years of teaching it, he still can't recite it from memory. A simpler definition: **systems engineering is a set of tools for breaking a complex technical problem into smaller, more manageable pieces, solving each piece, and combining them into an overall solution.** Without systems engineering, many of the complex products we have today (cars, aircraft, operating systems) simply wouldn't be possible — because a single human's capacity to manage complexity is limited.

## Mission system vs. enabling elements

Systems engineering doesn't only care about the end item itself (what we call the **mission system**). Support infrastructure — call centers, troubleshooting guides, user manuals, packaging, spare parts — has to be ready too. These are called **enabling elements**.

## Four core activities of the systems engineering process

| Activity | What it does |
|---|---|
| Requirements development | Collecting, refining and developing requirements into a solid specification — this course's main focus |
| Functional & mission development | What missions? How well? What functions are needed? (This is where diagrams like N-squared, functional flow, activity, sequence and state-machine get produced) |
| Physical definition | Assigns each function to a piece of hardware/software (physical allocation); this is also where "make or buy" decisions happen |
| Verification & validation | Does what we built actually meet the functions and requirements? |

These four activities run **iteratively and recursively** — one round's output is the next round's input, and each round adds more detail.

### Example: an ATM context diagram

Picture a context diagram for an ATM: inputs include the user's credentials, user commands, cash from the resupply guard, new parts from the maintainer; outputs include the requested cash, feedback to the user, a receipt, broken parts to the maintainer, requests/responses to the user's bank. From these, scenarios and use cases are built (withdrawing cash, a hacker attempting a break-in, the guard refilling cash, the owner collecting fees, the maintainer performing a repair) — and from these scenarios, the system's required functions are extracted.

## The analysis and control toolbox: eight technical management processes

Per the US Department of Defense's systems engineering guidebook, the development team uses these 8 management processes alongside the 4 core activities:

| Process | Summary |
|---|---|
| Technical planning | Schedule, budget, contracts |
| Technical assessment | Monitoring project progress against plan |
| Decision analysis | Justifying and documenting important decisions (e.g. trade studies) |
| Risk management | Identifying and preventing bad outcomes |
| Configuration management | Keeping a shared baseline and controlling changes |
| Technical data management | Archiving and keeping accessible the data needed for development/production/support |
| Requirements management | Identifying, allocating, tracing and sharing requirements (this course's final module) |
| Interface management | Defining how different system elements "talk" to each other |

## Requirements engineering vs. systems engineering

The key point of this lesson: **requirements engineering is a subset of systems engineering, not the other way around.**

- Systems engineering **requires** requirements engineering (since requirements development is one of its 4 core activities).
- But requirements engineering doesn't **always** require systems engineering — for relatively simple systems (say, an ordinary IT network, versus a stealth fighter jet), using the full systems-engineering toolbox might be a waste of resources, but you still need to write good requirements.

## Hierarchy and recursion

Any sufficiently complex element of the system hierarchy (a subsystem, a component) can itself be treated as an independent "system" and have these same 4 activities run on it again, at a smaller scale. For example, the propulsion subsystem engineer develops requirements, missions, functions and physical allocation specific to just that subsystem.

**When do we stop?** When the element in question is no longer complex enough to justify the cost of systems engineering — at that point we switch to specialized tools directly (e.g. for designing a circuit board, we use electrical engineering tools, not systems engineering).

> Bottom line: systems engineering is about managing complexity. Requirements engineering is one of its core, always-necessary activities — but systems engineering itself is only needed when the system is genuinely complex.
