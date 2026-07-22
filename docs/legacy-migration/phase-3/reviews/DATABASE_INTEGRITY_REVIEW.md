# Phase 3 database-integrity review

**Review date:** 2026-07-22  
**Verdict:** **FAIL - two High-severity blockers remain.**  
**Open findings:** 0 Critical, 2 High, 4 Medium.

This is the final independent review of the frozen Phase 3 migrations, protected-store models and repositories, idempotency/recovery and number-allocation code, focused tests, Phase 2F interfaces, and Phase 3 specifications. No Classic UHMS or renewed production database was connected to or written. Execution used isolated in-memory SQLite and synthetic data only.

## Final verification evidence

- The focused database/recovery/allocation/environment selection passed: **67 tests, 321 assertions**.
- Applying the three migrations to isolated SQLite produced exactly **16** `legacy_migration_*` tables, **32 foreign-key constraints** (50 constrained columns), **54 indexes**, and **42 validation/immutability triggers**.
- Every foreign key targets a `legacy_migration_*` table and every delete action is `RESTRICT`; there are no business-table foreign keys or cascades.
- The longest actual index name is `legacy_migration_reconciliation_results_idempotency_token_unique` at exactly **64 characters**. All other observed names are shorter.
- All `encrypted_*` fields have encrypted model casts. Protected fields and generated guard keys are hidden from ordinary serialization, and the schema test proves synthetic plaintext is absent from the raw encrypted manifest.
- No business-domain row is created by the focused allocator tests. The concrete collision probe reads empty synthetic namespace tables only.

## High-severity blockers

### DBI-004 - High - recovery is not durably implemented or crash-tested

No application class implements `AtomicRecoveryJournal` or `CompareAndSetStateStore`; the only implementations are `InMemoryRecoveryJournal` and `InMemoryCasStore` in `tests/Unit/LegacyMigration/Foundation/Recovery/RecoveryFoundationTest.php:146-191`. `RecoveryRepository` does not implement either interface or atomically bind intent resolution, recovery decision, state CAS, and checkpoint repair.

The ten `PILOT-RESUME-*` cases exercise a classifier with supplied evidence booleans. They do not inject crashes at the ten real database transaction boundaries, restart from durable rows, prove target writes are not replayed, or prove that a committed unit with a missing checkpoint repairs only the checkpoint through the real storage adapter. The durable resume/checkpoint criterion remains unproven.

### DBI-005 - High - concrete allocator concurrency and rollback evidence is absent

`LaravelNumberReservationStore` has a plausible MariaDB design: one transaction, coordinate creation, `SELECT ... FOR UPDATE`, reservation insert, and sequence CAS (`app/Services/LegacyMigration/Foundation/Allocation/LaravelNumberReservationStore.php:38-110`). The evidence does not exercise it with two concurrent database workers.

The concrete SQLite adapter test is sequential. Rerun and rollback-failure tests use an in-memory fake with `failAfterPersist`; there is no concrete transaction fault injection proving both sequence and reservation changes roll back together. Required concurrent allocation, rerun, and rollback behavior must be demonstrated on an approved disposable MariaDB 10.4 instance.

## Medium-severity findings

### DBI-007 - Medium - sequence reconciliation ignores ordinal identity

`SequenceReconciler` de-duplicates `lineageToken` and counts classifications but never uses `SequenceConsumption::sequenceOrdinal`. Duplicate or out-of-range ordinals with different lineage tokens can balance a missing ordinal and still pass by count. Reconciliation should validate ordinal uniqueness, range, and coverage for the pinned coordinate.

### DBI-008 - Medium - remediation/provenance repository claims exceed validation

`ProtectedEvidenceRepository` validates token shape only. It does not reject unapproved, revoked, expired, or conflicting remediation input, nor validate complete provenance dispositions, while `foundation_capabilities.json` claims those failure controls are implemented. Persistence immutability is sound, but semantic admission validation and tests remain missing.

### DBI-009 - Medium - MariaDB 10.4 DDL and failure recovery remain unexecuted

Static reasoning finds the generated columns, `CHECK` constraints, composite foreign keys, trigger syntax, identifier lengths, and expected index widths compatible with MariaDB 10.4/InnoDB. This is not execution evidence.

MariaDB DDL auto-commits. Each Laravel migration creates multiple tables, constraints, indexes, and triggers; a mid-migration permission or DDL failure can leave a partial unrecorded schema, while rerun encounters existing objects. Non-test `down()` is deliberately hard-blocked, so it is not a recovery route. An approved disposable MariaDB 10.4 target must verify installation, partial-failure recovery, trigger privileges, generated keys, checks, and exact `SHOW CREATE TABLE` output.

### DBI-010 - Medium - capability/test traceability overstates recovery and allocator coverage

