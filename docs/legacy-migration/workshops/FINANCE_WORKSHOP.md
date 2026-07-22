# Billing, payments, receivables and accounting workshop

## Purpose and evidence boundary

Classic financial columns are source facts recorded as floating-point values. The target has invoices, items, payments, allocations, receivables and double-entry journals, but Classic does not evidence all of those histories.

| ID | Plain-language question | Why it matters | Confirmed evidence / affected count | Options and risks | Phase 1B recommendation (superseded by accepted outcome below) | Original proposed approval owner | Domains blocked | Decision point |
|---|---|---|---|---|---|---|---|---|
| D-203 / Q-103 | Which source financial facts are imported, which opening positions are approved, and which renewed domains start empty? | Reconstructing allocations, refunds, credits or journals without source events invents history and may double count. | 111,731 billing rows and 31,892 claims. Classic has no complete allocation/refund/credit/double-entry ledger. Target payment services auto-allocate, post accounting and emit side effects. | Rebuild full target history: unsupported. Import invoices plus derived payments: may invent allocation timing. Approved opening AR only: loses transaction detail but is reconcilable. Source-fact archive plus labelled openings: strongest provenance. | Preserve approved billing/claim facts in migration-specific structures/mappings and post only policy-approved labelled opening balances; begin unsupported allocations/refunds/credits/GL history empty. Never call operational payment/posting services for history. | CFO/finance controller + audit | Billing, payments, AR, claims, GL | Before finance implementation |
| D-107 / Q-101 | Is `Bill - Discount - Paid = Balance` authoritative, and how are discrepancies handled? | Exact reconciliation is mandatory. | Current hashed query: totals Bill 6,588,851.33; Discount 5,055.00; Paid 5,764,343.70; Balance 819,795.39; 906 mismatches over 0.01; aggregate delta -342.76; one negative-component row and 79 owing rows with nonpositive balance. Phase 1A reported 907 mismatches, so the query definition/capture coordinate must accompany the reconciliation specification. | Trust stored balance: components may not reconcile. Recalculate: alters source facts. Preserve both and exception: auditable. | Preserve each source amount, calculate the equation using decimal arithmetic, and require controlled disposition for every mismatch; do not overwrite either fact. | Finance controller + external/internal audit | Billing, AR opening, reconciliation | Before finance implementation |
| Q-102 | Which claim amount is authoritative for financial recognition? | Claim component mismatches can affect receivables and revenue. | 43/31,892 claims differ from the candidate equation; aggregate delta 1,106.56. | See insurance/claims pack. | Preserve the reported total and components and do not post mismatches until the shared Phase 2 reconciliation contract permits it. | Claims manager + finance controller | Claims and AR/GL | Before finance implementation |

## Retained Phase 2 technical outputs

- Scope ledger naming every target finance table as source fact, policy-approved opening, target-owned, or excluded.
- Billing and claim equations, tolerances, decimal rounding, exception codes, and disposition authority.
- Opening-balance effective date and reconciliation report format.

## Controlled decision outcome records

The evidence, options, owners, decision points, and recommendations above are preserved as the Phase 1B record. The project owner exercised final authority directly on 2026-07-21; no workshop, quorum, committee approval, meeting minutes, or additional signature was required. The selected option in each record is the recommendation only as modified by the consolidated clarifications in the authoritative directive.

