# Durable recovery implementation

## Status

Phase 3B implements a persistent, foundation-only recovery journal and compare-and-set adapters. It does not implement or authorize a patient/domain importer, Cohort B, pilot execution, or a business-table write.

Confirmed implementation:

- `LaravelAtomicRecoveryJournal` and `LaravelCompareAndSetStateStore` delegate exclusively to `ProtectedRecoveryStore`; neither adapter performs a model or query-builder write.
- `ProtectedRecoveryStore` verifies the keyed envelopes for the idempotency record, run, source/target snapshots and approved contract bundle before it admits an intent, checkpoint, decision or CAS transition.
- `RecoveryRepository` supplies protected intent/checkpoint persistence. `RecoveryJournalRepository` supplies protected journal append and intent CAS operations. Every mutation creates or advances a keyed envelope and emits a keyed protected-access audit.
- migration `2026_07_22_000115_create_legacy_migration_recovery_journal.php` adds `transition_attempt_count` and append-only `legacy_migration_recovery_journal_entries`.
- every journal row is tied by restricted foreign keys to one run, atomic intent, idempotency record, source/target snapshot and contract bundle. There is no business-table foreign key and no cascade.
- journal entries bind transaction, write-set, crosswalk, provenance, reconciliation, checkpoint and compensation evidence hashes. `AuthorityBoundRecoveryJournalAttributeFactory` derives tokens, versions, classifications and contexts from verified durable coordinates, an externally keyed HMAC provider and the pinned protected-store authority; caller arrays cannot supply these values.
- compatible replay returns the recorded decision without a second journal row, checkpoint, replacement allocation or target replay.
- conflicting input lineage, checkpoint evidence or recovery classification fails closed.

## Ten restart boundaries

`DurableRecoveryJournalTest` persists pre-interruption state to a file-backed SQLite database. For every `PILOT-RESUME-001` through `PILOT-RESUME-010` boundary, process A writes durable facts and terminates abruptly with the injected crash exit; a fresh process B boots from only the database file and recovers. Same-process reconnect cases additionally repeat recovery to prove idempotency.

The current source passes 21 focused cases with 234 assertions, with one expected worker-only skip. They prove:

- ten durable restart classifications;
- one recovery journal outcome per intent/boundary;
- checkpoint-only repair creates only the compatible checkpoint;
- partial durable facts classify as `COMPENSATION_REQUIRED`;
- conflicting lineage stops;
- monotonic CAS survives adapter reconstruction;
- every intent, checkpoint, decision and CAS transition has a current keyed envelope and keyed access-audit event;
- direct model access and an unguarded repository fail closed;
- the container resolves a concrete authority-bound factory only when all recovery/protected-store/key/policy gates are enabled and mutually consistent;
- no `patients` table or business row exists in the fixture.
- recovery observes durable state before replaying a recorded decision and rejects replay when the keyed durable-evidence hash changed;
- allocator coordinates verify keyed idempotency, run, source/target snapshot and contract-bundle envelopes, including expected protected-source lineage.
- allocation-lineage resolution treats raw crosswalk rows only as lookup hints: immutable-row tampering is database-rejected, a forged later envelope is denied and audited, and an unsealed raw row cannot authorize an allocation.
- recovery, allocation, reservation, protected-envelope and crosswalk-query connections must be exactly identical before a transaction or raw lookup; a mismatched crosswalk connection fails closed without changing durable rows.

Test-file SHA-256: `35351181f64a551e4123c03a6bd7865243d4e900bce53c4737b445a2ff483603`.

## Activation boundary

The production adapters require `persistent_journal_enabled`, verified recovery authority, an exact recovery connection, full envelopes, keyed integrity, purpose-scoped access, a matching protected-access policy reference, an authoritative Phase 2F bundle hash and one usable external key reference. Defaults remain disabled. Hard deletion is never the default recovery action.

## Evidence classification

- **Confirmed:** durable foundation storage, CAS, database reconstruction tests and duplicate-free recovery behavior above.
- **Approved policy:** Phase 2F crash boundaries, no replay, checkpoint-only repair, explicit compensation and no number recycling.
- **External prerequisite:** approved runtime access context, keys and protected evidence coordinate before protected population.
- **Not authorized:** domain persistence, compensation execution, pilot execution or commit.
