Act as the Lead Architect and Implementer for Phase 14R.8 — OBGYN/Maternity Snapshot Completion-Occurrence Identity Hardening.

Phase 14R.7 is complete with verdict `READY_FOR_CLINICAL_PILOT`.

This phase exists only to close confirmed risk P2:

A consultation route that is reopened and recompleted within the same second currently reuses the second-precision completion reference:

`route:{route_id}@{completed_at}`

The second genuine completion is therefore treated as the same completion occurrence. Snapshot v1 remains intact, but no v2 is created and the clinical state at the second completion is not captured.

This is a medico-legal record-completeness problem. Fix it before starting Phase 14.2 billing work.

## Read first

Read:

* AGENTS.md
* All OBGYN/Maternity implementation plans and reports
* Phase 14R.5 through Phase 14R.7 reports
* `OBGYN_MATERNITY_PILOT_REGRESSION_PHASE_14R_7_REPORT.md`
* All snapshot migrations, models, services, observers, listeners and commands
* Consultation completion, reopen and recompletion services
* Consultation route/session lifecycle code
* Consultation completion/activity/log models
* Maternity summary snapshot capture code
* Snapshot presentation, preview and print services
* Snapshot hash and versioning services
* Existing snapshot tests
* Existing consultation reopen/completion tests
* Existing manual pilot/preflight commands

Inspect the installed schema and actual runtime flow before choosing the implementation.

Do not assume the current completion timestamp is the only available completion identity.

## Exact objective

Give every genuine consultation completion occurrence a stable, persisted, transactionally authoritative identity.

The following must hold:

1. Initial completion creates snapshot v1.
2. Reopen followed by recompletion creates a new completion occurrence.
3. Recompletion creates snapshot v2 even when it happens within the same second as v1.
4. A second reopen and recompletion creates v3, including when all three completion actions occur under a frozen same-second clock.
5. Repeating the same completion request without an intervening reopen remains idempotent and creates no duplicate snapshot.
6. Retrying a failed request for the same completion occurrence creates or resolves the same snapshot.
7. Existing snapshots remain readable and verifiable.
8. Existing snapshot payloads and hashes remain unchanged.
9. Snapshot records remain append-only.
10. No historical snapshot is updated, replaced or deleted.
11. No billing behavior changes.
12. No feature flag is enabled automatically.

## Scope boundary

Include only:

* Completion-occurrence identity
* Snapshot idempotency identity
* Snapshot version creation
* Reopen/recompletion lifecycle integration
* Concurrency protection
* Crash/retry behavior
* Legacy snapshot compatibility
* P2 preflight/readiness reporting
* Focused tests and documentation

Do not implement:

* Phase 14.2 billing posting
* Billing deduplication changes
* Invoice posting
* Maternity billing events
* New clinical fields
* Snapshot editing or deletion
* Snapshot UI redesign
* Browser-testing infrastructure
* Order-set retargeting
* Environment reconciliation writes
* Clinician sign-off
* Feature-flag rollout
* Broad consultation workflow redesign

The 14R.7 order-set warning, browser E2E absence and wide-suite memory problem remain separate issues.

## Architecture requirements

### 1. Find the authoritative completion event

Inspect whether the application already creates a durable row or immutable event for each genuine completion, such as:

* Consultation completion log
* Route transition log
* Session status-history row
* Visit journey transition
* Activity or domain event record
* Route completion attempt/occurrence record

Prefer an existing durable completion-event primary key or immutable UUID/ULID when it truthfully represents one completion occurrence.

Do not use a general-purpose activity-log entry if that entry is optional, asynchronous, mutable or can be suppressed.

Do not use:

* Second-precision timestamps alone
* Millisecond timestamps alone
* Request time alone
* Current user
* Snapshot row count
* `MAX(version) + 1` without locking
* Random identity generated separately on each retry
* Browser-generated identity
* JavaScript state

### 2. Completion-occurrence ledger

If no existing durable completion event safely identifies an occurrence, introduce the smallest dedicated server-side completion-occurrence structure.

A completion occurrence should be bound to:

* Consultation route
* Consultation/session where applicable
* Visit
* Completion generation or occurrence ID
* Completion transition
* Completion timestamp
* Reopen generation or preceding occurrence where useful
* Created transaction/run
* Idempotency reference
* Optional completion actor only when already truthfully available

