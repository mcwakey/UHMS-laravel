# Phase 3 independent migration review

Review date: 2026-07-22  
Reviewer role: independent migration reviewer  
Review mode: read-only/static inspection and isolated synthetic tests; no live Classic, renewed production or business-domain write was used  
Verdict: **FAIL**  
Findings: **Critical 0, High 7, Medium 6**  
Phase 4A synthetic foundation exercise: **NOT AUTHORIZED**

## Executive decision

The package is safely inert, but Phase 3 is not complete and does not satisfy its exit gate. Current controls unconditionally reject Phase 3 domain commit, real patient-number reservation is blocked, patient and insurance target-state policies remain commit-blocking, and no domain importer, Cohort B selector, patient-pilot command, controller, route or scheduler was found.

Those containment controls do not prove the migration foundation safe for activation. Production target identity is not independently pinned; protected-store authorization and keyed integrity are not integrated; source/target snapshots are caller-hash constructors rather than capture workflows; durable recovery is absent; runtime isolation is not bound to real application effects; allocator concurrency/rollback is unproved; and complete dry-run reporting declares rather than measures zero-write facts.

The three specialist reviews all fail with High findings:

- security/privacy: Critical 0, High 1, Medium 3;
- database integrity: Critical 0, High 2, Medium 4;
- runtime isolation: Critical 0, High 3.

Overlapping specialist findings are counted once in this independent total. Safety fixes made after the specialist reports closed checkpoint-lineage, contradictory-evidence, ordinal, compensation-action, arbitrary report-string, reconciliation-command and legacy-command diagnostic findings; this review rechecked those closures. The specialist reports themselves have not yet been rerun. This review also identified additional production-identity and snapshot/report trust findings.

## Post-correction verification — 2026-07-22

This verification was performed against the latest tree after the correction batch. It supersedes any stale sentence below that describes a corrected path as still open; the numbered findings and final counts have been revised to the current evidence.

Confirmed closures:

- checkpoint intent/idempotency/run lineage is now composite-bound and an adversarial cross-lineage test passes;
- contradictory recovery evidence rejects before classification, and terminal repair requires a durable committed unit;
- compensation actions are checked against the unit-specific registry;
- sequence consumption validates positive, unique, in-range ordinals;
- aggregate report values accept numeric/boolean/null only and reject identifier-like strings under neutral keys;
- `foundation-reconcile` always fails closed until an authoritative bundle evaluator exists;
- Classic/target inspection commands now suppress raw exception diagnostics;
- raw unexpected target enum values are no longer persisted; allow-listed values remain explicit and unexpected values are represented by a count plus digest;
- the capability and Phase 2F traceability manifests now explicitly classify incomplete capabilities and their blockers rather than marking them implemented.
- the Phase 2F privacy scan-scope evidence was refreshed to 329 files with manifest hash `a74c8c7fdd8f5d0ebc16ac4f291c5eea5c849b47bb2fc51422ac08d3b72a7231`; the full Evidence suite is green again.

These corrections do not close production server identity, protected-store integration, authoritative snapshot capture, persistent recovery, real side-effect isolation, concrete allocator concurrency/rollback, or authoritative dry-run measurement. The target enum digest is unkeyed SHA-256, and the Classic evidence account check omits table-level grant inspection; both remain Medium findings. Final verdict remains **FAIL**, with **Critical 0, High 7, Medium 6**, and Phase 4A remains unauthorized.

## High findings

### IMR-P3-001 — High — production target writes are not technically impossible

The DDL boundary rejects `APP_ENV=production`, production-like database names, Classic names and mismatched configured coordinates. It does not independently identify the database server. Target host, port, TLS/server identity and an owner-approved immutable target identity are absent from `config/legacy-migration.php:26-33` and `GuardConfiguration.php:33-39`. The target connection allow-list defaults to the configured target itself (`GuardConfiguration.php:52-54`). `FoundationDatabaseWriteBoundary.php:27-45` therefore trusts mutable environment labels and target coordinates supplied by the same operator enabling schema writes.

