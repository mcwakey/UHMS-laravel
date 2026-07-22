# Phase 3 security and privacy review

Review date: 2026-07-22  
Reviewer role: independent security/privacy reviewer  
Review mode: read-only inspection and isolated synthetic tests; no live Classic or target database was used  
Verdict: **FAIL — one High finding remains**

## Executive decision

Phase 3 does not pass the security/privacy exit gate. No Critical finding was identified, but one High finding blocks Phase 3 approval, Phase 4A synthetic exercise authorization, Cohort B work and every importer.

The latest tree has strong default-deny behavior: Phase 3 domain commits are blocked, direct allocator reservation is blocked, protected-store mutation and purge are blocked outside isolated SQLite tests, source inspection is read-only, sensitive Eloquent attributes are encrypted and hidden, and the artifact scanner currently completes with zero unallowlisted findings. The former foundation-DDL and low-entropy-key High findings were remediated and independently rechecked. Protected-store cryptographic/access integration remains incomplete.

## Scope and authoritative basis

The review covered the full Phase 3 directive, `AGENTS.md`, the authoritative decisions, Phase 2F handoff and privacy/crosswalk/provenance interfaces, all current Phase 3 application code, configuration, migrations, models, commands, focused tests, documentation and machine specifications.

The controlling requirements include:

- exact read-only Classic `legacy_uhms` / `uuhms`, with no production write path;
- domain-, environment-, key- and canonicalization-separated HMAC tokens;
- keyed record integrity and protected-store access boundaries;
- encryption and safe serialization;
- fail-closed retention/purge handling;
- artifact-wide scanning with complete coverage and redacted diagnostics;
- no repository secrets, raw patient/staff/member/contact identifiers or non-synthetic row data.

## Severity findings

### SEC-P3-001 — High — protected-store cryptographic and access controls are not integrated with persistence

`ProtectedStoreAccessGuard` correctly validates a full encoded token against environment, domain, key ID, key version and canonicalization version, and can create/verify a token-bound keyed integrity seal. It is only referenced by its unit test and is not called by any protected-store repository or model read path.

The actual repositories continue to accept bare 64-character lowercase hex strings through `Storage\ProtectedToken::assert()`. Most protected tables persist a caller-supplied domain, HMAC key version and 64-character checksum, but omit token environment and HMAC key ID. The repositories do not reconstruct or verify the full token envelope and do not recompute a keyed seal on create, resolve or transition. The existing storage integration test proves only fixed width, while schema fixtures use ordinary SHA-256 values for tokens and checksums.

Access enforcement is also incomplete:

- protected models can be queried directly and encrypted casts automatically decrypt payloads without consulting `ProtectedStoreAccessGuard`;
- every shipped query-builder mutation path now explicitly invokes the Phase 3 connection write guard, but this controls whether writes are allowed, not who may read/decrypt a protected record or whether its cryptographic context is authentic;
- database triggers protect deletion and selected immutable lineage fields, but they are integrity controls, not purpose-scoped read authorization or keyed record authentication.

Impact: a wrong-domain, wrong-key-context or plain-hash lookup value can enter the logical store in isolated/future authorized writes; caller-provided checksums can be mistaken for authenticated integrity; protected payload reads are not purpose-authorized; and future key rotation cannot be safely verified from the stored coordinates. This violates `PILOT-PRIV-004/005`, the Phase 2F keyed checksum contract and the Phase 3 protected-store/access exit criteria.

Required remediation:

1. Make the full protected token context and keyed integrity seal mandatory inputs to every protected repository operation.
2. Persist environment and key ID alongside domain, key version and canonicalization version for every protected token-bearing record, or persist an equivalent encrypted/verifiable envelope.
3. Verify token context and keyed integrity on every resolve/read/transition, not only in an isolated helper test.
4. Put authorization in repository/query boundaries that cannot be bypassed by mass updates or direct model reads.
5. Add adversarial integration tests for plain SHA-256 input, wrong domain/environment/key ID/version/canonicalization, altered metadata, seal replay, unauthorized read and mass-update bypass.

