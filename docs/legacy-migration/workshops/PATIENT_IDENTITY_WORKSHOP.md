# Patient identity and registration workshop

## Purpose and evidence boundary

This table preserves the Phase 1B pre-decision evidence, options, owners, decision points, and substantive recommendations. The authoritative accepted outcomes and clarifications are recorded below; no workshop occurred. Confirmed aggregates come from the 2026-07-21 read-only `uuhms` capture and the Phase 1A baseline. No record-level identity data is included.

| ID | Plain-language question | Why it matters | Confirmed evidence / affected count | Options and risks | Phase 1B recommendation (superseded by accepted outcome below) | Original proposed approval owner | Domains blocked | Decision point |
|---|---|---|---|---|---|---|---|---|
| D-201 / Q-001 | Should Classic OPD numbers become renewed patient numbers, aliases, or be replaced? | The target patient number is unique and sequence-managed. | 16,950 patients; 141 blank OPD values. The reproducible nonblank-normalized query finds 275 duplicate groups covering 1,259 rows; Phase 1A reported 276/1,400 under a different counting basis. | Preserve as primary: collisions/blanks violate target rules. Alias: preserves provenance but duplicate aliases still conflict. New number: safe uniqueness but loses visible continuity unless an alias/crosswalk is retained. | Generate renewed numbers and retain unique Classic OPD values as typed aliases plus an internal legacy-PK mapping; quarantine duplicate alias ownership pending D-211. | Registration lead + clinical governance | Patient pilot and every patient-linked domain | Before patient pilot |
| D-211 / Q-008 | Who owns a duplicated Classic OPD alias? | Target alias uniqueness permits only one owner, and distinct people must not be merged by name. | 275 nonblank duplicate OPD groups/1,259 rows in the current hashed query; name-only merging is prohibited. | Assign one owner: risks misidentification. Duplicate target aliases: violates constraint. Decorate aliases: changes historical identifier. Retain only in mapping evidence: less visible operationally. | Do not assign a duplicated alias to any patient until reviewed; preserve original value only in the protected migration crosswalk and create a classified exception per affected row. | Clinical governance + privacy + registration | Patient pilot, identity search, downstream reconciliation | Before patient pilot |
| Q-002 | What evidence is sufficient to distinguish patients in duplicate groups? | A wrong merge is a patient-safety and privacy event. | 1,001 duplicate normalized-name groups covering 2,278 rows; Classic PK is stable for technical mapping but is not a clinical identity key. | Name-only: prohibited. Multi-factor deterministic match: safer but still needs thresholds/review. Never merge during migration: creates possible duplicates but avoids false merges. | Never merge automatically. Use Classic PK for record linkage and submit suspected duplicates to a governed post-import review using specified multi-factor evidence. | Clinical governance + privacy officer | Patient deduplication and identity resolution | Before patient pilot |
| D-213 / Q-007 | What happens when target-required patient fields are blank or invalid? | The target requires first name, last name, DOB, gender and phone. | Phase 1A: 211 blank names, 151 blank sex values and 5,050 blank phone numbers; one pre-1900 DOB. Exact overlap across these defects is not yet quantified without row-level handling. | Invent placeholders: unsafe and hides defects. Drop rows: prohibited. Source remediation: best quality but may delay. Quarantine: transparent but excludes affected histories until resolved. | Require source/business remediation where possible; otherwise quarantine with reason codes. No invented names, DOBs, gender or phone values. | Registration lead + clinical governance | Patient pilot | Before patient pilot |
| Q-006 | How should encounters whose patient link is missing be handled? | Importing them under the wrong patient would corrupt longitudinal history. | `attendance.PAT_ID -> patients.PAT_ID` is an inferred relationship; the Phase 1A aggregate found 3,338 unmatched/sentinel-inclusive links among 51,927 attendances. The Phase 1B manifest records the exact predicate and candidate-zero rule. | Attach to a generic patient: unsafe. Drop: prohibited. Quarantine chain: preserves evidence but delays history. Source remediation: preferred where feasible. | Quarantine the encounter and its dependent chain unless a controlled source correction supplies a valid patient mapping. | Clinical governance | Visits, clinical, billing, claims, admissions | Before patient pilot for pilot-linked rows; before later domain import otherwise |

