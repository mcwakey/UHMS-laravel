# Phase 3B security and privacy review

Review date: 2026-07-22  
Reviewer role: independent security/privacy reviewer  
Review mode: read-only code inspection and isolated synthetic tests; no Classic database, renewed production database, importer, pilot, Cohort B selector or domain-data write was used  
Verdict: **PASS for the Phase 3B security/privacy implementation gate**  
Findings: **Critical 0, High 0, Medium 0**

## Executive decision

The Phase 3B repository implementation passes the independent security/privacy gate. The Phase 3 High finding for protected-store cryptographic and access integration and the three Phase 3 Medium findings for key rotation, privacy detector coverage and retention/access controls are closed to the extent authorized in Phase 3B.

This result supports only a Phase 4A synthetic foundation exercise if the other independent Phase 3B reviews also pass. It does not authorize protected data population, a domain importer, Cohort B selection, the patient pilot, Classic writes, production use or business-domain writes.

Real credentials, environment identity, key custody and retention approvals remain external activation prerequisites. The current defaults fail closed when those prerequisites are absent. They are not reclassified as repository defects and are listed separately below.

## Scope and controlling requirements

The review covered `AGENTS.md`, the Phase 3B directive, all four Phase 3 review reports, the Phase 2F privacy and machine-interface contracts, and current Phase 3/3B security, protected storage, capture, allocator composition, durable recovery, runtime isolation, privacy, environment-identity, configuration, migration, model, repository, service-provider and focused test changes. This report was refreshed after the production allocator composition, 127 operational invocation guards, subprocess recovery tests and DDL evidence changed.

The controlling security/privacy requirements were:

- Classic access is limited to the exact `legacy_uhms` connection and `uuhms` schema and remains read-only;
- renewed production and patient-pilot execution remain unavailable;
- protected identifiers use typed, domain-, environment-, key- and canonicalization-separated HMAC tokens;
- protected records require purpose-scoped authorization, keyed integrity, encrypted sensitive values, append-only lineage and audited access;
- retained keys are verification-only, revoked or expired keys fail closed, and rotation is authority-bound and append-only;
- remediation and provenance assertions must be bound to actual verified protected records;
- reports, exceptions, debug output and committed artifacts must not disclose identifiers, credentials or key material;
- retention and purge remain blocked until owner-approved policy and operational custody exist.

## Closure assessment