A static probe showed that `EnvironmentGuard` accepts a `local` target called `hospital_live` when the caller supplies matching connection/fingerprint/count coordinates. No database was contacted. A production server presented through non-production labels and a structurally matching schema could therefore pass the application checks and receive foundation DDL.

The current defaults (`enabled=false`, `schema_writes_enabled=false`) contain immediate risk, but `PHASE_3_EXIT_REPORT.md` criterion 3 overclaims technical impossibility. Before any foundation DDL, pin an independently approved non-production server identity and use credentials/network policy incapable of reaching renewed production.

### IMR-P3-002 — High — protected-store trust and access controls are not integrated

`ProtectedStoreAccessGuard.php:16-84` correctly checks token environment, domain, key ID/version, canonicalization version and a keyed integrity seal. No application repository invokes it. Repositories instead accept bare 64-character strings through `Storage/ProtectedToken.php:7-18`; for example, `CrosswalkRepository.php:12-31` and `ProtectedEvidenceRepository.php:11-25` accept caller-provided tokens without reconstructing or verifying their HMAC envelope or integrity seal.

Protected models can be read and encrypted attributes decrypted without a purpose-scoped read guard (`ProtectedFoundationModel.php:85-112`). Stored coordinates omit enough context to authenticate environment/key identity consistently. This affects run, crosswalk, remediation, provenance, quarantine, reconciliation, audit, idempotency and recovery stores, not only the crosswalk.

This confirms `SEC-P3-001`. Current non-test writes and deletes are hard-blocked, but the stores are not safe to populate or read with protected data.

### IMR-P3-003 — High — snapshot/run authority is caller-asserted rather than captured and authenticated

`CoordinatedSourceSnapshotManager.php:20-44` and `TargetCollisionSnapshotManager.php:27-79` accept caller-supplied query and result/set hashes. Neither performs or coordinates the read-only capture it claims to represent, and neither is used by foundation preflight. `RunManifestService.php:9-51` composes those objects and accepts any nonempty version map. Persistence/runtime prerequisites are also caller-provided booleans (`MigrationRuntimeRequest.php:17-22`, `PersistenceBoundaryContext.php`).

Consequently, `PHASE3-FOUND-003`, `004` and `005` do not yet prove that source, target-collision and cohort/run evidence originated from the guarded database coordinates. Before Phase 4A, capture adapters must derive hashes from the verified read-only sessions, persist them through the protected authority boundary, and resolve all later prerequisites from authenticated records rather than caller assertions.

### IMR-P3-004 — High — durable recovery and crash/restart proof are absent

No application class implements `AtomicRecoveryJournal` or `CompareAndSetStateStore`; only in-memory test doubles do (`RecoveryFoundationTest.php:146-191`). `RecoveryRepository` does not atomically bind intent resolution, state CAS, decision evidence, checkpoint repair and reconciliation. The recovery command classifies caller-supplied booleans and deliberately writes nothing (`FoundationRecoveryAuditCommand.php:57-98`). The ten tests prove a decision table, not crash/restart recovery.

The current tree now rejects contradictory evidence in `RecoveryEvidence.php:18-23`, requires a durable committed unit before terminal repair (`CrashBoundaryClassifier.php:84-90`) and composite-binds checkpoint intent/idempotency/run coordinates in the recovery migration. Those corrections close the newly identified classifier/schema defects, but they do not implement persistence or crash/restart recovery.

This confirms the remaining core of `DBI-004`/`RTI-002`: duplicate-free resume, durable checkpoint-only repair and terminal completion are not implemented end to end.

### IMR-P3-005 — High — runtime side-effect isolation is not bound to the application

`SubsystemIsolationControl.php` remains an interface. There is no real implementation, provider binding or command that constructs `MigrationRuntimeFactory` with application controls. No operational service, observer, event, queue, scheduler, notification or integration initiation point invokes `SideEffectGuard`.