`foundation_capabilities.json` names focused tests that do not exist (`AtomicRecoveryCoordinatorTest`, `CrashBoundaryClassifierTest`, `MonotonicStateMachineTest`, `CompensationPlanRegistryTest`, and `LaravelNumberReservationStoreTest`) and records no remaining blocker for durable recovery or concrete concurrency. The manifest must name the real tests and retain the two High blockers until closed.

## Verified closures from the initial review

| Previous finding | Final result | Evidence |
|---|---:|---|
| Destructive `down()` could target the default/source/production connection | CLOSED | All three `down()` methods call `assertSchemaRemovalAllowed()` before DDL. It allows isolated testing SQLite only and otherwise throws `LM-SEC-FOUNDATION-SCHEMA-REMOVAL-BLOCKED`. A non-test isolated probe confirmed the rejection. |
| Incompatible idempotency coordinates were reused | CLOSED | `IdempotencyRepository` now compares run, both snapshots, both protected tokens, input/outcome type, and all version coordinates. The adversarial focused test rejects incompatible reuse. |
| Caller-controlled quarantine coordinate permitted another authoritative root | CLOSED | `lm_quar_one_root_per_coordinate_uq` is now `(root_domain, root_token, source_snapshot_id)`. The adversarial duplicate-root test fails at the database. |
| Raw SQL could jump `NOT_STARTED` to `COMPLETED` | CLOSED | `lm_runs_state_transition` encodes the exact legal edges and requires `lock_version = OLD.lock_version + 1`; the raw-SQL bypass test now fails. |
| Query-builder updates bypassed the model write guard | CLOSED | `CompareAndSet`, crosswalk revocation, quarantine sealing, and number-reservation mutation explicitly call `Phase3ProtectedStoreModelGuard::assertConnectionWriteAllowed()`. |
| Long MariaDB index names | CLOSED | Actual maximum is 64 characters. |
| Cross-run lineage coordinates | CLOSED | Composite same-run foreign keys cover snapshots, idempotency records, intents, checkpoints, compensations, and reservations. |
| Crosswalk revocation conflicted with immutable coordinates | CLOSED | `active_coordinate_token` remains mutable only for the valid active-to-revoked transition; an isolated revoke reached inactive/null coordinate at version 1. |

## Control matrix

| Control | Result | Evidence |
|---|---:|---|
| SQLite migration DDL | PASS | 16 tables; 32 FKs; 54 indexes; 42 triggers |
| MariaDB 10.4 exact DDL | BLOCKED | Reasoned compatible; not run on approved disposable MariaDB |
| Index names and cardinality | PASS | Maximum 64 characters; active crosswalk, authoritative root, and per-root primary uniqueness enforced |
| Append-only/immutable evidence | PASS | Append-only, immutable-coordinate, and no-delete DB triggers |
| Encrypted and hidden payloads | PASS | Encrypted casts, hidden fields, and raw-ciphertext test |
| No cascade/business FKs | PASS | All 32 FKs are foundation-only and `RESTRICT` |
| Active crosswalk uniqueness/revoke | PASS | Generated active-source key and successful controlled revoke |
| Quarantine roots/primary/topology | PASS for Phase 3 | One authoritative root; atomic build/seal with exactly one primary; same-run/source parent FK; release remains hard-blocked |
| Nonzero reconciliation pass | PASS | SQLite triggers and MariaDB checks require complete measurement, zero difference, and zero tolerance |
| Idempotency/CAS/checkpoint constraints | PASS | Full coordinate comparison, legal run-state trigger, unique compatible checkpoint coordinates |
| Durable recovery execution | FAIL | No persistent journal/CAS adapter or crash/restart proof |
| Ten crash boundaries | FAIL | Classification-only, in-memory evidence |
| Concrete number concurrency/rerun/rollback | FAIL | Sequential concrete test; concurrency and concrete rollback absent |
| Transaction boundaries | PARTIAL | Storage transactions exist; durable recovery orchestration and MariaDB fault proof do not |
| No business rows in Phase 3 tests | PASS | Empty synthetic namespace reads only |
| No Classic/production writes during review | PASS | No live connection used |
| Technical prevention of Classic/production DDL | PASS | Exact guarded `up()` boundary; non-test `down()` hard-blocked before DDL |

## Required before database-integrity re-review

1. Implement persistent `AtomicRecoveryJournal` and `CompareAndSetStateStore` adapters and exercise real crash/restart recovery at all ten boundaries.
2. Run concurrent-worker, deterministic rerun, and injected concrete rollback tests against the allocator on an approved disposable MariaDB 10.4 instance.
3. Validate sequence ordinal uniqueness/range/coverage and add adversarial reconciliation tests.
4. Add the missing remediation/provenance semantic admission validation.
5. Correct the capability/test traceability manifest and record the remaining blockers.
6. Complete disposable MariaDB 10.4 DDL, permissions, and partial-failure recovery verification. Never use Classic UHMS or renewed production.
