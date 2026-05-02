# UHMS Full Project Analysis Report

**Project:** UHMS - Ultimate Hospital Management System  
**Date:** 2026-05-02  
**Scope:** Current project structure, workflow analysis, repetition/coupling audit, UI consistency, sidebar behavior, dashboards, performance, and module boundaries.

---

## 1. Executive Summary

UHMS has evolved into a feature-rich Laravel 12 application with strong domain coverage across patients, visits, triage, consultation, billing, insurance, investigations, pharmacy, claims, store, HR, and reporting. The current problem is no longer missing features. The main engineering risk is that workflow rules, UI behavior, and module boundaries are enforced inconsistently across controllers, services, Blade views, and JavaScript.

The codebase is already moving in the right direction:

- A `Module` model, `modules` table, `ModuleService`, middleware alias, sidebar integration, and an admin modules screen now exist.
- Sidebar flash-of-unstyled-content mitigation has been added in the main layout.
- Submit/Save button normalization has started.
- Role-aware routing now supports admin, doctor, and shared staff dashboard entry points.
- Performance indexes have been added on several hot tables.

The remaining work is to finish Phase 1 stabilization cleanly:

- standardize UI into reusable shared pieces
- remove remaining full-page workflow posts on clinical screens
- centralize workflow transitions more strictly
- finish separating core features from optional modules
- align dashboards and patient/visit information surfaces across pages

---

## 2. Current Technical Baseline

### 2.1 Backend Stack

- Laravel 12
- PHP 8.2
- MySQL / MariaDB
- Spatie Permission
- Spatie Activity Log
- Maatwebsite Excel
- Breeze installed as a package, but not used as the actual app shell

### 2.2 Frontend Stack

The live application is still primarily:

- Blade views
- Bootstrap 5
- jQuery + fetch-based AJAX
- Tabler Icons
- template-driven JS from `resources/js/script.js`

Additional packages now exist but are not yet the primary UI runtime for hospital workflows:

- Vue 3
- `@inertiajs/vue3`
- `inertiajs/inertia-laravel`
- `vite-plugin-pwa`

Conclusion: the project is currently **Blade-first with AJAX enhancements**, not an Inertia/Vue SPA yet. That matters because Phase 1 should improve SPA-like behavior using the existing frontend approach instead of attempting a broad migration immediately.

### 2.3 Current Structure

Key application surfaces:

- `app/Http/Controllers/` contains broad admin + doctor controllers
- `app/Services/` contains a service layer, but responsibilities are still mixed in several services
- `resources/views/` contains most UI screens in Blade
- `routes/web.php` is large and feature-dense
- `resources/js/` still contains template-centric JS rather than page/component-local modules

---

## 3. Current Architecture Assessment

### 3.1 Positive Findings

- Controllers generally use services for the heavier business logic more than earlier iterations.
- Billing and insurance each already have dedicated services.
- Consultation CRUD actions are largely AJAX-capable.
- The modules system now has foundational persistence and toggle logic.
- Several pages already use eager loading correctly.

### 3.2 Architectural Gaps

The target architecture is:

```text
Controllers
    -> Form Requests
    -> Services
    -> Models / Repositories
    -> Events / Jobs / Notifications
```

The current state still diverges from that target in these ways:

- `VisitService` mixes visit creation, queue behavior, insurance resolution side-effects, and workflow/state logic.
- `BillingService` still updates visit status directly instead of delegating to a dedicated workflow engine.
- `ConsultationController` remains feature-heavy and coordinates too many screen concerns.
- `ConsultationService` is CRUD-oriented and does not fully act as a workflow orchestrator.
- `LabService` still carries legacy “lab” naming while being used as the broader investigations entry point.
- Many validations are still inline request validation blocks rather than dedicated Form Requests.

### 3.3 Missing Target Services

These target services are not yet properly established as first-class orchestrators:

- `VisitWorkflowService`
- `TriageService`
- `DepartmentService`
- `ServiceCatalogService`
- `InvestigationService`
- `DashboardService`

Existing service coverage is good, but orchestration remains spread across `VisitService`, `BillingService`, `ConsultationService`, `InsuranceService`, and `LabService`.

---

## 4. Workflow Analysis

## 4.1 Visit Workflow

### Current State

- Visit creation happens in `VisitService::create()`.
- Scheduled and walk-in visits are differentiated there.
- Walk-ins are routed to TRIAGE immediately.
- Initial status logging exists.
- Queue entry creation is triggered from the visit flow.

### Findings

- This is functional, but the workflow engine is still embedded in `VisitService` instead of a dedicated `VisitWorkflowService`.
- Status changes also occur outside a single owner service, for example from billing completion logic.
- This violates the intended rule that visit state transitions should be centralized.

### Repetition / Coupling Risk

- Visit status logic is partially domain-driven, partially convenience-driven.
- Controllers and services can still evolve their own status transition behavior over time.

### Recommendation

- Extract all transitions into `VisitWorkflowService`.
- Keep `VisitService` focused on visit CRUD, listing, doctor/service lookups, and visit setup.

---

## 4.2 Triage Workflow

