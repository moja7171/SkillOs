## What a scenario is

A scenario is a stepwise walkthrough of one mission we expect the system to perform, in a specific context and operational environment. For example, for the tree seed planting drone: an operator transports the drone near the deforested site, unpacks and sets it up, loads it with seeds, flies it out to plant, and then flies back to the point of origin. The operator can then replenish the seeds and turn the drone around for another mission, or end the mission.

This is a high-level description that doesn't drive or assume any solution — other than the fact that the operator uses a "drone" and it drops tree seeds, which was assumed at the outset. If we don't yet know what the system-level solution is, we can replace "tree seed dropping drone" with "reforestation device" to keep it vaguer; then we'd need system-level analysis and trade studies to determine the actual solution.

## What a scenario describes

A scenario describes how the system and user work together, step by step, to accomplish a goal. Early on, scenarios are very high-level and vague; later in the life cycle we can decompose a scenario into lower-level use cases and user stories with much more detail, as the system's internal workings become clearer.

Beyond the mission walkthrough itself, a scenario includes **actors** — the user or other stakeholders — and their roles; assumptions such as **preconditions** that must hold before the scenario begins, and **postconditions** expected afterward; **resources** required to perform the mission; and the **operational environment**.

Sometimes the operational environment alone drives a distinct scenario. Starting, taking off, and flying an aircraft in snowy Arctic conditions is different from doing the same in a temperate environment — the former might drive solutions like skis instead of wheels, a backup battery, a heating or de-icing system. But we wouldn't mention any of these solutions in the scenario itself — only the capabilities the system provides to the user or actors.

Each mission can have multiple scenarios, and they don't have to be limited to operating the system — they can pertain to any phase of the life cycle (testing, production, deployment, heavy maintenance, retirement), though the operational or maintenance phase is the most common.

## Scenarios and goals

Scenarios highlight the important features stakeholders require of the system, based on the needs and goals already captured. Scenarios also **refine** those needs and goals — adding more detail and rigor toward meeting them.

Sometimes a scenario describes a mission-failure point. For example, if our airplane loses power mid-flight, it should give the pilot the capability to land safely, assuming a flat piece of land is available at a reasonable distance — in other words, the airplane shouldn't simply drop out of the sky like a stone. Or if the tree seed dropping drone gets caught in power lines, the system needs a capability to be retrieved — how it's retrieved can be figured out later; we just need to highlight that the capability must exist.

## A few important notes

**The number of scenarios needs to be balanced.** More scenarios generally build a more solid foundation for the requirements pulled from them — but too many overwhelm the team, or end up retelling the same story with diminishing new capability, driving up cost and schedule without adding enough value. Judging this line is one of the requirements engineer's important skills, learned through practice and domain experience.

**A scenario isn't the end in itself — it's a means to an end**, just like requirements. We pull capabilities from scenarios and convert those into requirements. We can't jump straight from the need statement or goals to requirements; we can only get there through the mission domain.

**Scenarios can be created at any point in the life cycle.** Usually they're created before requirements elicitation, but sometimes new questions arise during elicitation or decomposition that call for new scenarios — that's perfectly normal.

A scenario describes a process or workflow the system performs together with its users, and is usually used to set the stage for further analysis — for example, generating the data flow, activity, and sequence diagrams we'll cover later in this course.

> Bottom line: a scenario is a short description of how a mission gets accomplished — speaking in terms of capabilities and functions, not solutions.