## Retained Phase 2 technical outputs

- Implement all five accepted outcomes below in mapping specifications, including an exception owner and SLA.
- Define the pilot identity test set using synthetic/anonymised cases only.
- Verify that Classic PKs are crosswalk keys and never renewed primary keys.

## Controlled decision outcome records

The evidence, options, owners, decision points, and recommendations above are preserved as the Phase 1B record. The project owner exercised final authority directly on 2026-07-21; no workshop, quorum, committee approval, meeting minutes, or additional signature was required. The selected option in each record is the recommendation only as modified by the consolidated clarifications in the authoritative directive.

### Decision outcome: D-201 / Q-001

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-201` | Related open-question IDs | `Q-001` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Generate renewed patient numbers through the renewed numbering mechanism; preserve each unique valid Classic OPD number as a typed legacy alias; keep the Classic patient PK only in the protected crosswalk. Blank or duplicate OPD values become neither renewed numbers nor automatic aliases. |
| Rejected alternatives | Reusing Classic PKs or OPD numbers as renewed PKs/numbers; assigning blank or duplicate OPD values as aliases. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Alias normalization and eligibility must be specified and tested in Phase 2; Classic PK access remains protected. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 16,950 Classic patients, including 141 blank OPD values and 1,259 rows in 275 normalized duplicate-OPD groups. | Target domains affected | Patient identity, aliases, patient pilot, all patient-linked domains. |
| Transformation consequence | Generate target numbers and eligible typed aliases; create protected legacy-to-renewed mappings. | Exception consequence | Classify blank/duplicate alias values; never merge or assign duplicate ownership automatically. |
| Reconciliation consequence | Reconcile every eligible source patient to one target patient or classified quarantine, and every unique eligible alias to at most one patient. | Manual-review owner | Registration identity-review owner designated for the patient pilot. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Patient pilot mapping contract |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: D-211 / Q-008

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-211` | Related open-question IDs | `Q-008` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Do not assign a duplicated Classic OPD number to any patient automatically. Preserve it in the protected crosswalk, create a duplicate-OPD exception for every affected patient, and establish alias ownership only through controlled manual identity review without merging distinct records. |
| Rejected alternatives | Automatic ownership, decorated aliases, duplicate target aliases, or merges performed to satisfy alias uniqueness. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Manual review requires approved identity evidence; name alone is insufficient. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 1,259 patient rows in 275 normalized duplicate-OPD groups at the captured evidence coordinate. | Target domains affected | Patient aliases, identity search, patient pilot, downstream reconciliation. |
| Transformation consequence | Suppress operational alias creation for duplicate values while preserving protected provenance. | Exception consequence | Create one classified duplicate-OPD exception per affected patient until controlled review resolves ownership. |
| Reconciliation consequence | Reconcile duplicate-group membership, exceptions, reviewed ownership, and unassigned values without loss. | Manual-review owner | Registration identity-review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Patient pilot mapping contract |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: Q-002 / Q-002

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `Q-002` | Related open-question IDs | `Q-002` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Do not merge patients automatically. Use the Classic patient PK only for source linkage, import separate eligible source patients separately, and send suspected duplicates to the renewed identity-review or merge workflow after migration. Names alone never justify a merge. |
| Rejected alternatives | Name-only or heuristic automatic merges during migration. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 may define candidate-review signals, but they cannot authorize automatic merging. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 2,278 patient rows in 1,001 normalized-name duplicate groups; all eligible patient rows remain individually traceable. | Target domains affected | Patient deduplication, identity review, patient pilot. |
| Transformation consequence | Maintain one source-chain mapping per Classic patient PK. | Exception consequence | Flag suspected duplicates for post-migration controlled review; do not coalesce dependent chains. |
| Reconciliation consequence | Reconcile source patient count to separately imported or quarantined patients and reported duplicate candidates. | Manual-review owner | Renewed patient identity-review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Patient pilot mapping contract |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: D-213 / Q-007

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-213` | Related open-question IDs | `Q-007` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Never invent required identity values. Apply reliable source remediation where evidenced; quarantine unresolved patients with explicit reason codes, retain their dependent source chain, and never reassign it. Any nullable or explicit-unknown target representation requires a separate controlled schema specification. |
| Rejected alternatives | Invented placeholders, silent drops, guessed values, or reassignment to another patient. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must define field-level validity, remediation evidence, quarantine reasons, and any separately approved target schema change. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | Affected baseline includes 211 blank names, 151 blank sex values, 5,050 blank phones, and one pre-1900 DOB; overlaps require row-safe measurement. | Target domains affected | Patient registration, demographics, patient pilot, dependent histories. |
| Transformation consequence | Apply only evidenced remediation; otherwise withhold target creation through classified quarantine. | Exception consequence | Quarantine unresolved patient and all dependent records as a traceable source chain. |
| Reconciliation consequence | Reconcile all source patients and dependent rows to imported or reason-coded quarantined chains. | Manual-review owner | Registration remediation and exception owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Patient pilot mapping contract |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: Q-006 / Q-006

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `Q-006` | Related open-question IDs | `Q-006` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Never attach an orphan encounter to a generic, first, current, or guessed patient. Quarantine the encounter and every dependent clinical, billing, claim, admission, and related row as one traceable chain; release only after a valid approved patient relationship is established. |
| Rejected alternatives | Generic-patient attachment, guessed linkage, silent discard, or isolated child import. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must enumerate the chain traversal, relationship predicate, sentinel rule, release control, and exception code. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 3,338 unmatched/sentinel-inclusive attendance-to-patient links among 51,927 attendances at the captured evidence coordinate, plus dependent chains. | Target domains affected | Visits, clinical, billing, claims, admissions, patient pilot. |
| Transformation consequence | Prevent persistence of the entire orphan source chain until the patient relation is approved. | Exception consequence | Create a chain-level orphan-patient exception with retained source relationships. |
| Reconciliation consequence | Reconcile orphan roots and every dependent row into the same imported or quarantined disposition. | Manual-review owner | Clinical identity exception owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Patient pilot mapping contract for pilot-linked rows; later domain contracts for remaining rows |
| Decision status | Accepted | Approval method | Direct project-owner decision |

## Workshop completion record

No physical or virtual workshop was conducted. This pack was resolved by direct project-owner decision and is therefore superseded as a workshop instrument.

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Workshop date | Not conducted; final-authority directive dated 2026-07-21. | Participants | No workshop held. |
| Stakeholder roles represented | Not applicable; the project owner exercised final authority directly. | Quorum or required authority present | Not required; final authority exercised directly. |
| Decisions accepted | D-201/Q-001; D-211/Q-008; Q-002; D-213/Q-007; Q-006 | Decisions partially accepted | None. |
| Decisions deferred | None at business-policy level; technical specifications remain Phase 2 tasks. | Contradictions discovered | No unresolved policy contradiction after the consolidated directive. |
| Actions assigned | Define patient-number, alias, quarantine, identity-review, and exception contracts in Phase 2. | Action owners | Phase 2 delivery owners to be assigned; project owner retains final decision authority. |
| Action deadlines | Set during Phase 2 planning. | Follow-up workshop required | No. |
| Workshop chair approval | Not applicable; no workshop occurred. | Records/governance approval | mcwakey (established repository identity), Project Owner and Final Decision Authority |
| Workshop status | Superseded by final-authority decision | Approval method | Direct project-owner decision |
