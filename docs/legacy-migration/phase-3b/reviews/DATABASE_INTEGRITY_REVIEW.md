# Phase 3B database-integrity review

Review date: 2026-07-22  
Reviewer: independent database-integrity reviewer (no Phase 3B implementation ownership)  
Verdict: **PASS**  
Open findings: **Critical 0, High 0, Medium 0**

The Phase 3B database-integrity exit criterion is met. Phase 4A is eligible from the database-integrity perspective only. This review does not authorize an importer, protected-data population, Cohort B, a patient pilot, a domain write, a Classic write or a production write.

The final connection-atomicity correction binds sequence, reservation, envelope/audit, recovery-coordinate and crosswalk operations to one exact target connection. Mismatch fails before sequence locking or a foundation write and is covered by current disposable MariaDB evidence.

No Classic database, renewed production database, shared target or business-domain table was connected to or written during this review. Independent reviewer execution used repository inspection and synthetic file-backed/in-memory SQLite foundation fixtures. Current disposable MariaDB results were inspected from the retained, nonsecret evidence package; the reviewer did not restart or repopulate the disposable server.

## Independent verification

- Current durable recovery source SHA-256 `35351181f64a551e4123c03a6bd7865243d4e900bce53c4737b445a2ff483603`: **21 passed, 234 assertions, one expected worker-only skip**. All ten boundary cases also passed through actual abrupt subprocess termination and a separately booted recovery process.
- Current protected-store repository integration suite: **16 passed, 130 assertions**. Decisive production reservation/crosswalk slices additionally passed **2 tests, 22 assertions**.
- Current allocator unit suites: **18 passed, 59 assertions**.
- Current installation, installation-journal and foundation-schema suites: **30 passed, 323 assertions**.
- Retained current-source allocator proof: **one test, 44 assertions** on disposable MariaDB `10.4.32`; verification source SHA-256 `d58cf9cf0565bed179b0b9eacd20bfe8419074a84f60d7734bdc75ba23b1fd94`, worker `b04710a09d9adb2826981b440b92c7819d22a553bc5b40bee6eaa855495ccacf`, harness `a1f3f978246a98d0538997f023b07b61a614a491d75ccebeb86a925a14fb07a6`, reservation repository `52702208f2f28ed7c7fa10d593d6fd4b8b110d98de890832fa43c43c040975fd`, protected-security repository `f4355e2ca6f8c379e3579c3d4dc23209f1a4849f99ae761154c4d0432c82ca48`, recovery store `34a8c57c70f7fd4f2c98592429c07f5583e8dc6e2f7cc4a20e2d53b1ee43cf61`, allocation store `4c1f012c6d54fd5a23c682f92b72d16f3ef68c0c1d1e6d5191d247531dc71cee` and crosswalk resolver `7bdb3553058fff07c5f3b578449efdf89135611518e5027ccc8af2cc84cb678a`.
- The allocator evidence records execution reference `phase3b-allocator-mariadb-10.4.32-20260722-current-source`, canonical concurrency result `6d63d23c5781273a402befba765ffefd977d6d7eac3ef135dce218258dcf28c9` and canonical rollback result `33d58b026d0a22ef20e308d38b2b1570d777bb4b2a7ae1348e71f490aeb59056`.
- Retained MariaDB DDL evidence identifies **24 foundation tables, 566 columns, 53 triggers, four CHECK constraints and two generated columns**, with no foreign key to a business table and no cascading foundation foreign key.
- Canonical installed DDL hashes are `a1f17f4618ef2353474d14b88cb898bb199b9875c7b4bb7f676ab68ff3f55de9` for the 24-table `SHOW CREATE TABLE` bundle and `5e241c2e2e7b8a67f2642e016b2aa74b866248cd8000220306f501197199936b` for the 53-trigger bundle.

## Finding closure

### DBI-P3B-001 — closed — authoritative durable recovery and ten real restarts

Recovery no longer accepts caller-asserted booleans. `ProtectedRecoveryStore` reconstructs the decision facts from keyed, coordinate-bound foundation records and verifies the idempotency record, run, source/target snapshots and contract bundle before classification. A changed durable-evidence hash invalidates a recorded decision.

Each `PILOT-RESUME-001` through `PILOT-RESUME-010` test now persists synthetic pre-crash facts, terminates process A with the injected crash exit, boots process B from the database file and verifies the recovery disposition, unchanged durable-fact cardinality and one journal outcome. Partial patient-core facts reach `COMPENSATION_REQUIRED`; checkpoint-only repair creates only the compatible checkpoint; repeated recovery does not replay the unit.

### DBI-P3B-002 — closed — CAS graph enforced at every concrete boundary

The concrete store and repository call `MonotonicStateMachine::assertTransitionAllowed()`. Migration `000115` also installs `lm_intent_state_transition_guard`, which enforces the exact state graph and exact `+1` lock-version/attempt movement. Focused tests reject direct-adapter, repository and raw-SQL illegal jumps, terminal exits, unchanged-state version changes and invalid attempt increments. Actual MariaDB evidence rejects illegal `NOT_STARTED -> COMPLETED` and accepts the exact legal edge.

### DBI-P3B-003 — closed — protected production allocator composition and concrete MariaDB proof

`AuthorityBoundReservationFactory` is the production `ReservationAttributeFactory` and `ReservationProtectionFactory`; `AppServiceProvider` composes it with `ProtectedRecoveryStore`, `NumberReservationRepository` and `LaravelNumberReservationStore`. Defaults remain disabled. The factory resolves allocator lineage only through keyed-envelope verification and rejects disabled authority, a different protected source, an incompatible contract bundle or missing snapshot lineage.

