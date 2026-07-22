# Phase 3B key rotation and retention

Status: key-reference and append-only rotation mechanics implemented with synthetic tests; real key custody and real purge remain externally blocked.

## Key provider contract

`ExternalReferenceKeyProvider` accepts `OperationalKeyReference` metadata and resolves the secret only at HMAC use time through an injected secret resolver. The Laravel configuration contains a reference such as an environment/secret-manager/Vault/KMS URI, never resolved key bytes.

References may be supplied through a SHA-256-pinned JSON reference manifest. `KeyReferenceManifestLoader` accepts only non-secret metadata, rejects embedded key/password/credential material, accepts only `env://`, `secret://`, `vault://` or `kms://` secret-reference URIs, and passes the external reference list to the provider. HMAC rotation selects the active key for the token's exact domain; it does not fall back to another domain's default active key.

Each reference binds:

- key ID and version;
- environment and permitted domains;
- activation and optional retirement time;
- verification expiry;
- rotation authority;
- revocation state; and
- signing-active or verification-only use.

Exactly one reference may be active for signing in a provider. Retired keys are verification-only until their verification expiry. Revoked, expired, wrong-environment and wrong-domain keys fail closed. Key material still passes the existing minimum-length, placeholder and diversity checks after external resolution.

## Rotation lineage

Rotation verifies the old envelope and every canonical token message using the retained old key. The container loads active and retained verification references from the pinned external reference manifest. `PinnedProtectedStoreAccessAuthority` permits only those declared key contexts, while the external provider independently rejects revoked or expired references. `PinnedProtectedRecordRotationAuthority` is then composed into `ProtectedRecordSecurityRepository`, pins the approving authority and allowed reason, derives the complete active-key token set, and supplies the approved active factory/verifier internally. Missing rotation configuration resolves an explicit fail-closed authority. The caller cannot submit a new token, factory or verifier. Rotation then creates and verifies the new keyed envelope and appends:

- a new envelope generation;
- the old/new key IDs and versions;
- reason and authority reference;
- run and source/target snapshot context;
- old/new verification results; and
- a keyed lineage checksum.

The old envelope is never overwritten or deleted. `supersedes_envelope_id` plus `legacy_migration_protected_token_rotations` preserves lineage, while reads select the newest verified generation. A failed verification creates no successor generation.

## Retention contract

`RetentionPolicy` and `RetentionPurgeGate` require exact access/retention classifications, an owner-approved policy, minimum retention period, review date, no legal or operational hold, purge authority, verified integrity and proof that business lineage will not be destroyed.

`ProtectedLifecycleRepository` can append external key-reference metadata, policy metadata and an audited purge request only through purpose-scoped protected methods. Caller-supplied integrity checksums are rejected; the repository derives keyed checksums, seals every lifecycle record and appends access audit. Legacy unsealed methods are denied. It deliberately has no executable purge path: `purge()` always raises `LM-SEC-STORE-PURGE-NOT-AUTHORIZED`. Requests remain `blocked_pending_owner_policy` or, after a future approved policy, `eligible_not_executable_phase3b`. There is no business-row cascade and no crosswalk lineage deletion.

An implementation phase after owner approval must decide whether encrypted payload erasure, a tombstone or aggregate evidence is contractually required for each retention class. That decision must be versioned and independently reviewed before adding a purge executor.

## External prerequisites

The following remain unresolved external activation gates, not hidden implementation defaults:

1. Owner-approved retention schedule and legal/operational hold rules.
2. Named key-custody and rotation authority.
3. Real secret-provider references and permissions for the approved non-production environment.
4. Verification-retention duration for retired keys.
5. Database credential separation for protected repository access.
6. Independent security/privacy review of the configured environment.

Until all six exist, protected population and purge remain blocked. This does not block synthetic unit/integration verification and does not authorize any importer or patient pilot.

## Focused evidence

- Active and retained verification-only external references resolve at use time.
- Revoked/expired retained keys fail closed.
- A rotation creates two envelope generations and one append-only rotation record.
- Unapproved rotation reasons and forged canonical messages create no successor generation.
- Container-composed v1-to-v2 rotation verifies a retained v1 envelope and signs with active v2; revoked and expired retained keys are rejected.
- The old envelope remains intact.
- Lifecycle key references, retention policies and blocked purge requests carry repository-derived keyed integrity, protected envelopes and access audits.
- Holds and missing owner policy block purge.
- Even an approved policy rejects a request that would destroy lineage.
- No real key, PHI, patient identifier or real purge was used.
