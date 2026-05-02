# UHMS Optimum Solutions Implementation Plan

**Project:** UHMS - Ultimate Hospital Management System  
**Date:** 2026-05-02  
**Purpose:** Practical implementation roadmap for workflow cleanup, modular architecture, UI consistency, SPA-like behavior, and performance.

---

## 1. Target Outcome

UHMS should become a stable, modular hospital platform where:

- core clinical and billing workflows never break
- optional modules can be disabled safely
- patient and visit information appears consistently across pages
- clinical screens behave like SPA pages without forced resets
- UI semantics are uniform across the system
- dashboards are role-aware and module-aware
- heavy queries and repeated calculations are cached, indexed, or deferred

---

## 2. Current-State Strategy

The correct short-term strategy is **incremental stabilization on top of the existing Blade application**.

Reasons:

- Blade + Bootstrap + AJAX is still the live workflow platform.
- Inertia/Vue is installed but not yet the operational shell for hospital workflows.
- Rewriting all critical screens during stabilization would add risk and slow delivery.

Therefore:

- **Phase 1 to Phase 4** should improve the existing Blade experience first.
- Inertia/Vue can later be adopted screen-by-screen once workflow ownership and UI contracts are stable.

---

## 3. Architecture Target

Target architecture:

```text
Controllers
    -> Form Requests
    -> Services
    -> Models / Repositories
    -> Events / Jobs / Notifications
```

### 3.1 Service Ownership Map

#### Keep / strengthen

- `BillingService`
- `InsuranceService`
- `ConsultationService`
- `PharmacyService`
- `ModuleService`

#### Create / extract

- `VisitWorkflowService`
- `TriageService`
- `DepartmentService`
- `ServiceCatalogService`
- `InvestigationService`
- `DashboardService`

### 3.2 Immediate Rule Changes

- `VisitService` should stop being the owner of workflow transitions.
- `BillingService` should stop mutating visit status directly.
- Controllers should stop performing inline validation where feature flows are becoming large.

---

## 4. Modular System Plan

### 4.1 Core Modules

Must never be disabled:

- auth
- users / roles
- patients
- visits
- triage
- consultation
- departments
- services
- billing
- settings
- audit logs

### 4.2 Optional Modules

Disableable with fallback behavior:

- insurance
- claims
- pharmacy
- inventory
- investigations
- analyzer
- reports
- HR
- payroll
- notifications
- medical patterns

### 4.3 What Already Exists

- `modules` table
- `Module` model
- `ModuleService`
- middleware alias
- admin module toggles
- partial sidebar module gating

### 4.4 What Must Be Added Next

- route group protection for all optional-module routes
- service-layer fallbacks for disabled modules
- one authoritative module-definition map for menus, routes, and dashboard widgets

### 4.5 Required Fallback Rules

- insurance disabled -> all billing becomes cash and carry
- pharmacy disabled -> prescriptions can be recorded, dispensing hidden/blocked
- investigations disabled -> investigation request actions hidden/blocked safely
- analyzer disabled -> manual result entry only
- claims disabled -> invoice settlement remains direct
- reports disabled -> no report menus or widgets should render

---

## 5. Workflow Cleanup Plan

## 5.1 Visit Workflow

Create `VisitWorkflowService` with methods such as:

```php
createVisit()
sendToTriage()
completeTriage()
assignConsultation()
startConsultation()
sendToInvestigation()
referToDepartment()
moveToBilling()
completeVisit()
cancelVisit()
```

### Rules

- All status changes go through this service.
- Each transition logs the state change.
- Side-effects like queue updates and notifications happen from workflow events.

## 5.2 Triage

Create `TriageService` to own:

- vitals submission
- triage score evaluation
- priority routing
- escalation to emergency or inpatient candidate

## 5.3 Consultation

Keep `ConsultationService` as the clinical record owner, but split screen concerns:

- shared header / summary UI
- async section save helpers
- reusable investigation request handling

## 5.4 Billing and Insurance

- keep monetary logic in `BillingService` and `InsuranceService`
- remove visit transition ownership from `BillingService`
- use workflow service after invoice or payment milestones

---

## 6. UI / UX Standardization Plan

## 6.1 Action Color Rules

Standardize everywhere:

- Save / Accept / Confirm / Submit -> `btn-primary`
- Cancel / Back / Close -> `btn-secondary` or `btn-light`
- View / Details -> `btn-info`
- Warning / Pause / Review -> `btn-warning`
- Delete / Reject / Remove -> `btn-danger`

### Immediate cleanup targets

- remaining `btn-purple`
- any remaining `btn-info` used as a save/accept action
- inconsistent outline variants for destructive actions

## 6.2 Shared Blade Components / Partials

Build first as Blade partials, then optionally migrate to Blade components:

- `partials/patient-visit-header`
- `partials/visit-status-badge`
- `partials/insurance-summary-badge`
- `partials/page-header`
- `partials/action-button-set`
- `partials/empty-state`
- `partials/loading-spinner`

## 6.3 Page Consistency Contract

The following should render the same way wherever relevant:

- patient identity block
- visit status badge
- assigned/current department
- insurance summary
- triage score summary

---

## 7. Sidebar Stability Plan

### Current Position

- anti-FOUC is already in place
- menu is partly module-aware

### Next Step

Move from a giant conditional Blade file to a menu-definition builder.

Target shape:

