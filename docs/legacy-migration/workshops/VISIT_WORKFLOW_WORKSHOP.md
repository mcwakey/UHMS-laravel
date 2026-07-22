# Visit, appointment and workflow mapping workshop

## Purpose and evidence boundary

This pack covers historical visits, appointment derivation, workflow enums and admission-date semantics. Target operational services are not suitable historical import paths because they generate current numbers, actors, timestamps, queues, billing and notifications.

| ID | Plain-language question | Why it matters | Confirmed evidence / affected count | Options and risks | Phase 1B recommendation (superseded by accepted outcome below) | Original proposed approval owner | Domains blocked | Decision point |
|---|---|---|---|---|---|---|---|---|
| D-205 / Q-004 | Which Classic visit, triage and workflow values map to target enums? | Unknown enum strings can fail model hydration or create false workflow states. | 51,927 attendances. Confirmed values include 42,708 outpatient, 7,998 inpatient and 1,221 outsider; triage contains 35,271 blanks and out-of-domain numeric values. Full safe aggregate distributions are in `CLASSIC_AGGREGATE_RESULTS.json`. | Convenient defaults: prohibited. One-to-one mapping where exact: low risk. Explicit transform/reject table: more work but auditable. | Specify a value-by-value map; preserve exact matches, transform only explicitly specified variants, and classify every unknown as an exception. | Clinical operations + registration + architecture | Visits, appointments, admissions, downstream workflow | Before patient pilot |
| D-206 / Q-301 | What is the policy for zero, future, reversed and implausible visit-related dates? | Target date rules and historical chronology must not be falsified. | 1,003 attendance rows discharge before admission; five appointments are future at capture and one is year 2100+; one patient DOB is pre-1900; 20 claims reverse admission dates. | Coerce to null/today: invents facts. Reject all: may exclude substantial history. Field-specific quarantine/preserve: transparent but requires rules. | Preserve valid dates; quarantine impossible chronology; treat zero/placeholder candidates as unknown only where the Phase 2 target design supports it; never replace with execution time. | Clinical governance + records management | Visits, admissions, appointments, claims | Before patient pilot for patient/DOB/visit rules; later before affected domains |
| Q-302 | Are equal Classic admit/discharge dates real episodes or defaults? | Treating defaults as admissions would create tens of thousands of false episodes. | 50,531/51,927 attendance rows have equal admission/discharge dates; 50,607 admission dates and 51,251 discharge dates equal the attendance date. | Treat all as episodes: severe over-creation risk. Treat all as defaults: may lose real same-day care. Derive only with corroborating inpatient/bed evidence: incomplete but defensible. | Do not create an admission from equal dates alone; require specified corroborating fields and quarantine ambiguous candidates. | Inpatient operations + clinical governance | Admissions, bed history, inpatient billing | Later implementation before admission mapping |
| D-207 / Q-005 | Which zero relationship values are sentinels rather than missing parents? | Turning sentinels into foreign keys invents reference data; treating true IDs as null loses links. | No Classic FKs exist. Examples: every `billing.SERV_ID` is unmatched/zero, every `consult_scan_lab.USER_ID` is zero, and 460,730/460,758 `serv_results.SCL_ID` values are zero. All 77 tested candidate relations now have predicates, query/result hashes and counts. | Global zero-is-null: may erase legitimate domain-specific zero. Zero-is-orphan: floods errors. Per-relation rule: auditable. | Specify sentinel semantics separately for each manifest relationship; a zero rule for one column must not propagate to another. | Source SME + each domain owner | All relationship mappings | Before each affected domain; critical patient links before pilot |
| Q-010 | How are Classic appointments numbered and linked when attendance is absent or unmatched? | The target requires patient, department, date/time and creator and normal requests reject past dates. | 1,364 appointment rows; no null dates, five future, one year 2100+, and 67 inferred attendance links unmatched/sentinel-inclusive. | Derive all fields from attendance: fails unmatched cases and may infer wrong department. Import only matched: explicit but incomplete. Quarantine incomplete: safe. | Import only where date and required parent derivations are evidence-backed; generate target number, retain Classic PK in crosswalk, and quarantine unmatched/ambiguous rows. | Scheduling lead + clinical governance | Appointments and linked visits/routes | Later implementation |
| D-214 (appointment portion) / Q-203 | May historical rows bypass current operational validation, and under what migration-only rules? | Normal services stamp `now()`, current actors, prices, queues and notifications, and reject past appointments. | Target code confirms these side effects; 1,364 appointments and 51,927 attendances are potential inputs. | Use normal services: corrupts history. Relax production rules globally: unsafe. Migration-only validated persistence: requires engineering controls. | Use a migration-specific persistence boundary with explicit FK/enum/invariant validation and side-effect suppression; do not weaken normal services. | Architecture + clinical governance | Visits, appointments, admissions, clinical history | Before implementation, after Phase 2 specification |

