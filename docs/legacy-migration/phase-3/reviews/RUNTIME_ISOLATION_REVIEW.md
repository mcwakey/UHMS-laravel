# Phase 3 runtime-isolation review

Review date: 2026-07-22  
Reviewer role: independent runtime side-effect reviewer  
Review mode: read-only code inspection and isolated synthetic SQLite tests; no Classic or renewed production database was connected to or written  
Verdict: **FAIL — three High findings remain**

## Executive decision

Phase 3 does not pass the runtime-isolation exit gate. The current package is safely inert: Phase 3 commit activation is unconditionally rejected, real patient-number reservation is blocked, dry-run persistence cannot reach a commit method, and no importer, pilot runner or domain persistence adapter exists. Those controls prevent present use from creating a business-domain row, but they do not constitute the complete, application-bound isolation and durable recovery evidence required by the Phase 3 directive.

Phase 4A, Cohort B selection, the patient pilot and every importer remain unauthorized.

## Scope and authoritative basis

The review covered:

- `AGENTS.md`, the Phase 3 directive and the Phase 2F runtime, atomicity, rollback/resume, allocator and acceptance contracts;
- all classes under `app/Services/LegacyMigration/Foundation/Runtime`, `Persistence`, `Validation`, `Allocation` and `Recovery`;
- the related protected-storage adapters, models and migrations where they affect runtime or recovery safety;
- `config/legacy-migration.php`, the side-effect deny-list and Phase 3 runtime/persistence/recovery documentation;
- application service-provider, scheduler, event, observer, notification, queue, integration, billing, stock, pharmacy, bed and activity paths needed to determine whether the controls are actually bound;
- focused tests only, using `APP_ENV=testing`, SQLite `:memory:` and synthetic values.

## Confirmed safety controls

- `MigrationRuntimeRequest::assertCanActivate()` unconditionally rejects `ExecutionMode::Commit` in Phase 3. Caller-supplied approval booleans cannot override that phase gate.
- Runtime activation is console-only, rejects production-like environments, requires matching protected run/snapshot coordinates and rejects nested activation.
- `SideEffectIsolationRegistry` requires all 25 directive subsystems and all six outbound channels. Missing or unknown entries fail closed.
- The isolation coordinator verifies every requested subsystem after isolation, counts attempted effects, restores after success and failure, attempts every restoration even when an individual control fails, and rejects unproved restoration.
- Dry-run guarded persistence returns `projected_no_write`; it does not invoke the subclass commit method.
- All six domain persistence surfaces are interfaces or abstract guarded boundaries. There is no concrete patient, alias, contact, insurance-history, current-membership or existing-target domain adapter.
- Patient and insurance target-state policies remain commit-blocking. Existing-target linking is metadata-only and requires zero before/after mutation evidence.
- `LaravelNumberReservationStore` defaults to `Phase3AllocationWriteGuard`, which rejects a real reservation before sequence or reservation writes. Dry-run allocation is symbolic and nonbinding.
- Protected-store Eloquent mutations and every current foundation mass-update path now call the Phase 3 write guard. Non-test, non-`testing`, non-SQLite writes are rejected. The earlier mass-update bypass was corrected and is closed in this review.
- No foundation code calls `PatientService::create()`, an operational billing/payment/stock/pharmacy service, `Auth::user()`, `auth()` or a current-user resolver. Allocation fixes `created_by_token` and `updated_by_token` to null.
- No domain importer class, pilot command, Cohort B selector or business-table migration was found. The only business tables referenced by foundation runtime code are read-only patient-number collision namespaces; the final tests create no synthetic business rows.

## Blocking findings

### RTI-001 — High — the 25 subsystem controls are contracts and test doubles, not application-bound isolation

`ApplicationSideEffectIsolationDriver` correctly requires one `SubsystemIsolationControl` per prohibited subsystem, but the repository contains no concrete implementation of that interface. `MigrationRuntimeFactory` is not registered in `AppServiceProvider` or another provider and is not used by a command. No real service, facade, observer, listener, scheduler or integration call site invokes `SideEffectGuard`.

The application has active operational paths that demonstrate why interface-only proof is insufficient: `AppServiceProvider` registers clinical, payment, stock and audit-forwarding events and observers; `routes/console.php` and `bootstrap/app.php` register operational schedules; services dispatch events, notifications, jobs and HTTP integrations; billing, payment-allocation, stock, pharmacy and bed services remain independent of the migration runtime.

The focused runtime test invokes each enum value through `SideEffectGuard` using synthetic drivers and controls. It does not exercise or suppress any real application path. There is no boot-time verification of real controls. Consequently, queue, scheduler, notification, integration, activity, billing, accounting, payment allocation, stock, pharmacy, bed and pathway isolation is not operationally implemented or proven.

Impact: a future migration persistence adapter cannot safely activate commit mode. The current unconditional Phase 3 commit rejection contains the risk, so this is fail-closed rather than an exploitable business-write path today.

Required remediation:

1. Implement and bind one observable real control for every prohibited subsystem.
2. Guard the actual effect initiation points, including facade and direct-service paths, not only a migration callback.
3. Add boot-time completeness/state verification in the isolated migration console runtime.
4. Add one focused integration test per real prohibited path, plus restoration and normal-runtime-unaffected tests.
5. Retain environment isolation for schedulers and external workers; process-local flags alone are insufficient.

### RTI-002 — High — recovery classification is not connected to a durable recovery journal

`AtomicRecoveryCoordinator` depends on `AtomicRecoveryJournal`, and `MonotonicStateMachine` depends on `CompareAndSetStateStore`. No application class implements either interface. Their only implementations are `InMemoryRecoveryJournal` and `InMemoryCasStore` inside `RecoveryFoundationTest`.

