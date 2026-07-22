# Rollback and compensation design

Recovery preserves the ten Phase 2F crash boundaries. Before commit, use transaction rollback where proven. After a complete atomic commit, repair missing metadata/checkpoints without replaying target work. Partial durable facts enter `COMPENSATION_REQUIRED`; missing or failed mandatory equations enter `RECONCILIATION_FAILED`.

Compensation is explicit, reviewed and unit-specific. Hard deletion is not a universal rollback. Existing-target rows and children are immutable. A committed patient number is never rewound, recycled or replaced; every consumption is committed, transactionally released or explained.

Recovery audit compares intent, write-set, mapping, provenance, reconciliation and checkpoint checksums. Conflicting lineage or changed snapshots blocks resume.

Current code implements classification, state policy, tables and repositories, but no persistent recovery journal binds them into crash/restart orchestration. No compensation action is executable in Phase 3. Durable recovery remains a blocking implementation task.
