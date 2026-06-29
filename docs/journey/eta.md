# Journey ETA Estimates (Phase 9.9)

`JourneyEtaService` produces explainable time estimates for an active handoff — using
the historical baseline when there's enough sample, otherwise the SLA threshold.

## Inputs

- current elapsed minutes (live)
- SLA minutes (Phase 9.4)
- baseline `avg_wait` + `avg_resolve` + `sample` (Phase 9.8 snapshots)

## Method

```
hasHistory     = baseline.sample >= minimum_snapshot_count (5)
expectedTotal  = hasHistory ? baseline.avg_wait : sla
remaining      = expectedTotal - elapsed              (negative = over expected)

estimated_remaining   = hasHistory ? max(0, remaining) : max(0, sla - elapsed)
estimated_resolution  = hasHistory ? baseline.avg_resolve : null
minutes_to_breach     = sla - elapsed                 (from the handoff; may be negative)
```

## Note keys (translatable)

| Situation | Note |
|---|---|
| no history | `sla_estimate` — "SLA-based estimate" |
| elapsed ≥ SLA | `already_breached` |
| over historical average | `over_expected` |
| otherwise | `remaining` — "~N min remaining" |

## Safety

- Never overstates precision — values are rounded estimates with a note.
- With no history, falls back to SLA proximity and a **low** confidence.
- Output strings: "~20 min remaining", "Over expected time", "Already breached",
  "Not enough history yet".
