# Phase 3B runtime-isolation review

Review date: 2026-07-22  
Reviewer role: independent runtime-isolation reviewer  
Review mode: read-only code and evidence inspection plus focused synthetic tests; no Classic, renewed production, shared target, importer, pilot or business-domain write was used  
Verdict: **PASS**  
Findings: **Critical 0, High 0, Medium 0**

## Executive decision

The corrected Phase 3B runtime, recovery and allocator package closes the three High and one Medium findings from the initial review. The runtime now binds exactly 25 controls to framework sinks, real operational invocation boundaries and fail-closed prospective gateways. A typed boot capability is required, external barriers are freshly re-observed at every runtime entry, and the protected migration audit must be available before isolation begins.

Recovery decisions are derived from sealed repository facts, bind the durable observation hash, reject stale replay, and have fresh-process interruption coverage for all ten Phase 2F boundaries. The concrete allocator has current, hash-bound disposable MariaDB 10.4.32 concurrency and rollback evidence.

From runtime-isolation scope alone, Phase 4A is eligible only as the later synthetic foundation exercise after the complete four-review gate and its external deployment prerequisites pass. This does not authorize an importer, Cohort B, a patient pilot, a real reservation, protected patient-data population, a production write or a Classic write.

## Scope reviewed

The review covered:

- `AGENTS.md`, the Phase 3B directive, Phase 2F/Phase 3 runtime, recovery and allocation contracts, all four Phase 3 reports, and the current Phase 3B evidence documents;
- runtime controls, provider bindings, operational effect gates, protected runtime audit, recovery journal/CAS adapters, durable observers, allocator adapters and their protected repositories;
- operational initiation paths represented by the 25-subsystem registry, including framework facades, event/observer dispatch, callbacks, billing, receivables, accounting, stock, pharmacy, bed, queue, appointment, notification, integration and audit paths;
- the current focused runtime/recovery/allocation tests and the retained, current-hash disposable MariaDB result.

## Findings closed

### RTI-P3B-001 — actual application-bound isolation

- The registry contains exactly 25 distinct required subsystems and all six outbound channels.
- Framework controls replace and exactly restore Laravel/Eloquent dispatchers; Notification, Mail, Queue, Bus, Storage and HTTP facade roots; and Spatie operational activity state.
- `OperationalEffectGate` now denies pre-resolved and directly created service invocation, not only container resolution. A first-statement manifest covers 127 concrete public operational effect entries.
- Both payment and SMS callback `store`, `process` and `handle` paths deny before database or provider work.
- Direct billing, invoice, receivable, accounting-posting, allocation, stock, dispensing, bed, queue/pathway, appointment, notification, activity, callback and integration paths are guarded.
- Search indexing has no installed mutating application path; it remains a mandatory prospective fail-closed gateway rather than being falsely represented by a read-only search service.
- All gates remain open outside migration mode. Success, exception and partial-control failure restore the prior state.

### RTI-P3B-002 — independently evidenced durable recovery

- `AtomicRecoveryCoordinator` accepts no caller-authored booleans or checkpoint hashes. It obtains a sealed `RecoveryObservation` from `ProtectedRecoveryStore` before classification or replay.
- The observation reconciles the exact run, snapshots, bundle, intent, crosswalk, provenance, reconciliation, reservation, checkpoint and compensation records and binds their IDs/facts into `durableEvidenceHash`.
- Recorded decisions bind that hash. Replay re-observes current durable facts and fails with `RECOVERY-RECORDED-EVIDENCE-CHANGED` if facts changed after the decision.
- Checkpoint-only repair is derived from verified committed facts, is re-observed after append, and cannot repair any other durable unit.
- CAS transitions enforce the explicit state graph, expected version and exact attempt increment; illegal and terminal-exit transitions leave durable state unchanged.
- Allocation lineage resolution rejects unsealed or raw-tampered crosswalk identity and audits the failed protected read; reservation composition verifies keyed idempotency, run, source/target snapshot and contract-bundle envelopes.
- All ten Phase 2F boundaries are exercised after abrupt process exit and fresh PHP/application boot, in addition to database disconnect/reconstruction tests.

### RTI-P3B-003 — protected migration-audit availability

- Runtime construction requires `MigrationRuntimeAudit`; the application binds `ProtectedMigrationRuntimeAudit`.
- Audit readiness is proven before isolation. Missing configuration, protected access, key capability or sealed run/snapshot lineage blocks activation.
- Activation, isolation, failure, denied attempts and restoration outcomes are appended through the protected migration-audit repository. Operational activity logging remains isolated and is not reused as migration audit.
- Audit failure and restoration failure remain fail-closed.

### RTI-P3B-004 — adversarial proof and traceability

Focused coverage now includes missing controls/evidence, a lying non-application control, a real control that refuses isolation, barrier revocation, nested activation, web-boundary activation, production activation, callback/direct/pre-resolved paths, normal inactive gates, denied effects, successful restoration and restoration failure. Current documentation records the exact test hashes and results.

## Boot and external-process assessment

