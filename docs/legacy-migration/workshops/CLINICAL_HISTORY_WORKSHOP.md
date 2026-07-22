# Clinical history and consultation grouping workshop

## Purpose and evidence boundary

Classic records are encounter-linked rows without a renewed consultation-route event model. The table preserves Phase 1B evidence and substantive recommendations; the accepted outcomes below authorize detailed specification, not transformation execution.

| ID | Plain-language question | Why it matters | Confirmed evidence / affected count | Options and risks | Phase 1B recommendation (superseded by accepted outcome below) | Original proposed approval owner | Domains blocked | Decision point |
|---|---|---|---|---|---|---|---|---|
| D-214 (clinical portion) / Q-011 | How should Classic clinical rows be grouped into consultation routes/medical records, and who is the doctor? | Target medical records require visit, patient and doctor; a wrong group/actor changes the clinical record. | Classic has no route/session entity or general audit log. High-volume candidates include 65,244 complaints, 123,940 diagnoses, 34,532 histories, 210,902 prescriptions, 72,680 scan/lab orders and 460,758 service results. Target route-to-record uniqueness is service-only, not DB-enforced. | One record per attendance: simple but may merge specialties. Group by timestamps/text doctor: inference can split/merge incorrectly. Quarantine ambiguous groups: incomplete but safe. | Pilot deterministic one-record-per-attendance only where one evidence-backed clinician and compatible department/service context exist; quarantine all ambiguous groupings and never infer from name alone. | Clinical governance + medical director + architecture | Medical records, consultations, diagnoses, orders/results | Before detailed clinical mapping; later than patient pilot |
| D-208 / Q-201 | Which plain text, RTF, catalogue and free-text fields take precedence? | Choosing one silently can discard clinical meaning or duplicate it. | Phase 1A confirms inconsistent paired fields: 4,477 empty plain histories vs 2,132 empty RTF histories; 699 empty plain treatment plans vs 57 empty RTF plans. | Plain only: loses RTF-only content. RTF only: unsafe rendering/content risk. Concatenate: duplicates and may embed unsafe markup. Preserve both in labelled fields/attachments: target fit must be designed. | Sanitize RTF, derive display text without overwriting source facts, retain both source representations where the accepted privacy policy and Phase 2 specification permit, and report conflicts. | Clinical governance + privacy/security | History, treatment plans, notes | Later implementation before clinical import |
| Q-202 | What does an empty result mean? | Empty must not be silently relabelled pending, cancelled or normal. | 126,066/460,758 `serv_results.Result` values are empty; 11,892/72,680 scan/lab order results are empty; all 60,260 `consult_services.Result` values are empty. | Default pending/cancelled/normal: invents status. Drop: loses order evidence. Preserve order with unknown result state: explicit. | Preserve the source-evidenced order/request and use a migration-specific “result not evidenced” exception/state under the Phase 2 target design; never synthesize a clinical result. | Laboratory lead + clinical governance | Investigations, lab results, clinical reconciliation | Later implementation |
| Q-204 | How much historical workflow/audit should be represented? | Target operational logs use current actors/times and are not proof of Classic events. | Classic has event timestamps on some rows but no general audit/change journal. Target has 26 activity-logged models and extensive event/listener side effects. | Simulate target transitions: invents history. Import only explicit source events: incomplete but truthful. Summary snapshot: less detail but clear provenance. | Import only source-evidenced facts/timestamps; label migration audit separately and never manufacture contemporaneous status logs. | Compliance + clinical governance | Workflow history, audit, medical records | Later implementation; retention specification before cutover |

## Retained Phase 2 technical outputs

- Grouping predicate and ambiguity rules implementing the accepted policy.
- Clinical content precedence/sanitisation specification.
- Result-state vocabulary that distinguishes “not evidenced” from clinical status.

## Controlled decision outcome records

The evidence, options, owners, decision points, and recommendations above are preserved as the Phase 1B record. The project owner exercised final authority directly on 2026-07-21; no workshop, quorum, committee approval, meeting minutes, or additional signature was required. The selected option in each record is the recommendation only as modified by the consolidated clarifications in the authoritative directive.

