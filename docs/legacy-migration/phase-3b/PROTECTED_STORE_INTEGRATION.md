# Phase 3B protected-store integration

Status: implemented foundation repository boundary; protected population remains blocked pending approved environment configuration and independent review.  
Scope: foundation records only. No importer, patient pilot, Cohort B selection or domain write is authorized.

## Confirmed implementation

`ProtectedRecordSecurityRepository` is the repository-only security boundary for envelope-bound records. A protected operation requires:

- a `ProtectedStoreOperationContext` naming the purpose, allowed operation/domain, environment, run and snapshot coordinates, classifications, authority reference and return projection;
- a configured `ProtectedStoreAccessAuthority` policy that independently permits that exact purpose, operation, authority reference and access/retention classification; caller-declared context strings cannot grant authority;
- a full `lmt1` protected-token envelope, not a bare 64-character value;
- exact environment, domain, key ID, key version and canonicalization agreement;
- a keyed integrity seal binding record type and ID, every full token envelope, a canonical fingerprint of the complete persisted record, run/source/target coordinates, access classification and retention classification;
- an append-only protected access audit with a keyed event checksum.

The secure methods added to `RunManifestRepository`, `SnapshotRepository`, `CrosswalkRepository`, `ProtectedEvidenceRepository`, `QuarantineRepository`, `ReconciliationRepository`, `MigrationAuditRepository`, `IdempotencyRepository`, `RecoveryRepository`, `NumberReservationRepository`, `ProtectedLifecycleRepository` and `CompareAndSet` derive persisted lookup digests only after parsing the full envelope. They atomically seal the resulting foundation record and expose purpose-scoped read projections. Every non-null `*_token` attribute must have a corresponding full envelope entry; an incomplete token set fails before sealing. Idempotency and reservation reuse verifies the latest keyed envelope plus all immutable lineage coordinates before accepting an existing row. State transitions use CAS and append a successor envelope bound to the changed complete-record fingerprint. Crosswalk revocation removes the active-coordinate token from the successor set and adds the verified actor token. Quarantine secondary exceptions first verify their root envelope and lineage; release verifies every parent envelope and topological/disposition rule before mutation, then adds the approval token and successor seal.

Envelope-bound Eloquent reads require a verified repository session. Direct Eloquent retrieval fails with `LM-SEC-STORE-DIRECT-MODEL-READ-001`; ordinary and low-level attribute access (`getAttributeValue`, `getOriginal`, `getRawOriginal`, `getAttributes`, `attributesToArray`) fails outside the verified session; direct encrypted-cast decryption fails; secured model serialization fails; protected fields remain hidden; and raw query-builder access sees ciphertext and HMAC lookup digests. A compare-and-set transition must append a new envelope generation bound to the changed complete-record fingerprint. Append-only triggers reject mass update and deletion of lifecycle records. Raw database access is still governed by database credentials and cannot be made purpose-aware by Eloquent; dedicated least-privilege foundation credentials remain an operational prerequisite.

Denied reads, transitions and rotations append a keyed, redacted audit using only trusted envelope metadata; caller authority and purpose strings are not persisted. If the stored primary token itself is malformed, a keyed denial event cannot safely be derived. That exceptional condition remains fail-closed and the original denial is never masked; durable database/tamper monitoring must report the failed audit append without recording untrusted values.

Legacy repository methods that return mutable models or create rows without envelopes are permanently restricted to isolated SQLite tests. Non-test calls fail with `LM-SEC-STORE-LEGACY-UNSEALED-METHOD-001`; protected methods use private validated creation paths and cannot be redirected through those compatibility methods. Contract-bundle reuse is available only through an exact version/hash lookup followed by envelope and lineage verification; an existing version with another hash fails closed.

## Protected metadata schema

Migration `2026_07_22_000114_create_legacy_migration_protected_lifecycle_tables.php` adds only foundation tables:

| Table | Purpose |
|---|---|
| `legacy_migration_protected_key_references` | External secret references and key lifecycle metadata; never key material. |
| `legacy_migration_protected_record_envelopes` | Versioned full token/seal context for any protected foundation model. |
| `legacy_migration_protected_token_rotations` | Append-only verified old/new envelope lineage. |
| `legacy_migration_retention_policies` | Owner policy, holds, review and purge eligibility metadata. |
| `legacy_migration_protected_purge_requests` | Audited requests only; no deletion API. |
| `legacy_migration_protected_access_audits` | Purpose-scoped aggregate access audit. |

All foreign keys point only to `legacy_migration_*` tables and use restricted deletion. Lifecycle tables are append-only. The migration uses the shared foundation DDL guard and inherits the independently pinned target identity gate when that shared boundary is active.

## Remediation and provenance admission