The application continues to register real events/observers/audit forwarding (`AppServiceProvider.php:127-150`) and schedules (`bootstrap/app.php:35-54`). Runtime tests use boolean test controls, not those operational paths. Migration audit remaining enabled while activity/audit forwarding is isolated is also not demonstrated.

This confirms `RTI-001`. The unconditional Phase 3 commit rejection contains present risk, but complete side-effect isolation and scoped restoration do not exist.

### IMR-P3-006 — High — allocator concurrency and concrete rollback remain unproved

`LaravelNumberReservationStore.php:38-119` has a plausible transaction, row-lock and sequence-CAS design. The concrete tests are sequential SQLite; concurrency and post-persist rollback are simulated in memory. No two-worker MariaDB 10.4 contention test, concrete transaction fault injection or exact ordinal reconciliation follows. Required application implementations for some allocator evidence/attribute inputs are also absent.

This confirms `DBI-005`/`RTI-003`. The default `Phase3AllocationWriteGuard` prevents current real reservations, so no duplicate is created today, but concurrent uniqueness, rollback and no-replacement behavior are not activation evidence.

### IMR-P3-007 — High — complete authoritative dry-run measurement and reporting are absent

The current `AggregateDryRunReportBuilder.php:44-61` correctly permits only numeric, boolean or null aggregate values, and its adversarial neutral-key string test passes. `foundation-reconcile` now always fails closed until an authoritative contract-bundle evaluator exists (`FoundationReconcileCommand.php:16-21`). These changes close the arbitrary-string and operator-selected-subset paths.

The builder still hard-codes source/business write counts to zero (`AggregateDryRunReportBuilder.php:25-33`) instead of measuring them and does not require the complete Phase 2F input/output contract. There is no complete dry-run evaluator.

`PHASE3-FOUND-025` therefore remains blocked. Implement a closed, versioned schema of stable rule IDs and measured counters, and retain the command-level fail-closed block until all `PILOT-DRY-003` through `008` inputs and outputs are derived from authenticated evidence.

## Medium findings

### IMR-P3-008 — Medium — protected evidence and migration-audit semantics are mostly structural

`ProtectedEvidenceRepository.php:11-25` does not enforce remediation approval, expiry, revocation, conflict, complete provenance disposition, exact-duplicate separation or preservation of every insurance source row. `MigrationAuditRepository` validates only token shape and is unused. Compensation persistence now validates actions through the unit-specific `CompensationPlanRegistry`, closing that portion of the earlier finding.

The tables provide useful immutability/cardinality primitives, but remediation, provenance and migration-audit semantic admission remain unimplemented. The current capability manifest now correctly records those blockers. Compensation action admission is unit-registry-bound, while actual compensation execution remains correctly blocked.

### IMR-P3-009 — Medium — key rotation, access and retention lifecycles are not operational

In-memory HMAC rotation primitives pass, but application configuration does not expose retained verification-only keys, persisted rotation lineage is absent, and resolved key material may enter Laravel's configuration cache. Purpose-scoped protected reads and an approved non-cascading purge workflow are absent. Retention remains explicitly pending. This carries `SEC-P3-004` and `SEC-P3-006` forward as prerequisites before protected data exists.

### IMR-P3-010 — Medium — target-state policies are fail-closed but not bound to authoritative files

Patient and insurance validators correctly retain unconditional commit blockers and required exception codes. However, `TargetStatePolicy.php:19-84` duplicates the authoritative Phase 2F rules in PHP and fingerprints its own arrays (`:92-108`); it does not load or compare the authoritative JSON fingerprint. The authority strings also omit the actual `specifications/` path. A future specification change would not invalidate the validator automatically.

Keep the current blockers. Before any approval update, compile or verify the runtime policy directly against the authoritative machine specification and add cross-field coherence tests.

### IMR-P3-011 — Medium — privacy detector coverage has known format gaps

