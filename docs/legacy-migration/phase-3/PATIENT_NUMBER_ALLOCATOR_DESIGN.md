# Migration-safe patient-number allocator

The allocator is infrastructure only and creates no patient row. It first resolves compatible patient crosswalk and idempotency lineage, then pins prefix, pattern, width, reset period, timezone and period key. It locks the existing sequence coordinate and reserves the next renewed-UHMS value deterministically.

Classic primary keys and OPD values are never numbers. Random collision suffixes are prohibited. Collision in patient, archive or relevant alias namespaces fails closed. A successful rerun returns the exact prior allocation. A committed reservation is never replaced, decremented or recycled.

Sequence reconciliation is `opening coordinate + allocated consumptions - transactionally released consumptions = closing coordinate`, with all other consumption explicitly explained. Dry-run returns a symbolic action and changes neither sequence nor mapping state.

Phase 3 hard-blocks the concrete reservation store before any real sequence/foundation write. Sequential SQLite and in-memory rollback tests pass, but required concurrent-worker and injected-rollback proof on an approved disposable MariaDB 10.4 target is absent. The allocator is not authorized for Phase 4A or an importer.
