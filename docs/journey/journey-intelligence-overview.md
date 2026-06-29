# Patient Journey Intelligence — Architecture Overview (Phase 9 Closure)

The Phase 9 subsystem answers, end-to-end: **where is the patient, why are they
delayed, who must act, who's blocking whom, who owns it, who's notified, who escalates,
how are we doing, what's likely to breach, and were we right?** Everything is derived
from existing operational records; only operational coordination state and *aggregate*
analytics/outcomes are persisted.

## Phases

| Phase | Question | Key delivery |
|---|---|---|
| 9.1 | Where is the patient? | journey stages, timeline, location |
| 9.2 | Why delayed? | delay-cause resolution |
| 9.3 | Who acts & where? | action worklists + safe deep links |
| 9.4 | Who blocks whom + SLA? | cross-department handoffs + SLA |
| 9.5 | Who owns it & when escalate? | assignment/acknowledge/escalation |
| 9.6 | Notify + auto-escalate + cleanup | notifications, scheduled sweep, stale dismiss |
| 9.7 | Exact routing + preferences | supervisor routing, per-event prefs, unassigned sweep |
| 9.8 | How are we doing over time? | aggregate snapshots + SLA reports |
| 9.9 | What's likely to breach? | explainable risk scoring + ETA |
| 9.10 | Were we right? | prediction outcomes + accuracy + closure |

## Core services (`app/Services/Journey`)

`PatientJourneyService · JourneyDelayService · JourneyDelayCauseResolver ·
JourneyTimelineBuilder · JourneyActionResolver · JourneyActionLinkResolver ·
JourneyWorklistService · JourneyHandoffResolver · JourneySlaService ·
JourneyHandoffWorklistService · JourneyHandoffAssignmentService · JourneyEscalationService ·
JourneyAssignableUserService · JourneyHandoffNotificationService ·
JourneyNotificationRecipientResolver · JourneySupervisorResolver ·
JourneyNotificationPreferenceService · JourneyBottleneckService ·
JourneyAnalyticsMetricRegistry · JourneyAnalyticsSnapshotService · JourneyAnalyticsQueryService ·
JourneyAnalyticsExportService · JourneyPredictionBaselineService · JourneyRiskScoringService ·
JourneyEtaService · JourneyEtaDistributionService · JourneyPredictionService ·
JourneyPredictionOutcomeService · JourneyPredictionEvaluationService ·
JourneyPredictionAccuracyService · JourneyPredictiveEscalationAdvisor`

## Data tables (persisted)

- `journey_handoff_assignments` — ownership/escalation coordination state (9.5–9.7)
- `journey_notification_preferences` — per-user/event channel prefs (9.7)
- `departments.supervisor_user_id / escalation_user_id` — routing (9.7)
- `journey_flow_snapshots` — aggregate operational analytics (9.8)
- `journey_prediction_outcomes` — hashed prediction-vs-actual (9.10)

No full patient journey, no patient-identifiable analytics/prediction display data.

## Routes

`GET admin/journey/worklist` (+ `/refresh`), `admin/journey/handoffs/{claim,assign,
{assignment}/acknowledge,{assignment}/resolve}`, `GET admin/journey/analytics` (+
`/export`), `GET/PUT admin/settings/journey-notifications`. Patient widget on the visit
page.

## Commands & schedule

- `journey:handoffs:escalate` (`--include-unassigned`) — every 5 min
- `journey:analytics:snapshot` — daily 00:30
- `journey:predictions:check` — on demand (alerts off by default)
- `journey:predictions:evaluate` (`--capture` hourly, `--evaluate` daily 01:00)

## Permissions

Phase 8 capability profiles (consultation/investigation/pharmacy/ward/financial/stock
access via permission OR department-type membership), plus `journey.oversight` and
`journey.predictions.view`.

## Config — `config/journey.php`

thresholds · active_statuses · cause_sla · sla · worklist_refresh · notifications ·
handoff_escalation · escalation_policy · notification_digest · analytics_snapshot ·
analytics · prediction · prediction_alerts · prediction_accuracy ·
predictive_escalation.

## Audit events (`LogModule::CLINICAL_TASKS`)

`JOURNEY_HANDOFF_{CLAIMED,ASSIGNED,ACKNOWLEDGED,RESOLVED,DISMISSED,ESCALATED,NOTIFIED} ·
JOURNEY_HANDOFF_ESCALATION_RUN · JOURNEY_DEPARTMENT_SUPERVISOR_CHANGED ·
JOURNEY_NOTIFICATION_PREFERENCES_UPDATED · JOURNEY_ANALYTICS_SNAPSHOT_RUN ·
JOURNEY_PREDICTION_CHECK_RUN · JOURNEY_PREDICTION_EVALUATE_RUN`.

## Extension points

- Per-department (id-level) snapshots → richer baselines + drilldowns (columns exist).
- ML-tuned risk weights (keep the explainable reason + confidence contract).
- Bounded auto-actions once accuracy is proven (advisor → action, config-gated).
- Hourly granularity, median/percentile ETAs, websocket live worklists, digest scheduler.

## Safe future ML path

The risk score decomposes into named, config-weighted factors with a reason +
confidence, and Phase 9.10 measures precision/recall/ETA error. A model can later
replace the weights **without** breaking the explainable contract or the accuracy
feedback loop — predictions stay estimates, never auto-mutating clinical workflow.

## Test coverage

**169 journey feature tests** across stages, delay, worklists, handoffs, SLA,
assignment/escalation, notifications, supervisor routing, preferences, analytics
snapshots/queries/reports, prediction baseline/scoring/ETA/service/command, and
prediction outcomes/accuracy/evaluation/drilldown/closure — all green, with the
department dashboard held **under 40 queries**.
