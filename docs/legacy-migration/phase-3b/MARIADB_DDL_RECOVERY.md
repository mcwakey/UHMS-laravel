# Phase 3B MariaDB foundation DDL and partial recovery

Status: **actual MariaDB 10.4 DDL and deterministic interrupted-install recovery proven through migrations 000110-000115; repository activation is identity-bound and fail-closed**  
Evidence date: 2026-07-22

## Safety boundary

All database evidence came from a fresh local-only MariaDB datadir under the explicitly marked disposable Phase 3B artifact root. It was not Classic `uuhms`, a renewed target, production or a valued/shared database. It contained only synthetic metadata and foundation structures. Credentials, host details and the ephemeral port are not recorded here.

The server was stopped through a clean MariaDB shutdown after DDL and allocator verification. The explicit disposable marker and datadir were retained for audit; nothing was recursively deleted.

No `down()` migration, destructive repair, automatic object drop or protected-record deletion was used.

## Implemented recovery controls

The `Foundation\Installation` namespace supplies:

- version-bound typed object expectations for tables, columns, indexes, constraints, triggers and ledger metadata;
- normalized definition-hash observations;
- deterministic states for absent, matching, drifted, conflicting and repairable metadata-gap objects;
- reserved-namespace conflict detection;
- a planner that permits only absent or explicitly metadata-only expected operations;
- hard rejection of version mismatch and drift;
- an allow-listed DDL executor interface;
- post-operation reinspection before recording success;
- a fail-closed installation coordinator requiring verified physical identity and audit authority; and
- an append-only `legacy_migration_installation_journal` added by migration `2026_07_22_000113`.

The only repository-integrated coordinator is `FoundationInstallationCompositionService`. It consumes the separately disabled schema-write, installation-journal and partial-repair configuration gates, resolves the external identity key only at use time, verifies the observed physical target and issues one HMAC-sealed installation session bound to the target, audit reference and immutable manifest versions. The executor still revalidates the physical proof and session on every operation, so direct executor construction provides no authority.

Normal Laravel execution of migrations 000110-000115 on MariaDB/MySQL is permanently denied. These migration classes remain the schema source and the SQLite test fixture path, but real installation/recovery must use the ordered immutable manifests and installation journal. No command has been added to expose this path.

The journal records only protected target identity, object coordinates, expected/observed definition hashes, state, attempt and audit reference. Database triggers reject update and deletion. It has no foreign key to a business table and no cascade.

An interrupted run can therefore be inspected without guessing:

- exact matching objects are retained and skipped;
- absent approved objects can be planned explicitly;
- missing approved ledger metadata can be classified separately;
- drifted or unknown reserved objects fail closed; and
- unknown/conflicting objects are never dropped or rewritten automatically.

## Actual MariaDB 10.4 installation evidence

Migrations 000110 through 000115 ran successfully on disposable MariaDB `10.4.32-MariaDB`. The first 000114 attempt exposed and failed on an overlength implicit index; the migration owner corrected it to explicit `lm_purge_request_ref_uq`, after which a clean installation succeeded. This is direct MariaDB evidence rather than SQLite inference.

Installed evidence:

| Measurement | Observed |
|---|---:|
| Foundation tables | 24 |
| Foundation columns | 566 |
| Foundation triggers | 53 |
| CHECK constraints | 4 |
| Generated columns | 2 |
| Foreign keys to business tables | 0 |
| Cascading foundation foreign keys | 0 |
| Longest index name | 64 characters |

All 24 foundation tables were InnoDB with `utf8mb4_unicode_ci`; no index name exceeded MariaDB's limit and all installed triggers were row triggers with valid timing.

Canonical final verification hashes use SHA-256 over canonical JSON maps sorted by object name, with DDL whitespace normalized and runtime `AUTO_INCREMENT` values removed:

- ordered 24-table `SHOW CREATE TABLE` bundle: `a1f17f4618ef2353474d14b88cb898bb199b9875c7b4bb7f676ab68ff3f55de9`;
- ordered 53-trigger definition bundle: `5e241c2e2e7b8a67f2642e016b2aa74b866248cd8000220306f501197199936b`.

The installation journal accepted an append and rejected both an update and a delete on MariaDB.

## Actual partial-failure evidence

A second synthetic schema used a deliberately limited account with table DDL privileges but without `TRIGGER`. The first Phase 3 migration failed at the first trigger creation after MariaDB had auto-committed its table statements.

