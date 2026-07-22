# Dry-run reporting design

Dry-run reports are nonbinding and aggregate-only. Required coordinates are opaque run and snapshot tokens, configuration/contract versions, cohort-precondition status, stage counts, classified exception counts, reconciliation verdicts and zero-write/zero-side-effect measures.

Record lists, raw identifiers, field values and free clinical/demographic text are rejected. The current builder always states `commit_authorized=false` and declares source/business writes as zero; it does not measure those counters from authoritative runtime evidence. Therefore it is a safe nonbinding formatter, not a completed dry-run evaluator, and cannot support an acceptance verdict.

Verdict hierarchy is `FAIL_CLOSED`, `BLOCKED_PREREQUISITE`, then `DRY_RUN_ACCEPTED_NOT_COMMIT_AUTHORIZED`.

Before activation, the report must require the complete Phase 2F input/output/reconciliation set and observed runtime/DB counters. Missing measurements remain `FAIL_CLOSED` or `BLOCKED_PREREQUISITE`.
