This lesson covers ten brief rules of thumb to keep in mind when generating scenarios, user stories, use cases, and general life-cycle concept documents.

**1. Eventually converge on a numbered or table list.** It's fine for a scenario to start in narrative format when eliciting from stakeholders, but eventually convert it into a numbered list or table. A numbered list or table helps generate sequence, activity, and data flow diagrams, giving design engineers a deeper understanding of what we expect the system to do to meet the mission's goals. Numbered steps also give all stakeholders — not just design engineers — clarity, and help quickly locate a specific step under question instead of referencing a large paragraph.

**2. Use present tense for scenario steps.** This makes steps more explicit and easier for all stakeholders to understand.

**3. Use active voice.** Active voice makes the subject and object more explicit. Instead of "the button was pressed by the user," say "the user presses the button" — subject first, then object.

**4. Use a subject-verb-object format for each step.** This makes the scenario much easier to read. Subject = the person/thing/concept performing the action, verb = the action, object = the recipient of the action. Sticking to this format creates a clear, direct narrative for each step.

**5. Describe steps from a third-person perspective.** This gives all stakeholders a balanced view of how the system and various stakeholders accomplish the mission together. If we speak only from the system's perspective, we might lose insight into interactions between stakeholders outside the system boundary; if we speak only from the user's perspective, we might lose insight into the system's interactions with other stakeholders. Third person suits scenarios and requirements best.

**6. Use verbs that assume the system fulfills the step.** Avoid verbs that speak in terms of probability, suggestion, obligation, or option. State concretely what the system does to accomplish the mission — if there's an option or probability, capture it in a separate alternative or exception scenario.

**7. Explicitly state the actor and system at each step.** Once more: avoid pronouns that assume the subject or object ("it", "they") — explicitly name the user, the maintainer, the system, and so on.

**8. Separate each interaction from the others.** Don't put one interaction followed by another under the same step number — split each interaction into its own numbered step. This lets the team take each interaction on its own for further decomposition, analysis, or implementation.

**9. Write the sequence assuming everything goes as expected first.** In other words, the sunny day mission. Create the sunny day scenario first, and rainy day scenarios afterward. That way, if you run out of time or resources, at least the sunny day mission has been captured and work can begin from it. If we designed only for rainy day conditions, there'd be no guarantee the system works correctly, or doesn't behave unexpectedly, on a sunny day.

**10. Make sure the whole scenario satisfies one or more system goals.** Every step should focus on satisfying the goal, without chasing pipe-dream or pie-in-the-sky ideas that deviate from accomplishing the mission. For example, focus first on mission-related sequences and steer clear of detailed UI feature steps — those come later.

> These ten rules of thumb make mission-domain documents more consistent, easier to read, and more digestible for every stakeholder on the project.
