## Every product has a life

Every product or system — an ATM, a seed-planting drone, a car — follows a defined path: it starts as an idea, gets documented and modeled, becomes something real, gets tested, gets mass-produced, reaches the market, gets used and supported, and eventually retires. We call this the **product life cycle**.

The requirements engineer plays a role across the **entire** path, not just at the start:

| Stage | Requirements engineer's role |
|---|---|
| Concept | Capturing the concept as specifications and models |
| Development | Translating specifications into engineering design and construction; maintaining traceability from each piece back to its origin |
| Test | Helping verify specifications and validate that the product is actually useful |
| Production | Helping define a system that's easy to produce |
| Utilization | Managing requirements for updates and upgrades |
| Retirement | Helping define how the system is properly disposed of |

## Why organize the life cycle at all

If an organization skips through the stages without discipline, or drops some stages entirely, the risk of project failure rises sharply — either the product never reaches the market, or it does and users end up unhappy with it.

That's why the life cycle is broken into **phases**, with a **decision gate** between each one — a review meeting where the team and stakeholders check each other's work before moving to the next phase (and spending more money).

Interesting detail: the requirements engineer is busiest in the **early** phases (concept and early development). The further along we go, the more demand shifts to other specialties (mechanical, electrical, logistics, production) — but demand for requirements engineering never fully disappears.

## Where does the life cycle start?

There are two common starting points:

1. **Product need:** there's a problem that needs a solution. For example, "how do I access the same file across multiple devices at once?" → the solution turned out to be cloud technology.
2. **Technological opportunity:** a new technology enters the market before anyone's quite sure where it'll be used. For example, touch-sensitive surfaces reacting to skin heat — which later became the touchscreen smartphones we all carry now.

## Five main stages

A product moves through this path:

1. **Concept:** the problem is identified and the product need is validated. Stakeholders and their needs are identified, possible technologies are explored, and the final concept is documented in development specifications.
2. **System Development:** the concept is turned into actual hardware/software pieces, integrated, and tested against the specification.
3. **Production:** the system is mass-produced, quality-inspected, packaged and shipped to the customer.
4. **Utilization & Support:** end users use the product; support and updates continue to keep it competitive and effective.
5. **Retirement:** the system is stored, archived, or disposed of.

> Note: in textbook diagrams these stages look clean and separate, but in reality they overlap — for example, the moment the first production system reaches a user, the utilization stage begins in parallel with production.

## A few real-world examples (just for familiarity)

Many large organizations (the US Department of Defense, NASA, the ISO/IEC/IEEE 15288 standard) have their own version of this cycle, with different names for each phase (e.g. "Material Solution Analysis," "Technology Maturation and Risk Reduction," or NASA's "Phase A" through "Phase F"). They all ultimately line up with the five stages above — just broken down in more detail. This course uses the simplified ISO 15288-style version because it's easier to follow.

One interesting detail: the **software** side has its own cycle that usually never truly ends — software builds keep going all the way through utilization, because changing software is typically much faster and cheaper than changing hardware.

## The incremental approach

Instead of building every capability of a product all at once, we can build a base version with the core capabilities first, ship it, and add more capabilities in later releases. This is common for complex, expensive products (aircraft, cars, medical devices).

It also makes business sense: if the first increment succeeds, demand for the later increments proves itself. If it fails, you've only lost the cost of the first increment, not the cost of the whole product built at once.
