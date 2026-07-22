# MariaDB allocator verification

## Status

The concrete `LaravelNumberReservationStore` was verified on 2026-07-22 on an independently attested, disposable, loopback-only MariaDB 10.4.32 instance. The hardened test used a fresh synthetic schema containing only sequence, idempotency, foundation-reservation and `phase3b_allocator_collision_namespace` fixtures. It created no `patients`, archived-patient, alias or other business-domain table or row. It did not use Classic `uuhms`, the renewed target, production, `.env` or valued/shared data.

The schema was dropped by guarded teardown. The server was shut down and both its process and listener were confirmed absent. Its non-sensitive generated datadir remains inert under the local temporary directory because recursive cleanup was denied by execution policy; it contains no Classic, target or patient data. Credentials, host, port, datadir and ephemeral schema name are not recorded here.

## Fail-closed admission

`MariaDbAllocatorVerificationTest` requires all of the following before any write:

- explicit opt-in environment flag;
- a JSON disposable-instance attestation whose file SHA-256 is independently supplied;
- an unexpired attestation purpose, exact allow-listed schema and nonblank authority reference;
- a physical-identity hash matching a direct observation of MariaDB version, server ID, hostname and port before schema creation;
- MariaDB version beginning `10.4.`;
- a fresh schema name matching the synthetic `phase3b_allocator_*` allow-list;
- a dedicated connection different from both Classic source and target connections;
- `legacy-migration.disposable_verification` identity approval;
- a `ProtectedStoreAccessSession` whose exact purpose is `mariadb_allocator_verification`;
- an injected connection-specific allocation write guard.

Missing or mismatched evidence blocks the write. The default application configuration remains disabled.

## Concrete evidence

The hardened source passed one focused MariaDB test with 44 assertions. It exercised:

- two independent PHP/PHPUnit processes contending on the same sequence coordinate with different lineages;
- an exact same-idempotency-key rerun from two processes;
- two fresh lineages contending concurrently on independent 2027 and 2029 period coordinates;
- rejection of a different protected-source lineage attempting to reuse an existing patient-core reservation;
- candidate collision rejection through the isolated synthetic collision namespace abstraction;
- interruption before reservation persistence;
- interruption after reservation insert and before sequence CAS;
- interruption after sequence CAS and before transaction commit;
- transaction rollback of reservation and sequence together;
- no replacement allocation and one reservation per lineage;
- exact positive unique ordinal coverage and reconciliation.
- eleven keyed idempotency-parent envelopes and six keyed reservation envelopes, all bound to the exact run, source snapshot and target-collision snapshot;
- deadlock retry and fail-closed connection-loss behaviour.
- fail-closed rejection, before sequence locking, when the allocation, recovery, protected-security or reservation-repository connection differs; the mismatch created zero sequence and reservation rows.
- durable protected records and transition results must retain the exact protected-security connection; reservation verification and consumption classification recheck composition before access or mutation.

Observed integrity references:

| Evidence | SHA-256 |
|---|---|
| Verification test source | `d58cf9cf0565bed179b0b9eacd20bfe8419074a84f60d7734bdc75ba23b1fd94` |
| Worker test source | `b04710a09d9adb2826981b440b92c7819d22a553bc5b40bee6eaa855495ccacf` |
| Synthetic harness source | `a1f3f978246a98d0538997f023b07b61a614a491d75ccebeb86a925a14fb07a6` |
| Reservation repository source | `52702208f2f28ed7c7fa10d593d6fd4b8b110d98de890832fa43c43c040975fd` |
| Protected-security repository source | `f4355e2ca6f8c379e3579c3d4dc23209f1a4849f99ae761154c4d0432c82ca48` |
| Recovery store source | `34a8c57c70f7fd4f2c98592429c07f5583e8dc6e2f7cc4a20e2d53b1ee43cf61` |
| Allocation store source | `4c1f012c6d54fd5a23c682f92b72d16f3ef68c0c1d1e6d5191d247531dc71cee` |
| Crosswalk resolver source | `7bdb3553058fff07c5f3b578449efdf89135611518e5027ccc8af2cc84cb678a` |
| Independent attestation file | `ae7474e635860cabf16fd290e65da80c3791317e0926b31059616a10b69da970` |
| Directly observed physical identity | `408684e66cdff4d239bc1dbbaa295a869c7baf851926af8b2b0e96ffbde841ab` |
| Execution reference | `phase3b-allocator-mariadb-10.4.32-20260722-current-source` |
| Canonical concurrency result | `6d63d23c5781273a402befba765ffefd977d6d7eac3ef135dce218258dcf28c9` |
| Canonical rollback result | `33d58b026d0a22ef20e308d38b2b1570d777bb4b2a7ae1348e71f490aeb59056` |

The observed 2026 coordinate closed with ordinals 1, 2 and 3; the separate 2027 and 2029 coordinates each closed at 1, and deadlock-retry coordinate 2028 closed at 1. Same-lineage contention retained one ordinal. All three injected failures left the durable 2026 sequence at 3 and created no reservation. No unexplained ordinal remained. Guarded teardown confirmed zero remaining schemas, and the disposable server was then stopped with no listener or process remaining.

`SequenceReconciler` now rejects duplicate lineage, duplicate ordinal, zero/negative ordinal, out-of-range ordinal, missing ordinal, operational-count mismatch and operational consumption without exact ordinal evidence. Transactionally released ordinals do not explain a durable closing range.

## Authorization boundary

This proof authorizes no real reservation. `Phase3AllocationWriteGuard` remains the default and rejects application reservations. The disposable verification exception is test-only, purpose-scoped and identity-bound. Patient-number allocation in Phase 4A or any importer still requires a separate authorization after the complete Phase 3B review gate.
