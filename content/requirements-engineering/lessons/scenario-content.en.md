This lesson walks through the elements found in a scenario — these apply to any of the scenario types covered in the previous lesson.

## Actors

An actor is a person or piece of equipment that interacts with our system at some point in the scenario. It's important to identify the actor at each step so we can separate what the actor does from what the system does — because it's the system's actions that ultimately become requirements in the specification. We can also capture how actors interact with each other (as in an external scenario from the previous lesson) for more detail and clarity.

Scenarios can also include assumptions about actors — their physical attributes, education level, beliefs, common behaviors, level of training on the system, and other relevant information.

## Roles

Roles identify the kinds of tasks actors are expected to perform — they define the actors' responsibilities in the scenario.

## Goals

Goals are tied to specific scenarios and provide traceability, keeping the team focused on the scenario's purpose: fulfilling one or more of the system's goals.

## Resources

Resources describe what's required — by actors, the system, or some external element — to fulfill the scenario's goal. If not all resources are present at the start, the scenario won't succeed, or the system enters a **degraded mode**: still able to accomplish the mission, but in an undesired way (like driving to the store on a flat tire — possible, but not smart or safe).

Resources can include people, data, funding, energy, signals, or knowledge. For the tree seed dropping drone: a power source, a transport vehicle to the deployment site, the actual seeds, the user or maintenance manual, and — if we know the power type — fuel or batteries. These naturally imply solutions, which means we're working later in the life cycle where the system's internal workings are known.

## Preconditions and postconditions

**Preconditions** are assumptions about what must be true before the scenario begins — concrete ones (power provided, internet connection) or more abstract ones (operators are fully trained on the system).

**Postconditions** describe what must be true after the scenario ends — typically successful mission accomplishment, and can include consumed resources. The set of elements necessary for a successful scenario is sometimes called a **success guarantee** (we'll revisit this term with use cases).

Things that remain true after the scenario ends regardless of whether the mission succeeds are called **minimal guarantees**. Example: with an ATM, we requested cash but our account was empty and the request was denied — but the ATM still gave back our card and a statement showing the empty account. The card and that data are the minimal guarantees.

## Operational environment

The operational environment describes the conditions the system is expected to operate under while performing the scenario — atmospheric phenomena (storms, rain, snow), environmental factors (air pressure, temperature, humidity, radiation), terrain factors (rough terrain, hills, mountains, forests, icebergs, underwater), and man-made factors (paved roads, sidewalks, buildings, towers, vehicles, power lines, ports, gas/water lines).

## Main, alternative, and exception scenarios

A scenario includes a **main scenario**, **alternative scenarios**, and **exceptions**.

The **main scenario** captures the most common sequence of actions that fulfills the goal — walking step by step from the preconditions to successful mission accomplishment, delivering the postconditions and success guarantees. It should be plain, jargon-free, and absolutely must not force or imply solutions — speak only in terms of capabilities or functions performed by the actors or the system. Some organizations speak purely from the system's perspective, some purely from the actors', some mix as appropriate — the tone should ultimately let us derive requirements from it. Software-heavy, UI-driven systems tend to speak from the actor's perspective; complex technical systems whose internal workings are beyond the user's comprehension (like a helicopter) tend to speak from the system's perspective, since the point there is to inform the design engineers, not the actors.

**Alternatives** describe other ways of accomplishing the scenario — branching off from the single main scenario, replacing all or part of it, still satisfying the goal, but not necessarily the most common interaction. Example: saving a Word document — Ctrl+S, or File → Save As, or closing the app and hoping for a save prompt. These are all alternative paths to the same goal. We shouldn't force stakeholders down one specific usage — let them speak openly about the different ways they see themselves using the system; the more flexible the system, the more marketable it is.

**Exceptions** capture failures or unexpected sequences from users or hostile actors — hardware failures, network failures, vandalism, theft. An exception documents how the system might recover, if possible, and still meet the goal fully or partially. An exception typically starts at one of the scenario's steps and walks down what's called an **exception path**. Example: the tree seed dropping drone's sunny-day scenario runs into power lines mid-flight — here the system needs to detect it's caught in an obstacle, notify the user, and maybe try to work itself free by folding and unfolding back into its transport configuration.

> Bottom line: a scenario is built from actors, roles, goals, resources, pre/postconditions, operational environment, and a main/alternative/exception structure — together these form the foundation we later pull requirements from.
