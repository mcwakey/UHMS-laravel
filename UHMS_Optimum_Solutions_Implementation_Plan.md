# UHMS Optimum Solutions & Implementation Plan

**Project:** UHMS — Ultimate Hospital Management System  
**Document Type:** System overhaul, optimization, and implementation plan  
**Generated:** 2026-05-02

---

## 1. Goal

The goal is to overhaul UHMS into a stable, modular, high-performance Laravel hospital platform where:

- Core workflow is always available.
- Optional modules can be disabled safely.
- UI is consistent across all pages.
- SPA-like behavior is enforced.
- Billing and insurance logic are centralized.
- Performance is considered in every module.

---

## 2. Guiding Principles

1. **Core first, modules second.**
2. **No duplicated business logic.**
3. **No full page reloads for clinical workflows.**
4. **One design system for the entire app.**
5. **Every module communicates through services/events.**
6. **All heavy calculations must be optimized or cached.**
7. **No optional module should break the visit flow.**

---

## 3. Proposed Modular Architecture

```text
app/
├── Core/
│   ├── Patients/
│   ├── Visits/
│   ├── Departments/
│   ├── Services/
│   ├── Billing/
│   └── Settings/
│
├── Modules/
│   ├── Insurance/
│   ├── Claims/
│   ├── Consultation/
│   ├── Triage/
│   ├── Investigations/
│   ├── Pharmacy/
│   ├── Inventory/
│   ├── Reports/
│   ├── HR/
│   └── Analyzer/
│
├── Shared/
│   ├── UI/
│   ├── Actions/
│   ├── DTOs/
│   ├── Enums/
│   └── Helpers/
```

---

## 4. Feature Flag System

Create a module control system.

### Table: modules

```text
id
name
slug
description
is_core
is_enabled
depends_on nullable
created_at
updated_at
```

### Rules

- Core modules cannot be disabled.
- Optional modules can be disabled.
- If a module is disabled:
  - its menu item is hidden
  - its routes are protected
  - its workflow hooks are skipped
  - fallback behavior is used

### Example Fallbacks

| Disabled Module | Fallback |
|---|---|
| Insurance | All billing becomes Cash and Carry |
| Pharmacy | Prescriptions recorded only, no dispensing |
| Lab | Lab removed from investigation departments |
| Claims | Bills remain payable directly |
| Analyzer | Lab results entered manually |
| HR | No staff/payroll functions |

---

## 5. Core Workflow Engine

Create a centralized `VisitWorkflowService`.

### Visit statuses

```text
TRIAGE
WAITING_CONSULTATION
IN_CONSULTATION
WAITING_INVESTIGATION
IN_INVESTIGATION
REFERRED
EMERGENCY
INPATIENT
BILLING
COMPLETED
DISCHARGED
CANCELLED
```

### Required Methods

```php
createVisit()
sendToTriage()
processTriage()
assignConsultationDepartment()
startConsultation()
referToDepartment()
sendToInvestigation()
markEmergency()
admitPatient()
completeVisit()
```

### Rule

No controller should directly change visit status.

All status changes must pass through `VisitWorkflowService`.

---

## 6. Billing Engine Solution

Create a centralized `BillingService`.

### Responsibilities

- Create billing items.
- Apply correct pricing.
- Call InsuranceService if insurance module is enabled.
- Split insurance and patient amount.
- Fall back to Cash and Carry if insurance is exhausted.
- Prevent duplicate billing for the same service when required.

### Billing Item Fields

```text
id
visit_id
patient_id
department_id
service_id
module_source
quantity
unit_price
total_amount
insurance_amount
patient_amount
patient_insurance_id nullable
payment_status
created_by
created_at
updated_at
```

### Core Rule

Every billable action must call:

```php
BillingService::billService(...)
```

Never create billing items directly from controllers or modules.

---

## 7. Insurance Engine Solution

Create a centralized `InsuranceService`.

### Constraints

Each patient insurance may have:

- max_per_visit
- max_per_month
- max_per_year
- max_visits_per_month

### Rules

- Validate insurance during visit creation.
- Validate insurance during every billing operation.
- If any limit is reached:
  - current excess becomes Cash and Carry
  - future billing becomes Cash and Carry
  - previous billing lines remain unchanged

### Required Methods

```php
getDefaultValidInsurance($patient)
evaluateCoverage($patientInsurance, $visit, $incomingAmount)
getRemainingLimits($patientInsurance, $visit)
markVisitInsuranceExhausted($visit)
```

---

## 8. Consultation System Solution

### Layout

```text
Left Sidebar Tabs | Main Clinical Workspace | Right Previous Visits Panel
```

### Always Visible

- Patient header
- Occupation
- Religion
- Marital status
- Insurance badge
- Visit status badge
- Vitals section
- Triage score

### Tabs

