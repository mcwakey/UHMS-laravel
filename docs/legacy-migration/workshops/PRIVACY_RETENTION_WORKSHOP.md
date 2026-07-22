# Privacy, retention and historical audit workshop

## Purpose and evidence boundary

This pack decides what historical content may be retained and how provenance is represented. It does not authorize copying Classic credentials or operational logs.

| ID | Plain-language question | Why it matters | Confirmed evidence / affected count | Options and risks | Phase 1B recommendation (superseded by accepted outcome below) | Original proposed approval owner | Domains blocked | Decision point |
|---|---|---|---|---|---|---|---|---|
| D-209 / Q-303 | Should Classic notifications be retained, summarized or excluded? | Notifications can contain PHI/free text but are not a reliable audit trail. | 141 notification rows; free-text request fields exist; Classic has no general audit log. | Copy all: privacy and unintended notification risk. Summarize: may still expose content and invent audit meaning. Exclude with aggregate evidence: loses operational messages but minimizes risk. | Exclude notification content from the renewed operational tables; retain only nonidentifying aggregate reconciliation unless records management identifies a legal retention obligation. | Privacy officer + records manager | Notifications, retention, cutover archive | Before later implementation |
| Q-305 | Should dormant maternity and occupation tables be excluded? | Empty modules can add unsupported scope without preserving data. | `mat_family`, `mat_obstetrics`, and `sett_ocuupation` each contain zero rows in the fingerprinted capture. | Include empty importers: unnecessary scope. Exclude explicitly: safe unless a later delta adds rows. | Exclude for the captured baseline, but make pre-cutover row-count fingerprint changes a stop condition requiring scope review. | Clinical product owner + records manager | Maternity/occupation scope | Later implementation; recheck at cutover |
| D-106 / Q-204 | How should migration provenance and historical audit be represented? | Target activity/events use current actors/times; simulating them would misstate history. | Classic has no general change journal. Target has 26 activity-logged models and external audit forwarding. | Generate normal target activity: false contemporaneous audit and side effects. No migration audit: fails traceability. Separate labelled migration audit: truthful. | Record migration execution/mapping/reconciliation audit separately and label all reconstructed facts; import only source-evidenced event history. | Compliance + privacy + audit | All domains and evidentiary reporting | Before implementation and cutover |
| D-008 | Confirm that Classic credentials and permission flags remain excluded. | Credential reuse and blanket permissions are unsafe. | 86 Classic users; password material exists but was not selected; all observed Classic permission flags are `Y`. | Migrate credentials/flags: prohibited. Re-provision access: secure but requires operations. | Keep D-008 accepted: migrate only approved identity/attribution; provision credentials/roles independently. | Security owner + HR | Users and access control | Before patient pilot |

## Retained Phase 2 technical outputs

- Retention schedule and lawful basis by data class under the accepted policy.
- Historical-audit wording and access controls for migration evidence.
- Assign the re-evaluation owner and stop procedure for a changed source fingerprint.

## Controlled decision outcome records

The evidence, options, owners, decision points, and recommendations above are preserved as the Phase 1B record. The project owner exercised final authority directly on 2026-07-21; no workshop, quorum, committee approval, meeting minutes, or additional signature was required. The selected option in each record is the recommendation only as modified by the consolidated clarifications in the authoritative directive.

