## Why most projects need a contract

If your organization has all the expertise and materials in-house, maybe you can avoid contracting. But for most complex systems, that's not the case — you'll need outside help, which means at least one contract (often several) over the system's life.

This lesson only skims the surface of how contracting works, focusing on where the requirements engineer fits in.

## The story so far

Before we get to the contract, this has already happened:

1. A need was identified and validated (the concept stage).
2. Stakeholder requirements were elicited and captured in a "stakeholder requirements specification."
3. That document was approved at a gate review.
4. The team went a layer deeper and decomposed the system architecture down to the component level, and captured the result in a "system requirements specification."
5. That document was approved at another gate review.

Now the organization is ready to find someone to design, develop, integrate and test the system.

## What is a Request for Proposal (RFP)?

A **Request for Proposal (RFP)**, sometimes called a solicitation, is the formal way of kicking off a contract for a product, services, or a mix of both. An RFP can be for just purchasing an existing system, just for labor (e.g. consulting), or for designing and building a whole new system — and its time span can be short (design only) or long (design + development + integration + test, all together).

## What's inside an RFP?

Two key documents are always inside an RFP:

- **System requirements specification:** describes what the end item itself is and what characteristics it needs.
- **Statement of Work (SOW):** describes what services need to be performed — system design, documentation (drawings), development, integration, testing, monthly status reports, and so on. The SOW doesn't describe the end item itself, it describes the work needed to get there. (A dedicated lesson covers it in full later.)

## From RFP to signed contract

1. The organization publishes the RFP (for government contracts, this usually has to be publicly available by law; rarely it's given directly to a single supplier, called a "sole-source contract").
2. Interested suppliers analyze the RFP and prepare a **proposal** — their proposed solution, with a cost and time estimate for each piece of work.
3. The customer evaluates the proposals against a standard set of criteria and picks the best value/risk combination.
4. The customer and the chosen supplier enter **negotiation** — usually with legal representatives from both sides present — to agree on price, schedule and final terms.
5. The contract is signed, and work begins once initial payment is received.

## After the contract is signed

Say the contract is for designing, developing, integrating and testing an ATM. The contractor picks up right where the customer left off:

1. Performs systems engineering activities to define the design and reduce risk on high-risk technologies.
2. Presents and reviews the preliminary design (another decision gate).
3. Moves from design into implementation — pieces are built and integration/verification begins.
4. Integration starts at the lowest level (component → subsystem → whole system), tested at each step.
5. The requirements engineer plays a key role here too — usually responsible for tracking the specification's status, including verification status.
6. After the whole system is verified, validation may happen here too, or under a new contract for transition and production.

Important note: the prime contractor may themselves award and manage several subcontracts to lower-level suppliers — a separate contract to buy displays, a separate contract for encryption development, and so on.

> The further along the life cycle you go, the cost and effort involved rises sharply compared to the early stages. That's why getting the contract and specification right from the start is critical — a small mistake here creates a much bigger time and money cost later.