## Retained Phase 2 technical outputs

- Detailed enum and date-rule appendices implementing the accepted policy.
- Per-relationship sentinel specifications for pilot-critical links.
- Appointment inclusion/quarantine criteria and owning team.

## Controlled decision outcome records

The evidence, options, owners, decision points, and recommendations above are preserved as the Phase 1B record. The project owner exercised final authority directly on 2026-07-21; no workshop, quorum, committee approval, meeting minutes, or additional signature was required. The selected option in each record is the recommendation only as modified by the consolidated clarifications in the authoritative directive.

### Decision outcome: D-205 / Q-004

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-205` | Related open-question IDs | `Q-004` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Create explicit source-value-to-target-value tables for visit, triage, and workflow values. Preserve exact semantic matches, transform only explicitly approved variants, and classify blank, unknown, and out-of-domain values as exceptions without defaults. |
| Rejected alternatives | Convenient defaults, literal mappings without semantic proof, or unrecorded normalization. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must enumerate every observed value, target enum, approved transform, exception code, and reconciliation count. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 51,927 attendances and the categorical distributions recorded in the Classic aggregate evidence. | Target domains affected | Visits, triage, workflow, admissions, downstream domains. |
| Transformation consequence | Apply only approved per-value crosswalk entries. | Exception consequence | Unknown, blank, or out-of-domain values produce classified exceptions. |
| Reconciliation consequence | Reconcile source value frequencies to mapped, transformed, and exception frequencies exactly. | Manual-review owner | Clinical workflow mapping owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Visit and workflow detailed mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: D-206 / Q-301

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-206` | Related open-question IDs | `Q-301` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Preserve valid historical dates; never replace dates with migration time, current date, or guesses. Treat zero or approved placeholder dates as unknown/not evidenced only where the target representation supports it, and quarantine impossible, reversed, or implausible chronology that cannot be represented safely. |
| Rejected alternatives | Coercion to today, migration time, guessed dates, or a global zero-date rule. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must define rules per field and domain, including target representation and chronology tests. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | Date defects documented above, including 1,003 reversed attendance spans and other future, zero, placeholder, or implausible values. | Target domains affected | Visits, admissions, appointments, claims, patient dates. |
| Transformation consequence | Use field-specific validated date transformations only. | Exception consequence | Unrepresentable or impossible chronology becomes a reason-coded exception. |
| Reconciliation consequence | Reconcile each date field into preserved, approved-unknown, or exception categories. | Manual-review owner | Domain date-exception owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Relevant domain detailed mapping before pilot/import |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: Q-302 / Q-302

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `Q-302` | Related open-question IDs | `Q-302` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Equal admission and discharge dates alone do not establish an admission. Create an admission only with corroborating inpatient, bed, ward, or other approved source evidence; quarantine ambiguous candidates. |
| Rejected alternatives | Treating every equal-date attendance as an admission or assuming every equal date is a default. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must specify approved corroborating predicates and ambiguity reasons. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 50,531 of 51,927 attendance rows have equal admission/discharge dates. | Target domains affected | Admissions, beds, inpatient billing. |
| Transformation consequence | Derive admissions only when the approved corroboration contract succeeds. | Exception consequence | Ambiguous candidates remain quarantined and cannot create admission or bed history. |
| Reconciliation consequence | Reconcile all candidates to corroborated admissions, non-admissions, or classified ambiguity. | Manual-review owner | Inpatient mapping review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Admissions detailed mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: D-207 / Q-005

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-207` | Related open-question IDs | `Q-005` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Define sentinel handling separately for every relationship. A zero sentinel in one column creates no global zero-is-null rule; preserve unresolved relationship facts in exception metadata and never create artificial parents. |
| Rejected alternatives | Global zero-is-null, treating every zero as an orphan, or fabricating parent records. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must specify a predicate and sentinel rule for each in-scope relationship using the evidence manifest. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | All 77 tested candidate relationships and their hashed orphan/sentinel counts. | Target domains affected | All relationship-dependent domains. |
| Transformation consequence | Apply relationship-specific sentinel and join contracts. | Exception consequence | Unresolved or invalid references become relation-specific exceptions with protected source facts. |
| Reconciliation consequence | Reconcile every tested relationship, including zero-orphan relationships, under its approved predicate. | Manual-review owner | Domain relationship-mapping owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Each domain detailed mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: Q-010 / Q-010

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `Q-010` | Related open-question IDs | `Q-010` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Import appointments only when patient, department, creator, and date/time derivations are evidence-backed. Generate renewed appointment numbers, retain Classic keys only in the crosswalk, quarantine unmatched or ambiguous appointments, and bypass present-day request validation only through the migration persistence boundary. |
| Rejected alternatives | Guessing parents, preserving Classic PK as target PK/number, silently dropping rows, or using normal operational creation services. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must define required-field derivations, historical validation, number generation, exception codes, and reconciliation. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 1,364 Classic appointments, including 67 unmatched/sentinel-inclusive attendance links and documented date anomalies. | Target domains affected | Appointments, visits, routes. |
| Transformation consequence | Generate renewed numbers and create only evidence-complete historical appointments through the migration boundary. | Exception consequence | Missing or ambiguous parent/date/creator evidence becomes a classified appointment exception. |
| Reconciliation consequence | Reconcile all Classic appointments to imported or reason-coded exception outcomes. | Manual-review owner | Scheduling exception-review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Appointment detailed mapping |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: D-214 / Q-203

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-214` | Related open-question IDs | `Q-203` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Use a migration-specific persistence layer, not normal operational services, for historical creation. Enforce foreign keys, enums, uniqueness, precision, and approved logical invariants while suppressing queues, notifications, billing, stock, accounting, bed, pathway, and audit side effects; never weaken production validation globally. |
| Rejected alternatives | Normal service creation, global validation relaxation, or relying solely on model event suppression. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | This is design authority, not importer authorization; Phase 2 contracts and the reviewed migration foundation are prerequisites. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | All approved historical source rows that may eventually enter renewed UHMS. | Target domains affected | All migration domains and runtime integrations. |
| Transformation consequence | Define validated migration-only persistence contracts distinct from operational services. | Exception consequence | Fail closed on constraint, invariant, or isolation failure with classified errors. |
| Reconciliation consequence | Prove dry-run/commit parity, side-effect isolation, and per-domain reconciliation before authorization. | Manual-review owner | Migration engineering exception owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Migration foundation after Phase 2 mapping specifications |
| Decision status | Accepted | Approval method | Direct project-owner decision |

