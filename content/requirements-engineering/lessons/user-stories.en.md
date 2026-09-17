## Agile and user stories

There's a process for quickly and effectively developing software called **Agile**. Summarized: continuously developing and testing code in small segments called **sprints** — a few days to a few weeks, but two weeks is typical.

In Agile, the features implemented don't always come from the requirements this course focuses on; they come from reviewing current features and a **backlog** of features desired by customers, end users, and other stakeholders (like system or database administrators). These features are described using **user stories**.

A user story is a type, or subset, of scenario, which is why it's covered here in this section of the course. Besides agile software development, user stories can also be used as a tool during requirements elicitation itself — a topic we'll cover in the next module.

## What a user story is

A user story is a description of a small feature that a user or other stakeholder would like to see in their ideal system. Recall that "user" doesn't always mean the end user — it can also refer to support roles like maintainers, administrators, owners, or even adversarial users like hackers and vandals. A user story describes a small feature that helps the user achieve their goal, much like a regular scenario, but in a short, single sentence.

User stories help product developers better understand what their users want the system to do, and sometimes how well they want it done. They also help developers prioritize features and manage the overall development of the system.

## Structure: who, what, why

A user story is a single sentence containing a **role**, a **function/feature**, and the **benefit/justification** for it — who, what, and why. It's typically written in an informal, conversational style, and should be as specific as possible, so nothing gets lost in translation between what the author had in mind and what designers implement.

Example:

> As an ATM user, I want to transfer money from any account to any other account at the same bank, so I can manage my money remotely without having to go to the bank.

- "As an ATM user" = who
- "I want to transfer money from any account to any other account" = what
- "so I can manage my money remotely" = why

As we move from the user story toward implementing the feature, further discussion is usually needed for clarification — since the user story may not fully capture what its author had in mind. Like scenarios and requirements, user stories can be refined and updated as new information comes in, as we move from defining the system concept toward implementation, integration, and production. This is especially helpful when requirements aren't contractually binding, making them easy to change if managed properly — one of Agile's benefits.

## Six rules of thumb: INVEST

Before moving to use cases, here are six rules of thumb for creating user stories (from an article by Bill Wake at XP123): a user story should be **Independent**, **Negotiable**, **Valuable**, **Estimable**, **Small**, and **Testable** — spelling out the acronym **INVEST**.

**Independent.** A user story should be written so the organization can implement, test, and deliver it on its own — without depending on other user stories. If two user stories seem linked or dependent, they should probably be combined into one. This interacts with the "small" trait. Independent user stories let us keep a prioritized backlog and implement them in priority order — giving the customer tangible features earlier for feedback.

**Negotiable.** If a user story affects other stakeholders (e.g. one story's feature impacts another story's feature), there needs to be room for discussion among the affected stakeholders to reach consensus. Negotiation can happen anytime before implementation starts; once implementation begins, any change ripples into other parts of the system, so it's best to negotiate and reach consensus beforehand, tracking any changes made.

**Valuable.** It must be valuable to users or stakeholders. This isn't always as simple as it sounds — a feature may be valuable to one stakeholder and not to another. For example, diagnostic codes aren't useful to a car's driver, but are very valuable to a maintainer. A user story should trace back to an originating need, problem, or project goal — if a feature doesn't support these, it might be unnecessary (a term called **gold plating**: adding more than what's needed). Value has to be balanced against feasibility (cost, schedule, technology).

**Estimable.** It must be possible to estimate the scope of implementing, integrating, troubleshooting, and regression-testing the feature — in terms of time, money, and resources. If a user story isn't detailed enough to estimate these, it needs to be broken into smaller pieces.

**Small.** It must be small enough to implement and test within a single sprint (typically two weeks). If it takes longer, it needs to be decomposed. If a user story has multiple conjunctions ("and", "or"), it likely needs to be split — for example, "as an ATM user, I want to withdraw cash, check my balance, transfer, and deposit funds" should become four separate user stories.

**Testable.** It must be possible to verify the team implemented it correctly. For example, "as an end user, I want the ATM to have a user-friendly interface" isn't testable, because "user-friendly" means different things to different people. To make it testable, we'd say "I want the ATM to comply with interface standard XYZ" — which itself can be decomposed into lower-level user stories or requirements.

> Bottom line: a user story is a short sentence with a "who, what, why" structure describing a small feature. The six INVEST rules (Independent, Negotiable, Valuable, Estimable, Small, Testable) help us write good ones.
