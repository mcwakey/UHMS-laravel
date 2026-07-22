# Rollback and compensation design

Recovery preserves the ten Phase 2F crash boundaries. Before commit, use transaction rollback where proven. After a complete atomic commit, repair missing metadata/checkpoints without replaying target work. Partial durable facts enter `COMPENSATION_REQUIRED`; missing or failed mandatory equations enter `RECONCILIATION_FAILED`.

Compensation is explicit, reviewed and unit-specific. Hard deletion is not a universal rollback. Existing-target rows and children are immutable. A committed patient number is never rewound, recycled or replaced; every consumption is committed, transactionally released or explained.

Recovery audit compares intent, write-set, mapping, provenance, reconciliation and checkpoint checksums. Conflicting lineage or changed snapshots blocks resume.

Phase 3B binds the coordinator to a protected persistent journal and concrete compare-and-set store. Recovery observations are derived from verified durable intent, crosswalk, provenance, reconciliation, reservation, checkpoint and compensation records; caller-authored recovery booleans are rejected. Sealed decisions replay deterministically after restart, illegal or unchanged CAS edges are rejected by both application and database controls, completed work is not replayed, and compatible missing checkpoints are repaired without target replay.

All ten Phase 2F crash boundaries pass reconnect-based durable tests. Compensation remains an explicit classified record, not an executable domain undo operation in Phase 3B.
