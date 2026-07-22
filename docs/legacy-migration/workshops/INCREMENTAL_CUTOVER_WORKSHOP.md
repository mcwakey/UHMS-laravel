# Incremental synchronization and cutover workshop

## Purpose and evidence boundary

The Phase 1B capability matrix describes what can be extracted. The accepted outcomes below authorize Phase 2 extraction/cutover specification only; they do not implement synchronization. The Classic account used for discovery remains over-privileged and is not approved for migration execution.

| ID | Plain-language question | Why it matters | Confirmed evidence / affected count | Options and risks | Phase 1B recommendation (superseded by accepted outcome below) | Original proposed approval owner | Domains blocked | Decision point |
|---|---|---|---|---|---|---|---|---|
| D-210 / Q-401 | How are changes captured for tables without timestamps? | Timestamp watermarks cannot detect inserts/updates/deletes on these tables. | 23/55 Classic tables have no date/timestamp column, listed in `CLASSIC_INCREMENTAL_CAPABILITY_MATRIX.md`; total affected rows must be calculated per approved domain from its row-count entries. | Timestamp anyway: misses changes. Full snapshot/hash diff: reliable but heavier. Database CDC: infrastructure/privilege complexity. Freeze-only: operational downtime. | Use ordered full snapshot plus row/content hash comparison for small/master tables; evaluate CDC or controlled freeze for high-risk mutable tables. Never infer delete events without evidence. | Architecture + DBA + operations | Incremental sync for reference, stock and result tables | Before incremental implementation |
| Q-402 | Which mutable timestamps are valid watermarks, and how are ties ordered? | `ON UPDATE` timestamps may overwrite creation meaning; timestamp-only pagination can skip rows. | Nine Classic fields are `ON UPDATE`; every table’s candidate timestamp/key/index capability is recorded in the matrix. | Timestamp only: skip/duplicate risk. `(timestamp, PK)` high-water mark: better but cannot detect deletes and fails without stable key. Snapshot diff: heavier. | Specify per-table extraction: immutable timestamp plus PK where evidenced; mutable timestamp plus PK with overlap/reconciliation; snapshot/hash where no reliable watermark. | Architecture + DBA | Incremental extraction | Before incremental implementation |
| Q-403 | What is the freeze window, cutover authority and reconciliation-exception process? | Final consistency cannot be proven while the source continues changing. | 55 tables/1,548,058 rows at the Phase 1B capture; source is writable and the discovery account has broad privileges. Duration and business tolerance are not yet evidenced. | No freeze: race conditions. Full freeze: operational impact. Staged/domain freeze with final delta: complex but controllable. | Specify a staged freeze and final read-only delta, with explicit go/no-go thresholds; the project owner is final go/no-go authority. | Programme sponsor + operations + clinical/finance leads | Final delta and cutover | Cutover |
| Q-404 | Which integrations, schedulers and listeners are disabled and restored? | Historical rows can trigger real SMS, notifications, queue moves, accounting, stock and audit forwarding. | Target registers 26 activity-logged models, observers/listeners, and scheduled appointment, claim, notification, auto-close, payment, expiry and journey jobs. Explicit installed-database queries found zero triggers, zero scheduled events and zero stored routines. | Leave active: unacceptable side effects. Disable everything globally: operational outage/restore risk. Scoped isolation with checklist: safest. | Use an environment-isolated migration runtime plus a specified pause/restore checklist; `Model::withoutEvents()` alone is insufficient. | Operations + security + domain owners | Commit runs and cutover | Before any commit-mode test; project-owner go/no-go at cutover |
| D-101 / D-102 | What connection and schema guards are mandatory for execution? | Three Classic-shaped schemas exist and the current account can write. | Approved source is exactly `uuhms`; the current structural fingerprint is recorded in `CLASSIC_SCHEMA_FINGERPRINT.json`. Server/session defaults are writable and schema privileges include writes; the discovery command forces REPEATABLE READ plus a session-level read-only transaction and verifies both during capture. | Reuse broad account: prohibited as final account. Dedicated SELECT account + allow-list/fingerprint: limits blast radius. | DBA creates a `SELECT`/metadata-only account scoped to `uuhms`; every run verifies schema name, fingerprint contract and expected table/column counts before extraction. | DBA + security + architecture | All migration execution | Before any implementation connectivity test |

## Retained Phase 2 technical outputs

