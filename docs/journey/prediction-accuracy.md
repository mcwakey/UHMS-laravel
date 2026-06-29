# Prediction Accuracy (Phase 9.10)

`JourneyPredictionAccuracyService` turns evaluated outcomes into trust metrics. A
prediction **"calls a breach"** when its level is HIGH or CRITICAL.

## Confusion matrix

| | actual breach | actual no-breach |
|---|---|---|
| predicted breach (high/critical) | TP | FP (false alarm) |
| predicted no-breach (low/medium) | FN (miss) | TN |

## Formulas (all zero-denominator safe → 0)

```
precision        = TP / (TP + FP)     of called breaches, how many breached
recall           = TP / (TP + FN)     of real breaches, how many we caught
false_alarm_rate = FP / (FP + TN)     of non-breaches, how many we cried wolf
miss_rate        = FN / (FN + TP)     of real breaches, how many we missed
overall_accuracy = (TP + TN) / total
eta_error_avg    = avg(|predicted_remaining − actual_minutes_to_resolve|)
```

Computed in **one SQL query** (CASE sums). Breakdowns by **confidence** and **risk
level**, plus **worst paths by error** (FP + FN). Period comparison
(`comparePeriods`) is zero-denominator safe.

## How to read it

- **High precision, low recall** → we rarely cry wolf but miss real breaches → loosen
  the threshold / raise breach-rate weight.
- **High recall, low precision** → we catch breaches but over-alarm → tighten.
- **High ETA error** → baselines for those paths need more history.

## Permissions, scope, cache

`journey.predictions.view` required. Oversight sees hospital-wide; others are scoped
to their capability domains (`scope_types`). Results cached per scope+filter — no
cross-user leakage. The dashboard chip (`dashboardAccuracy`) is one cached query,
shown only with permission **and** evaluated data.

## Surfaces

- **Analytics** — a "Prediction accuracy" section (precision / recall / false-alarm /
  miss / ETA error + comparison + worst paths). Aggregate-only, no patient details.
- **Dashboard** — a compact "Prediction accuracy: X%" chip (**+1 cached query**).
- **Export** — `prediction_accuracy` / `risk_paths` CSV (aggregate only).