Evidence: `Security/ProtectedStoreAccessGuard.php`; `Storage/ProtectedToken.php`; all classes under `Foundation/Storage`; `ProtectedFoundationModel.php`; protected/recovery migrations; `ProtectedStoreAccessGuardTest.php`; `ProtectedTokenStorageIntegrationTest.php`.

### SEC-P3-002 — Closed — foundation schema-write authorization now proves the approved target coordinate

Every Phase 3 migration calls `FoundationDatabaseWriteBoundary::assertSchemaInstallAllowed()`. The remediated boundary now executes `GuardConfiguration` and `EnvironmentGuard`, rejects `uuhms`, `uhms`, `uuhmss` and production-pattern database names, and binds connection/database/driver/environment flags before allowing DDL.

The first migration opens a read-only metadata snapshot, measures the target version/table/column/fingerprint coordinate and calls `EnvironmentGuard::assertTarget()` before DDL. Later migrations require exact prior foundation tables and recheck the target database version. Every non-test `down()` path calls `assertSchemaRemovalAllowed()` and is denied.

Result: the earlier wrong-target/production-risk finding is closed by code inspection. No live database was used, so the approved non-production target must still be exercised separately with current pinned metadata before schema installation.

Evidence: `FoundationDatabaseWriteBoundary.php`; all three Phase 3 migrations; `EnvironmentGuard.php`; `config/legacy-migration.php`.

### SEC-P3-003 — Closed — obvious low-entropy HMAC keys are rejected

`HmacKeyMaterial` now rejects keys shorter than 32 bytes, placeholder-like material, fewer than 12 distinct byte values, or a dominant byte occupying more than half the key. The security tests use diverse synthetic material, and `KeyConfigurationTest` explicitly rejects a repeated-byte key.

Result: the earlier repeated/low-diversity key finding is closed. Operational key generation and custody must still use a cryptographically secure random source; the heuristic is a configuration backstop, not an entropy proof.

Evidence: `HmacKeyMaterial.php:17-18,49-52`; `HmacTokenServiceTest.php`; `CanonicalTypedMessageEncoderTest.php`; `ProtectedStoreAccessGuardTest.php`.

### SEC-P3-004 — Medium — configured key rotation is not operationally wired

The HMAC service supports previous keys when they are supplied programmatically, and `EnvironmentKeyProvider` supports indirection by environment-variable name. The application factory instead uses `ConfiguredKeyProvider` with active secret material already resolved into Laravel configuration. The committed configuration exposes no previous-key entries, and no store rotation repository or persisted rotation lineage uses `TokenRotationResult`.

Impact: an operator cannot execute a complete versioned rotation/verification lifecycle using the documented configuration alone, and configuration caching can retain the resolved secret with the application configuration cache.

Required remediation: use environment/secret-manager indirection at resolution time, configure active and retained verification-only key references, implement auditable protected-store rotation lineage, and test rotation across persisted records.

### SEC-P3-005 — Medium — privacy detector coverage still has narrow format gaps

The remediated scanner correctly makes required roots mandatory, scans Phase 3 code/migrations/configuration/unit and feature tests, detects unquoted secret assignments and common structured raw identifier values, redacts diagnostics, and blocks on missing scope. A synthetic adversarial probe confirmed those improvements.

However, a plain text scalar member identifier written as an unquoted label followed by a colon and number was not detected. The credential token rule also omits some common modern token formats, including GitHub fine-grained token prefixes. No such value was found in the current reviewed tree, so this is defense-in-depth rather than a confirmed disclosure.

Required remediation: extend structured/text detectors for colon-delimited scalar identifier reports and current credential formats, with redacted adversarial tests.

### SEC-P3-006 — Medium — access/retention remains deliberately blocked rather than operational

The frozen Phase 3 tree safely denies non-test model writes and all model deletes, and database no-delete triggers prevent silent purge. Configuration still labels protected and aggregate retention as pending an approved schedule, and there is no authorized purge workflow. This is safe for Phase 3 because normal protected-store population is blocked, but it is not an implemented operational retention lifecycle.