The current scanner reports complete coverage and zero unallowlisted findings, but the specialist review proved gaps for colon-delimited scalar member identifiers and modern credential formats. The aggregate report builder now rejects arbitrary scalar strings. Target enum inspection now replaces an unexpected raw value with `hash('sha256', "target-enum-unexpected\0".$value)` (`TargetSchemaInspectionService.php:549-567`), but this remains an unkeyed, stable digest of a potentially low-entropy anomalous value. It is not domain/environment/key-version-separated HMAC and may be brute-forceable or cross-artifact linkable if a categorical column contains misplaced protected data. Use a protected HMAC token or report only an unexpected-value count. Add adversarial tests for this path and the known detector formats.

### IMR-P3-012 — Medium — foundation DDL is not recoverable after partial MariaDB failure

Each migration performs multiple auto-committing MariaDB DDL statements after one preflight. Non-test `down()` is always denied (`FoundationDatabaseWriteBoundary.php:87-93`). A mid-migration failure can leave unrecorded partial tables/triggers that make rerun collide, with no approved repair procedure. MariaDB 10.4 installation, trigger permissions, exact DDL and partial-failure recovery were not executed.

### IMR-P3-013 — Medium — Classic evidence account enforcement omits table-level grants and lacks focused tests

`ClassicEvidenceCaptureService.php:92-108,370-397` now checks `information_schema.USER_PRIVILEGES` and `SCHEMA_PRIVILEGES`, rejects non-`USAGE` global privileges, restricts schema privileges to `SELECT`/`SHOW VIEW`, requires `SELECT`, and rejects another schema. It does not inspect `information_schema.TABLE_PRIVILEGES` or an equivalent complete `SHOW GRANTS` result. An account with a table-scoped `INSERT`, `UPDATE`, `DELETE` or other DML grant can therefore pass this evidence-command check because that grant appears in neither inspected collection.

The command's read-only transaction still prevents writes during evidence capture, and the Phase 3 `SourceAccountVerifier` independently uses `SHOW GRANTS` and rejects the privilege. Nevertheless, broad-account rejection is not yet consistent across the current `legacy-migration:*` Classic command surface. Add complete table/routine/role grant inspection or reuse `SourceAccountVerifier`, with focused tests for table-scoped DML, cross-schema table grants and redacted failures. The new target-enum redaction path likewise has no focused test; `TargetSchemaInspectionServiceTest` currently covers only output-path containment.

## All 25 capability trace

`Supported` means the Phase 3-scoped interface/primitive requirement is met; it is not importer or commit authority. `Partial` means useful implementation exists but a required proof or integration is absent. `Blocked` means the capability is not safely usable.