| Prior finding or review issue | Result | Independent evidence |
|---|---|---|
| SEC-P3-001: protected-store cryptographic/access controls not integrated | **Closed** | Protected repositories now require full encoded token sets, purpose-scoped operation context and keyed envelopes. Read, transition, revocation, quarantine release, snapshots, reconciliation, recovery, remediation, provenance and lifecycle operations verify the stored envelope and append access audit. Direct Eloquent attribute, raw-original, serialization and decryption entry points fail closed; query-builder access exposes ciphertext and keyed lookup material only. |
| SEC-P3-004: key rotation not operationally wired | **Closed for repository implementation** | The container composes external active and retained references, a pinned rotation authority and a fail-closed unavailable authority. Rotation verifies the old envelope and canonical messages, derives the replacement token set internally, appends a successor envelope and rotation lineage, and preserves the old generation. Revoked or expired retained keys are rejected. Real custody remains external. |
| SEC-P3-005: privacy detector format gaps | **Closed** | Scanner version `P3B-PRIVACY-SCANNER-3` includes structured scalar identifiers, current credential patterns, mandatory Phase 3 application/model/provider/test/document roots, deterministic scope accounting, redacted diagnostics and fail-closed missing-root handling. The final aggregate scan has zero unallowlisted findings. |
| SEC-P3-006: access/retention blocked rather than operational | **Closed for Phase 3B scope; external activation gate remains** | Protected access is purpose-, authority-, environment-, operation-, domain-, access-classification- and retention-classification-bound. Lifecycle metadata is sealed and audited. Purge has no executable path and remains denied pending owner policy, holds, custody and a separately reviewed executor. This is the required fail-closed Phase 3B state. |
| Direct protected-model read/decryption bypass found during Phase 3B review | **Corrected and closed** | `ProtectedFoundationModel` guards direct attribute retrieval, original/raw-original retrieval, attribute arrays, serialization and encrypted-string conversion. Adversarial integration tests obtain ciphertext independently and confirm that unauthorized low-level decryption and model access are denied. |
| Key material exposed by object debug output found during Phase 3B review | **Corrected and closed** | `HmacKeyMaterial` supplies redacted debug metadata. Unit tests confirm both debug dump and printable object output omit synthetic key bytes. |
| Caller-forgeable remediation/provenance integrity found during Phase 3B review | **Corrected and closed** | Public booleans and no-op interfaces no longer establish integrity. A privately constructed, purpose-specific `EnvelopeVerifiedIntegrityAuthority` binds claims to the actual sealed `Remediation` or `ProvenanceRecord` row and its run/source coordinates. Unrelated-envelope replay is rejected. |
| Unrestricted key-reference URI and caller-directed rotation inputs found during Phase 3B review | **Corrected and closed** | The manifest loader permits only approved external-reference URI schemes and rejects embedded material. Callers cannot supply replacement tokens, factories or verifiers; configured authority and approved reasons control rotation. |
| Unsealed lifecycle writes and incomplete protected lifecycle operations found during Phase 3B review | **Corrected and closed** | Key-reference, retention-policy and purge-request writes reject caller checksums, derive keyed integrity, seal the stored row and append access audit. Crosswalk revocation and quarantine secondary/release operations verify and reseal lineage. |
| Protected allocator-coordinate raw-read bypass found during final refresh | **Corrected and closed** | `AuthorityBoundReservationFactory` now resolves its idempotency/run/source-snapshot/target-snapshot/contract-bundle coordinate through `ProtectedRecoveryStore`. `NumberReservationRepository` independently verifies the idempotency parent envelope before sealing a reservation. The crosswalk allocation resolver uses a protected projection and expected hidden-token comparisons rather than trusting raw row values. Malformed-envelope and unsealed-row tests fail closed and record no false authorized read. |
| Operational-guard files omitted from mandatory privacy scope found during final refresh | **Corrected and closed** | `OperationalGuardScopeManifest` is the shared deterministic list for the application files changed by migration isolation. The scanner includes every manifest path in mandatory scope, and a test proves a configured scan cannot omit one. The refreshed aggregate scan covers these files and has zero unallowlisted findings. |
| Cross-connection protected-record and reservation escape found during final connection refresh | **Corrected and closed** | The security repository now rejects a record or transition result whose effective connection differs from its exact configured/default connection. Protected reservation verify and transition entry points independently assert repository/security connection equality. Recovery, allocation-store and crosswalk paths also compare their exact connection before protected lookup, sequence locking or mutation. Adversarial tests preserve row, envelope and audit counts on mismatch. |

## Controls independently confirmed

- **Protected token and envelope boundary:** bare digest-shaped input, wrong purpose, wrong authority, wrong domain/environment/key context, altered metadata, seal replay and caller-supplied checksums fail closed.
- **Read authorization:** successful and denied protected operations create redacted, trusted audit metadata where a valid authority token is available. A malformed token cannot cause sensitive diagnostic output; deployment database monitoring remains required for faults that occur before authenticated audit context exists.
- **Direct-access resistance:** sensitive model data cannot be obtained through ordinary attribute methods, original/raw-original methods, array serialization or direct encrypted-string conversion without an authorized protected projection.
- **Encryption and query behavior:** direct query-builder reads see ciphertext/HMAC lookup values; protected mutations are repository-bound; append-only triggers reject direct update/delete of protected lineage.
- **Allocator lineage and connection:** raw lookup queries may locate candidate IDs only. The idempotency parent, run, source/target snapshots, contract bundle, crosswalk and number reservation must then pass keyed envelope, coordinate and access-audit verification on the same exact protected/default target connection. Unsealed, malformed successor-envelope or cross-connection candidates cannot authorize allocation.
- **Remediation and provenance:** only actual envelope-verified records can authorize candidate admission or completeness claims; replaying a remediation authority as provenance is rejected.
- **Rotation:** container-composed active and retained keys support verified v1-to-v2 lineage; unapproved reasons, forged messages, revoked references and expired verification references create no valid successor.
- **Key handling:** secrets resolve from approved external references only when used. Configuration and manifests contain reference metadata, not key bytes. Weak, placeholder and low-diversity material fails closed. Debug output is redacted.
- **Retention:** legal/operational holds, absent owner policy and lineage destruction block purge. Phase 3B exposes no purge executor.
- **Target evidence:** unexpected installed enum-compatible values are counted without emitting the value or a stable unkeyed digest. Physical target identity errors are bounded and redacted.
- **Operational invocation boundaries:** the shared manifest covers 127 public operational entries. Pre-resolved and directly created services, payment/SMS callback paths, framework facades and container gateways are denied inside isolation; gates remain open after restoration and in normal non-migration runtime. Denials use bounded fault codes and do not echo operational inputs.
- **Recovery privacy/integrity:** all ten crash boundaries pass after abrupt subprocess termination and a separately booted recovery process. Recovery uses protected facts and sealed coordinates rather than caller booleans; process diagnostics are sanitized and fixtures remain synthetic.
- **Source boundary:** no reviewed command or service authorizes Classic DML/DDL or another Classic schema. The dedicated account and complete table-level privilege proof remain required before activation.
- **Scope:** no patient, staff, reference or insurance importer, pilot runner or Cohort B selector was introduced by the reviewed security/privacy work.

