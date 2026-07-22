# Migration-safe patient-number allocator

The allocator is infrastructure only and creates no patient row. It first resolves compatible patient crosswalk and idempotency lineage, then pins prefix, pattern, width, reset period, timezone and period key. It locks the existing sequence coordinate and reserves the next renewed-UHMS value deterministically.

Classic primary keys and OPD values are never numbers. Random collision suffixes are prohibited. Collision in patient, archive or relevant alias namespaces fails closed. A successful rerun returns the exact prior allocation. A committed reservation is never replaced, decremented or recycled.

Sequence reconciliation is `opening coordinate + allocated consumptions - transactionally released consumptions = closing coordinate`, with all other consumption explicitly explained. Dry-run returns a symbolic action and changes neither sequence nor mapping state.

Phase 3 still hard-blocks ordinary concrete reservation writes. The protected MariaDB path additionally requires the real protected repository, a purpose-scoped access context and a keyed envelope; a non-SQLite call without that boundary fails closed.

Phase 3B verified the allocator on an explicitly attested disposable MariaDB 10.4.32 instance using the two immutable foundation DDL manifests and all 24 `legacy_migration_*` tables. Evidence covered full run/source-snapshot/target-snapshot/idempotency coordinates, repository-sealed reservations, same-coordinate and different-coordinate workers, deterministic rerun, exact ordinal coverage, collision rollback, three injected transaction interruption points, one-time deadlock retry and connection-loss failure without sequence or reservation leakage. The schema was dropped by test teardown and the disposable server was shut down after verification. This is foundation evidence only; it does not authorize an importer or patient pilot.