| ID | Capability | Phase 2F interface trace | Independent result |
|---|---|---|---|
| 001 | Environment/schema guards | `PILOT-XW-001`, `PILOT-DRY-001` | **Partial** — exact source and declared-production guards pass; target server identity is not pinned. |
| 002 | Least-privilege source verification | `PILOT-DRY-001` | **Partial/external** — foundation verifier rejects broad grants and exact D-101 credentials remain external; evidence capture omits table-level grant inspection. |
| 003 | Coordinated source snapshot | `PILOT-PROV-001`, `PILOT-RECON-004` | **Partial** — required hash sets exist, but no guarded capture/persistence workflow. |
| 004 | Target-collision snapshot | `PILOT-DRY-001`, `PILOT-RECON-004` | **Partial** — required sets/drift comparison exist, but no authoritative read-only capture/precommit integration. |
| 005 | Run manifest | `PILOT-XW-001`, `PILOT-DRY-002` | **Partial** — deterministic composition exists; input authority, complete version registry and protected persistence are incomplete. |
| 006 | Protected crosswalk | `PILOT-XW-002/003/004` | **Blocked** — cardinality works; access/keyed integrity is not integrated. |
| 007 | Protected remediation | `PILOT-DRY-002`, `PILOT-REMED-001/013/015` | **Partial** — table/encryption exist; semantic admission and access authority do not. |
| 008 | Provenance/history | `PILOT-PROV-001/004` | **Partial** — append-only structure exists; completeness/tamper verification is not implemented. |
| 009 | Quarantine/exception ledger | `PILOT-QUAR-001/006` | **Partial** — root/primary constraints work; protected authority and operational topological release remain blocked. |
| 010 | Reconciliation storage | `PILOT-RECON-001/002/003` | **Partial** — nonzero cannot pass; authoritative complete measurement set and protected integrity are not established. |
| 011 | HMAC/key management | `PILOT-XW-001`, `PILOT-PRIV-004/005` | **Partial** — cryptographic primitives pass; store integration and operational rotation do not. |
| 012 | Separate migration audit | `PILOT-PROV-005` | **Partial** — separate append-only table exists; real audit/runtime separation and authority are absent. |
| 013 | Patient-number allocator | `PILOT-XW-002/005`, `PILOT-ATOM-001`, `PILOT-IDEM-003` | **Blocked** — crosswalk-first algorithms and ordinal validation exist; concrete concurrency and rollback proof is absent. |
| 014 | Patient persistence boundary | `PILOT-XW-002`, `PILOT-RECON-007`, `PILOT-ATOM-001`, `PILOT-IDEM-001`, `PILOT-STATE-020` | **Supported as Phase 3 interface only** — no Classic transformer/domain adapter; commit remains blocked as required. |
| 015 | Alias persistence boundary | `PILOT-RECON-007`, `PILOT-ATOM-003`, `PILOT-IDEM-004` | **Supported as Phase 3 interface only** — no importer/adapter; dry-run cannot invoke commit. |
| 016 | Contact persistence boundary | `PILOT-RECON-007`, `PILOT-ATOM-004`, `PILOT-IDEM-005` | **Supported as Phase 3 interface only** — no importer/adapter; existing-target mutation remains blocked. |
| 017 | Insurance history/membership boundaries | `PILOT-PROV-004`, `PILOT-RECON-006`, `PILOT-ATOM-005/006`, `PILOT-IDEM-007/008`, `PILOT-INS-INIT-015` | **Supported as Phase 3 interfaces only** — no importer/adapter; initialization remains commit-blocking. |
| 018 | Side-effect isolation | `PILOT-DRY-008`, `PILOT-ACCEPT-002` | **Blocked** — complete registry/test doubles exist; real controls and call-site binding do not. |
| 019 | Idempotency | `PILOT-XW-005`, `PILOT-RECON-007`, `PILOT-IDEM-*` | **Partial** — record uniqueness/resolution primitives exist; protected trust and durable end-to-end execution do not. |
| 020 | Checkpoints | `PILOT-RECON-007`, `PILOT-IDEM-011`, `PILOT-RESUME-004` | **Blocked** — exact lineage constraint now passes; no durable journal/checkpoint-repair execution. |
| 021 | Resume | `PILOT-RECON-007`, `PILOT-RESUME-004` through `010` | **Blocked** — contradictory evidence now rejects; classification only, with no restart execution. |
| 022 | Rollback/compensation | `PILOT-RECON-007`, `PILOT-RESUME-006` through `010` | **Partial/blocked** — persistence is now unit-registry-bound; execution remains absent. |
| 023 | Target-state validators | `PILOT-DRY-002`, `PILOT-ACCEPT-004`, `PILOT-STATE-020`, `PILOT-INS-INIT-015` | **Supported fail-closed** — patient and insurance commit blockers remain enforced; authoritative-file binding is still needed. |
| 024 | Privacy scanning | `PILOT-PRIV-007/008` | **Partial** — current scan and refreshed Phase 2F manifest evidence pass; known detector gaps and unkeyed unexpected-enum digests remain. |
| 025 | Privacy-safe dry-run reporting | `PILOT-DRY-003/005` | **Blocked** — aggregate scalar type enforcement passes, but the complete evaluator is absent and zeroes are declared rather than measured. |

Summary: **5 supported within the intentionally narrow Phase 3 interface scope, 14 partial, 6 blocked.** All 25 IDs and their Phase 2F interface arrays are present, but semantic traceability and minimum proofs are not complete.

## Confirmed controls and scope boundaries

