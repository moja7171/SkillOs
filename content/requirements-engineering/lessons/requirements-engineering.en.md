## The full map of the requirements engineering process

So far we've met different concepts piece by piece. This lesson lines them all up on a single path — the map the rest of this section (and the whole course) follows.

The process starts with an external **circumstance** — good or bad, something that can be improved. For example: "getting equipment and people to a burned field to plant trees is hard," or "withdrawing cash means going to the bank and dealing with a teller."

## The stages, in order

1. **Need:** an unidentified problem or situation that, if solved, improves quality of life. Could be a need for something entirely new, or an improvement to something that exists.
2. **Goals:** what does our solution need to have to meet the need? Goals are decomposable — e.g. "perform a bank transaction" becomes "withdraw cash" + "deposit" + "transfer funds."
3. **Constraints:** technological, legal/regulatory, social, etc. constraints that shrink the solution space.
4. **Measures of Effectiveness:** how do we know the proposed solution actually met the goal and the need?
5. **Life cycle concepts:** a short story of what the solution is supposed to do at each stage of the life cycle — the operational concept (OPSCON, the biggest and most important one), test concept, production concept, deployment concept, support concept, retirement concept.
6. **Requirements and functions:** from the life cycle concepts, we pull out capabilities, functions, inputs and outputs, and turn them into requirement statements.
7. **Refinement:** we decompose high-level, abstract requirements into more detailed, measurable ones — something the design team can actually work with.
8. **Architecture:** building the conceptual solution out of the requirements (this course only covers the parts of architecture related to requirements, not the whole subject).
9. **Solution development:** the development team builds a solution that meets the requirements.
10. **Verification & validation:** was the solution actually built to spec (verification), and does it actually solve the original need (validation)?

## Important: this path has feedback loops

There are backward arrows between every stage. For example, before spending significant resources engineering a solution, we need to make sure our life cycle concepts actually cover the real need — otherwise we have to go back and fix it. Likewise, when the solution is tested, the verification results can loop back to earlier stages and trigger another round of requirements refinement.

## Loops within loops

Many of these stages (especially goals and requirements) have their own backward arrow — meaning they can be repeatedly decomposed into finer levels. This is exactly the same hierarchical logic we saw before: humans manage complex problems by organizing their thinking into layers and hierarchies.

## The rest of this section follows exactly this path

The upcoming lessons in this section ("Product Needs and Goals," "Mission Need Statement," "Identifying Project Constraints") dig deeper into just the first three stages of this map. The later stages (measures of effectiveness, scenarios, use cases, eliciting and writing requirements) show up in later sections of the course.

> The golden rule that repeats throughout this map: **never jump from "problem" to "solution" too early.** Needs and goals must stay implementation-free — exactly the topic of the next lesson.
