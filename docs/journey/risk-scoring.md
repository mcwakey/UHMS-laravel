# Journey Risk Scoring (Phase 9.9)

Explainable, deterministic breach-risk scoring — **no ML, no black box**. A 0–100
score from five weighted, inspectable factors, plus a human reason and confidence.

## Factors & weights (`config/journey.prediction.risk_weights`)

| Factor | Weight | Signal |
|---|---|---|
| elapsed_ratio | 40 | `min(1, elapsed / sla)` — SLA proximity |
| historical_breach_rate | 25 | baseline breach rate for the path/cause (0–1) |
| current_assignment_state | 15 | unassigned 1.0 · assigned 0.55 · acknowledged 0.2 |
| department_pressure | 10 | breached fraction of the destination's active handoffs |
| escalation_state | 10 | critical 1.0 · supervisor 0.6 · warning 0.3 · none 0 |

`score = Σ (factor × weight)`, capped at 100.

## Level

`fromScore`: <30 LOW · 30–54 MEDIUM · 55–79 HIGH · ≥80 CRITICAL. Then a guard:
an **already-breached** SLA can never be LOW/MEDIUM (≥ HIGH), and `critical_breach`
is always CRITICAL. Level → recommended priority (routine/watch/prioritize/urgent).

## Confidence

From the baseline **sample size**: `< low_sample_threshold` (5) → low,
`< medium_sample_threshold` (20) → medium, else high. With no history, confidence is
**low** and the estimate is SLA-based.

## Reason (human-readable, EN/FR)

The most salient signal is picked, e.g.:
- "Already in critical breach."
- "Unassigned and already breached."
- "At 92% of SLA with a high historical breach rate (68%)."
- "Unassigned and nearing SLA (74%)."
- "At 60% of SLA — estimate based on SLA only (little history)."

Predictions are always labelled as **estimates, not guarantees**.

## Why this, not AI

Every score decomposes into named factors with fixed weights from config — auditable,
tunable per deployment, and safe with sparse data. A future ML model could replace the
weights, but the explainable contract (reason + confidence) stays.