## Independent verification

Commands executed from the current shared worktree:

```text
php artisan test tests/Unit/LegacyMigration/Foundation/Security tests/Unit/LegacyMigration/Foundation/Allocation tests/Feature/LegacyMigration/Foundation/ApplicationBoundSideEffectIsolationTest.php --compact
```

Result: **94 passed, 418 assertions**.

```text
php artisan test tests/Unit/LegacyMigration/Foundation/Privacy tests/Feature/LegacyMigration/Foundation/ProtectedStoreRepositoryIntegrationTest.php --compact
```

Result: **28 passed, 183 assertions**.

```text
php artisan test tests/Feature/LegacyMigration/Foundation/DurableRecoveryJournalTest.php --compact
```

Result: **21 passed, 232 assertions, one expected worker-only skip**. The passing cases include all ten abrupt process-exit/fresh-boot scenarios and the protected allocation-crosswalk adversarial test.

Combined non-overlapping focused evidence before the final connection-only delta: **143 passed, 833 assertions, one expected skip**.

The final connection/privacy delta was then rerun against the current tree:

```text
php artisan test tests/Feature/LegacyMigration/Foundation/ProtectedStoreRepositoryIntegrationTest.php tests/Unit/LegacyMigration/Foundation/Allocation --compact
php artisan test tests/Feature/LegacyMigration/Foundation/DurableRecoveryJournalTest.php --filter=allocation_crosswalk_resolver_rejects_unsealed_and_raw_tampered_lineage_with_audit --compact
php artisan test tests/Unit/LegacyMigration/Foundation/Privacy --compact
```

Current delta result: **48 passed, 265 assertions**. This includes cross-connection seal/transition and reservation rejection, protected parent/crosswalk envelope verification, unchanged state/envelope/audit cardinalities on mismatch, and the mandatory privacy scope. It overlaps the broader evidence above and is therefore not added to the 143-test total.

Final privacy scan: `scanner_version=P3B-PRIVACY-SCANNER-3`, `file_count=778`, `coverage_difference=0`, `finding_count=66`, `unallowlisted_finding_count=0`, `release_blocked=false`, `scan_scope_manifest_hash=9fb2853f16769c431dbf3c10c4423ac8b112a4720f674d9a19d585993f2c2e4f`.

An independent pattern screen found no committed real private key, access token or nonempty migration/database credential. Its only matches were an empty synthetic scanner fixture and an unrelated documentation placeholder; neither is operational material.

No broad test suite, live Classic connection, renewed production connection, production-like patient data, real key, external secret provider, importer, patient pilot or domain-data write was used by this reviewer.

## External fail-closed prerequisites

The following remain required before protected population or any later non-synthetic execution. They are external/configuration approvals rather than unresolved security implementation findings:

1. A dedicated D-101 account restricted to `SELECT` and metadata access on exact schema `uuhms`, with the complete table-level privilege verification passing.
2. Independently approved non-production target physical identity, TLS identity and network allow/deny evidence, with production unreachable.
3. Real external key references, a named key custodian and rotation authority, approved reasons, retained-key verification period and provider permissions.
4. Owner-approved retention schedule, legal/operational hold rules, review dates and purge/tombstone policy.
5. Dedicated least-privilege foundation-store database credentials and operational database monitoring.
6. A separately reviewed deployment isolation authority and runtime audit availability before any executable migration scope.

Absence or mismatch of these prerequisites must continue to fail closed. None may be replaced with a repository default, local label or caller assertion.

## Final gate decision

| Gate | Decision |
|---|---|
| Critical security/privacy findings | **0** |
| High security/privacy findings | **0** |
| Medium security/privacy findings | **0** |
| Repository secrets or identifying data found | **No** |
| Security/privacy implementation gate | **PASS** |
| Phase 4A synthetic foundation exercise | **Eligible from security/privacy perspective only** |
| Protected-store population | **Blocked by external prerequisites** |
| Importers, Cohort B and patient pilot | **Blocked** |
| Production or Classic write authorization | **Not authorized** |

Final security/privacy result: **PASS — Critical 0, High 0, Medium 0.**
