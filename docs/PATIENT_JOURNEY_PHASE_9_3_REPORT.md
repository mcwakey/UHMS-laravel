# Phase 9.3 — Journey Action Worklists & Deep Links

- Phase 9.1 answered **where is the patient?**
- Phase 9.2 answered **why are they delayed?**
- Phase 9.3 answers **who must act, and where do they click?** — moving UHMS from
  passive intelligence to operational action routing. Still derived from existing
  records; no new journey tables, no visit-status/workflow changes, no dashboard
  redesign.

## 1. Journey action DTO — `App\Data\Journey\JourneyAction`

Immutable value object (primitives + enums only — no Eloquent leaks into views):
visit/patient, stage, cause, severity, owner, **action_label**, **action_status**
(`open|actionable|blocked|resolved`), **action_url**, elapsed minutes, waiting-since.
Helpers: `rank()` (critical → delayed → longest waiting), `isActionable()`, `toArray()`.

## 2. Journey action resolver — `App\Services\Journey\JourneyActionResolver`

`resolve(Visit, ?User)` reuses the 9.1/9.2 services (snapshot → delay → cause) and
adds the **action status** + **deep link**. Built only for *displayed* rows.

### 3. Action status rules

| status | meaning |
|---|---|
| `open` | delayed, action exists, but no specific resolving record (e.g. awaiting consultation / create lab request) |
| `actionable` | a concrete record + route exists (pending lab result, pending prescription, admission, payment) |
| `blocked` | cause known but a prerequisite is missing (awaiting bed but no admission; awaiting dispensing but no prescription items) |
| `resolved` | the cause no longer applies (terminal/completed) — hidden from worklists, kept for tests/audit |

## 4. Deep link resolver — `App\Services\Journey\JourneyActionLinkResolver`

Maps each cause to a **safe** resolving route. Returns null unless the route exists
**and** the user holds the *target screen's* capability — gated by the screen the
staff actually clicks (e.g. a clinician ordering a lab acts on the visit page, not
the lab screen), never the cause's "responsible" owner. Never hard-fails on a
missing route; at most one cheap (eager-load-aware) record lookup per call.

```
AWAITING_LAB_RESULT      → admin.lab.requests.show         (investigation_access)
AWAITING_DISPENSING      → admin.pharmacy.dispensing.index (pharmacy_access)
AWAITING_BED/ADMISSION   → admin.admissions.*              (ward_access)
AWAITING_PAYMENT         → admin.billing.invoices.index    (financial_access)
AWAITING_PROCEDURE       → admin.theatre.board             (consultation_access)
default (request causes) → admin.visits.show               (consultation_access)
```

## 5. Worklist service — `App\Services\Journey\JourneyWorklistService`

`forUser` / `forDepartment` / `summaryForUser`. One bounded candidate query
(capped, joined to departments for type + capability filter + proxy-delayed by
threshold), then the full resolver runs only on the **eager-loaded** displayed rows
(≤ 50), sorted worst-first. `summaryForUser` is a **light, query-free aggregation**
(candidate scan + `quickCause` + thresholds) safe for menus/dashboards.

## 6. Worklist page — `GET /admin/journey/worklist`

Capability-gated controller (403 if the user has no journey capability). Summary
cards (needs-action / critical / delayed), filters (severity / status / cause),
worst-first table: patient, visit, stage, cause, owner, severity, waiting time, next
action + status badge, and an **Open action** button only when a safe link exists
(else "Action unavailable"). Empty state. EN/FR.

## 7. Dashboard action preview

The Phase 9.2 `journey_insight` banner now also shows the **next action** for the top
cause and a **View worklist** button (deep-linked to the department) — still gated to
flow-relevant, permitted departments. Dashboard impact: **+1 scoped cached query**
(the existing insight), unchanged.

## 8. Patient widget action button

The visit-page widget's delay block now renders an **Open action** button when the
deep link is safe for the viewer; otherwise just the action text (no button) — via
the action resolver.

## 9. Menu entry

A capability-gated **Journey Worklist** item (a new `visible` callback on the sidebar
builder, `menu.journey_worklist` EN/FR) in both the standard and consultation
sidebars — hidden for users with no journey capability.

## 10. Tests

`JourneyActionLinkTest`, `JourneyActionResolverTest`, `JourneyWorklistTest`
(**15 new; 39 journey tests total**): lab/pharmacy/bed/payment links, unauthorized →
null, request-action link, open/actionable/blocked/resolved statuses, DTO fields,
capability-filtered worklist, critical-before-delayed sort, scoped `forDepartment`,
summary counts + top cause.

## 11. Query impact

- **Dashboard:** unchanged (+1 scoped cached query from 9.2; the new preview reuses it).
- **`summaryForUser`** (menu/dashboard badge): **1 query** (light path).
- **Worklist page:** one candidate query + one eager-load (6 relations), then
  loaded-relation resolution per row — ~**2 queries/row**, ~114 for a full 50-row page
  (page-only; bounded). Optimisation: the 9.1/9.2 services now prefer already-loaded
  relations, cutting per-row queries ~5× (564 → 114).
- **Verification:** 39 journey + 65 dashboard tests pass · EN/FR parity OK · audit 0 ·
  views compile · `git diff --check` clean · no workflow/status/permission changes.

## Remaining opportunities (Phase 9.4+)

- **Pagination / live refresh** on the worklist (currently bounded to 50 worst rows).
- **Deeper actionable targeting**: link straight to the specific lab *result* row or
  the prescription *dispense* action, not just the request/index screen.
- **Per-cause SLA + ETA** and breach forecasting; "claim/assign" an action to a user.
- **Cross-department handoff view**: actions *my department creates for another* vs
  *owes to others*, to expose systemic bottlenecks over time.
