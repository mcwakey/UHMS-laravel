# Journey Predictions (Phase 9.9)

Explainable breach-risk forecasting for **active** handoffs. Descriptive analytics
(Phase 9.8) → predictive operations.

## Pipeline

```
live handoffs (9.4–9.7) + aggregate baselines (9.8 snapshots)
   → JourneyPredictionBaselineService   [cached fallback map]
   → JourneyRiskScoringService + JourneyEtaService
   → JourneyPredictionService           [batch, capability-aware, cached]
   → worklist risk column · dashboard signal · analytics forecast · command
```

## Services

- **Baseline** — historical breach rate / avg wait / avg resolve per path → to+cause →
  cause → global → config. Built once and cached (`baseline_ttl`, 30 min). See
  [risk-scoring.md](risk-scoring.md).
- **Scoring** — weighted, explainable 0–100 score + reason + confidence.
- **ETA** — remaining / resolution estimates. See [eta.md](eta.md).
- **Prediction** — `predictForHandoff · predictForHandoffs (batch) · predictForWorklist ·
  summaryForUser · summaryForDepartment`. One cached baseline read per batch — **no
  per-row snapshot query**.

## Surfaces

- **Worklist** — a Risk column (level badge + score + ETA + confidence), a `risk_level`
  filter and `sort=risk`. Gated by `journey.predictions.view`.
- **Dashboard** — a compact "N likely to breach soon" signal derived from the existing
  light handoff pass (**+0 query**).
- **Analytics** — a "Risk forecast" section (aggregate cards only; no patient details),
  gated by `journey.predictions.view`, from the cached prediction summary.
- **Command** — `journey:predictions:check` (below).

## Permissions

`journey.predictions.view` (RoleSeeder → Super Admin/Admin). Department users with a
journey capability see predictions for their domain (the worklist is already scoped);
oversight sees hospital-wide. No unauthorized predictions or deep links.

## Command

```bash
php artisan journey:predictions:check [--dry-run] [--limit=500] [--department=ID] [--risk=high] [--notify]
```

Scans active near-breach+ handoffs, scores them, reports high/critical counts. **Alerts
are OFF** unless `prediction_alerts.enabled` AND `--notify`; when on they reuse Phase
9.7 routing + dedupe, carry **no patient identifiers**, and link to the worklist.
Dry-run mutates nothing; audited (`JOURNEY_PREDICTION_CHECK_RUN`).

## Cache (no cross-user leakage)

baselines 30 min · active predictions / summary 60 s · analytics forecast 5 min. Keys
include the user scope + filters.

## Safe degradation

With little/no history the system still produces SLA-proximity risk, minutes-to-breach
and a basic priority — at **low confidence**, labelled "Not enough history — using an
SLA-based estimate."

## Limitations & future

Predictions are **estimates, not guarantees**; no patient-level prediction history is
stored. Snapshots are type-level, so baselines are domain-level. Future: ML-tuned
weights, per-department baselines, median ETAs, hourly granularity.