- Complaints
- History
- Diagnosis
- Investigations
- Treatment
- Notes

### Removed

- Vitals tab
- Lab request tab

### Diagnosis Improvements

Each diagnosis should have:

```text
type: PROVISIONAL or FINAL
is_primary: boolean
```

Rules:

- First diagnosis becomes primary.
- User can edit provisional/final status.
- User can set another diagnosis as primary.
- Only one primary diagnosis per visit.

### SPA Behavior

All saves should use AJAX or Vue/Livewire actions.

Required behavior:

```text
Save record → update section → remain on same tab
```

No full page reloads.

---

## 9. Investigation Module Solution

Use one Investigation module.

### Flow

1. Select investigation department.
2. Load services in selected department.
3. Select one or more services.
4. Create investigation order.
5. Create billing items through BillingService.

### Departments

- Lab
- X-ray
- Scan
- CT-scan
- Other configured investigation departments

---

## 10. UI Design System

Create reusable UI components.

### Buttons

| Action | Class |
|---|---|
| Main save/accept action | `btn btn-primary` |
| Secondary action | `btn btn-secondary` |
| Informational action | `btn btn-info` |
| Warning action | `btn btn-warning` |
| Delete/destructive action | `btn btn-danger` |
| Cancel/close | `btn btn-light` |

### Rule

Accept, Save, Submit, Confirm = always primary.

Never use `info` for final acceptance actions.

### Shared Components

- AppLayout
- Sidebar
- PageHeader
- PatientHeader
- VisitStatusBadge
- InsuranceSummaryBadge
- VitalsPanel
- DataTable
- Modal
- ConfirmDialog
- ActionButton
- EmptyState
- LoadingSpinner

---

## 11. Sidebar Fix Plan

### Likely Problem

Sidebar CSS breaks because of:

- template class conflicts
- dynamic menu rendering
- active state JavaScript issues
- late-loading CSS or JS

### Solution

1. Convert sidebar into one controlled component.
2. Remove inline/sidebar-specific page overrides.
3. Use fixed class naming.
4. Cache menu permissions.
5. Render only enabled module menu items.
6. Test collapsed/expanded states.
7. Avoid reinitializing template JS on every page load.

### Required Menu Logic

Sidebar menu should be built from:

- user permissions
- enabled modules
- role dashboard access

---

## 12. SPA Behavior Plan

Choose one consistent approach.

Recommended:

- Laravel + Inertia + Vue  
or
- Laravel Livewire  
or
- Vue SPA consuming Laravel API

### Rule

Do not mix multiple patterns randomly.

For current system, the safest path is:

```text
Laravel Blade layout + Vue/Alpine components for dynamic sections
```

or full:

```text
Laravel + Inertia + Vue
```

### Pages That Must Be SPA-like

- Consultation
- Visit creation
- Triage
- Billing
- Pharmacy dispensing
- Investigation orders
- Insurance selection

---

## 13. Dashboard Overhaul

Currently only Admin dashboard exists.

Create role-based dashboards:

### Admin Dashboard

- Total patients
- Visits today
- Revenue summary
- Active modules
- Staff overview

### Triage/Nurse Dashboard

- Triage queue
- Emergency cases
- Pending vitals

### Doctor Dashboard

- Waiting consultations
- In-consultation patients
- Referred patients
- Previous visit quick access

### Cashier Dashboard

- Unpaid bills
- Paid today
- Insurance/cash split
- Pending discharge bills

### Investigation Dashboard

- Pending investigation orders
- Completed results
- Critical results

### Pharmacy Dashboard

- Pending prescriptions
- Low stock alerts
- Dispensed today

---

## 14. Data Consistency Rules

### Patient Header

Every clinical page must show:

- Patient name
- Age
- Gender
- Phone
- Occupation
- Religion
- Marital status
- Insurance being used
- Visit status

### Insurance Display

Every billing/visit page must show:

- Current insurance
- Validity
- Remaining per visit
- Remaining monthly amount
- Remaining yearly amount
- Remaining monthly visits
- Cash fallback state if exhausted

### Visit Display

Every workflow page must show:

- Visit number
- Current department
- Current status
- Triage score
- Current responsible user/department if available

---

## 15. Performance Implementation Rules

### Database

Add indexes to:

```text
visits.patient_id
visits.status
visits.current_department_id
visits.created_at
billing_items.visit_id
billing_items.patient_id
billing_items.patient_insurance_id
billing_items.created_at
patient_insurances.patient_id
services.department_id
doctor_specialty.doctor_id
service_specialty.service_id
```

### Query Rules

- Use eager loading.
- Avoid N+1 queries.
- Paginate large lists.
- Load modal details only when opened.
- Do not load all previous visit details upfront.
- Cache sidebar menu and module configuration.

### Background Work

Use queues for:

- notifications
- analyzer processing
- report generation
- heavy dashboard summaries
- insurance usage recalculation if needed