`RecoveryRepository` can create intents, append checkpoints and compensation records, but it is not an adapter for the coordinator or state machine and does not atomically bind compatible intent resolution, recovery classification, compare-and-set state, checkpoint repair and decision evidence. `legacy-migration:foundation-recovery-audit` classifies caller-supplied aggregate booleans and writes nothing; it does not restart from durable records.

The ten crash-boundary tests therefore prove the classifier's decision table, not durable crash recovery, duplicate-free resume, checkpoint-only repair after a committed unit, or unit-specific compensation persistence.

Impact: checkpoints and recovery tables exist, but the required resumable behavior is not implemented end to end. Current business writes remain blocked, so no unsafe resume can run today.

Required remediation:

1. Implement a durable journal/state-store adapter over the protected recovery repositories.
2. Bind intent, state, attempt, write-set, reconciliation and checkpoint coordinates in one transaction where the contract requires atomicity.
3. Add fault-injection/restart tests at all ten durable boundaries.
4. Prove completed units are not replayed and a missing checkpoint repairs only that checkpoint.

### RTI-003 — High — concrete patient-number concurrency and rollback are not tested

The concrete allocator design is plausible: crosswalk/idempotency lookup occurs first, sequence coordinates use transactions and `lockForUpdate()`, reservation and sequence advancement share a transaction, collision evidence fails closed, and the default Phase 3 write guard blocks real use.

The evidence does not satisfy the directive's concurrent synthetic exit gate. `locked_allocations_are_unique_and_rerun_never_allocates_a_replacement` makes sequential calls against an in-memory boolean lock. `LaravelAllocationAdaptersTest` also runs sequentially on SQLite. Transaction rollback after reservation is simulated only by the in-memory store; there is no concrete database fault-injection test proving reservation and sequence rollback together.

Impact: no duplicate can currently be created because the real reservation path is phase-blocked, but concurrency safety and rollback behavior are unproved for later activation.

Required remediation: run a safe, disposable MariaDB-compatible concurrency test with at least two independent workers contending on the same coordinate, add concrete transaction fault injection, and reconcile exact ordinal coverage afterward. Do not use Classic, a renewed production database or synthetic business rows.

## Non-blocking future-boundary observations

- `MigrationRuntimeRequest` and `PersistenceBoundaryContext` accept prerequisite booleans from their caller. This is safe only while commit is unconditionally blocked. A future commit-capable factory must derive them from pinned, authenticated run/snapshot/reconciliation records rather than caller assertions.
- `ExistingTargetEvidence` validates token domains, context equality and zero deltas but is a constructible DTO. A future commit-capable boundary must obtain and verify it through the protected collision/evidence service, including HMAC authenticity, rather than trusting caller construction.
- Guarded persistence validates the returned result after the commit callback. Future concrete adapters must use an enclosing transaction and independently measured write set; a reported write count is not an enforcement boundary.

## Focused verification

Command executed:

```text
php artisan test tests/Unit/LegacyMigration/Foundation/Runtime tests/Unit/LegacyMigration/Foundation/Persistence tests/Unit/LegacyMigration/Foundation/Validation tests/Unit/LegacyMigration/Foundation/Allocation tests/Unit/LegacyMigration/Foundation/Recovery tests/Unit/LegacyMigration/Foundation/Integration/FoundationPackageSafetyTest.php tests/Feature/LegacyMigration/Foundation/FoundationSchemaTest.php --stop-on-failure
```

Result: **102 tests passed, 485 assertions**.

The passing suite confirms internal fail-closed behavior and schema guards. It does not close RTI-001, RTI-002 or RTI-003 because the missing real controls, durable adapter and concurrent workers are not test subjects.

## Exit-gate assessment

| Requirement | Result | Evidence |
|---|---|---|
| Runtime scoping and production rejection | Pass for the inert Phase 3 runtime | Console/non-production checks and unconditional commit rejection are tested. |
| Every prohibited effect path isolated | **Fail** | All 25 names exist; no real control/call-site binding exists. |
| Queue/notification/integration isolation | **Fail** | Synthetic guard tests only; operational paths remain unbound. |
| Activity logging disabled while migration audit remains available | **Fail** | Registry distinction exists; no concrete activity/audit controls exist. |
| No current-user stamping | Pass for current foundation code | No current-user resolver or operational service call was found; allocation actor tokens are null. |
| No operational service use | Pass | Foundation code does not invoke operational creation/posting/stock services. |
| Normal runtime unaffected | Pass today, incomplete proof for future isolation | No real controls are bound, so behavior is unchanged; scoped real-control restoration remains untested. |
| Ten crash-boundary classifications | Pass | All ten classifier cases are tested. |
| Durable duplicate-free resume/checkpoint repair | **Fail** | No persistent journal/state-store adapter or restart tests. |
| Patient-number crosswalk-first/collision/dry-run behavior | Pass at unit/interface level | Focused tests pass and real reservation is phase-blocked. |
| Concrete allocation concurrency/rollback | **Fail** | Sequential SQLite/in-memory evidence only. |
| No importer, pilot or business-domain write path | Pass | Static inspection and package safety tests found none. |

## Final authorization

Runtime-isolation review: **not approved**.  
Phase 3 exit: **not authorized by this review**.  
Phase 4A synthetic foundation exercise: **not authorized**.  
Cohort B, patient pilot and importer implementation/execution: **blocked**.

The exact next runtime work is to implement and independently test the real 25-subsystem isolation bindings, the durable recovery journal/state-store adapter, and concrete MariaDB-compatible allocator concurrency/rollback evidence, then rerun this independent review.
