# Insurance and claims workshop

## Purpose and evidence boundary

This pack separates coverage identity, claim history and financial interpretation. A source aggregate is not an approved accounting rule.

| ID | Plain-language question | Why it matters | Confirmed evidence / affected count | Options and risks | Phase 1B recommendation (superseded by accepted outcome below) | Original proposed approval owner | Domains blocked | Decision point |
|---|---|---|---|---|---|---|---|---|
| D-212 / Q-009 | When Classic insurance rows consolidate to one target patient/provider row, what is retained? | Target uniqueness is `(patient_id, insurance_provider_id)`; overwriting can lose membership history. | 31,307 insurance rows. The reproducible provider-tuple query finds 36 duplicate groups covering 88 rows; 1,019 duplicate nonblank member-number groups cover 2,555 rows. | Keep latest: loses history and zero/future dates complicate ordering. Create duplicate current rows: violates target uniqueness. Consolidate with protected history: requires a Phase 2 protected-history representation. | Create one current membership only when determinable and retain every source row in migration evidence/history; quarantine conflicting current-state candidates. | Insurance lead + clinical governance | Patient insurance, eligibility, claims | Before patient pilot if insurance is in pilot; otherwise later implementation |
| D-206 (insurance/claims) / Q-301 | How should invalid coverage and claim dates be represented? | Current validation may reject them, while coercion falsifies eligibility. | Insurance: 17,091 zero issue dates, 15,717 zero expiry dates, 19 future issue dates, 54 future expiry dates and 66 expiry-before-issue rows. Claims: 20 reversed admission/discharge rows and one future discharge. | Replace with null: only if target and policy explicitly allow unknown. Coerce: prohibited. Exclude/quarantine: transparent but affects coverage/claim completeness. | Map zero dates to a Phase 2 supported “unknown/not evidenced” representation only where the target design supports it; quarantine impossible chronology and preserve raw fact only in protected evidence. | Insurance lead + claims lead + compliance | Insurance and claims | Later implementation; pilot if coverage included |
| D-205 (payer/claim portion) / Q-004 | What are the approved payer and claim status mappings? | Target enums and workflows have different meanings from Classic labels. | 31,692 claims are `ACTIVE`, 200 `ARCHIVES`; insurance is split almost evenly between NHIS (15,653) and private (15,654). | Literal map: semantics may differ. Default to pending/active: invents workflow. Explicit crosswalk with exceptions: auditable. | Explicitly specify each source-value mapping and quarantine unknowns; archived is not assumed to mean paid, closed or denied. | Claims lead + insurance lead | Claims, eligibility, reporting | Later implementation |
| Q-102 | Is the candidate claim component equation authoritative? | Using an unapproved formula can create invented receivables or hide discrepancies. | Candidate `ClaimTotal = ServTariff + InvTariff + PharmTariff`: 31,892 rows, 43 mismatches over 0.01, aggregate difference 1,106.56; component totals are in the hashed aggregate file. | Accept formula: must classify 43 exceptions. Treat total only as authoritative: loses components. Preserve all facts without deriving balance: safest pending accounting decision. | Preserve reported ClaimTotal and all components independently, calculate the component equation, and quarantine differences over 0.01 from posting. | Claims manager + finance controller | Claims, receivables, accounting | Before finance/claims implementation |

## Retained Phase 2 technical outputs

- Provider consolidation and membership-history specification.
- Payer/status/date mapping tables implementing the accepted policies.
- Claim reconciliation equation, tolerance, exception codes, and disposition authority.

## Controlled decision outcome records

The evidence, options, owners, decision points, and recommendations above are preserved as the Phase 1B record. The project owner exercised final authority directly on 2026-07-21; no workshop, quorum, committee approval, meeting minutes, or additional signature was required. The selected option in each record is the recommendation only as modified by the consolidated clarifications in the authoritative directive.

