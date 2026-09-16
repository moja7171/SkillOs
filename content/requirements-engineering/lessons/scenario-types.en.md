## Existing system or future system

A scenario can describe a mission for a future, not-yet-built system, or a mission an existing system currently performs. In other words, scenarios can describe situations either to improve an existing system, or to define a system that doesn't exist yet.

## Sunny day vs. rainy day

Scenarios are either **sunny day missions** or **rainy day missions**.

A **sunny day mission** describes nominal operating conditions — everything works properly, the user operates the system the way we expect, and there's no deviation from the path to accomplishing the mission. Sunny day missions are typically created first and form the foundation for building rainy day missions.

A **rainy day mission** is the opposite — using the system under non-nominal conditions. It doesn't necessarily mean literal rain (though it can), it can also include:

- The user not using the system the way we designed it to be used
- A harsh or unexpected mission environment (e.g. driving a small sedan off-road or on a beach)
- The user completing the mission, but it takes longer than expected because the system is too complex — probably the most common kind of rainy day mission, since engineers tend to design systems for engineers to use, rather than for the actual end user, who typically isn't an engineer (the curse of knowledge again)
- Hostile actors working against the system — anti-aircraft guns trying to shoot down an enemy fighter before it drops bombs, or a hacker trying to break into an ATM

In rainy day scenarios, the system fails to meet its goal; if the scenario is well constructed, the story continues with mitigations that put the system back on track toward mission accomplishment — these are called **exception paths**, which we'll cover more when we get to use cases.

## Abstraction level: broad vs. detailed

Early in the life cycle, scenarios typically describe how the whole system operates in the operational environment, without looking inside the system — because its internal workings are unknown, and we don't want to force a solution onto the design engineer (even if we are a design engineer ourselves, we usually specialize in just one narrow area).

Later, once we've allocated functions to components and identified the primary subsystems, we can write scenarios at the subsystem level and keep decomposing until we reach a level ready for implementation.

An **abstract scenario** doesn't look inside the system; it describes the capabilities the whole system provides to the end user and the operational environment, in generalized terms. Example: "the user drives the vehicle to the destination, exits, and secures it." These overlap with what's called a **user story**, which we'll cover later in this section.

A **detailed scenario** starts with knowledge of the system's internal workings; it describes what specific subsystems provide, told through specific instances. Example: "on a clear sunny day the user goes to their car, drives to the store, finds a spot, parks, gets out, locks the vehicle, sets the alarm…" This is more detailed because it names specific actors, inputs, and outputs (locking, setting the alarm) that imply a solution.

Rule of thumb: use detailed scenarios for important or already-solidified information; use abstract scenarios for less important or still-unknown information. Speak in the abstract if you want to avoid conflicting information between scenarios; speak in detail if you want to force stakeholder discussions toward agreement.

## The most detailed kind of scenario: the use case

A **use case** is the most detailed kind of scenario — it focuses on a specific sequence of interactions between the system and its actors in a specific situation. A scenario is typically decomposed into several use cases, each focused on a single goal or interaction; when that goal completes, a new use case begins. Use cases typically have a single primary actor with supporting actors, and one or more diagrams (use case, sequence, state machine, activity) to visualize the sequence — which we'll cover later in the course.

## External/context scenarios

Scenarios can also be **external or context-related** — describing how external actors interface with each other (trading information or materials) in a way that plays a role in how our system is used. These external scenarios enhance the story and add background — for example, how a government process or business regulation affects the scenario — and help the team fully grasp how the system fits into the bigger picture.

> Bottom line: scenarios can be for an existing or future system, sunny day or rainy day, abstract or detailed (with the use case as the most detailed kind), and external/contextual.