- Table-by-table extraction mode and deletion-evidence strategy implementing the accepted policy.
- Freeze schedule, go/no-go authority and exception thresholds.
- Integration pause/restore runbook owners.
- Dedicated read-only Classic account acceptance evidence.

## Controlled decision outcome records

The evidence, options, owners, decision points, and recommendations above are preserved as the Phase 1B record. The project owner exercised final authority directly on 2026-07-21; no workshop, quorum, committee approval, meeting minutes, or additional signature was required. The selected option in each record is the recommendation only as modified by the consolidated clarifications in the authoritative directive.

### Decision outcome: D-210 / Q-401

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-210` | Related open-question IDs | `Q-401` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Use ordered full snapshots and deterministic row/content-hash comparison for small/master tables without reliable timestamps. For high-volume or high-risk mutable tables use an approved freeze, snapshot, or CDC approach where supported; never infer deletion without evidence. |
| Rejected alternatives | Unreliable timestamp watermarks, unproved deletion inference, or one extraction strategy for all tables. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must select and performance-test a strategy per table without broadening Classic privileges. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | 23 of 55 Classic tables lack date/timestamp columns; their recorded row counts and capabilities define the design inputs. | Target domains affected | Incremental extraction, reference, stock, and result domains. |
| Transformation consequence | Apply table-specific deterministic snapshot/hash or approved infrastructure strategy. | Exception consequence | Unexplained differences, missing deletion evidence, or non-deterministic hashes stop the delta. |
| Reconciliation consequence | Reconcile ordered snapshots, content hashes, inserts/changes, and evidence-backed deletions per table. | Manual-review owner | Incremental extraction exception owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Incremental synchronization detailed design |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: Q-402 / Q-402

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `Q-402` | Related open-question IDs | `Q-402` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Use timestamp-plus-primary-key high-water marks where both are reliable, overlap windows plus reconciliation for mutable ON UPDATE timestamps, and full snapshot/hash comparison where no reliable watermark exists. Define the strategy per table in Phase 2. |
| Rejected alternatives | Timestamp-only pagination, assuming mutable timestamps are creation times, or forcing watermarks where unsupported. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must specify stable ordering, overlap, re-read, deletion handling, indexes, and reconciliation per table. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | All 55 Classic tables, including nine ON UPDATE fields and 23 tables without date/timestamp columns. | Target domains affected | Incremental extraction and every source domain. |
| Transformation consequence | Produce an approved extraction contract for each table. | Exception consequence | Rows outside reliable watermark semantics or ambiguous deltas become extraction exceptions. |
| Reconciliation consequence | Reconcile overlaps, ties, re-reads, snapshot hashes, and final counts per table. | Manual-review owner | Incremental extraction design owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Incremental synchronization detailed design |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: Q-403 / Q-403

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `Q-403` | Related open-question IDs | `Q-403` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Use a staged freeze followed by a final read-only delta with explicit go/no-go thresholds. No activation may occur while critical reconciliation differences remain unresolved; the project owner is final go/no-go authority and all clinical/financial exceptions remain visible and classified. |
| Rejected alternatives | No freeze, activation with critical unresolved differences, or hidden exception waivers. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2/cutover planning must define dates, stages, thresholds, rollback, evidence pack, and owner decision record; this approval does not activate cutover. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | All 55 tables and all in-scope migration/reconciliation populations at the final extraction coordinate. | Target domains affected | Cutover, final delta, clinical and finance reconciliation. |
| Transformation consequence | Execute only the approved staged-freeze/final-delta runbook. | Exception consequence | Threshold breach or critical unresolved exception is a no-go condition. |
| Reconciliation consequence | Final source/target counts, financial/stock contracts, exceptions, and fingerprints must satisfy explicit thresholds. | Manual-review owner | Cutover reconciliation owner; project owner retains final go/no-go authority. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Incremental/cutover design; operational execution at cutover |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: Q-404 / Q-404

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `Q-404` | Related open-question IDs | `Q-404` |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Use an environment-isolated migration runtime and pause or isolate queues, schedulers, SMS, email, notification dispatch, audit forwarding, billing/accounting posting, stock operations, pathway/queue mutations, and bed-state actions. Model event suppression alone is insufficient; restore only after reconciliation and cutover checks pass. |
| Rejected alternatives | Normal-runtime imports, incomplete listener suppression, production testing, or restoration before checks pass. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must produce a tested pause/restore inventory and fail-closed isolation preflight; no production writes are authorized. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | All historical rows and target integrations/services identified in target-side-effect evidence. | Target domains affected | Runtime, all domains, cutover integrations. |
| Transformation consequence | Route historical persistence exclusively through an isolated migration runtime. | Exception consequence | Any active forbidden integration or side effect aborts commit mode and is reported. |
| Reconciliation consequence | Prove zero forbidden side effects and controlled restoration after successful reconciliation. | Manual-review owner | Migration runtime/isolation owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Migration foundation design and cutover runbook |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: D-101

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-101` | Related open-question IDs | None. |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Require a database account limited to SELECT and metadata access on the approved uuhms schema. The existing broad-privilege Classic account is not approved for migration execution. |
| Rejected alternatives | Using the current broad-privilege account or granting write/cross-schema access. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | DBA/security must provision and independently verify least privilege before any implementation connectivity test. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | The approved Classic uuhms schema and all metadata/evidence queries. | Target domains affected | Classic connectivity, evidence capture, every extraction. |
| Transformation consequence | Use only the dedicated least-privilege connection for migration execution. | Exception consequence | Privilege, schema, or connection mismatch fails closed before extraction. |
| Reconciliation consequence | Record verified grants and prove no write capability without exposing credentials. | Manual-review owner | DBA/security connectivity owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Migration foundation prerequisite |
| Decision status | Accepted | Approval method | Direct project-owner decision |

