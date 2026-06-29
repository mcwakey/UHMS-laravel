# Predictive Escalation (Phase 9.10) — Advisory Only

`JourneyPredictiveEscalationAdvisor` recommends a coordination action from a
prediction but **never assigns, escalates or notifies**. Every recommendation
requires a manual decision.

```php
adviseFor(JourneyRiskPrediction $p): [
    'recommendation' => 'watch | notify_supervisor | prioritize | pre_assign',
    'reason' => $p->riskReason,          // explainable
    'auto_enabled' => false,             // journey.predictive_escalation.enabled (default false)
    'manual_action_required' => true,    // always
]
```

## Recommendation by risk level

| Risk | Recommendation |
|---|---|
| CRITICAL | pre-assign a candidate |
| HIGH | notify supervisor |
| MEDIUM | prioritize on the worklist |
| LOW | watch |

## Why advisory, not automatic

Auto-acting on a prediction (which is an **estimate**, see
[risk-scoring.md](risk-scoring.md)) would risk over-assignment and alert fatigue, and
could mutate clinical coordination on a probabilistic signal. So Phase 9.10 keeps it
advisory: the advisor surfaces *what a human could do*, gated by
`journey.predictive_escalation.enabled` (false), and performs **no** workflow mutation
or notification. A future phase could enable bounded auto-actions once accuracy
(Phase 9.10 metrics) is proven on real data.