Use UUID/ULID or another immutable generated identifier suitable for idempotency.

The occurrence must be created or resolved transactionally during the completion transition.

It must not be generated independently inside the snapshot service on every invocation.

### 3. Reopen lifecycle

A reopen must make a later completion a new occurrence.

Define an explicit lifecycle such as:

```text
ACTIVE
  -> COMPLETION_OCCURRENCE_1
  -> COMPLETED
  -> REOPENED
  -> COMPLETION_OCCURRENCE_2
  -> COMPLETED
```

Repeated calls to complete while still attached to `COMPLETION_OCCURRENCE_1` must resolve occurrence 1.

After an approved reopen, the next completion must create occurrence 2 regardless of wall-clock timestamp.

Do not create a new occurrence merely because the completion endpoint was retried.

### 4. Snapshot identity

Bind each snapshot to the authoritative completion occurrence.

The snapshot’s idempotency identity should be based on something equivalent to:

```text
maternity-summary-snapshot
+ consultation-route identity
+ completion-occurrence identity
+ snapshot contract/version
```

Do not use `completed_at` as the sole identity.

Preserve the human-readable completion timestamp separately.

The occurrence identity should prevent duplicate snapshots for one occurrence while permitting multiple genuine occurrences in the same second.

### 5. Legacy compatibility

Existing snapshots using references such as:

`route:{id}@{completed_at}`

must remain valid and readable.

Do not rewrite:

* Existing clinical payload
* Existing snapshot version
* Existing snapshot hash
* Existing completion timestamp
* Existing audit history

Choose a compatibility strategy after inspecting the schema. Acceptable approaches include:

* A new nullable occurrence reference used only by future snapshots
* A separate completion-occurrence ledger linked to future snapshots
* A versioned idempotency reference format
* A metadata-only compatibility link that does not alter snapshot hash semantics

Do not mass-backfill a fabricated historical occurrence identity.

If old rows need metadata association, classify it as legacy-derived metadata and prove that snapshot content/hash verification is unaffected.

### 6. Versioning

Snapshot versions must remain monotonic for each consultation route or approved snapshot aggregate.

Create the next version under a transaction and lock the relevant aggregate/occurrence coordinate.

Do not rely on an unlocked:

```sql
MAX(version) + 1
```

Protect against:

* Concurrent completion requests
* Concurrent snapshot listeners
* Queue retry where applicable
* Duplicate HTTP submission
* Retry after timeout
* Retry after transaction rollback

At most one snapshot may exist for one completion occurrence.

### 7. Transaction and failure behavior

Determine the correct transaction boundary between:

* Completion status transition
* Completion-occurrence creation
* Snapshot creation
* Snapshot version assignment
* Snapshot hash generation
* Completion checkpoint/audit

The design must not leave a completed consultation with an unexplained missing snapshot when snapshot capture is mandatory.

Define deterministic handling for:

* Failure before occurrence creation
* Failure after occurrence creation but before snapshot creation
* Failure after snapshot insert but before completion response
* Duplicate request after successful commit
* Reopen after a prior partial failure
* Hash-generation failure
* Unique-key conflict
* Deadlock/retry

Do not report success merely because a unique-key error occurred. Resolve and verify the existing occurrence/snapshot lineage.

### 8. Append-only guarantee

Preserve existing snapshot immutability:

* No snapshot update route
* No snapshot delete route
* No model update
* No soft delete
* No replacement of v1
* No modification of the historical payload or hash

If the snapshot model currently blocks mutation, retain and extend those protections as needed.

The Phase 14R.7 fixture-only raw snapshot teardown remains isolated manual-test cleanup and must not become a clinical deletion mechanism.

### 9. Hash behavior

Verify the snapshot hash contract.

The occurrence identity may be:

* Included in the hashed metadata for new snapshot-contract versions; or
* Stored as immutable envelope metadata outside the historical clinical payload

Choose deliberately.

Existing v1 snapshot hashes must remain byte-identical and continue to verify.

A newly captured v2 must have its own valid hash and immutable payload.

Changing live maternity data after v1 must never change v1.

### 10. Presentation behavior

The following must continue working without redesign:

* Summary tab
* Completed-snapshot default view
* Current live-record action
* Snapshot history/version navigation
* Preview modal
* Print view
* Compact `printMode` fragment
* Permission boundaries
* Feature flags

