# Phase 9.9 — Predictive ETA, Breach Forecasting & Risk Scoring

- 9.1–9.8 built live coordination + descriptive analytics.
- **9.9 → which active handoffs are likely to breach, and what should be prioritized
  before it's too late?**

Moves UHMS from descriptive to **explainable predictive** operations — heuristics, not
black-box AI. No visit-status/workflow/KPI/dashboard changes; no patient-level
prediction storage.

## 1–2. Prediction DTO + risk-level enum

[`JourneyRiskPrediction`](app/Data/Journey/JourneyRiskPrediction.php) (immutable;
score/level/reason/confidence/ETA/priority) and
[`JourneyRiskLevel`](app/Enums/JourneyRiskLevel.php) (low/medium/high/critical +
color/icon/priorityRank/recommendedPriority, EN/FR).

## 3. Config

`journey.prediction` (weights, history_days, thresholds, cache TTLs) +
`journey.prediction_alerts` (OFF by default). Fully configurable.

## 4–6. Baseline + scoring + ETA

- [`JourneyPredictionBaselineService`](app/Services/Journey/JourneyPredictionBaselineService.php)
  — historical baselines from 9.8 snapshots with a path→to+cause→cause→global→config
  fallback, built once + cached.
- [`JourneyRiskScoringService`](app/Services/Journey/JourneyRiskScoringService.php) —
  explainable weighted 0–100 score + reason + confidence ([risk-scoring.md](docs/journey/risk-scoring.md)).
- [`JourneyEtaService`](app/Services/Journey/JourneyEtaService.php) — history-or-SLA
  time estimates ([eta.md](docs/journey/eta.md)).

## 7,13,14. Prediction service

[`JourneyPredictionService`](app/Services/Journey/JourneyPredictionService.php):
`predictForHandoff · predictForHandoffs (batch) · predictForWorklist · summaryForUser ·
summaryForDepartment`. Capability-aware, bounded, **one cached baseline read per
batch** (no per-row snapshot query), cached per scope, and **safe with no history**
(SLA fallback at low confidence).

## 8. Worklist risk integration

A Risk column (level badge + score + ETA + confidence), a `risk_level` filter and
`sort=risk` — gated by `journey.predictions.view`. Predictions clearly labelled as
estimates.

## 9. Dashboard risk insight

A compact "N likely to breach soon" signal derived from the **existing light handoff
pass** — **+0 dashboard queries** (added `near_breach` to the same scan).

## 10–12. Analytics forecast + cards + explanation

A "Risk forecast" section on `/admin/journey/analytics` (aggregate cards: likely / high
/ critical / avg remaining / top risk cause + department) from the cached prediction
summary — **aggregate only, no patient details**, gated by `journey.predictions.view`.
Every risk shows a human reason; the section carries an "estimates, not guarantees" note.

## 15–16,21. Command + alerts + audit

`journey:predictions:check` — scans, scores, reports; **alerts OFF by default** (require
`prediction_alerts.enabled` + `--notify`, reuse 9.7 routing/dedupe, no patient
identifiers). Dry-run mutates nothing; audited (`JOURNEY_PREDICTION_CHECK_RUN`).

## 17. Permission

`journey.predictions.view` (Super Admin/Admin via syncPermissions; grant to clinical/
oversight roles as needed).

## 18. Tests

`JourneyRiskScoringTest` (8), `JourneyPredictionBaselineTest` (3),
`JourneyPredictionServiceTest` (4), `JourneyPredictionUiTest` (3),
`JourneyPredictionCommandTest` (4) — **22 new; 148 journey tests total**: exact/fallback/
no-history baselines; low/medium/high/critical scoring, elapsed-ratio, unassigned >
acknowledged, escalation + breach-rate factors, confidence; ETA with/without history;
worklist risk badges (shown only with permission); analytics renders; command dry-run /
audit / **alerts-off-by-default** / limit.

## 19. Query impact

- **Dashboard: +0** beyond Phase 9.8 (the risk signal reuses the journey-handoff light
  pass) — stays **28, under 40**.
- **Worklist:** +1 cached baseline read for the whole displayed batch; no per-row
  snapshot query — same shape as Phase 9.8.
- **Analytics:** one cached prediction summary (60 s); reads snapshots, never rebuilds.
- **Command:** bounded by `--limit`; dry-run mutates nothing.
- **Verification:** 148 journey + 65 dashboard tests pass · parity OK · localisation
  audit 0 · logs:audit no gaps · views compile · 10 routes · `git diff --check` clean ·
  no patient-level leakage.

## Remaining opportunities (Phase 9.10+)

- **ML-tuned weights** trained on snapshot history (keep the explainable contract).
- **Per-department baselines** + drill-down (snapshot columns already exist).
- **Median ETAs** + hourly granularity + confidence intervals.
- **Predictive escalation**: pre-emptively assign/route the highest-risk handoffs.
- **Forecast accuracy tracking**: compare predicted vs actual breaches over time.