Observed post-failure state:

- four foundation tables present;
- zero foundation triggers present;
- no successful Laravel migration-ledger row; and
- no business table or protected record.

This reproduces the exact unsafe MariaDB partial-install class identified by `IMR-P3-012`/`DBI-009`. A normal rerun is correctly prevented by structural preflight drift rather than compounding the partial state.

The deterministic inspector/planner unit tests prove that a matching partial installation executes only absent allow-listed operations, becomes a no-op when complete, rejects definition drift, rejects an unapproved reserved object and treats a metadata gap explicitly.

## Exact manifests and recovery proof

Two immutable, version-bound manifests cover 83 ordered operations:

- `P3B-DDL-1`: 59 operations for 000110-000113, payload hash `cf8459db7744c9d9d64a7aa2c18b1c5fdede90d336358cdb9fc0b4a2ee9b2bfd`;
- `P3B-DDL-2`: 25 operations for 000114-000115, payload hash `cffa2f553a3eeb89116f2242b1aac1a1ffc57085de0b267bb9bb6f217f660d20`.

The manifests carry exact create/approved-add SQL and normalized expected definition hashes. Each operation passes through the HMAC-sealed physical-identity gate, is re-inspected before success, and is append-journalled. Migration-ledger rows are separate terminal operations after their migration objects. Existing exact objects are skipped; drift/conflict blocks without drop or down.

Actual recovery runs proved:

- 000110 missing-trigger injection: four matching tables, zero triggers and zero ledger entries were recovered by 52 allow-listed operations; the journal recorded 52 `started` and 52 `verified`, and an exact rerun planned zero operations;
- 000114 missing-trigger injection: six matching tables, zero lifecycle triggers and zero 000114 ledger entry were recovered by 18 allow-listed extension operations, including the 000115 column/table/triggers and both ledgers; exact rerun was complete with zero work;
- corrected complete 000114/115 state matched all 25 extension operations;
- the pre-correction overlength-index partial object was classified as drift and was not automatically altered or dropped.

The installation journal table and its two append-only triggers are bootstrapped first through the physical-identity gate, then all subsequent operations are journalled. Runtime `AUTO_INCREMENT` table options are stripped from approved create SQL so recovery does not invent sequence consumption.

## Hardened partial-state and restart evidence

The current executor/session signatures were exercised against three new marked disposable schemas. A synthetic interruption was injected immediately after MariaDB auto-committed the first, middle and last `P3B-DDL-2` operation respectively. Each restart used a newly observed physical proof, a new connection-instance binding, a newly sealed exact partial-state contract and the existing append-only journal.

| Injected post-DDL interruption | Exact objects present | Retry operations | Adopted unjournalled object | Exact rerun operations |
|---|---:|---:|---:|---:|
| First operation | 1 | 24 | 1 | 0 |
| Middle operation | 13 | 12 | 1 | 0 |
| Last operation | 25 | 0 | 1 | 0 |

For every case the final journal contained 25 `started`, 24 `verified`, one `failed_closed` and one `adopted` record. Every final schema contained 24 foundation tables, 53 triggers and both extension migration-ledger rows. The `adopted` state is used only when an object has the exact approved definition but MariaDB committed it before the corresponding verified journal append; drift is never adopted. A deliberately added `legacy_migration_unapproved_probe` table was refused by exact reserved-namespace inspection.

The connection-bound session was also re-presented after reconnect and rejected; unit coverage independently verifies the rejection is caused by the changed connection-instance binding rather than duplicate-object SQL.

Migration 000115 and `P3B-DDL-2` now install `lm_intent_state_transition_guard`. The database trigger implements the same exact 24-state edge graph as `MonotonicStateMachine` and requires both `lock_version` and `transition_attempt_count` to increase by exactly one on an edge. On actual MariaDB 10.4, raw `NOT_STARTED -> COMPLETED` and an invalid attempt-counter jump were rejected with SQLSTATE `45000`, while `NOT_STARTED -> EXTRACTED` with exact counters was accepted. Focused durable-adapter tests confirm the repository's CAS semantics remain compatible.

## Expanded MariaDB failure matrix

The expanded matrix used fresh `phase3b_matrix_*` schemas in the marked disposable datadir. Each migration-boundary case used the current identity/session/executor signatures, injected a failure immediately after the named operation committed, disconnected, re-observed the physical target through a new connection, issued a new exact partial-state contract, resumed and performed an exact zero-operation rerun.