The preview modal must continue using the server-rendered maternity context and must never introduce a second renderer.

Do not move completion/snapshot logic into JavaScript.

## Database design checks

Before adding a migration, inspect:

* Current snapshot unique constraints
* Current version constraints
* Current completion-reference fields
* Route/session completion timestamps and precision
* Existing completion-history tables
* Foreign keys and cascade behavior
* Identifier lengths under MariaDB
* Index-name length
* Soft-delete behavior
* Snapshot append-only triggers or model guards
* Existing timestamp precision

Any migration must:

* Be safe on MariaDB
* Use explicit short constraint/index names
* Preserve existing rows
* Avoid destructive rewrite
* Avoid fabricated historical data
* Be reversible where safely possible
* Document any intentionally irreversible append-only structure

## Required focused tests

Add a dedicated P2 closure suite.

### Same-second completion identity

1. Complete once under a frozen clock: v1 created.
2. Reopen and recomplete under the exact same frozen second: v2 created.
3. Reopen and recomplete again under the same second: v3 created.
4. All versions have distinct completion-occurrence identities.
5. v1 remains byte-identical after v2 and v3.
6. Every hash verifies.

### Intended idempotency

7. Repeat completion without reopen: one occurrence and one snapshot.
8. Retry the same completion request: no duplicate snapshot.
9. Invoke snapshot capture twice for one occurrence: one snapshot.
10. Duplicate listener/event delivery: one snapshot.
11. A one-second-separated reopen/recompletion still creates v2.

### Concurrency

12. Two concurrent completion requests for the same occurrence create one snapshot.
13. Two concurrent capture requests for the same occurrence create one snapshot.
14. Version numbers remain unique and monotonic.
15. Unique-key conflicts resolve only through verified lineage.

### Failure and retry

16. Failure before occurrence persistence leaves no false completion success.
17. Failure after occurrence creation can safely resume and create the missing snapshot once.
18. Failure after snapshot commit and before response resolves the existing snapshot on retry.
19. Hash failure cannot leave a falsely accepted snapshot.
20. A later reopen creates a new occurrence after a recovered completion.

### Legacy compatibility

21. Existing legacy-reference snapshot remains readable.
22. Existing legacy-reference snapshot hash remains unchanged.
23. Existing snapshot history still orders correctly.
24. No backfilled identity is presented as a genuine historical fact.
25. Snapshot append-only mutation guards still reject update/delete.

### Workflow protection

26. Preview modal still shows the correct completed version.
27. Preview modal contains no scripts/forms/raw JSON.
28. Summary and print views select the same version.
29. Current live record remains lazy and separate.
30. Permission denial omits maternity snapshot context.
31. Flag-off behavior remains unchanged.
32. Reopen permissions remain unchanged.
33. Consultation completion readiness remains unchanged.
34. No invoice item is created.
35. No maternity billing event is created.
36. No order-set write is performed.
37. No environment reconciliation write is performed.
38. No feature flag is changed.

### Performance

39. Snapshot history does not add an N+1 query.
40. Preview does not introduce a second maternity projection.
41. Feature-off query counts remain at their established contracts.

Use the existing targeted test conventions.

Do not create a browser-testing framework.

## Preflight update

Update `maternity:obgyn-pilot-preflight` with a P2 check.

It should verify the installed snapshot identity capability, such as:

* Completion-occurrence structure present
* Required unique constraint present
* Legacy snapshot compatibility available
* Append-only protection active
* P2 focused verification/version marker present

Do not perform a clinical write during preflight.

Possible statuses:

* `PASS`
* `WARNING`
* `BLOCKED`

After this phase, the same-second identity problem must no longer appear as a production-readiness warning.

The preflight must continue reporting:

`clinician_acceptance: NOT_ASSESSED`

until actual clinician sign-off exists.

## Pilot documentation update

Update:

* `OBGYN_MATERNITY_PILOT_REGRESSION_PHASE_14R_7_REPORT.md` through a follow-up reference, not by rewriting historical results
* `OBGYN_MATERNITY_CLINICIAN_PILOT_GUIDE.md`
* `OBGYN_MATERNITY_PILOT_RESULTS_TEMPLATE.md`
* Implementation plan
* Gap analysis
* Test plan
* Rollout checklist
* Risk register

Mark P2 as:

`CLOSED_BY_PHASE_14R_8`