### Decision outcome: D-102

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Decision ID | `D-102` | Related open-question IDs | None. |
| Selected option | Approved recommendation, as clarified by the consolidated project-owner directive. | Final approved policy | Every run must verify connection name, exact database name uuhms, approved schema fingerprint, expected table/column counts, and expected database version/compatibility, and fail closed on mismatch. |
| Rejected alternatives | Best-effort warnings, alternative Classic schemas, or continuing after fingerprint/count/version mismatch. | Approval rationale | Direct project-owner approval of the evidence-backed, fail-closed recommendation and consolidated clarifications. |
| Conditions and limitations | Phase 2 must version the guard contract and controlled fingerprint-update process; only uuhms is approved. | Approving stakeholder name | mcwakey (established repository identity) |
| Approving stakeholder role | Project Owner and Final Decision Authority | Approval date | 2026-07-21 |
| Additional required approvers | None required; final authority exercised directly. | Signature or meeting-minute reference | Project-owner directive recorded in the migration decision register |
| Source records affected | The 55-table, 479-column uuhms schema fingerprint and database-version evidence. | Target domains affected | All Classic evidence capture and migration runs. |
| Transformation consequence | Execute schema guards before any source query beyond safe metadata preflight. | Exception consequence | Any mismatch is a hard stop with no extraction or write progression. |
| Reconciliation consequence | Persist guard inputs/results, query/tool versions, and approved fingerprint reference per run. | Manual-review owner | Migration schema-guard owner. |
| Manual-review SLA | Define in the Phase 2 exception specification before importer authorization. | Effective implementation phase | Migration foundation prerequisite |
| Decision status | Accepted | Approval method | Direct project-owner decision |

## Workshop completion record

No physical or virtual workshop was conducted. This pack was resolved by direct project-owner decision and is therefore superseded as a workshop instrument.

| Field | Recorded outcome | Field | Recorded outcome |
|---|---|---|---|
| Workshop date | Not conducted; final-authority directive dated 2026-07-21. | Participants | No workshop held. |
| Stakeholder roles represented | Not applicable; the project owner exercised final authority directly. | Quorum or required authority present | Not required; final authority exercised directly. |
| Decisions accepted | D-210/Q-401; Q-402; Q-403; Q-404; D-101; D-102 | Decisions partially accepted | None. |
| Decisions deferred | None at business-policy level; technical specifications remain Phase 2 tasks. | Contradictions discovered | No unresolved policy contradiction after the consolidated directive. |
| Actions assigned | Define per-table extraction contracts, isolated runtime runbook, staged-freeze thresholds, schema guards, and the restricted source account in Phase 2. | Action owners | Phase 2 delivery owners to be assigned; project owner retains final decision authority. |
| Action deadlines | Set during Phase 2 planning. | Follow-up workshop required | No. |
| Workshop chair approval | Not applicable; no workshop occurred. | Records/governance approval | mcwakey (established repository identity), Project Owner and Final Decision Authority |
| Workshop status | Superseded by final-authority decision | Approval method | Direct project-owner decision |
