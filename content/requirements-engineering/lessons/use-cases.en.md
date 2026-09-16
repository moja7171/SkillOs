Have you ever used a device expecting it to do one thing, but it did another — or it was hard to use, or didn't have the features you expected? Or maybe you pressed a couple buttons and the whole thing crashed. These kinds of problems can be addressed with a powerful tool called **use case analysis**.

## What a use case is

In requirements engineering, we build use cases as a tool to identify and refine requirements. **A use case is a list of steps a user (formally called an "actor") takes while operating the system** — not just operation, but also deployment, support, or disposal of the system. The actor performs these steps to achieve some desired outcome or goal tied to a life-cycle concept.

## Why use cases are useful

- They help the requirements engineer determine exactly what functions/activities the system must perform, and how well.
- Because we start high-level and decompose into smaller, nested sub-use cases, they foster **top-down design** — an optimal way to engineer a system or solve a complex problem.
- They're a great way to think ahead about errors and faults, and how the system recovers — instead of discovering them during test or after deployment, which is exponentially more expensive and time-consuming.
- They help break a larger problem into smaller, manageable pieces — each solved on its own and then combined into a system-level solution.
- They help ensure we build the right system that meets the initial need, keeping the team from getting sidetracked or gold-plating the system.
- They're easy to understand for laypeople and stakeholders, while also being useful for design engineers implementing and prototyping.

## Where use cases fit in the big picture

Use cases are part of the mission scenario set. Recall that the mission domain comprises life-cycle concepts describing the primary missions the system must perform at each life-cycle phase (typically starting at the test/verification phase). These missions decompose into mission phases, which decompose into sub-phases, each with several applicable scenarios. Use cases are part of the characteristics that define those scenarios — the assumptions about how the user interacts with the system, how the system responds, and how the user reaches their goal.

You might think you'll end up with hundreds or thousands of use cases covering every possibility — philosophically you might be right, but practically it's not advisable. Building use cases is a lot of work; doing it to excess slows down the design team or can even introduce errors. As requirements engineers, we should focus on formulating use cases for the most important or higher-risk areas of a project, rather than covering every nuance. Experienced design teams don't typically need a use case telling them how to do something they've done a dozen times before.

## Elements of a use case

- **Actor(s):** stakeholders that interact with or call on the system for one or more of its services.
- **Summary paragraph:** a short description of what the use case is about.
- **Goal:** all steps in the use case are performed to achieve this — the primary outcome the system provides the actor(s).
- **Preconditions:** things we assume to be true about the system, environment, or actors for the use case to achieve its goal. If not met, another use case is needed to establish them first.
- **Guarantees:** the least the system does for the actor even if the goal isn't met — e.g. if an ATM can't dispense cash, it at least gives a reason and returns the card.
- **Trigger:** what sets the use case in motion. Some keep the trigger separate from the course of events, others treat it as step one — either is fine, but naming it explicitly keeps everyone focused on what starts things off.
- **Course of events:** the main section — a numbered list of actions the actor(s) and system take to accomplish the primary goal. **The primary course of events is entirely sunny day** — it shouldn't include exceptions or alternatives, unless the use case itself exists specifically to address exceptions/alternatives from a parent use case (there are separate fields for those).
- **Alternative paths:** sometimes users do things a little differently to reach the same goal — like saving a document via File→Save, Ctrl+S, or a disk icon.
- **Exception paths:** what happens when something goes wrong and takes the actor or system off the main course — the failure modes and the steps taken to get back on track. If there's no recovery, the use case fails, but the actor still gets the guarantees.
- **Extension paths:** reference points where a use case can call another use case — created when the same course of events repeats across multiple use cases, so it can be broken out into its own extension use case and referenced, meaning developers only code that portion once.
- **Administrative info:** creation/change date, author, implementer, status, and so on.
- **Diagrams:** usually in UML/SysML format.

## The use case diagram

A use case is drawn as an oval with its name inside. The actor connects to it via a **reference association**. A person actor is shown as a stick figure; a machine actor as a block or icon. The association carries a **multiplicity** indicating how many instances of the actor or use case are involved — e.g. "0..*" meaning zero to any number. The system itself can be drawn as a block enclosing the use cases, with actors outside it, similar to a context diagram.

If a use case is general and more specific use cases specify it further, a **generalization** relationship is used. If a use case references another use case within it, an **include** relationship connects them. For exception or alternative paths, an **extend** relationship is used — these are circumstantial use cases that may or may not apply depending on the situation.

## Example: withdrawing cash from an ATM

The primary actor is the customer; the supporting actor is the bank. The "Withdraw Cash" use case is a sub-use case under a primary use case. Goal: the customer withdraws cash from their checking or savings account. Precondition: the customer has an active checking or savings account. Success guarantee: the customer leaves with the desired cash and an optional receipt.

An important point about writing course-of-events steps: they must be **generic and broad**, without specifying a solution. For example, instead of "the customer presses the touchscreen or types on the keypad," we say "the customer interacts with the ATM" — because we don't yet know if the final solution uses a touchscreen or a keypad. Instead of "the customer inserts their ATM card and types a PIN," we say "the customer provides account credentials" — because the final solution might be facial recognition or a fingerprint. Design engineers decide these details later in the life cycle.

The main course of events is sunny day only — the customer has sufficient funds and their credentials verify successfully. Cases like "insufficient funds" or "credentials not verified" go in the exceptions field.

The first few steps (providing credentials) and last few steps (asking "anything else?" and ending the transaction) repeat across every ATM use case (withdraw, deposit, check balance, transfer). So developers don't have to code this logic repeatedly, these get pulled into their own **Initialize Transaction** and **Finalize Transaction** use cases, referenced via the include relationship from every other use case.

## Steps to writing a use case

Use cases are usually written by a small team (not required, but it brings a wider range of ideas and potential issues). The steps:

1. **Review source documentation** — the need statement, context diagram, operational concept (OPSCON), and any applicable requirements from the specification.
2. **Identify and list the primary and secondary actors** that interact with the system.
3. **Identify and list the actors' goals**, usually drawn from the OPSCON or requirements documentation.
4. **Identify and list high-level use cases** — as general as possible, since they'll be decomposed later — and prioritize them.
5. **Select the highest-priority use case** and begin working on it.
6. **Identify the actors, stakeholders, preconditions, and guarantees** for that specific use case.
7. **Draft the main course of events** (sunny day only).
8. **Brainstorm alternative, extension, and exception paths** — usually the largest part of the work, since there are many possible cases.
9. **Create placeholders for lower-level use cases** to develop later.
10. **Review and revise the use case.**
11–12. **Move to the next use case** in the prioritized list and repeat.

For a system like an ATM, a small team might take one to two months to produce, refine, and get stakeholder approval for all relevant use cases — assuming the ATM had never been invented and this is the first time developing use cases for this kind of system.

## What to do after building use cases

Use cases are part of the mission domain and help generate the functional interactions the system must accomplish. A requirements engineer converts use case actions into system activities or actions in the system model, or — if the use case is mature enough — directly converts the steps into requirement statements. A requirements engineer may also generate N-squared, sequence, state machine, or activity diagrams from the course of events and its alternatives/exceptions. The use case also traces to driving requirements, the operational or other life-cycle concept, other use cases, the context diagram, or legacy functions.

> Bottom line: a use case is the most detailed mission-domain tool — a list of actor and system steps toward one goal, with preconditions, guarantees, a trigger, a sunny-day main course of events, and alternative/extension/exception paths. Focus on the most important, highest-risk parts of the system, not covering every possible case.