`RemediationAdmissionService` rejects records that are unapproved, revoked, expired, superseded, conflicting, cryptographically unverified, for another source token/field rule, or for another run/snapshot coordinate. A caller boolean or interface implementation is not integrity evidence. `EnvelopeVerifiedIntegrityAuthority` has a private constructor and purpose-specific factories restricted to actual `Remediation` or `ProvenanceRecord` rows. Admission re-verifies that row's keyed envelope and exact immutable claim hash, source token, rule/disposition, evidence, run and source-snapshot coordinates. A remediation authority cannot be replayed as provenance authority, and an unrelated valid envelope cannot bless claims. Eligible records use an explicit precedence ordinal, approval time and stable reference. Equal-precedence different-value candidates fail as a conflict.

`ProvenanceCompletenessValidator` enforces exactly one primary disposition for every consumed patient field, ordered secondary exceptions, source contract/query/result evidence, transformation version, root/subchain and run/snapshot integrity. It separately proves one distinct protected-history outcome for every consumed insurance source row. Identical insurance values do not collapse distinct source-row identities.

These are admission validators, not importers. They use synthetic references in tests and do not populate real protected stores.

## Bypass analysis

| Path | Result |
|---|---|
| Bare SHA-256 token | Rejected before record admission. |
| Wrong environment/domain/key/version/canonicalization | Rejected by the pinned access guard. |
| Altered record metadata or record integrity reference | Keyed seal verification fails. |
| Seal replay to another record/run/domain | Keyed seal or coordinate verification fails. |
| Unauthorized purpose/read | Operation/domain/session check fails and a trusted redacted denial audit is attempted. |
| Direct Eloquent read | Fails for an envelope-bound record. |
| Low-level model/cast access | Raw/original access and direct cast decryption fail outside a verified session. |
| Model serialization/queue rehydration | Serialization fails; rehydration must pass the repository read boundary. |
| Caller-supplied rotation token/factory/verifier | No public rotation API accepts them; pinned rotation authority verifies canonical messages and derives the active token/factory/verifier. |
| Caller-supplied lifecycle checksum | Rejected; key-reference, retention and purge-request checksums are purpose-keyed by the repository and the records are sealed/audited. |
| Debug/print output of HMAC material | `HmacKeyMaterial::__debugInfo()` exposes only key reference/version and a fixed redaction marker. |
| Query-builder mutation | Append-only/no-delete database triggers reject lifecycle mutation; existing protected table mutation guards remain in force. |
| Query-builder read | Only ciphertext/HMAC references are available; raw DB access still requires separately restricted credentials. |

## Evidence and limits

Focused synthetic tests cover keyed envelope verification, altered metadata, cross-run replay, direct-model and low-level accessor/decryption denial, projection-only reads, ciphertext storage, trusted redacted denial auditing, mass-update/delete denial, bare SHA rejection, authority-pinned append-only rotation (including forged-message/reason rejection), lifecycle checksum rejection and sealing, crosswalk revocation, quarantine secondary/release, remediation conflict/revocation/expiry and provenance completeness. Repository integration tests additionally cover protected run, source/target snapshot, reconciliation, idempotency, atomic intent, checkpoint, compensation, number reservation and CAS transition paths. Every tested mutable transition produces a successor envelope generation.

The following are not claimed complete by this document:

- MariaDB 10.4 DDL execution and trigger proof belong to the disposable DDL workstream.
- No owner-approved retention schedule or operational key references are currently supplied.
- No real protected data was populated and no purge was executed.
- Existing legacy-shaped repository methods remain usable only in the isolated SQLite foundation tests because the model write guard blocks non-test population.
- The durable recovery-journal runtime still requires its approved production attribute factory and container binding; repository envelope integration does not itself authorize that runtime.

## Required configuration keys

The root configuration now declares the fail-closed shape below. Deployment must supply and independently verify the external references before population is enabled:

- `legacy-migration.protected_store.population_enabled=false` by default
- `legacy-migration.protected_store.full_envelope_required=true`
- `legacy-migration.protected_store.keyed_integrity_required=true`
- `legacy-migration.protected_store.purpose_scoped_access_required=true`
- `legacy-migration.protected_store.direct_model_access_allowed=false`
- `legacy-migration.protected_store.access_policy_reference`
- `legacy-migration.key_provider.provider=external_reference`
- `legacy-migration.key_provider.references[]` containing metadata and secret references only; exactly one eligible reference may be signing-active
- alternatively, `legacy-migration.key_provider.reference_manifest` plus its pinned `reference_manifest_hash`; the loader rejects embedded key material and resolves only external secret references at use time
- `legacy-migration.retention.policy_reference`
- `legacy-migration.retention.schedule_resolved=false` by default
- `legacy-migration.retention.purge_execution_enabled=false`
- `legacy-migration.disposable_verification.enabled=false`
- `legacy-migration.disposable_verification.identity_verified=false`
- `legacy-migration.disposable_verification.environment_reference`
- `legacy-migration.disposable_verification.connection`
- `legacy-migration.disposable_verification.database`

Missing or mismatched values must block activation. Secret material must never be stored in Laravel configuration or its cache.
