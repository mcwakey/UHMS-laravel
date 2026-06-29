# Phase 9.10 — Prediction Accuracy, Drilldown & Journey Intelligence Closure

- 9.9 forecast which handoffs are likely to breach.
- **9.10 → were the predictions right, where can users drill down safely, and is the
  subsystem ready to close?**

The goal is **trust**: a predictive system must measure its own accuracy. No
visit-status/workflow/KPI/dashboard changes; no patient-identifiable prediction
display data.

## 1–2. Outcome table + model

`journey_prediction_outcomes` — predicted vs actual per hashed handoff identity.
**No patient name / visit number / diagnosis**; `visit_id` is an internal evaluation
reference only. See [docs/journey/prediction-outcomes.md](docs/journey/prediction-outcomes.md).

## 3–4. Capture + evaluation services

[`JourneyPredictionOutcomeService`](app/Services/Journey/JourneyPredictionOutcomeService.php)
captures scored handoffs (SHA-256 identity, idempotent, dry-run safe).
[`JourneyPredictionEvaluationService`](app/Services/Journey/JourneyPredictionEvaluationService.php)
fills in actuals from reliable signals (assignment lifecycle + re-derived handoff);
leaves undeterminable rows **unevaluated**.

## 5. Accuracy metrics

[`JourneyPredictionAccuracyService`](app/Services/Journey/JourneyPredictionAccuracyService.php)
— precision / recall / false-alarm / miss / overall / ETA-error, one SQL query, zero-
denominator safe, breakdowns by confidence + risk level, worst paths, period
comparison. Formulas in [docs/journey/prediction-accuracy.md](docs/journey/prediction-accuracy.md).

## 6–7. Command + scheduler

`journey:predictions:evaluate --capture/--evaluate` — idempotent, bounded, dry-run
safe, audited. Scheduled: capture hourly, evaluate daily 01:00.

## 8,13. Accuracy report + comparison

A "Prediction accuracy" section on `/admin/journey/analytics` (precision/recall/false-
alarm/miss/ETA-error cards with up/down comparison + worst paths). `journey.predictions.view`
required; oversight hospital-wide, others scoped; aggregate-only.

## 9. Drilldowns

Top causes link to the filtered worklist (`tab=sla_breaches&cause=…`), filters
preserved, capability-safe.

## 10. Expanded risk explanation

The worklist risk cell now shows the explainable **reason inline** (not just a tooltip)
— directly mapping to the scoring factors.

## 11. Per-department/path baselines

Fallback now: **id-path → type-path → to-id+cause → to-type+cause → cause → global →
config**. Id-level levels are used **only when their sample is sufficient**
(`minimum_snapshot_count`) so confidence never outruns the data. Cached; no per-row
query. (Id-level data lands once per-department snapshots exist.)

## 12. Median ETA

[`JourneyEtaDistributionService`](app/Services/Journey/JourneyEtaDistributionService.php)
returns `average_eta` + `sample_size`; **median/p75 deferred** (snapshots store totals,
not distributions — returning null is honest, not faked).

## 14. Predictive escalation advisor

[`JourneyPredictiveEscalationAdvisor`](app/Services/Journey/JourneyPredictiveEscalationAdvisor.php)
— **advisory only**, never mutates/notifies; `manual_action_required` always true. See
[docs/journey/predictive-escalation.md](docs/journey/predictive-escalation.md).

## 15. Dashboard accuracy chip

"Prediction accuracy: X%" — **+1 cached query** for permission-holders only, hidden
without `journey.predictions.view` or evaluated data.

## 16–17. Export + audit

Aggregate CSV adds `risk_paths` / `prediction_accuracy` (no patient columns).
`JOURNEY_PREDICTION_EVALUATE_RUN` audited (counts + precision/recall). `logs:audit` → 0 gaps.

## 19. Tests

`JourneyPredictionOutcomeTest` (4), `JourneyPredictionAccuracyTest` (7),
`JourneyPredictionEvaluationCommandTest` (4), `JourneyPredictionDrilldownTest` (3),
`JourneyPredictionClosureTest` (3) — **21 new; 169 journey tests total**: capture stores
no patient name/visit number, stable hash, idempotency, dry-run; precision/recall/false-
alarm/miss/ETA-error, dept scoping, dashboard chip null/with-data; evaluation marks
breached/resolved + leaves undeterminable + audited; cause drilldown preserves filter,
accuracy hidden without permission, export no patient columns; id-level baseline used
when sample sufficient / avoided when low; advisor recommends without mutating.

## 18. Query impact

- **Dashboard: +1** cached query (accuracy chip) for permission-holders only → **28,
  under 40**; **+0** for everyone else.
- **Analytics:** reads evaluated-outcome aggregates (cached); never evaluates on render.
- **Commands:** bounded by `--limit`; dry-run mutates nothing.
- **Baselines:** one cached map, no per-row query.
- **Verification:** 169 journey + 65 dashboard tests pass · parity OK · localisation
  audit 0 · logs:audit no gaps · views compile · 10 routes · `git diff --check` clean ·
  no patient-level prediction leakage · no auto predictive escalation.

## 20. Phase 9 closure

[docs/journey/journey-intelligence-overview.md](docs/journey/journey-intelligence-overview.md)
— the full 9.1→9.10 architecture: services, tables, routes, commands, schedule,
permissions, config, audit events, extension points and the safe future-ML path.

## Remaining opportunities

- Per-department snapshots → id-level baselines + drilldowns become live.
- ML-tuned weights once accuracy is proven; bounded auto-actions (advisor → action).
- Median/percentile ETAs, hourly granularity, websocket live worklists, digest scheduler.
- Forecast-accuracy trend dashboards over longer horizons.