## Workshop completion record

No physical or virtual workshop was conducted. This pack was resolved by direct project-owner decision and is therefore superseded as a workshop instrument.

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Workshop date | Not conducted; final-authority directive dated 2026-07-21. | Participants | No workshop held. |
| Stakeholder roles represented | Not applicable; the project owner exercised final authority directly. | Quorum or required authority present | Not required; final authority exercised directly. |
| Decisions accepted | D-205/Q-004; D-206/Q-301; Q-302; D-207/Q-005; Q-010; D-214/Q-203 | Decisions partially accepted | None. |
| Decisions deferred | None at business-policy level; technical specifications remain Phase 2 tasks. | Contradictions discovered | No unresolved policy contradiction after the consolidated directive. |
| Actions assigned | Create field/value crosswalks, date and sentinel rules, appointment mappings, and the migration-persistence contract in Phase 2. | Action owners | Phase 2 delivery owners to be assigned; project owner retains final decision authority. |
| Action deadlines | Set during Phase 2 planning. | Follow-up workshop required | No. |
| Workshop chair approval | Not applicable; no workshop occurred. | Records/governance approval | mcwakey (established repository identity), Project Owner and Final Decision Authority |
| Workshop status | Superseded by final-authority decision | Approval method | Direct project-owner decision |