`NumberReservationRepository::reserveProtected()` treats the raw parent row only as a coordinate hint, verifies the patient-core idempotency parent through its keyed envelope and audit boundary, cross-checks the verified projection against the hinted coordinate, and only then admits a reservation. A forged latest envelope and an unsealed raw parent are rejected without creating a reservation. Every surviving reservation is sealed to its run, source snapshot and target-collision snapshot. The persisted expected-source comparison uses the protected token's lookup digest, so an exact rerun succeeds while a different source lineage fails.

`LaravelCrosswalkAllocationLineageResolver` likewise uses a raw row only to locate the candidate before verifying the complete crosswalk through `ProtectedRecordSecurityRepository`. Immutable-row mutation is database-rejected; a forged later envelope is denied and audited; an unsealed crosswalk cannot authorize allocation lineage.

The production composition now requires the allocation store, protected reservation repository, protected-security repository, recovery store and crosswalk resolver to name the same exact default target connection. The store checks this boundary before lookup, sequence locking or persistence. The current MariaDB mismatch case creates zero sequence and reservation rows.

Protected security also rejects a durable model or transition result whose model connection differs from the repository's exact protected connection. Reservation verification and consumption classification recheck the composition before access or mutation.

The current MariaDB test installs the actual `000110`-`000115` foundation shape, uses synthetic run/snapshot/idempotency coordinates and the protected repository, and creates eleven sealed idempotency-parent envelopes plus six sealed reservation envelopes. All 17 bind the exact run/source/target coordinate. It creates no patient/business table or row.

### DBI-P3B-004 — closed — identity-bound and restart-safe DDL composition

Installation authority separates immutable physical-server approval from a manifest-aware, exact partial-state contract. `InstallationSessionCapability` binds the physical identity, active connection instance, audit reference and manifest hashes. The executor re-observes and compares the active physical connection before every operation; a session presented after reconnect is rejected.

`SafeFoundationDdlInstaller` records exact adopted objects when MariaDB committed DDL before the verified journal append, executes only absent allow-listed operations and refuses drift, conflicts and unknown reserved objects. First-, middle- and last-operation current-signature restarts all converge to the exact schema and then plan zero operations.

### DBI-P3B-005 — closed — allocator adversarial cases

The retained current-source proof includes:

- two independent processes contending on one coordinate with different lineages;
- exact same-lineage rerun without replacement allocation;
- two fresh lineages concurrently using independent 2027 and 2029 coordinates;
- collision refusal;
- faults before insert, after insert/before sequence CAS and after sequence CAS/before commit;
- deterministic deadlock retry and fail-closed connection-loss simulation; and
- exact positive, unique, in-range ordinal reconciliation without an unexplained gap.

The 2026 coordinate closes at 3, the 2027 and 2029 coordinates at 1, and the deadlock-retry 2028 coordinate at 1. Failed transactions create no reservation and do not advance the durable 2026 sequence.

### DBI-P3B-006 — closed — complete supported DDL recovery/refusal matrix

Current MariaDB evidence now spans boundaries in `000110` through `000115`, including interruption before and after trigger work, migration-ledger adoption and zero-operation rerun. Additional actual cases cover missing table, column, trigger and ledger; installation-journal deletion refusal; index and foreign-key drift refusal; metadata-lock timeout; killed connection followed by re-observation/re-authorization; exact-statement retry; and destructive/out-of-namespace SQL refusal.

Indexes and constraints are emitted inside one atomic `CREATE TABLE` statement, so there is no executable partial boundary between them without bypassing the approved manifest. The evidence correctly tests removal as nonrepairable enclosing-table drift instead of claiming an impossible partial statement. A repeatable metadata-lock timeout is recorded rather than inventing a nondeterministic DDL deadlock.

## Constraints independently confirmed

- Foundation migrations and immutable DDL manifests create only `legacy_migration_*` stores plus the isolated patient-number sequence primitive required by the allocator contract.
- Foundation foreign keys use restricted deletion; no foundation foreign key points to a business table and no business cascade exists.
- Recovery and installation journals are append-only; direct update/delete is rejected.
- Run/recovery state changes require legal state edges and compare-and-set counters.
- Reservation and sequence CAS are one transaction; rollback keeps them consistent.
- Direct reservation and crosswalk lineage paths cannot authorize a protected write from an unsealed or tampered parent.
- Sequence reconciliation rejects duplicate lineage, duplicate ordinal, nonpositive/out-of-range ordinal, missing coverage and operational-count mismatch.
- Partial DDL repair is explicit, version-bound, identity-bound, audited and create/add-only. It does not use destructive `down()`, drop a conflict or delete protected records.
- No `patients` table exists in the recovery or allocator fixtures.

## External activation prerequisites

This PASS does not satisfy deployment-specific authority. Shared-target activation still requires the owner-approved non-production physical identity/TLS contract, network denial of renewed production, externally held usable key references, installed and verified protected foundation schema, purpose-scoped protected-store authority and approved runtime-isolation authority. Their absence remains technically fail-closed and is not reclassified as a repository defect.

## Exit decision

- Critical: **0**
- High: **0**
- Medium: **0**
- Database-integrity review: **PASS**
- Phase 3B database exit criterion: **met**
- Phase 4A synthetic foundation exercise: **eligible from database-integrity perspective only**
- Protected-store population, Cohort B, patient pilot and every domain importer: **remain blocked**