```php
[
    [
        'title' => 'Clinical',
        'module' => 'consultation',
        'permission' => 'consultations.view',
        'items' => [...],
    ],
]
```

### Benefits

- one place to manage visibility rules
- easier module toggling
- easier active-state handling
- easier menu caching

---

## 8. SPA-Like Behavior Plan

## 8.1 Short-Term Rule

For critical screens, do not wait for a full frontend rewrite.

Use the current Blade approach with AJAX/fetch to enforce:

```text
save section -> refresh only affected DOM -> keep active tab -> keep context
```

## 8.2 Immediate Priority Screens

- consultation
- visit creation / service assignment
- triage
- billing actions
- investigation request actions
- insurance selection and validation feedback

## 8.3 Implementation Pattern

- standardize one reusable JS helper for AJAX forms
- standardize one reusable tab persistence helper
- return JSON success payloads for partial updates
- use redirect responses only for full screen transitions

## 8.4 Inertia/Vue Adoption Recommendation

Once the above behavior contracts are stable, move one workflow at a time to Inertia/Vue if desired. Start with dashboards or non-clinical management screens first, not the most complex consultation flow.

---

## 9. Dashboard Expansion Plan

Create `DashboardService` with role-focused summary methods:

- `adminSummary()`
- `doctorSummary()`
- `triageSummary()`
- `cashierSummary()`
- `investigationSummary()`
- `pharmacySummary()`
- `claimsSummary()`
- `storeSummary()`

### Rules

- widgets must respect enabled modules
- dashboard queries should be cached for short TTLs
- each role only loads what it needs

### Current Implementation Status

- admin dashboard exists
- doctor dashboard exists
- shared staff dashboard exists

### Next Step

Keep the shared staff dashboard as a transitional shell, but split it into dedicated role presenters/widgets over time.

---

## 10. Performance Plan

## 10.1 Already Done

- key table indexes added
- module cache introduced
- dashboard queries are limited in some places

## 10.2 Next Actions

- cache dashboard summary cards
- cache menu/module-aware sidebar definitions
- paginate or lazy-load large history blocks
- audit list pages for eager loading consistency
- move heavy notifications/report generation into queues where needed

## 10.3 Hard Performance Rules

- no repeated insurance recalculation outside `InsuranceService`
- no repeated module/permission logic queries in every Blade branch if they can be precomputed
- no full reload for section-level clinical saves
- no unbounded “load all historical records” queries on hot pages

---

## 11. Refactoring Priorities

### Priority A - finish stabilization

- extract shared patient/visit header
- normalize remaining action buttons
- convert remaining consultation full-post actions to AJAX
- standardize success/error message handling

### Priority B - centralize workflow ownership

- introduce `VisitWorkflowService`
- reroute visit transition side-effects from billing/consultation into workflow methods
- add status log coverage for all transitions

### Priority C - strengthen modules

- route protection for all optional modules
- service-level fallbacks
- dashboard widget awareness by module

### Priority D - reduce view/controller weight

- extract big Blade sections into partials
- replace inline validation with Form Requests for large flows
- move repeated JS helpers into dedicated page scripts

---

## 12. Implementation Roadmap

## Phase 1 - Stabilization

### Status

Partially complete.

### Already completed

- module foundation
- admin module management UI
- anti-FOUC layout handling
- basic role-aware dashboard routing
- some primary button normalization
- investigation terminology cleanup in key UI

### Remaining tasks

- shared patient/visit header extraction
- remaining button normalization
- remove remaining full reload forms in consultation and other hot flows
- continue sidebar menu cleanup
- normalize page-level patient/visit information surfaces

### Acceptance criteria

- critical clinical saves do not reset page/tab context
- no primary action uses legacy color classes
- patient/visit summary is reusable and consistent

## Phase 2 - Core Workflow Refactor

- introduce `VisitWorkflowService`
- move status transitions behind one owner
- strengthen triage routing ownership

## Phase 3 - Billing and Insurance Hardening

- remove remaining workflow leakage from `BillingService`
- keep `InsuranceService` authoritative for coverage evaluation
- add lightweight insurance summary presenters for UI reuse

## Phase 4 - Consultation Overhaul

- break consultation Blade into partials/components
- unify section AJAX behavior
- move investigation routing fully async
- keep static vitals + previous history side panel pattern

## Phase 5 - Full Module Enforcement

- protect all optional-module routes
- add service-layer fallbacks
- finish menu integration from module definitions

## Phase 6 - Role Dashboards

- add role-specific dashboard builders
- make widgets module-aware
- cache summary cards

## Phase 7 - Performance Optimization

- targeted N+1 audits
- queue heavy tasks
- lazy-load large histories and modal details
- cache hot summaries and menu definitions

---

## 13. Recommended Next Engineering Slice

The best immediate next slice after writing this plan is:

1. extract a shared patient/visit header from the consultation screen
2. fix the remaining full-page consultation investigation route post
3. finish button normalization in that same slice

Why this slice first:

- it directly advances Phase 1
- it improves the most critical clinical screen
- it reduces both UI inconsistency and SPA regression in one contained change
- it is low-risk compared with a broad architectural refactor

---

## 14. Final Recommendation

Do not jump to a large rewrite. UHMS already has valuable domain structure and working services. The optimal path is to finish stabilization on the current Blade platform, centralize workflow ownership, strengthen module enforcement, and only then broaden Inertia/Vue adoption where it provides clear operational value.