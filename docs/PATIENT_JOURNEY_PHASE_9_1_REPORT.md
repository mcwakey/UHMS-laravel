# Phase 9.1 — Patient Journey Intelligence Foundation

Answers **"Where is the patient?"** — current stage, history, next step, how long
they've waited, whether they're delayed, and which department owns the delay.
**Derived entirely from existing records — no new tables, no workflow changes.**

## Data sources (reused)

- `visits.status` (the `VisitStatus` workflow position) + `visits.created_at` / `checked_out_at`
- `visit_status_logs` (timestamped transitions → durations + timeline)
- `lab_requests`, `procedure_requests`, `prescriptions`, `admissions` (all carry `visit_id`)
- `departments` (current location + ownership)

## 1. Journey stage definitions — `App\Enums\PatientJourneyStage`

Ordered, translatable (`lang/{en,fr}/journey.php`), configurable: REGISTERED →
CHECKED_IN → CONSULTATION → INVESTIGATION → PROCEDURE → TREATMENT → PHARMACY →
ADMISSION → DISCHARGE → COMPLETED. `fromVisitStatus()` maps the 28 visit statuses
onto stages; terminal statuses (cancelled/no-show/abandoned/deceased/rescheduled)
exit the journey.

## 2. Journey service — `App\Services\Journey\PatientJourneyService`

`snapshot($visit)` returns: `current_stage`, `current_status`, `completed_stages`,
`next_stage`, `is_terminal`/`is_completed`, `relevant_stages`, `touched_stages`,
`started_at`, `entered_current_at`, and `location` (department + type + "since").
A stage is **relevant** if it's a core step or has evidence (a related record /
status it passed through). Convenience accessors: `currentStage`, `completedStages`,
`nextStage`, `location`.

## 3. Timeline — `JourneyTimelineBuilder`

Ordered stages each marked **completed / active / waiting / skipped** with minutes
spent. "Skipped" = a relevant earlier stage the patient never actually entered.

## 4. Delay rules — `JourneyDelayService` + `config/journey.php`

- `currentDelay($visit)` → elapsed minutes in the current stage + **normal / delayed
  / critical** against configurable thresholds (consultation 60/120, investigation
  120/240, pharmacy 45/90, …).
- `blockedStage($visit)` → the current stage when delayed/critical.
- `durations($visit)` → minutes per stage (from the log timeline) + total visit time.

Thresholds live in `config/journey.php` and are fully overridable.

## 5. Department bottlenecks — `JourneyBottleneckService`

`bottlenecks()` aggregates **waiting / delayed / average wait per department** from a
**single** portable query over active visits (bounded set, aggregated in PHP so each
department uses its own stage threshold). `forUser($user)` filters to the departments
the user may see — **reusing the Phase 8.4 dashboard capabilities** — sorted
worst-first. (Live: 42 departments, e.g. General OPD 263 waiting/263 delayed.)

## 6. Patient journey widget — `partials/patient-journey-widget.blade.php`

Self-contained (pass a `$visit`); shows current stage, elapsed, status, current
location, the stage timeline, next step, and total duration. Added to the **visit
detail page** — no new navigation.

## 7. Tests

`tests/Feature/Journey/` — **12 tests**:
- `PatientJourneyTest`: stage mapping, completed/next stages, lab-driven relevance,
  terminal status, location.
- `JourneyDelayTest`: normal/delayed/critical classification, blocked stage,
  configurable thresholds, durations from the log timeline.
- `JourneyTimelineTest`: completed/active/waiting, skipped stage, completed visit.

## Safety

- ✅ **No workflow / dashboard / permission changes** — new subsystem; only the visit
  page gained an additive include + new config/lang.
- ✅ **No query explosion** — bottleneck = 1 query; per-patient snapshot ≈ 6 cheap
  reads (single page). Reuses `DepartmentSchemaCache` + dashboard capabilities.
- ✅ 12 journey tests pass · dashboard suite 65 pass (no regression) · EN/FR parity OK
  · audit 0 · views compile.

## 6. Future opportunities (Phase 9.2+)

- **Why** is the patient delayed (root-cause: awaiting result, awaiting bed, awaiting
  payment) — needs richer per-stage sub-states.
- **Which department causes systemic delays** — trend the bottleneck aggregates over
  time (would justify a lightweight persisted snapshot table, explicitly deferred).
- **Predictive flow** — expected vs actual stage durations, ETA to completion.
- **Cross-visit patient timeline** and department SLA dashboards.
- Precise "entered current stage" for the aggregate (currently `updated_at` proxy) via
  a denormalised `status_changed_at` column if/when analytics needs it.
