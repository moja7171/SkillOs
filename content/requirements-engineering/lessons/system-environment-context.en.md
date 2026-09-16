## Why we need to know where our system ends

Understanding a system's boundary isn't abstract theory — it directly relates to two real problems:

1. **Scope creep:** if we don't know where our system ends, we might unknowingly accept requirements that aren't actually part of our system — meaning extra cost and time.
2. **Unknown interfaces:** unclear boundaries mean the interfaces between the system and its environment stay unclear too — and those interfaces can affect the system's performance and reliability.

## Two viewpoints

- **External view:** we look at the whole system, without examining its insides, along with everything it interacts with. The system's behaviors and functions are usually described from the angle of these interactions.
- **Internal view:** we examine the elements inside the system's boundary — the one we saw in the system hierarchy lesson.

## System Environment

The system environment is the external entities the system interacts with in a given context, but which aren't considered part of the system. This is far more than just "weather":

For a military fighter jet, the environment includes:
- The natural environment (temperature, humidity, rain, snow; for a naval fighter jet, even sea salt causing corrosion)
- The operational platform (the aircraft carrier it takes off from and lands on)
- Allied systems (other aircraft in the same mission)
- The maintainer (supplying parts, fuel, oil)
- Operators/users (the pilots)
- GPS satellites (which only transmit, never receive anything back)
- Neutral systems (commercial ships, passenger airliners in international waters)
- Enemy systems and their munitions
- Its own munitions

## System Boundaries

A system's boundary determines what's "inside" the system and what's "outside" (part of the operational environment). Three rules of thumb for telling the difference:

1. If your organization **controls** the entity's behavior/performance, its development method, its requirements, or its funding, it's probably inside the system boundary.
2. If your organization has **operational control** over the entity after deployment (i.e. determines its missions), it's inside the boundary.
3. If your organization **owns and controls** the entity's design details and architecture, it's inside the boundary.

## A harder question: is the flight manual part of the system?

For a fighter jet, these questions come up: is the flight manual part of the system? What about the pilot's training simulator? Special repair tools? The factory where the parts are made? The truck that transports the aircraft?

General rule: if your system requires that entity to be **specifically designed or redesigned for it** (i.e. it costs extra time and money), it's probably inside the system boundary. But things like standard pilot gear, shared production/test facilities also used by other programs, transport vehicles, and common tools — these are outside the boundary, since they don't need to change for your particular system.

## Users are usually outside the system boundary

Interestingly, users are usually placed **outside** the system boundary, not inside it. Why? Because human behavior can't be fully predicted or controlled. We don't build systems that control the user — we build systems the user can control. The system boundary is exactly the **human-machine interface**. The system can't fully predict the user's behavior, but it can influence it — with on-screen instructions or an audible warning, for example.

## Context Diagram

The main tool for capturing and communicating a system's boundaries is the **context diagram**: the system itself sits in the middle of the diagram, with external entities arranged around it, connected by lines showing the primary interfaces.

A few important notes about this diagram:

- Interfaces are sometimes **color-coded** to show which side owns them (e.g. if the system's box is green, the interface between the system and that external entity belongs to the system).
- A context diagram is **context-dependent** — the diagram of a system performing its main mission looks completely different from the same system's diagram on the production line.
- You don't need to build a diagram for **every** possible context — that could mean hundreds of diagrams that are practically useless. Only build the ones that actually help you discover or refine requirements.

## Example: what's inside a car's boundary, what isn't

| Inside the system boundary (the car) | Outside the system boundary |
|---|---|
| Design process, luxury features, body shape | Safety and emissions standards |
| Engine and driving performance | Traffic laws |
| Comfort, cabin temperature, control layout | Road size and weight limits |
| | Underpass height, gas-pump nozzle size, shipping-container size |

This distinction matters because **change control** is only within our power for things inside the system boundary — things outside the boundary aren't ours to change, but we still have to account for them.
