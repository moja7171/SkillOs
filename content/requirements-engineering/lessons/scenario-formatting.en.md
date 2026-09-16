Now that we know what scenarios are and their common elements, let's look at how to format them. Scenario formatting can be wide and highly customizable, but there are three common formats accepted in both academia and industry: **narration**, **numbered list**, and **tabular**.

## Narration format

In the narration format, the scenario walks through the mission in a normal, conversational tone — as if the narrator is telling a story to friends, family, or coworkers, explaining how the system performs its mission. We typically use this format during requirements elicitation, written from the stakeholder's perspective — since most stakeholders are laypeople and rarely share the developing organization's technical vocabulary.

Narrative scenarios can reflect different levels of abstraction: if stakeholders know exactly what they want, they're free to write detailed narratives; if they're unsure about some aspect, they can speak more abstractly, and get more detailed on topics they're more familiar with. Narrative format is best suited for understanding how the system is expected to be used in the operational environment, and for generating higher-order capability or stakeholder-level requirements.

## Numbered list format

Here, instead of a conversational tone, we build a sequential, numbered list that steps through the scenario step by step. This format works best when the sequence is stepwise and sequential, with few or no concurrent steps — the more concurrent steps there are, the harder this format is to build.

Key rule: each step must state the subject, the verb (action), and the object explicitly, and avoid pronouns that assume the subject or object. Correct example:

1. The user readies the drone for deployment.
2. The user conducts a pre-flight inspection, ensuring the drone has enough resources to perform the mission.
3. The user powers up the drone.
4. The user commands the drone to take off and fly toward the seed deployment site.

Instead of using pronouns ("conducts the inspection", "powers it up", "commands it") — because it's unclear who the subject is. Even if this makes the scenario sound dry and robotic, we should still write it this way — we're not here to write an entertaining novel, we're here to build a solid foundation for a good set of requirements. The same rule applies to use cases.

## Table format

The table format is very similar to the list format, but each step goes into its own table cell, with additional columns for other important elements. For example, one column for the step number, one for the step text, one for the actor(s), one for the resources required, and two more for the inputs and outputs of each step — which helps us track the interfaces flowing into and out of the system at each step. After refinement, each of these elements becomes a requirement statement in the specification. The table format lets us track individual scenario elements more closely, and helps highlight important elements that might not be as readily visible in narrative or list form.

## Moving from one format to another

We usually start a scenario in narrative format during the early phases of requirements elicitation, then refine it into a more detailed numbered list, and later still convert it to a table format with even more information. Sometimes we jump straight to the table format, if stakeholders are comfortable with it — but experience shows that jumping straight to a table extends the time stakeholders need to spend, and they tend to get bored and rush through scenario creation. That's why the narrative format is usually recommended to start, refining it in-house with dedicated stakeholders later.

> Bottom line: there are three main scenario formats — narrative (to start and elicit), numbered list (for step-by-step sequencing), and table (for precise element tracking). We usually start narrative and refine toward tabular over time.