The retention schedule, legal/operational hold rules, purpose-scoped read authorization and audited non-cascading purge must be approved and implemented before any protected store receives real data.

## Controls that passed review

- **Repository content:** no secret, credential, private key or raw patient/staff/member/contact identifier was identified in the reviewed Phase 3 tree. The only value-bearing findings are the rigidly allow-listed `SYNTHETIC-ONLY-P2F` fixtures.
- **Scanner result:** `file_count=532`, `coverage_difference=0`, `finding_count=66`, `unallowlisted_finding_count=0`, `release_blocked=false`. A separate missing-root probe returned a coverage difference, an `artifact_missing` finding and `release_blocked=true`. Diagnostics contain no matched values.
- **Canonicalization/HMAC API:** typed length prefixing, explicit null/type markers, domain/environment separation, version checks, context mismatch rejection and in-memory old-key verification behave as tested.
- **Encryption/serialization:** sensitive payload columns use Laravel encrypted array casts; protected tokens, encrypted attributes and integrity fields are hidden from ordinary model serialization. Isolated schema tests confirmed ciphertext does not contain the synthetic plaintext.
- **Classic boundary:** Phase 3 source access uses the exact `legacy_uhms` / `uuhms` metadata wrapper, permits only `SELECT`/`SHOW GRANTS` inside a proven read-only snapshot, and rejects broad/write-capable grants. No Classic DML/DDL path was found in Phase 3 application services or commands.
- **Domain/runtime writes:** no importer, controller, route, scheduler or pilot command exists. `MigrationRuntimeRequest` rejects Phase 3 commit mode, the concrete side-effect driver cannot be built without all controls, the default allocator write guard blocks reservation/sequence writes, and dry-run tests report zero business writes.
- **Mutation boundary:** every shipped query-builder mutation path in compare-and-set, crosswalk revocation, number-reservation consumption and quarantine transition code invokes the Phase 3 connection write guard; no unguarded query-builder mutation path was found in the reviewed foundation tree.
- **Foundation DDL boundary:** every install migration proves the guarded target coordinate before DDL, later steps prove their exact prerequisites, and every non-test schema removal path is denied.
- **HMAC key material:** the configuration boundary rejects short, placeholder-like, repeated-byte and other obvious low-diversity keys; focused tests cover the repeated-byte case.
- **Retention safety:** purge is denied and foundation foreign keys use restricted deletion; no business-table cascade originates from foundation tables.
- **Safe failures:** reviewed command and security exceptions use bounded fault codes/redacted text and do not echo input values, credentials or HMAC material.

## Independent test evidence

Commands run from the frozen tree:

- `php artisan legacy-migration:privacy-scan --json` — exit 0 with 532 files, zero coverage difference and zero unallowlisted findings.
- Focused security/privacy/integration/schema/allocation/environment/persistence/runtime selection — **109 tests passed, 501 assertions**.
- Synthetic scanner adversarial probes — missing configured root blocked; an unquoted secret assignment and structured raw patient identifier were detected/redacted; the colon-delimited member scalar gap in SEC-P3-005 remained.
- Redacted independent repository pattern scan — no private-key, AWS access-key, GitHub token, bearer token or nonempty committed migration/database secret assignment hit. `gitleaks` was not installed.

No broad suite, production test, live database connection, source write attempt or target write was performed by this reviewer.

## Remaining blockers

Security/privacy approval requires closure and independent retest of SEC-P3-001. SEC-P3-004 through SEC-P3-006 must be resolved or explicitly carried as fail-closed prerequisites with owner-approved operational controls before protected data is introduced.

External prerequisites also remain: D-101 dedicated Classic credentials, a freshly approved non-production target coordinate, real high-entropy external key material and rotation custody, approved patient-state and insurance-initialization matrices, real application side-effect controls, and an approved retention schedule.

Final security/privacy result: **FAIL. Critical: 0. High: 1. Medium: 3.**