- The foundation verifier uses exact connection `legacy_uhms`, database `uuhms`, a read-only snapshot and an allow-list of `USAGE`, `SELECT` and `SHOW VIEW`; broad/write/admin grants fail closed (`SourceAccountVerifier.php:12-69`). No Phase 3 Classic DML/DDL API was found.
- The older Classic evidence command now rejects non-`USAGE` global privilege, non-`SELECT`/`SHOW VIEW` schema privilege, missing `SELECT` and another-schema privilege, and both inspection commands redact errors. Table-level grant inspection remains missing as recorded in IMR-P3-013.
- Configuration defaults disable the foundation, commit, Cohort B selection, importer execution, production execution and schema writes (`config/legacy-migration.php:8-63`).
- `MigrationRuntimeRequest.php:46-50` unconditionally rejects Phase 3 commit even when callers claim approval.
- Patient-state and insurance-initialization policies retain `LEGACY-PATIENT-STATUS-*` and `LEGACY-INSURANCE-*` commit blockers (`TargetStatePolicy.php:19-84`). Omitted fields cannot fall through to target defaults in the validator.
- No patient, staff, reference, contact, insurance, visit or clinical importer; no Classic-to-command transformer; no Cohort B selector; and no patient-pilot command, route, controller or scheduler was found.
- The six persistence surfaces are interfaces/guarded abstract adapters only, as Phase 3 requires. They do not call `PatientService::create()` or operational billing, payment, stock, pharmacy or bed services.
- Existing-target linking is metadata-only and zero-mutation evidence is checked. Current-user attribution was not found in foundation code; allocator actor tokens are fixed null.
- SQLite schema tests show foundation-only foreign keys with restricted deletion, active-crosswalk cardinality, authoritative quarantine roots, encrypted/hidden payloads and database rejection of nonzero reconciliation pass.
- No live database, source write, renewed production test, business-domain write, external integration or `DatabaseSeeder` execution occurred during this review.

## Verification evidence

- `php artisan test tests/Unit/LegacyMigration/Foundation tests/Feature/LegacyMigration/Foundation --compact` — **164 passed, 644 assertions**.
- `php artisan legacy-migration:privacy-scan --json` — exit 0 after adding this review; **534 files**, coverage difference 0, 66 allow-listed findings, 0 unallowlisted findings.
- Phase 3 machine specifications — all nine JSON files parsed; 25 requirement IDs exactly matched 25 capability IDs.
- Post-correction rerun: `php artisan test tests/Unit/LegacyMigration/Evidence --compact` — **57 passed, 20,213 assertions**. The refreshed Phase 2F privacy contract records 329 files and scan-scope manifest hash `a74c8c7fdd8f5d0ebc16ac4f291c5eea5c849b47bb2fc51422ac08d3b72a7231`.
- Static probes only: production-label acceptance and the now-remediated aggregate-string path were exercised without a database connection or filesystem mutation.
- The broad application suite was not run because the specialist/workstream gates failed. Criterion 26 is therefore not satisfied.

Passing focused tests prove current inertness, algorithms and SQLite constraints. They do not close the High findings or establish production readiness.

## Authorization

- Phase 3: **FAIL**.
- Phase 4A synthetic foundation exercise: **NOT AUTHORIZED**.
- Cohort B selection or dry-run: **BLOCKED**.
- Patient pilot: **BLOCKED**.
- Every domain importer and patient/business-domain write: **BLOCKED**.
- Foundation-store population with protected data: **BLOCKED**.
- Foundation DDL on any shared/real target: **BLOCKED pending approved server identity, disposable MariaDB proof and a partial-install recovery procedure**.

Re-review requires closure of all High findings, preservation of the corrected fail-closed machine statuses, a green focused/upstream suite, approved disposable MariaDB 10.4 DDL/concurrency/crash evidence, and new independent security, database, runtime and migration reviews with no Critical or High finding. D-101 credentials, owner-approved patient/insurance tuples, external key custody/rotation, retention policy and a fresh protected source/target capture remain external prerequisites; closing them still would not authorize an importer or Cohort B automatically.
