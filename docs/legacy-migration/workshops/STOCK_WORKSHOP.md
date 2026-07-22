# Pharmacy, inventory and stock workshop

## Purpose and evidence boundary

Classic exposes product/store/pharmacy snapshots, batches and requests, not a complete movement ledger. Target stock balances are derived by operational movement services that also value and post stock.

| ID | Plain-language question | Why it matters | Confirmed evidence / affected count | Options and risks | Phase 1B recommendation (superseded by accepted outcome below) | Original proposed approval owner | Domains blocked | Decision point |
|---|---|---|---|---|---|---|---|---|
| D-204 / Q-104 | Which stock/procurement facts are retained, and what opening stock position is approved? | Synthesizing movement history would invent dates, actors, valuation and accounting. | 4,303 medicines; 8,360 pharmacy snapshots totaling 11,752,954 units; 8,356 store snapshots totaling -3,832,473 units; 253 batches totaling 18,327 units; 146 requests. Negative-component rows: 323 pharmacy, 111 store, 52 requests. Duplicate MED_ID groups: 10 pharmacy, two store. | Import snapshots as movements: invents history. Use current snapshot as opening: requires product/location/dedup/negative policies. Start empty: operationally disruptive. | Retain snapshots/batches/requests as source facts and create only a policy-approved, labelled opening movement per product/location; do not reconstruct prior movements. | Chief pharmacist + stores lead + finance controller | Product stock, procurement, valuation, GL | Before stock implementation |
| Q-105 | What happens to expired batches still marked active? | Loading them as available stock creates medication-safety risk. | 252/253 batches are past expiry at capture while `Expired=0`; one expiry is future. One duplicate nonblank batch-number group covers 252 rows, so batch number is not a usable unique key alone. | Trust flag: unsafe. Trust date and discard: prohibited/lossy. Quarantine expired stock while retaining evidence: safe. | Treat expiry date as a safety gate, not proof of disposal; exclude expired quantity from available opening stock, retain it as quarantined/expired evidence, and require pharmacist disposition. | Chief pharmacist + medication safety officer | Batch stock and opening balance | Before stock implementation |

## Retained Phase 2 technical outputs

- Product identity/deduplication, location and unit-of-measure rules implementing the accepted policy.
- Negative-stock and expired-batch disposition.
- Opening quantity/valuation equation, exception codes, and operational disposition authority.

## Controlled decision outcome records

The evidence, options, owners, decision points, and recommendations above are preserved as the Phase 1B record. The project owner exercised final authority directly on 2026-07-21; no workshop, quorum, committee approval, meeting minutes, or additional signature was required. The selected option in each record is the recommendation only as modified by the consolidated clarifications in the authoritative directive.

### Decision outcome: D-204 / Q-104

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-204` | Related open-question IDs | `Q-104` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Preserve pharmacy, store, batch, and request snapshots as source evidence; do not reconstruct historical movements. Create only clearly labelled opening stock movements per approved product/location after explicit product, unit, location, and duplicate mapping. Exclude negative quantities from operational opening stock until reviewed and never fabricate valuation/accounting history. |
| Rejected alternatives | Snapshot-as-history reconstruction, silent negative normalization, start-current without mapping, or fabricated valuation/posting history. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must define product/unit/location crosswalks, snapshot basis, deduplication, opening equations, negative exceptions, and approval gates. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 4,303 medicines; 8,360 pharmacy, 8,356 store, 253 batch, and 146 request rows with documented totals, duplicates, and negative components. | Target domains affected | Pharmacy catalogue, inventory, batches, procurement, opening stock, accounting. |
| Transformation consequence | Transform only approved nonnegative reconciled snapshot positions into labelled openings. | Exception consequence | Negative, duplicate, unmapped, or unreconciled positions remain classified and unavailable operationally. |
| Reconciliation consequence | Reconcile every source snapshot/batch/request to evidence retention, approved opening, or exception without invented movements. | Manual-review owner | Pharmacy/stock reconciliation-review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Pharmacy and stock detailed mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: Q-105 / Q-105

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `Q-105` | Related open-question IDs | `Q-105` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Use expiry dates as medication-safety gates. Do not trust the Classic active/expired flag when it conflicts with expiry; exclude expired quantities from available opening stock, retain them in quarantined/expired evidence, require controlled pharmacist disposition, and do not use batch number alone as unique identity. |
| Rejected alternatives | Loading expired stock as available, discarding it silently, trusting contradictory flags, or matching batches by number alone. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must define expiry coordinate, batch identity, quarantine representation, disposition workflow, and reconciliation. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 253 batches; 252 past expiry while flagged not expired, one future expiry, and one duplicate nonblank batch-number group covering 252 rows. | Target domains affected | Batch inventory, opening stock, medication safety. |
| Transformation consequence | Gate operational availability by approved expiry evaluation and compound batch mapping. | Exception consequence | Expired/conflicting/ambiguous batches remain quarantined pending pharmacist disposition. |
| Reconciliation consequence | Reconcile total batch quantities into available approved opening, expired quarantine, and other exceptions. | Manual-review owner | Controlled pharmacist disposition owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Pharmacy and stock detailed mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

## Workshop completion record

No physical or virtual workshop was conducted. This pack was resolved by direct project-owner decision and is therefore superseded as a workshop instrument.

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Workshop date | Not conducted; final-authority directive dated 2026-07-21. | Participants | No workshop held. |
| Stakeholder roles represented | Not applicable; the project owner exercised final authority directly. | Quorum or required authority present | Not required; final authority exercised directly. |
| Decisions accepted | D-204/Q-104; Q-105 | Decisions partially accepted | None. |
| Decisions deferred | None at business-policy level; technical specifications remain Phase 2 tasks. | Contradictions discovered | No unresolved policy contradiction after the consolidated directive. |
| Actions assigned | Define product/location crosswalks, opening-stock equations, negative/expired exception codes, and pharmacist disposition in Phase 2. | Action owners | Phase 2 delivery owners to be assigned; project owner retains final decision authority. |
| Action deadlines | Set during Phase 2 planning. | Follow-up workshop required | No. |
| Workshop chair approval | Not applicable; no workshop occurred. | Records/governance approval | mcwakey (established repository identity), Project Owner and Final Decision Authority |
| Workshop status | Superseded by final-authority decision | Approval method | Direct project-owner decision |