| Boundary | Injected operation | Relative trigger boundary | Retry operations | Exact rerun | Result |
|---|---|---|---:|---:|---|
| 000110 | fourth table | immediately before first 000110 trigger | 52 | 0 | recovered |
| 000111 | first 000111 trigger | immediately after trigger creation | 34 | 0 | recovered |
| 000112 | first 000112 trigger | immediately after trigger creation | 11 | 0 | recovered |
| 000113 | migration-ledger row | after journal table and both append-only triggers | 0 | 0 | exact committed row adopted |
| 000114 | first table and middle trigger in separate runs | before and after 000114 trigger work | 24 / 12 | 0 / 0 | recovered |
| 000115 | final migration-ledger row | after column, table and all 000115 triggers | 0 | 0 | exact committed row adopted |

All four base-manifest cases recorded `failure_observed=true` and `old_connection_rejected=true`. Their protected operation-name hashes were respectively `d0428c7c98f13ebf173ec9c0ef3b415c5c64bda36504da8691cf8dedd70df6a7`, `b21b4422d57eaa19572042277b5912258e02af79e9fce87e655e96f119167dd1`, `ed02a0e5995d9fa2f60a4757c50214b41f0d8aff55bea545627b48d6aa2e7f94` and `fa0ba6010fe050ea32e5a4e5c58bf110f165d349fe16809076339ea8ca736982`. The execution bundle hashes are the immutable manifest hashes `cf8459db7744c9d9d64a7aa2c18b1c5fdede90d336358cdb9fc0b4a2ee9b2bfd` and `cffa2f553a3eeb89116f2242b1aac1a1ffc57085de0b267bb9bb6f217f660d20`. The complete extension partial-state result hash was `eefc172d2927687dcb0b03117cf16a7ee37702bad4b979d5b94e04bdb1c3a7c0`; final installed DDL hashes are recorded above.

Additional supported recovery/refusal evidence:

| Failure or missing object | Actual MariaDB result |
|---|---|
| Missing table | leaf table plus its two absent triggers restored in 3 allow-listed operations; rerun 0 |
| Missing column | approved column plus dependent state trigger restored in 2 operations; rerun 0 |
| Missing trigger | restored in 1 operation; rerun 0 |
| Missing migration-ledger row | restored in 1 metadata operation; rerun 0 |
| Installation-journal row deletion | rejected by append-only trigger with SQLSTATE `45000` |
| Missing index | enclosing table definition classified as drift; no repair SQL executed |
| Missing foreign-key constraint | enclosing table definition classified as drift; no repair SQL executed |
| Metadata lock timeout | failed closed, lock released, ledger repaired in 1 operation; rerun 0 |
| Connection killed between authority issuance and operation | physical re-observation failed closed, reconnect/re-authorize repaired ledger in 1 operation; rerun 0 |
| Retry of an exact statement | exact object observed and skipped; zero SQL operations |
| Malformed/destructive/out-of-namespace SQL | rejected by the immutable-manifest/create-only allow-list before database execution |

Two requested injections are intentionally not claimed as actual evidence. Indexes and constraints are part of one atomic `CREATE TABLE` statement in these manifests, so there is no executable boundary "between index and constraint" and a partial split cannot be produced without bypassing the manifest. A deterministic DDL deadlock was not manufactured: MariaDB metadata-lock timeout was reproduced instead, while inventing or probabilistically claiming a deadlock would not be repeatable evidence. Both remain explicit test limitations, not successful cases.

Shared-target activation still requires the approved physical identity/TLS contract and external key reference. Per-migration static schema fingerprints must not be caller-updated as DDL advances: the sealed pre-install identity starts one ordered manifest session, while exact per-operation postconditions authorize the evolving partial schema. Restart re-observes physical identity and resumes only manifest-absent operations. Any disabled gate, identity mismatch, manifest drift or journal drift stops before an unapproved SQL operation.

## Focused tests

- `PhysicalServerIdentityVerifierTest`
- `SafeFoundationDdlInstallerTest`
- `InstallationJournalSchemaTest`
- existing `FoundationSchemaTest`

Focused environment/installation and installation-schema result: **44 tests passed, 243 assertions**. Existing foundation schema suite: **16 tests passed, 166 assertions**. Focused durable recovery compatibility: **3 tests passed, 18 assertions**.
