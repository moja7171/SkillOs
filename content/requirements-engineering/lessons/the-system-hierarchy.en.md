## Why we decompose a system

A complex system can't be understood or managed all at once. So the requirements engineer breaks it into smaller, more manageable pieces — this is called building a **system hierarchy**.

Per INCOSE's definition: a system hierarchy is an organized representation of a system's structure using **partitioning relationships**. It shows how each element relates to the element one level up, without getting into the details of how they interact with each other — the focus is purely on "what is this made of."

## Layers of the hierarchy

Take the car example:

| Layer | Example (car) |
|---|---|
| System | The car itself |
| Subsystem | Engine, transmission, chassis, wheels & brakes, electrical system |
| Component | E.g. inside the engine subsystem: piston assembly, fuel injection, exhaust |
| Subcomponent | The pieces inside each component |
| Part | Piston ring, nuts and bolts, mounting bracket |

Each subsystem, once integrated with the others, fulfills the system's higher-order goal — the engine, transmission, chassis and electrical system work together so the car can provide transportation.

## Notation: the Block Definition Diagram

In SysML/UML, the "made of" relationship is shown with a **filled diamond (composite association)** on the parent element's side. When you see this diamond, read it as "is composed of" (top to bottom) or "is a part of" (bottom to top):

- The system **is composed of** subsystems.
- Each subsystem **is composed of** components.
- And so on down the chain.

### Repeated count: multiplicity

If a subcomponent is made up of several identical instances of a part (say, 4 identical bolts), we don't need to show each bolt separately. Instead, we write the repeat count (multiplicity) next to the connecting line — e.g. "4" next to "bolt."

In the car example: the car is made up of 9 subsystems (powertrain, electrical, starting & charging, suspension & handling, etc.). The powertrain subsystem is made up of several components (engine block, air metering, cylinder head, cooling). And the power-generation component is made up of 4 pistons + 4 connecting rods + 4 piston pins — shown with multiplicity instead of repetition.

## The hierarchy depends on your perspective

What sits "at the top" of the hierarchy depends on whose point of view you're looking from:

- If you're a **car manufacturer**, the car is at the top of the hierarchy and the engine/transmission sit one level down, at the subsystem level.
- If you're an **engine manufacturer** (not a car manufacturer), your core product is the engine — so from your perspective, the engine is at the top of the hierarchy and pistons/crankshaft/spark plugs sit one level down.

## Hierarchies aren't just for hardware

The examples so far have been physical, but a hierarchy can also represent abstract concepts — like requirements, functions, or interfaces between elements. Later in the course we'll cover requirements hierarchies too.

## How far down should we decompose?

A common question: do we have to go all the way down to nuts and bolts, or is a few layers enough? The answer depends on the hierarchy's purpose:

- During concept development, the goal is to decompose the system to a level where each element can be **developed and integrated** independently — usually down to the component or subcomponent level.
- Another criterion: if you can decide to **build it in-house, buy it, or reuse it from another system** (a "make-buy" decision), no further decomposition is needed.

## Last point: the hierarchy is a result, not a starting point

A system hierarchy is a **product** of the systems engineering process, not something designed first and then built on top of. The hierarchy just **organizes** the engineering results to make them easier to manage — and lets the team hand off each part to a specialized group.
