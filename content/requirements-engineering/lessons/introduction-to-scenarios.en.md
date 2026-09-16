## Why you can't jump straight from goal to requirement

So far we've covered the need statement and goals/sub-goals. By themselves, these aren't enough to elicit requirements — they're too high-level and lack detail. We need an intermediary product that translates goals into requirements. This lesson calls that the **mission domain**.

The mission domain is made up of three things: **mission descriptions**, **scenarios**, and **use cases**. Software developers use a similar tool called **user stories**. This module of the course covers each of these in turn.

## What the mission domain is

The mission domain describes the missions we expect the product or service to perform — it can take the form of a scenario, a mission description, a use case, or a user story.

Two example mission descriptions:

> The mission of the tree seed planting drone is to autonomously and efficiently plant tree seeds in deforested areas, operating under various environmental conditions and terrain types. The drone should significantly reduce the time, cost, and labor associated with traditional tree-planting methods while increasing the survival rate and overall effectiveness of reforestation projects.

> The mission of an automated teller machine is to provide cash to bank account holders at all hours of the day throughout the year, without the help of a human bank teller — plus accepting deposits, checking balances, and transferring funds between accounts, all without human assistance.

## What role the mission domain plays

The mission domain forms a bridge between the stakeholders and the engineers who will develop, implement, integrate, test, and deliver the product. The reason is a phenomenon called the **curse of knowledge**.

Many experts (myself included) suffer from it: when we talk to a stakeholder, we assume they share our level of engineering experience, language, and terminology. Usually that's not the case, unless the stakeholder is themselves an expert in the same field. The result: the stakeholder doesn't understand what the expert is saying, but to save face or avoid embarrassment, they simply go along with it — without truly understanding.

This isn't unique to engineering. When you take your car to a mechanic and they explain what's wrong, you often don't understand, because they too suffer from the curse of knowledge. The same is true for accountants, lawyers, or any specialized field.

The mission domain eliminates this problem, because scenarios, mission descriptions, use cases, and user stories are all expressed in the stakeholder's **layman terminology**, not the requirements engineer's. It's much easier for stakeholders to explain what they want by describing how they plan to use the system — as though they were actually performing the mission — rather than speaking in engineering terms.

> Bottom line: you can't jump straight from the need statement and goals to requirements. The mission domain (scenario, mission description, use case, user story) is the bridge — it adds the missing detail, and it speaks the stakeholder's language so the curse of knowledge doesn't get in the way.