### Decision outcome: D-209 / Q-303

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-209` | Related open-question IDs | `Q-303` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Exclude Classic notification content from renewed operational notification tables. Retain only non-identifying aggregate reconciliation evidence unless a later explicit legal-retention requirement is established, and never trigger historical notifications. |
| Rejected alternatives | Copying or summarizing identifiable notification content into operational tables or triggering it. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Any later retention expansion requires a new controlled decision and privacy design; generated evidence remains non-identifying. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 141 Classic notification rows and their aggregate counts; no notification content is approved for migration. | Target domains affected | Notifications, privacy, retention, cutover evidence. |
| Transformation consequence | Create aggregate-only reconciliation evidence; create no operational notifications. | Exception consequence | Unexpected content exposure or attempted operational persistence is a stop condition. |
| Reconciliation consequence | Reconcile notification row counts only through non-identifying aggregates and prove zero operational imports/triggers. | Manual-review owner | Privacy incident-review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Privacy/audit detailed mapping and cutover controls |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: Q-305 / Q-305

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `Q-305` | Related open-question IDs | `Q-305` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Exclude currently empty maternity and occupation source tables from implementation. A changed pre-cutover fingerprint or nonzero row count is a stop condition requiring scope review. |
| Rejected alternatives | Building empty importers or ignoring later source population changes. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | The exclusion applies only to the approved fingerprinted zero-row baseline. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | mat_family, mat_obstetrics, and sett_ocuupation, each zero rows at the Phase 1B capture. | Target domains affected | Migration scope, maternity, occupation, cutover guards. |
| Transformation consequence | No mappings/importers for the empty baseline tables. | Exception consequence | Fingerprint or row-count change stops execution and reopens scope review. |
| Reconciliation consequence | Pre-cutover reconciliation must confirm the approved tables remain empty and fingerprint-compatible. | Manual-review owner | Migration scope-change review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Phase 2 scope controls and cutover recheck |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: D-106 / Q-204

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-106` | Related open-question IDs | `Q-204` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Maintain a separate, clearly labelled migration audit recording run identity, mappings, transformations, failures, approvals, and reconciliation. Import only source-evidenced historical facts and never create false operational audit events. |
| Rejected alternatives | Normal operational audit simulation or migration without traceability. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must define audit schema/contracts and privacy-safe retention before the migration foundation is authorized. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | All approved source rows, transformations, mappings, failures, approvals, and reconciliation outputs. | Target domains affected | Migration audit, provenance, all domains. |
| Transformation consequence | Create migration-specific audit facts distinct from operational activity. | Exception consequence | Missing provenance, false operational events, or audit leakage are fail-closed conditions. |
| Reconciliation consequence | Reconcile every run, mapping, transform, failure, approval, and domain result through the migration audit. | Manual-review owner | Migration audit/governance review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Migration foundation after Phase 2 specifications |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: D-008

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-008` | Related open-question IDs | None. |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Exclude every Classic password and permission flag. Import only approved staff identity and historical attribution; provision renewed authentication, roles, and permissions independently. |
| Rejected alternatives | Credential or Classic permission migration, reuse, or derivation. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Evidence capture, mappings, fixtures, and logs must never contain credential material. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 86 Classic users; password material and blanket Classic permission flags are excluded. | Target domains affected | Staff identity, authentication, authorization, security. |
| Transformation consequence | Map identity/attribution only; create no credentials, roles, or permissions from Classic values. | Exception consequence | Any captured or emitted credential/permission material is a security stop condition. |
| Reconciliation consequence | Prove zero Classic credentials/permission flags in migration artefacts and outputs. | Manual-review owner | Security incident-review owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Staff mapping and migration-foundation security controls |
| Decision status | Accepted | Approval method | Direct project-owner decision |

## Workshop completion record

No physical or virtual workshop was conducted. This pack was resolved by direct project-owner decision and is therefore superseded as a workshop instrument.

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Workshop date | Not conducted; final-authority directive dated 2026-07-21. | Participants | No workshop held. |
| Stakeholder roles represented | Not applicable; the project owner exercised final authority directly. | Quorum or required authority present | Not required; final authority exercised directly. |
| Decisions accepted | D-209/Q-303; Q-305; D-106/Q-204; D-008 | Decisions partially accepted | None. |
| Decisions deferred | None at business-policy level; technical specifications remain Phase 2 tasks. | Contradictions discovered | No unresolved policy contradiction after the consolidated directive. |
| Actions assigned | Define aggregate notification evidence, scope fingerprint gates, migration audit, provenance, and credential-exclusion controls in Phase 2. | Action owners | Phase 2 delivery owners to be assigned; project owner retains final decision authority. |
| Action deadlines | Set during Phase 2 planning. | Follow-up workshop required | No. |
| Workshop chair approval | Not applicable; no workshop occurred. | Records/governance approval | mcwakey (established repository identity), Project Owner and Final Decision Authority |
| Workshop status | Superseded by final-authority decision | Approval method | Direct project-owner decision |