### Current State

- Triage exists as a workflow stage and menu surface.
- Triage score logic is visible on the consultation screen.
- Vitals are available and surfaced in the consultation interface.

### Findings

- Triage exists in the domain, but its orchestration layer is weakly separated.
- The triage score is shown effectively, but triage routing decisions are not obviously isolated into a single service boundary.

### Recommendation

- Introduce `TriageService` to own:
  - triage recording
  - score calculation hooks
  - routing to consultation
  - emergency escalation

---

## 4.3 Consultation Workflow

### Current State

- `ConsultationController@show()` builds a rich consultation EHR screen.
- `ConsultationService` manages complaints, diagnoses, investigations, and treatments.
- The consultation Blade view already uses AJAX for most CRUD blocks.
- Tab persistence uses `localStorage`, which is good.
- A static patient header and vitals panel already exist on the consultation page.

### Findings

- This is one of the strongest screens in the app functionally.
- The main remaining weakness is **view size and UI coupling**: the Blade file contains patient summary, vitals panel, history, modal workflows, tab scripts, AJAX handlers, and quick actions in one place.
- Some actions still use full-page form posts, especially the investigation routing form inside the modal.
- There is still inconsistent button styling inside the consultation screen (`btn-purple` remains present).

### Repetition / Coupling Risk

- Patient summary markup is embedded directly in the consultation page instead of being a shared header component.
- AJAX utility patterns are repeated inline across sections.
- Clinical workflow state is split between PHP service logic and large view-local JS.

### Recommendation

- Extract shared patient/visit header partial/component.
- Standardize AJAX form helpers.
- Convert remaining full reload forms in consultation to async updates.

---

## 4.4 Insurance Workflow

### Current State

- `InsuranceService` is one of the more mature services.
- It already supports:
  - per-visit limits
  - monthly limits
  - annual limits
  - max visits per month
  - minimum visit interval
  - holder vs beneficiary constraints
- It returns fallback behavior for Cash and Carry.

### Findings

- The insurance engine is substantially centralized already.
- This is a strong base for the required behavior.
- The main architectural issue is not duplication inside insurance; it is the number of places that still depend on its outputs while also performing neighboring workflow updates.

### Recommendation

- Keep `InsuranceService` authoritative.
- Prevent future direct insurance calculations outside this service.
- Expose lightweight DTO-style responses for UI summary panels.

---

## 4.5 Billing Workflow

### Current State

- `BillingService` creates invoices, records payments, and builds items from visit services, lab, and pharmacy sources.
- Insurance usage is linked during invoice generation and payment finalization.

### Findings

- Billing logic is significantly centralized already.
- However, `BillingService` still mutates visit status directly to `BILLING` and `COMPLETED`.
- This is workflow coupling and should be moved behind a dedicated visit workflow layer.

### Recommendation

- Keep invoice/payment assembly in `BillingService`.
- Move visit transition side-effects into `VisitWorkflowService`.

---

## 4.6 Investigation Workflow

### Current State

- The UI now uses “Investigations” terminology in key places.
- `LabService` is currently the de facto investigation engine.
- Requests support a `target_department_id`, free-text or catalog-backed items, and result-type-aware departments.
- Analyzer integration and investigation stock/catalog screens exist.

### Findings

- Domain direction is correct, but implementation naming is still transitional.
- The system conceptually supports unified investigations, but the code is still structurally centered on lab models and services.
- This is acceptable short term, but it remains a coupling hotspot.

### Recommendation

- Keep backward compatibility now.
- Introduce an `InvestigationService` façade or orchestrator that wraps `LabService` behavior.
- Gradually move routes, UI labels, and cross-module dependencies toward investigation terminology.

---

## 5. Dashboard Analysis

### Current State

- Admin dashboard exists and is still the richest dashboard.
- Doctor dashboard route exists.
- A shared `StaffDashboardController` now supports role-aware dashboards for reception, nurse, pharmacist, lab, and cashier/accounting.

### Findings

- Dashboard coverage is no longer “admin only,” but role-specific dashboards are still uneven.
- The current staff dashboard is a useful transitional layer, not the final modular dashboard architecture.
- Dashboard logic is controller-driven and query-heavy; there is no `DashboardService` yet.

### Recommendation

- Create `DashboardService` with role-specific summary builders.
- Cache summary cards where safe.
- Keep dashboards module-aware.

---

## 6. Sidebar / Menu Behavior Analysis

### Current State

- Sidebar lives in a large Blade partial.
- Anti-FOUC CSS was added to the main layout.
- `@module('pharmacy')`, `@module('investigations')`, and `@module('reports')` wrapping now exists.
- Permission checks remain inline throughout the menu.

### Findings

- The visual loading problem is partially mitigated.
- The sidebar is still structurally large, Blade-heavy, and tightly coupled to route names, permission strings, and module toggles all in one file.
- More optional modules still need to be fully module-gated.
- The menu is generated by conditionals rather than a cached menu-definition layer.

### Recommendation

- Move sidebar generation toward a menu-definition array or service.
- Cache module-enabled state and permission-aware menu building where possible.
- Keep Blade rendering thin.

