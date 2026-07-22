# Idempotency and checkpoint design

Idempotency keys are versioned HMAC tokens over domain, atomic-unit type, protected source/group coordinate, pinned snapshots and contract/transformation versions. One compatible outcome is enforceable per key. Changed lineage does not overwrite history and fails closed.

Tables and repositories exist for durable atomic intents and checkpoints, and the recovery classifier requires expected prior state and attempt number. A success checkpoint is valid only when intended target facts, protected ledgers and mandatory reconciliation agree. Completed work must not be replayed; a missing checkpoint after a complete atomic commit may be repaired only after exact verification.

Phase 3B provides persistent `AtomicRecoveryJournal` and `CompareAndSetStateStore` adapters over the protected repositories. Observation and replay derive from keyed durable rows rather than caller declarations. Reconnect tests cover all ten crash boundaries, exact decision replay, illegal/unchanged CAS rejection, completed-unit non-replay and checkpoint-only repair.

The state vocabulary is the 24-value Phase 2F state matrix. No convenient default or implicit transition is allowed.