### Decision outcome: D-214 / Q-011

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-214` | Related open-question IDs | `Q-011` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Create one historical consultation route/medical record per attendance only when one evidence-backed clinician and compatible department/service context can be determined. Quarantine ambiguous grouping or attribution, never infer a clinician by name similarity, and validate logical one-record-per-route behavior. |
| Rejected alternatives | Timestamp/text-name grouping without proof, name-similarity clinician matching, or forced grouping. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must define compatible context, clinician evidence, grouping predicates, and service-only uniqueness validation. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | Clinical candidates documented above, including 65,244 complaints, 123,940 diagnoses, 34,532 histories, 210,902 prescriptions, 72,680 orders, and 460,758 service results. | Target domains affected | Consultation routes, medical records, clinical history, orders/results. |
| Transformation consequence | Build routes only under the approved deterministic attendance/clinician/context contract. | Exception consequence | Ambiguous grouping or attribution quarantines the affected clinical chain. |
| Reconciliation consequence | Reconcile every clinical row to one approved route/record or a classified exception; detect logical duplicates. | Manual-review owner | Clinical grouping review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Clinical detailed mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: D-208 / Q-201

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-208` | Related open-question IDs | `Q-201` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Preserve plain-text, RTF, and catalogue representations without silent overwrite. Sanitize RTF before rendering, derive safe display text while retaining protected provenance, report conflicts, and never concatenate blindly or execute embedded markup. |
| Rejected alternatives | Plain-only or RTF-only overwrite, blind concatenation, or rendering/executing unsanitized markup. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must define field precedence for display only, sanitization, protected retention, conflict codes, and privacy controls. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | Paired representation defects documented above, including 4,477 empty plain versus 2,132 empty RTF histories and 699 versus 57 treatment-plan empties. | Target domains affected | History, treatment plans, clinical notes. |
| Transformation consequence | Retain source representations in protected provenance and derive only a safe approved display value. | Exception consequence | Conflicting, unsafe, or unrepresentable content becomes a classified clinical-content exception. |
| Reconciliation consequence | Reconcile presence, sanitization, derived display, conflicts, and protected source retention counts. | Manual-review owner | Clinical content review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Clinical detailed mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: Q-202 / Q-202

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `Q-202` | Related open-question IDs | `Q-202` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Preserve a source-evidenced order or request when valid and represent its result as not evidenced through a migration-specific exception/state. Never convert an empty result to normal, negative, pending, cancelled, or completed and never synthesize a result. |
| Rejected alternatives | Dropping valid orders, defaulting clinical meaning, or synthesizing results. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must specify the target representation and exception contract before implementation. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 126,066 empty service results, 11,892 empty scan/lab order results, and 60,260 empty consult-service results at the evidence coordinate. | Target domains affected | Investigations, laboratory results, clinical reconciliation. |
| Transformation consequence | Create valid orders while withholding unevidenced result meaning under the approved target design. | Exception consequence | Use a result-not-evidenced state/exception; invalid orders remain separately classified. |
| Reconciliation consequence | Reconcile orders and results independently, including empty-result counts. | Manual-review owner | Laboratory result-exception owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Investigations and results detailed mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: Q-204 / Q-204

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `Q-204` | Related open-question IDs | `Q-204` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Import only source-evidenced facts and timestamps; do not simulate renewed workflow transitions. Record migration execution, mappings, transformations, failures, approvals, and reconciliation in a separate migration audit without contemporaneous-looking operational logs. |
| Rejected alternatives | Synthetic workflow history, normal operational activity generation, or an unaudited migration. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must define migration-audit records and source-evidence rules without changing operational audit semantics. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | All source rows and target domains; Classic has no general change journal. | Target domains affected | Clinical workflow, audit, all migration domains. |
| Transformation consequence | Persist only evidenced historical facts through migration-specific contracts. | Exception consequence | Missing event evidence remains absent or explicitly classified; it is never reconstructed. |
| Reconciliation consequence | Reconcile migration audit, mappings, transformations, failures, approvals, and domain outcomes. | Manual-review owner | Migration audit review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Migration foundation after Phase 2 specifications |
| Decision status | Accepted | Approval method | Direct project-owner decision |

## Workshop completion record

No physical or virtual workshop was conducted. This pack was resolved by direct project-owner decision and is therefore superseded as a workshop instrument.

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Workshop date | Not conducted; final-authority directive dated 2026-07-21. | Participants | No workshop held. |
| Stakeholder roles represented | Not applicable; the project owner exercised final authority directly. | Quorum or required authority present | Not required; final authority exercised directly. |
| Decisions accepted | D-214/Q-011; D-208/Q-201; Q-202; Q-204 | Decisions partially accepted | None. |
| Decisions deferred | None at business-policy level; technical specifications remain Phase 2 tasks. | Contradictions discovered | No unresolved policy contradiction after the consolidated directive. |
| Actions assigned | Define consultation grouping, representation sanitization, empty-result, and migration-audit contracts in Phase 2. | Action owners | Phase 2 delivery owners to be assigned; project owner retains final decision authority. |
| Action deadlines | Set during Phase 2 planning. | Follow-up workshop required | No. |
| Workshop chair approval | Not applicable; no workshop occurred. | Records/governance approval | mcwakey (established repository identity), Project Owner and Final Decision Authority |
| Workshop status | Superseded by final-authority decision | Approval method | Direct project-owner decision |
