## Three main requirement types

Different organizations categorize requirements differently. This course uses the three types accepted by the ISO/IEC/IEEE 29148 standard and most academic and industry sources: **functional, quality, and constraint**.

> Requirements engineering isn't a hard science with rigid rules — it's a toolbox. If your project needs a different categorization, that's perfectly fine.

## Functional requirements

Describe how a system is expected to behaviorally react to an external stimulus — "when X happens, the system shall do Y."

Example: "The system shall accelerate forward when the driver presses the accelerator pedal." (behavior = accelerating, stimulus = pressing the pedal)

Functional requirements can also say what a system should **not** do — but these are harder to define, since the universe of things a system shouldn't do is practically infinite; it's better to focus on the most common scenarios (based on mission analysis).

The input/output of a functional requirement can be: force, energy, data, signal, material, or service (immaterial). For an ATM:

| Type | Example |
|---|---|
| Energy | Power from the outlet to the ATM |
| Force | Physically holding the card during a transaction |
| Data | Reading the card's information |
| Signal | Pressing a PIN button on the keypad |
| Material | The card itself (input), cash and receipt (output) |
| Service | Remote, 24/7 access to cash |

A functional requirement is usually written as a **verb + object**, and the system's input/output often forms the core of that verb.

## Quality requirements

Describe the system's performance characteristics using numerical measures (measures of effectiveness / measures of performance) — like speed, acceleration, accuracy, timing, efficiency, flexibility.

Interesting detail: a quality requirement usually emerges from a functional requirement. For example, "the system shall accelerate" (functional) later, after analysis, becomes "the system shall go from 0 to 100 km/h within 8 seconds" (quality). Important rule: **keep the functional requirement and derive the quality requirement from it** — don't delete and replace it. That way, **traceability** is preserved — you can always tell where a requirement came from.

⚠️ Quality requirements sometimes **conflict** with each other — a car's speed conflicts with its weight (heavier usually means slower). As a requirements engineer, you need to catch these conflicts early, with help from the relevant subject-matter experts.

## Constraint requirements

Restrictions imposed on the system from outside (by the organization or other stakeholders) — on how it's designed, developed, tested, produced, deployed, operated or retired. Constraints shrink the space of possible solutions — sometimes all the way to zero (e.g. building affordable housing when material costs, building codes and inflation all stack up together).

A constraint can be:
- Cultural, legal, regulatory
- An unchangeable law of nature (like conservation of energy — we still haven't built a 100%-efficient motor)
- A project constraint (cost, time, skilled personnel, tools, facilities)
- A logistics constraint (street size, underpass height, container size)

Important: constraints are usually **not negotiable**, except in extreme circumstances. Instead of trying to negotiate them away, identify them as early as possible and bake them into the specification.