### Decision outcome: D-212 / Q-009

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-212` | Related open-question IDs | `Q-009` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Create one current patient/provider membership only when deterministically established, preserve every Classic insurance row in protected provenance/history, quarantine conflicting current-membership candidates, and never violate target patient/provider uniqueness. |
| Rejected alternatives | Keep-latest without proof, duplicate current target rows, or silent overwrite/discard. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must define current-candidate evidence, history representation, uniqueness-safe transforms, and conflict codes. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 31,307 insurance rows, including 36 duplicate provider-tuple groups covering 88 rows and 1,019 duplicate member-number groups covering 2,555 rows. | Target domains affected | Patient insurance, eligibility, claims. |
| Transformation consequence | Consolidate only deterministic current memberships while retaining each source row in protected history. | Exception consequence | Conflicting current candidates become membership exceptions. |
| Reconciliation consequence | Reconcile every source row to current membership provenance, protected history, or classified exception without uniqueness violations. | Manual-review owner | Insurance membership-review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Patient insurance detailed mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: D-206 / Q-301

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-206` | Related open-question IDs | `Q-301` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Represent zero insurance/claim dates as unknown/not evidenced only where the approved target design supports it; preserve raw source values in protected evidence; quarantine reversed, impossible, or implausible chronology and never coerce invalid dates. |
| Rejected alternatives | Coercion to valid-looking dates, migration time, or a global zero-date rule. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must define representation and validity per insurance and claim field. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | Insurance and claim anomalies documented above, including 17,091 zero issue dates, 15,717 zero expiry dates, 66 reversed coverage spans, and 20 reversed claim spans. | Target domains affected | Insurance, eligibility, claims. |
| Transformation consequence | Apply only approved field-specific date representations. | Exception consequence | Unrepresentable or invalid chronology produces a classified exception. |
| Reconciliation consequence | Reconcile each source date into preserved, approved-unknown, or exception categories. | Manual-review owner | Insurance/claims date-review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Insurance and claims detailed mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: D-205 / Q-004

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-205` | Related open-question IDs | `Q-004` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Maintain explicit domain-approved payer and claim status crosswalks. Unknown values become exceptions; ARCHIVES does not automatically mean paid, closed, rejected, or denied; payer categories map without inventing coverage. |
| Rejected alternatives | Convenient defaults or unsupported semantic assumptions about ARCHIVES or payer categories. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must enumerate source values, exact target semantics, transformations, and exception codes. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 31,892 claims, including 31,692 ACTIVE and 200 ARCHIVES, plus 31,307 insurance rows across recorded payer categories. | Target domains affected | Insurance, claims, eligibility, reporting. |
| Transformation consequence | Apply only explicit payer/status crosswalk entries. | Exception consequence | Unknown or semantically unsupported values become classified exceptions. |
| Reconciliation consequence | Reconcile source and target value frequencies with mapped and exception counts. | Manual-review owner | Insurance/claims status-mapping owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Insurance and claims detailed mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: Q-102 / Q-102

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `Q-102` | Related open-question IDs | `Q-102` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Preserve Classic ClaimTotal as the reported source total and component values independently. Recalculate the component equation with decimal arithmetic; do not overwrite the reported total. Differences over 0.01 are quarantined from receivable, opening-balance, and GL posting, and no payment/allocation history is invented. |
| Rejected alternatives | Replacing ClaimTotal with a calculation, ignoring differences, or inventing payments/allocations/postings. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | The 0.01 claim tolerance is fixed. Phase 2 must define equation fields, signs, exception codes, and posting gates without changing that threshold. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 31,892 claims; 43 candidate-equation mismatches over 0.01 with aggregate difference 1,106.56 at the evidence coordinate. | Target domains affected | Claims, receivables, opening balances, accounting. |
| Transformation consequence | Preserve all fields and calculate a separate reconciliation result using decimal arithmetic. | Exception consequence | Mismatches over tolerance become fail-closed claim-reconciliation exceptions. |
| Reconciliation consequence | Report source total, components, calculated total, differences, counts, and amounts; exclude exceptions from posting. | Manual-review owner | Claims/finance reconciliation-review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Claims and finance detailed mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

## Workshop completion record

No physical or virtual workshop was conducted. This pack was resolved by direct project-owner decision and is therefore superseded as a workshop instrument.

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Workshop date | Not conducted; final-authority directive dated 2026-07-21. | Participants | No workshop held. |
| Stakeholder roles represented | Not applicable; the project owner exercised final authority directly. | Quorum or required authority present | Not required; final authority exercised directly. |
| Decisions accepted | D-212/Q-009; D-206/Q-301; D-205/Q-004; Q-102 | Decisions partially accepted | None. |
| Decisions deferred | None at business-policy level; technical specifications remain Phase 2 tasks. | Contradictions discovered | No unresolved policy contradiction after the consolidated directive. |
| Actions assigned | Define membership selection, date/status crosswalks, claim equations, and posting-quarantine contracts in Phase 2. | Action owners | Phase 2 delivery owners to be assigned; project owner retains final decision authority. |
| Action deadlines | Set during Phase 2 planning. | Follow-up workshop required | No. |
| Workshop chair approval | Not applicable; no workshop occurred. | Records/governance approval | mcwakey (established repository identity), Project Owner and Final Decision Authority |
| Workshop status | Superseded by final-authority decision | Approval method | Direct project-owner decision |