---

## 7. UI Consistency Analysis

### Positive Findings

- Button normalization has started.
- Consultation screen already presents a strong patient summary and vitals panel.
- Sidebar terminology has improved for investigations.

### Remaining Problems

- UI semantics are not yet universal.
- Legacy button classes still exist (`btn-info` for primary actions in some places was fixed selectively; `btn-purple` still appears).
- Shared headers and action components do not yet exist as reusable Blade components or partials.
- Flash, modal, table, and badge presentation varies by page.

### Recommendation

- Define a small design system for Blade immediately:
  - `patient-visit-header`
  - `visit-status-badge`
  - `insurance-summary-badge`
  - standardized action button classes

---

## 8. Data Repetition Analysis

### Repeated Patient/Visit Display Data

The same patient/visit fields are repeatedly reconstructed in views:

- patient name
- age
- gender
- phone
- occupation
- religion
- marital status
- visit status
- assigned doctor
- visit number

This repetition increases the risk of inconsistent visibility and inconsistent formatting.

### Repeated Billing/Insurance Context

Insurance and billing-related status is assembled across visit, invoice, insurance, and consultation contexts. The core engine is centralized, but the display layer is not.

### Recommendation

- Create shared Blade partials/components for repeated patient/visit/insurance display blocks.

---

## 9. Feature Repetition Analysis

### Repeated CRUD Patterns

Consultation sections repeat the same structure for:

- add item
- render list item
- delete item via AJAX
- badge count update

This is a candidate for either:

- reusable Blade partials + shared JS helper, or
- future Vue/Inertia componentization.

### Repeated Dashboard Query Patterns

Multiple dashboards and summary pages build similar “today + recent + status count” logic.

### Repeated Status Updates

Visit state transitions appear in more than one service boundary.

---

## 10. Modules and Coupling Audit

### Already Implemented

- `modules` table
- `Module` model
- `ModuleService`
- `EnsureModuleEnabled` middleware
- admin modules management screen
- partial sidebar awareness of modules

### Current Coupling Problems

- Optional modules are not yet consistently route-protected across the whole application.
- Some fallback rules are implemented conceptually, but not uniformly enforced in services/controllers.
- “Investigations” still depends on `LabService` and lab-named models.
- Claims, pharmacy, reports, and insurance still depend on static route/menu presence more than dynamic module definition.

### Core vs Optional Status

**Correct core candidates:**

- authentication
- users/roles
- patients
- visits
- triage
- consultation basics
- departments
- services
- billing/payment basics
- settings
- audit logs

**Correct optional candidates:**

- insurance
- claims
- pharmacy
- store/inventory
- investigations
- analyzer
- HR/payroll
- reports
- notifications
- medical patterns

---

## 11. Performance Analysis

### Positive Findings

- Several query surfaces already use eager loading.
- Dashboard queries often limit result size.
- Performance indexes have already been added on hot tables.
- Module state is cached in `ModuleService`.

### Current Concerns

- Sidebar permission/module checks are still done inline in a large Blade template.
- Consultation screen loads a large amount of related data at once.
- Some historical/clinical blocks can still grow large over time.
- Large Blade screens embed a lot of inline JS, which makes page-level maintenance harder.
- Dashboard query logic is not centrally cached.

### Main N+1 / Over-fetching Risk Areas

- dashboards
- patient history lookups
- menu rendering
- stock/invoice/report listings if filters expand

### Recommendation

- add dashboard summary caching
- add menu-definition caching
- keep historical lists paginated or lazy-loaded where practical
- continue targeted eager loading rather than blanket `load()` calls

---

## 12. Current Stabilization Status

### Already Done

- module persistence and toggles
- partial module-aware sidebar
- anti-FOUC sidebar/layout handling
- performance indexes migration
- shared staff dashboard entry route
- some button normalization
- consultation vitals panel and improved patient summary

### Still Open in Phase 1

- shared patient/visit header extraction
- remaining button color normalization
- remaining clinical full-page submits
- menu generation cleanup
- cross-page information consistency

---

## 13. Priority Findings

1. **Workflow ownership is still split.** `VisitService` and `BillingService` both participate in visit status changes.
2. **The consultation page is strong but too large.** It should be the first source of shared clinical UI components.
3. **The sidebar is improved but not yet fully generated from module + permission metadata.**
4. **The module system exists, but fallback behavior is not yet consistently enforced everywhere.**
5. **The UI system is partially standardized, not systematized.**
6. **Blade remains the actual UI platform.** Inertia/Vue is installed, but hospital workflows are still running on Blade + AJAX.

---

## 14. Recommended Immediate Direction

For the current codebase, the most practical path is:

1. finish Phase 1 using Blade + AJAX, not a broad frontend rewrite
2. centralize workflow transitions into a `VisitWorkflowService`
3. extract shared UI pieces from the consultation screen first
4. finish module-aware route/menu/fallback enforcement
5. introduce a `DashboardService` before building more role dashboards

That path preserves current velocity, avoids breaking routes, and keeps the migration toward a cleaner modular architecture incremental instead of disruptive.