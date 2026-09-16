## The Requirements Domain

A system's requirements aren't written in isolation — they form a whole, interconnected **domain** that includes the requirements themselves, the requirements tree, requirements diagrams, verification and traceability matrices, and every analysis or model used to build them.

This domain traces in two directions:

- **Backward**, to the originating need/stakeholder that caused the system to exist.
- **Forward and sideways**, to the other domains (like the functional and physical domains), since many elements in those domains come from these same requirements.

The primary goal within the requirements domain is to decompose requirements far enough that each one can be allocated to a single architectural entity in one of the other domains.

## What "specification" actually means

When we say "specs," we usually mean a product's technical specifications (like when buying a phone). But in requirements engineering, **specification** has a more formal meaning: a formal (and usually contractually binding) agreement that contains a system's essential requirements. A specification can describe the whole system, its materials, its manufacturing/operating process, services provided, or even the verification methods.

A good specification should answer these questions:

1. What does the system need to do? And how well does it need to do it?
2. How will we know these capabilities have been met? (the verification criteria)
3. What shouldn't be part of the system, or should be avoided?

Important: a specification should avoid **subjective/interpretive** language — because that ambiguity is exactly what later becomes the subject of disputes over whether a requirement was met (recall the "why requirements matter" lesson — requirements are the basis of a legally binding agreement).

## A few traits of a good specification

- **Standardized outline** across the organization — project X's spec should follow the same general format as project Y's.
- **Single owner** — usually the chief engineer or system architect, the document's official owner and interpreter.
- **Traceable upward** — every requirement should connect to the higher-level specification it was derived from.
- **Simple, unambiguous language** — relevant stakeholders should be able to understand it.
- **Realistic and feasible** — not just technically, but given the project's budget and schedule too.

## Specification Tree

When requirements are split across several specification documents, this hierarchy is called a **specification tree** — much like the system hierarchy we already saw, since both derive from the work breakdown structure (WBS). Across the life cycle, specifications typically develop in this order:

**Business specification (concept) → stakeholder requirements specification → system requirements specification → subsystem/interface specification → detailed hardware/software specification → production specification**

> This is just a high-level introduction. The "requirements documentation and modeling" section later in this course comes back to these in full detail (including the exact structure of each section of a spec document and how to write it).

## An example: the ATM's functional specification

Imagine the ATM hasn't been invented yet. At this stage, the functional specification just says: "an automated device that lets a user withdraw money from their bank account" — with no assumption about exactly how this is done. Those details are the job of the later life-cycle stages.
