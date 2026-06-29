# Phase 9.2 — Delay Root Cause & Bottleneck Intelligence

Phase 9.1 answered **"where is the patient?"**. Phase 9.2 answers **"why are they
delayed, who owns it, and what's the next action?"** — still derived entirely from
existing records (no new tables, no workflow/status changes).

## 1. Delay cause enum — `App\Enums\JourneyDelayCause`

14 causes (awaiting consultation / payment / lab request / lab result / radiology
request / radiology result / procedure / prescription / dispensing / admission /
bed / discharge / clinical review / **unknown** fallback). Translatable (EN/FR),
grouped by domain (`group()`), each maps to an `ownerType()` (responsible department
domain) and an `icon()`.

## 2. Delay cause resolver — `App\Services\Journey\JourneyDelayCauseResolver`

- `resolve($visit)` — full single-patient resolution: inspects the current stage,
  status and related records (lab requests / prescriptions / admission) to pick the
  most likely cause, the **owning department** (e.g. the lab request's target
  department), the **next action** and the severity (Phase 9.1 thresholds).
- `quickCause($status, $deptType)` — **query-free** coarse cause from status +
  department type, safe to call per active visit during aggregation.

### 3. Stage-specific logic (existing records only)

| Stage | Causes |
|---|---|
| Consultation | awaiting consultation / clinical review / payment (status `billing`) |
| Investigation | lab request (none yet) → lab result (pending request); radiology variants by dept type |
| Procedure | awaiting procedure |
| Pharmacy | prescription (none) → dispensing (pending) |
| Admission/Ward | bed (`admitting`/no bed) / admission / discharge (`discharging` or past expected) / clinical review |

## 4. Cause → action map (Task 4)

Each cause yields a short, localized action: *"Send patient to consultation"*,
*"Validate the pending laboratory result"*, *"Dispense the prescription"*, *"Assign
an available bed"*, *"Collect payment before next service"* … (`journey.action.*`).

## 5. Patient widget enhancement (Task 5)

The journey widget now shows a **delay reason / responsible department / next
action** block — but **only when the patient is delayed or critical**. No new
navigation; computed inline via the resolver, reusing the already-built snapshot/delay.

## 6. Bottleneck cause aggregation (Task 6) — `JourneyBottleneckService`

`bottlenecks()` now adds, per department, the **cause breakdown** and **top cause**
(via `quickCause`, **no extra queries**). New:
- `forDepartment($id)` — scoped single-department insight (one query).
- `summary()` — worst current bottleneck + **most common delay cause today**.
Still one bounded query, aggregated in PHP.

## 7. Department dashboard integration (Task 7)

The dashboard payload gains an optional `journey_insight` (delayed count + top cause
+ avg wait), rendered as a small banner in `_chrome`. It:
- runs **only** for flow-relevant department types and **only** when the user holds
  the matching Phase 8 capability (`insightForUser`) — finance/stores/etc. add **0**
  queries;
- adds **one scoped, cached query** for relevant types — budget held at **12–14
  (< 40)**;
- reuses existing values (no duplicate KPI computation) and Phase 8 capabilities.

## 8. Severity (Task 8)

Cause severity = the Phase 9.1 delay status (normal/delayed/critical) from
`config/journey.php` thresholds — future per-cause overrides can extend that config.

## 9. Tests

`tests/Feature/Journey/JourneyDelayCauseTest` + `JourneyBottleneckCauseTest` (12 new;
**24 journey tests total**): consultation/investigation/radiology/pharmacy/ward/
payment causes, unknown fallback, owner + action, cause aggregation, scoped
`forDepartment`, `summary` most-common cause, and capability filtering.

## 10. Safety

- ✅ No workflow / visit-status / permission changes; the only existing-surface edits
  are an additive payload key + two Blade includes.
- ✅ **24 journey tests pass · dashboard suite 65 pass (no regression)** · query budget
  12–14 (< 40) · EN/FR parity OK · audit 0 · views compile · `git diff --check` clean.
- ✅ Causes derived query-free for aggregation; per-patient resolution ≈ a few cheap
  reads on a single page.

## Remaining opportunities (Phase 9.3+)

- **Deeper root cause**: distinguish "result ready but not validated" vs "still
  processing" (needs per-result sub-states), and payment via actual unpaid invoices.
- **Trends over time**: which department/cause is *systemically* worst (would justify
  a lightweight persisted snapshot — still deferred).
- **Predictive ETA** and SLA breach forecasting per cause.
- **Actionable links**: turn each "next action" into a deep link to the resolving screen.
