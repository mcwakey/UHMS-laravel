# Phase 9.4 — Cross-Department Handoff & SLA Intelligence

- 9.1 → where is the patient? · 9.2 → why are they delayed? · 9.3 → who acts & where?
- **9.4 → which department is blocking which, and which actions are breaching SLA?**

Makes invisible handoff delays visible, measurable and actionable — still derived
from existing records; no new journey tables, no visit-status/workflow changes, no
dashboard redesign.

## 1. Handoff DTO — `App\Data\Journey\JourneyHandoff`

Immutable (primitives + enums only): visit/patient, stage, cause, severity,
**from** department (id/name/type), **to** department (id/name/type), action label /
status / url, elapsed, **sla_minutes / sla_status / minutes_to_breach**. Helpers:
`isCrossDepartment()` (domain-aware), `isBreached()`, `rank()` (breach → severity →
waiting), `toArray()`.

## 2. Handoff resolver — `App\Services\Journey\JourneyHandoffResolver`

Reuses the Phase 9.3 `JourneyActionResolver` (cause/owner/url/severity stay
single-sourced) then derives **FROM = the patient's current department** and
**TO = the cause owner** + SLA. When the owner falls back to the current department
(non-diagnostic causes) but the resolving *domain* differs, the specific target is
unknown — it surfaces the **domain** (e.g. "Pharmacy") instead of mislabelling the
from-department as the destination.

### 3. From / To rules

| Cause | From | To |
|---|---|---|
| awaiting lab/radiology result | current dept | lab request's **target department** |
| awaiting dispensing/prescription | current dept | Pharmacy (domain) |
| awaiting bed/admission | current dept (e.g. Emergency) | Ward/Inpatient |
| awaiting payment | current dept | Finance |
| awaiting consultation/review | current dept | Consultation |

## 4. SLA service — `App\Services\Journey\JourneySlaService`

Query-free. `config/journey.php` → `cause_sla` (minutes per cause) + `sla`
tuning (near-breach 80%, critical 2×). `evaluate(cause, elapsed)` →
`within / near_breach / breached / critical_breach` + signed `minutes_to_breach`.
Used identically in the light aggregation path and the full resolver.

## 5. Handoff worklist — `App\Services\Journey\JourneyHandoffWorklistService`

`owedByDepartment` (actions my dept must perform for others) / `owedToDepartment`
(others' actions blocking my patients) / `forUser` / `summaryForUser` /
`summaryForContext` (dashboard) / `matrixForUser`. Same bounded shape as 9.3: one
candidate query, **query-free `quickCause` + SLA** for filtering/aggregation, full
resolver only on the ≤50 displayed rows, sorted worst-first (critical breach →
breached → near breach → severity → waiting). Capability-aware on **both** sides
(a user sees a handoff if they can see the FROM or the TO domain).

## 6. Handoff matrix — `matrixForUser`

`From → To → count / breached / avg wait`, grouped in PHP from the light path
(query-free), only for pairs the user is allowed to see, sorted by breached then
volume.

## 7. Worklist page tabs — `/admin/journey/worklist`

Four tabs (**My Actions · Owed By My Dept · Owed To My Dept · SLA Breaches**); only
the active tab's rows are resolved per request (summaries/matrix are query-light), so
each load stays bounded. Rows show patient, visit, from→to, cause, severity, **SLA
badge + time-to-breach**, waiting, action status + safe action button. Handoff
hotspots matrix on top. EN/FR, mobile-friendly, filters preserved per tab.

## 8. Dashboard handoff insight

The `journey_insight` banner gains a handoff line: **Owes X · Waiting on Y · Z
breached · Mostly <dept>** with deep links to the Owed-By / Owed-To tabs. Driven by a
new `journey_handoff` payload key via `summaryForContext`, which **runs zero queries
for non-journey departments** (stores/admin) and **+1 (warm) cached query** for flow
departments — exactly the budget. Hidden when empty/unauthorized.

## 9. SLA badges & visual language

Localized `journey.sla.*` badges with consistent colours: within = success, near
breach = warning, breached / critical breach = danger. No redesign — badges only.

## 10. Live-refresh foundation

The worklist header carries a **Refresh** button, a **last-updated** timestamp, and
`data-journey-refresh` / `data-poll-interval="0"` attributes (polling disabled) —
preparing Phase 9.5 without adding websockets or JS complexity now.

## 11. Localisation

EN/FR for handoff, owed-by/owed-to, SLA states, from/to, tabs, refresh, last-updated,
empty states. Parity check passes.

## 12. Tests

`JourneySlaTest`, `JourneyHandoffResolverTest`, `JourneyHandoffWorklistTest`
(**17 new; 56 journey tests total**): SLA within/near/breached/critical + signed
minutes-to-breach + rank; lab→investigation, ward→pharmacy, emergency→ward,
clinical→finance handoffs + SLA carry; owed-by / owed-to lists; capability filtering;
matrix hides unauthorized departments; summary counts.

## 13. Query impact

- **Dashboard:** **+1 warm cached query** (`summaryForContext`), **0** for non-journey
  departments; total payload 17–26 (< 40 budget).
- **`summaryForUser` / `summaryForContext` / `matrixForUser`:** one light query each.
- **Worklist page:** one candidate query + one eager-load + loaded-relation resolution
  on ≤50 rows (~114 service queries) — **no worse than Phase 9.3**; only the active
  tab is resolved.
- **Verification:** 56 journey + 65 dashboard tests pass · EN/FR parity OK · audit 0 ·
  views compile · route registered · `git diff --check` clean · no workflow / status /
  permission / KPI changes.

## Remaining opportunities (Phase 9.5+)

- **Live refresh**: activate the prepared polling (or websockets) for real-time boards.
- **Precise multi-site targeting**: resolve the exact destination department for
  non-diagnostic causes (pharmacy/ward/finance) where multiple exist.
- **Historical SLA trends**: breach rate per handoff path over time (would justify a
  lightweight persisted snapshot — still deferred).
- **Escalation/assignment**: claim a handoff, notify the owning department, escalate on
  critical breach.
