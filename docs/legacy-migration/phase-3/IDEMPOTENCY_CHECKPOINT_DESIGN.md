# Idempotency and checkpoint design

Idempotency keys are versioned HMAC tokens over domain, atomic-unit type, protected source/group coordinate, pinned snapshots and contract/transformation versions. One compatible outcome is enforceable per key. Changed lineage does not overwrite history and fails closed.

Tables and repositories exist for durable atomic intents and checkpoints, and the recovery classifier requires expected prior state and attempt number. A success checkpoint is valid only when intended target facts, protected ledgers and mandatory reconciliation agree. Completed work must not be replayed; a missing checkpoint after a complete atomic commit may be repaired only after exact verification.

Phase 3 does not yet provide a persistent `AtomicRecoveryJournal`/`CompareAndSetStateStore` adapter binding the classifier to those tables. The ten boundaries are policy/classifier tests, not durable crash/restart tests. Recovery execution therefore remains blocked.

The state vocabulary is the 24-value Phase 2F state matrix. No convenient default or implicit transition is allowed.