The production application binding mints `ApplicationIsolationBootCapability` only after `ApplicationIsolationBootVerifier` directly observes the configured barrier provider. The capability is required by the factory and context and re-runs observation at construction and every runtime entry, so stale boot evidence cannot activate the bound runtime. Synthetic capability construction is PHPUnit-only.

The default provider remains `MissingApplicationIsolationBarrierProvider`, and all queue-worker, scheduler, binding and null-sink attestations default false/blank. Therefore the repository fails closed until deployment infrastructure supplies and directly proves the approved non-production barriers. This is a genuine external activation prerequisite, not a repository High finding.

## Allocator evidence

The current allocator evidence is bound to the exact tested sources:

| Evidence | SHA-256 |
|---|---|
| MariaDB verification test | `d58cf9cf0565bed179b0b9eacd20bfe8419074a84f60d7734bdc75ba23b1fd94` |
| Independent worker test | `b04710a09d9adb2826981b440b92c7819d22a553bc5b40bee6eaa855495ccacf` |
| Synthetic allocator harness | `a1f3f978246a98d0538997f023b07b61a614a491d75ccebeb86a925a14fb07a6` |
| Production reservation repository | `52702208f2f28ed7c7fa10d593d6fd4b8b110d98de890832fa43c43c040975fd` |
| Protected-security repository | `f4355e2ca6f8c379e3579c3d4dc23209f1a4849f99ae761154c4d0432c82ca48` |
| Protected recovery store | `34a8c57c70f7fd4f2c98592429c07f5583e8dc6e2f7cc4a20e2d53b1ee43cf61` |
| Production allocation store | `4c1f012c6d54fd5a23c682f92b72d16f3ef68c0c1d1e6d5191d247531dc71cee` |
| Production crosswalk resolver | `7bdb3553058fff07c5f3b578449efdf89135611518e5027ccc8af2cc84cb678a` |

The attested loopback-only disposable MariaDB 10.4.32 run passed **1 test with 44 assertions**. It covered independent-process same/different-coordinate contention, same-lineage rerun, collision namespaces, protected lineage mismatch, three transaction fault points, deadlock retry, connection-loss failure, 17 keyed idempotency/reservation envelopes bound to the complete protected run/source-snapshot/target-snapshot coordinate, and exact ordinal reconciliation. It also proves fail-closed rejection before sequence locking when the allocation, recovery, protected-security or reservation-repository connection differs; that mismatch creates zero sequence and reservation rows. Guarded teardown removed the synthetic schemas and the disposable server process/listener was confirmed absent. The default application write guard still rejects a real reservation.

## Focused verification

Command executed:

```text
php artisan test tests/Unit/LegacyMigration/Foundation/Runtime tests/Feature/LegacyMigration/Foundation/ApplicationBoundSideEffectIsolationTest.php tests/Feature/LegacyMigration/Foundation/DurableRecoveryJournalTest.php tests/Unit/LegacyMigration/Foundation/Recovery tests/Unit/LegacyMigration/Foundation/Allocation tests/Feature/LegacyMigration/Foundation/MariaDbAllocatorVerificationTest.php tests/Feature/LegacyMigration/Foundation/MariaDbAllocatorWorkerTest.php --compact
```

Result: **148 passed, 792 assertions, 3 expected gated/worker-only skips**. The skips are the internal recovery subprocess worker and the two disposable-MariaDB entry points in a normal, non-opted-in run. The independently attested current-hash MariaDB execution is recorded separately above.

Within that verification, the application-isolation feature test passed **47 tests with 294 assertions**, and the durable-recovery feature test passed **21 tests with 234 assertions** plus its one expected worker-only skip.

Relevant current test hashes:

- application isolation: `1e5803267a76a40cbae6dbe6a4893947ece26ea6ce28e390e198cbb970977379`;
- 127-entry operational-guard manifest: `5829121e0a5f1a3030724d630afeddc1e1b4a9c39662a53d058c3e75a6b2283f`;
- durable recovery: `35351181f64a551e4123c03a6bd7865243d4e900bce53c4737b445a2ff483603`.

## Exit-gate assessment

| Requirement | Result |
|---|---|
| Exactly 25 configured control identities | Pass |
| Actual framework and operational initiation paths guarded | Pass |
| Boot completeness and fresh external worker/scheduler/null-sink observation | Pass in repository; deployment provider remains fail-closed external input |
| Separate protected migration audit available and durable | Pass |
| Restoration and normal runtime unchanged | Pass |
| Durable protected journal and CAS adapters | Pass |
| Ten abrupt process-exit/fresh-boot recovery scenarios | Pass |
| Independently derived and replay-bound recovery facts | Pass |
| Concrete MariaDB allocator concurrency/rollback | Pass on current hash-bound disposable evidence |
| No importer, pilot, Cohort B, production or Classic write | Pass |

## Final authorization

Runtime-isolation review: **PASS — Critical 0, High 0, Medium 0**.  
Phase 4A synthetic foundation exercise: **eligible from runtime-isolation perspective only**, subject to all other independent reviews and deployment barrier evidence.  
Protected-store population with patient data, Cohort B, patient-pilot execution, every domain importer, commit mode, production writes and Classic writes: **blocked**.
