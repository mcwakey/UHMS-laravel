# Prediction Outcomes (Phase 9.10)

`journey_prediction_outcomes` records each prediction so it can be checked against
what actually happened.

## Why the identity is hashed

The handoff identity (`visit|cause|from|to`) is stored as a **SHA-256 hash**, not in
the clear. The table holds **no patient name, no visit number, no diagnosis** — only
aggregate-comparable fields. `visit_id` is kept as an internal operational reference
(exactly as `journey_handoff_assignments` already does) **solely** so the evaluation
can re-derive the actual outcome; the table is permission-gated.

## What is stored

- bucket: `prediction_date` (+ optional hour), `handoff_identity_hash`, dept/cause dims
- predicted: `predicted_risk_level / score / minutes_to_breach / remaining_minutes / confidence`
- actual (filled by evaluation): `actual_breached / critical_breached / resolved`,
  `actual_time_to_acknowledge / resolve`, `evaluated_at`

One capture per identity+date+hour (idempotent upsert).

## Capture

`JourneyPredictionOutcomeService::captureForActiveHandoffs` scores the active
near-breach+ handoffs (reusing the Phase 9.9 prediction service) and upserts a hashed
row each. Dry-run safe; bounded by `--limit`.

## Evaluation

`JourneyPredictionEvaluationService` (≥ `evaluation_delay_hours` later) determines the
actual outcome from **reliable signals only** — the persisted assignment lifecycle +
the re-derived current handoff:

- `actual_resolved` — the handoff identity is no longer active, or a resolved assignment exists.
- `actual_breached` — already breached at capture (definitive) · still active past SLA ·
  or resolved *after* the predicted breach moment.
- When the outcome genuinely **cannot be determined**, the row is left unevaluated.

Idempotent, bounded, no patient-level leakage.

## Command

```bash
php artisan journey:predictions:evaluate [--capture] [--evaluate] [--from --to] [--department] [--cause] [--limit] [--dry-run]
```

Scheduled: capture **hourly**, evaluate **daily**. Audited (`JOURNEY_PREDICTION_EVALUATE_RUN`).