Do not mark:

* Browser E2E as passed
* Clinician pilot as completed
* Wide suite as green
* Environment reconciliation as validated at scale
* Guarded pilot as approved
* Production rollout as approved

## Regression strategy

Run focused tests for:

* New P2 suite
* Phase 14R.6–14R.7 snapshot/preview suites
* Consultation completion and reopen suites
* Maternity summary/history/print suites
* Gynaecology completion
* Admission and Emergency integration
* Snapshot append-only protections
* Query-count contracts
* Billing non-posting regression

Do not run the full wide suite repeatedly.

The wide suite currently OOMs at the project’s intentional 512 MB limit around 60%, and the identical failure was reproduced with the Phase 14R.7 changes stashed.

Do not:

* Raise the limit secretly
* Change `RouteLoadMemoryTest`
* Create a fake green baseline
* Add current failures to a baseline
* Claim the wide suite passed

At completion, either:

* Run one final wide suite only if the separate memory-infrastructure issue has already been resolved; or
* Retain the existing measured R2 status and report the wide suite as incomplete/pre-existing

P2 closure must be supported by targeted regression, not by misrepresenting the incomplete wide suite.

## Documentation deliverables

Create:

* `OBGYN_MATERNITY_SNAPSHOT_COMPLETION_IDENTITY_PHASE_14R_8_REPORT.md`
* `OBGYN_MATERNITY_SNAPSHOT_COMPLETION_IDENTITY_DESIGN.md`
* `OBGYN_MATERNITY_SNAPSHOT_COMPLETION_IDENTITY_TEST_MATRIX.md`
* `OBGYN_MATERNITY_SNAPSHOT_COMPLETION_IDENTITY_ROLLBACK.md`

Document:

* Root cause
* Existing completion flow
* Chosen authoritative occurrence identity
* Rejected alternatives
* Schema changes
* Transaction boundary
* Idempotency key
* Concurrency handling
* Crash/retry behavior
* Legacy compatibility
* Hash compatibility
* Query behavior
* Tests
* Remaining pilot risks

## Independent review

Use an independent reviewer after implementation.

The reviewer must verify:

* Same-second reopen/recompletion creates a new version.
* Ordinary repeated completion remains idempotent.
* Completion identity does not depend solely on timestamps.
* Existing hashes remain unchanged.
* Snapshots remain append-only.
* No historical identity is fabricated.
* Concurrent requests cannot create duplicate versions.
* Retry resolves verified lineage.
* No business logic moved into JavaScript.
* No billing behavior changed.
* No flag was enabled.
* No clinician sign-off was fabricated.
* Wide-suite status remains honestly reported.

## Exit criteria

Phase 14R.8 passes only when:

1. A durable completion-occurrence identity exists.
2. Reopen causes the next completion to use a new occurrence.
3. Same-second reopen/recompletion creates v2.
4. Multiple same-second cycles create v3 and later versions.
5. Repeat completion without reopen remains idempotent.
6. Concurrent capture creates at most one snapshot per occurrence.
7. Version allocation is transactionally safe.
8. Failure/retry behavior is deterministic.
9. Existing snapshots and hashes remain unchanged.
10. Legacy references remain readable.
11. Snapshot append-only guarantees remain enforced.
12. Summary, preview and print behavior remain consistent.
13. Feature-off behavior remains unchanged.
14. Query contracts remain within the established baseline.
15. Billing rows/events created equal zero.
16. Feature flags changed equal zero.
17. Focused regression passes with no new failure.
18. Independent review reports no Critical or High finding.
19. P2 is removed from the open production-readiness risks.
20. Browser E2E, clinician acceptance and wide-suite limitations remain truthfully classified.

## Completion report

Report:

* Subagents used
* Root cause confirmed
* Authoritative completion identity chosen
* Schema/migrations created
* Services changed
* Transaction/idempotency design
* Legacy compatibility
* Same-second test results
* Concurrency results
* Failure/retry results
* Hash/append-only results
* Preview/summary/print regression
* Query-count regression
* Billing non-posting proof
* Feature-flag state
* Focused test totals
* Wide-suite status
* Independent-review findings
* Whether P2 is closed
* Whether the clinician pilot may continue
* Whether guarded pilot remains blocked
* Whether Phase 14.2 remains blocked or may begin
* The exact recommended next prompt