### Decision outcome: D-203 / Q-103

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-203` | Related open-question IDs | `Q-103` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Preserve approved Classic billing and claim facts with migration provenance. Create only clearly labelled opening balances approved by migration policy. Unsupported allocations, refunds, credit notes, receivable histories, GL postings, and journal histories start empty; never call normal payment/allocation/accounting services or double count source facts and openings. |
| Rejected alternatives | Reconstructed unsupported histories, normal-service posting, silent omissions, or double-counted source facts/openings. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must define the source-fact contract, opening-balance basis, anti-double-count rules, and posting gates; finance exceptions stay non-operational. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 111,731 billing rows and 31,892 claims; no complete Classic allocation/refund/credit/double-entry ledger. | Target domains affected | Billing, payments, receivables, claims, accounting. |
| Transformation consequence | Map approved facts and separately labelled openings only; leave unsupported transaction histories empty. | Exception consequence | Unsupported or unreconciled facts cannot create operational receivables or GL entries. |
| Reconciliation consequence | Reconcile source facts, approved openings, exclusions, and zero-start target domains without double counting. | Manual-review owner | Finance reconciliation-review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Billing, claims, and accounting detailed mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: D-107 / Q-101

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-107` | Related open-question IDs | `Q-101` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Preserve source Bill, Discount, Paid, and Balance independently. Calculate Bill minus Discount minus Paid using decimal arithmetic; treat stored Balance as a reported source fact, never overwrite it. Differences over 0.01 become reconciliation exceptions excluded from operational opening AR or GL until reviewed. |
| Rejected alternatives | Trusting only stored balance, replacing it with a calculation, floating-point calculation, or posting mismatches. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must define decimal precision/signs, exception codes, aggregate contracts, and reviewed-disposition controls. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 111,731 billing rows; 906 current hashed-query mismatches over 0.01, aggregate delta -342.76, plus documented negative/nonpositive anomalies. | Target domains affected | Billing, AR opening, reconciliation, GL. |
| Transformation consequence | Preserve four source facts and calculate a separate expected balance/difference. | Exception consequence | Rows over 0.01 tolerance become fail-closed billing-reconciliation exceptions. |
| Reconciliation consequence | Preserve mismatch counts and amounts and exclude unresolved mismatches from opening AR/GL. | Manual-review owner | Finance reconciliation-review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Billing and finance detailed mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: Q-102 / Q-102

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `Q-102` | Related open-question IDs | `Q-102` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Apply the approved claims policy consistently: preserve reported ClaimTotal and each component, calculate the component equation in decimal arithmetic, and quarantine differences over 0.01 from receivable, opening-balance, and GL posting without inventing payment or allocation history. |
| Rejected alternatives | Any finance treatment inconsistent with the insurance/claims decision, including overwriting totals or posting mismatches. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must use one shared claims reconciliation contract across claims and finance. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 31,892 claims; 43 mismatches over 0.01 with aggregate difference 1,106.56. | Target domains affected | Claims, receivables, opening balances, accounting. |
| Transformation consequence | Use the same source fields, equation, tolerance, and posting gates in both domains. | Exception consequence | Shared claim mismatches remain one classified exception population. |
| Reconciliation consequence | Produce one authoritative reconciliation result consumed consistently by claims and finance. | Manual-review owner | Claims/finance reconciliation-review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Claims and finance detailed mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

## Workshop completion record

No physical or virtual workshop was conducted. This pack was resolved by direct project-owner decision and is therefore superseded as a workshop instrument.

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Workshop date | Not conducted; final-authority directive dated 2026-07-21. | Participants | No workshop held. |
| Stakeholder roles represented | Not applicable; the project owner exercised final authority directly. | Quorum or required authority present | Not required; final authority exercised directly. |
| Decisions accepted | D-203/Q-103; D-107/Q-101; Q-102 | Decisions partially accepted | None. |
| Decisions deferred | None at business-policy level; technical specifications remain Phase 2 tasks. | Contradictions discovered | No unresolved policy contradiction after the consolidated directive. |
| Actions assigned | Define approved source facts, opening-balance, billing/claim equations, exception codes, and posting gates in Phase 2. | Action owners | Phase 2 delivery owners to be assigned; project owner retains final decision authority. |
| Action deadlines | Set during Phase 2 planning. | Follow-up workshop required | No. |
| Workshop chair approval | Not applicable; no workshop occurred. | Records/governance approval | mcwakey (established repository identity), Project Owner and Final Decision Authority |
| Workshop status | Superseded by final-authority decision | Approval method | Direct project-owner decision |