---

## 16. Implementation Phases

## Phase 1 — Stabilization

Goal: fix existing errors and UI instability.

Tasks:

- Fix sidebar component.
- Standardize button classes.
- Create shared layout components.
- Remove full reloads from critical pages.
- Create shared patient header.
- Create shared loading indicators.

Deliverables:

- Stable sidebar.
- Uniform buttons.
- Consistent layout.
- No reloads on consultation tab saves.

---

## Phase 2 — Core Workflow Refactor

Goal: centralize visit logic.

Tasks:

- Create VisitWorkflowService.
- Create VisitStatus enum.
- Add visit status logs.
- Ensure visit creation always sends patient to triage.
- Ensure triage routes to consultation.

Deliverables:

- Backend-enforced workflow.
- Auditable visit transitions.

---

## Phase 3 — Billing + Insurance Refactor

Goal: remove duplicated billing and insurance logic.

Tasks:

- Create BillingService.
- Create InsuranceService.
- Add insurance evaluation response object.
- Ensure every billing item passes through BillingService.
- Implement Cash and Carry fallback.

Deliverables:

- Centralized billing.
- Correct insurance enforcement.
- No duplicate billing logic.

---

## Phase 4 — Consultation Overhaul

Goal: make consultation a doctor-friendly workspace.

Tasks:

- Remove Vitals tab.
- Add static vitals panel.
- Add right previous visits panel.
- Add diagnosis edit and primary diagnosis.
- Remove Lab Request tab.
- Use unified Investigations tab.
- Save all tab entries asynchronously.

Deliverables:

- SPA-like consultation.
- Better clinical usability.

---

## Phase 5 — Modularization

Goal: make optional modules disableable.

Tasks:

- Create modules table.
- Add ModuleService.
- Protect routes with module middleware.
- Hide disabled module menus.
- Add fallback behaviors.

Deliverables:

- Disableable modules.
- Core workflow remains stable.

---

## Phase 6 — Role Dashboards

Goal: support real operational workflows.

Tasks:

- Build Doctor dashboard.
- Build Triage/Nurse dashboard.
- Build Cashier dashboard.
- Build Investigation dashboard.
- Build Pharmacy dashboard.

Deliverables:

- Role-specific work queues.
- Less dependency on admin dashboard.

---

## Phase 7 — Performance Optimization

Goal: scale safely.

Tasks:

- Add indexes.
- Fix N+1 queries.
- Cache menus/modules.
- Use queues.
- Optimize dashboard queries.

Deliverables:

- Faster pages.
- Lower DB load.
- Better user experience.

---

## 17. Copilot / Claude Opus Master Implementation Prompt

```text
You are a senior Laravel architect and frontend UX engineer.

We are overhauling UHMS, a Hospital Management System, into a modular Laravel application.

The system currently has:
- admin dashboard only
- sidebar CSS instability
- some full page reloads instead of SPA-like behavior
- inconsistent button styles
- growing modules for visits, triage, consultation, insurance, billing, investigations, pharmacy, and administration

Your task is to refactor and implement the system with these rules:

1. Core workflow must never break.
2. Optional modules must be disableable through a module feature-flag system.
3. Controllers must stay thin.
4. Business logic must be centralized in services.
5. Visit status changes must only happen through VisitWorkflowService.
6. Billing items must only be created through BillingService.
7. Insurance coverage must only be evaluated through InsuranceService.
8. UI must use shared components and consistent design classes.
9. No full page reloads on clinical workflows.
10. Performance must be considered through eager loading, pagination, caching, indexes, and queues.

Core modules:
- Auth
- Users/Roles
- Patients
- Visits
- Triage
- Consultation
- Departments
- Services
- Billing
- Settings

Optional modules:
- Insurance
- Claims
- Pharmacy
- Inventory
- Investigations
- Analyzer
- HR
- Reports
- MedicalPattern

Implement:
- modules table
- ModuleService
- module middleware
- VisitWorkflowService
- BillingService
- InsuranceService
- shared UI design system
- stable sidebar component
- role-based dashboards
- SPA-like consultation workflow

Always generate:
- migrations
- models
- relationships
- services
- controllers
- requests
- frontend components where needed
- tests for critical workflows

Do not duplicate business logic.
Do not create billing directly inside controllers.
Do not change visit statuses directly from controllers.
Do not use inconsistent button colors.
Do not reload entire pages when saving tab data.
```

---

## 18. Final Recommendation

Do not continue adding isolated features until Phase 1 and Phase 2 are completed.

The correct next move is:

1. Stabilize layout and UI system.
2. Centralize workflow.
3. Centralize billing and insurance.
4. Modularize optional features.
5. Build remaining dashboards and modules on top of that foundation.

This will prevent UHMS from becoming difficult to maintain as it grows.
