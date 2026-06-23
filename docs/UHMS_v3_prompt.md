You are a senior Laravel architect, full-stack engineer, UI/UX systems designer, and performance optimization expert.

We are working on **UHMS — Ultimate Hospital Management System**, a Laravel-based hospital management platform. The initial implementation plan has already been implemented, but now the project needs a full technical audit, workflow analysis, feature/data repetition analysis, and a complete system overhaul plan.

Your task is to analyze the current codebase thoroughly, generate two Markdown reports, then begin implementing the optimal solutions carefully without introducing errors.

---

# PRIMARY OBJECTIVES

## 1. Generate Full Analysis Report

Create a Markdown file:

`docs/UHMS_Full_Project_Analysis_Report.md`

This file must analyze:

* Current project structure
* Current workflow implementation
* Visit workflow
* Triage workflow
* Consultation workflow
* Insurance workflow
* Billing workflow
* Investigation workflow
* Dashboard structure
* Sidebar/menu behavior
* UI consistency
* Possible data repetition
* Possible feature repetition
* Performance concerns
* Modules that are currently too tightly coupled
* Any duplicated logic across controllers, services, views, and components

The report must be detailed, structured, and practical.

---

## 2. Generate Optimum Solutions Report

Create another Markdown file:

`docs/UHMS_Optimum_Solutions_Implementation_Plan.md`

This file must propose the best possible solutions for:

* Workflow cleanup
* Modular architecture
* Feature toggling
* UI consistency
* Sidebar stability
* SPA-like behavior
* Dashboard expansion
* Performance optimization
* Code refactoring
* Removal of duplicated logic
* Better separation between core features and optional modules

The report must include an implementation roadmap with phases.

---

# CURRENT KNOWN ISSUES

The system currently has these notable problems:

1. **Sidebar menu issue**

   * Sidebar menu tweaks break the CSS when loading.
   * Analyze why this happens.
   * Fix the layout so menu rendering does not break the design.

2. **Full page reloads**

   * Some pages reload entirely instead of behaving like SPA pages.
   * This is especially bad for clinical workflows such as consultation tabs.
   * Saving form sections should not reset the page or return the user to the first tab.

3. **Inconsistent UI design**

   * Buttons are not uniform.
   * Example: on some pages, the accept/save button is `primary`, while on others it is `info`.
   * Standardize all buttons and action colors across the system.

4. **Dashboard limitation**

   * At the moment, only the admin dashboard exists.
   * The system needs role-based dashboards for doctors, triage/nurses, cashier, pharmacy, investigation departments, claims, store, etc.

5. **Feature coupling**

   * Some features should be core.
   * Other features should be optional modules that can be disabled without breaking the main workflow.

6. **Page information inconsistency**

   * Patient information, visit status, insurance information, and action buttons must appear consistently across relevant pages.

7. **Performance**

   * Always consider performance.
   * Avoid N+1 queries.
   * Avoid unnecessary full reloads.
   * Use eager loading, caching, pagination, indexes, queues, and optimized queries where needed.

---

# CORE SYSTEM DESIGN RULES

## Core Features

These features must always remain available and must not be disabled:

* Authentication
* Users and roles
* Patients
* Visits
* Triage
* Basic consultation
* Departments
* Services
* Billing engine
* Basic cashier/payment
* Settings
* Audit logs

## Optional Modules

These features should be modular and disableable without breaking the core workflow:

* Insurance
* Claims
* Pharmacy
* Store / Inventory
* Investigations
* Lab
* X-ray
* Scan
* Analyzer integration
* HR
* Payroll
* Reports
* Medical pattern engine
* Real-time notifications

If an optional module is disabled, the system must use a safe fallback.

Examples:

* If Insurance is disabled, all billing defaults to Cash and Carry.
* If Pharmacy is disabled, prescriptions can still be recorded but dispensing is unavailable.
* If Lab is disabled, Lab must disappear from investigation options.
* If Claims is disabled, bills are handled directly without claims processing.
* If Analyzer is disabled, lab results are entered manually.

---

# REQUIRED ARCHITECTURE

Follow this architecture strictly:

```text
Controllers
    ↓
Form Requests
    ↓
Services
    ↓
Models / Repositories
    ↓
Events / Jobs / Notifications
```

Controllers must remain thin.

Business logic must be moved into services.

Required services include:

* `VisitWorkflowService`
* `TriageService`
* `ConsultationService`
* `BillingService`
* `InsuranceService`
* `DepartmentService`
* `ServiceCatalogService`
* `InvestigationService`
* `PharmacyService`
* `ModuleService`
* `DashboardService`

---

# WORKFLOW RULES

## Visit Workflow

All visit status transitions must be handled by:

`VisitWorkflowService`

Do not update visit status directly inside controllers, Blade files, Vue components, Livewire components, or random model methods.

Visit status examples:

* `TRIAGE`
* `WAITING_CONSULTATION`
* `IN_CONSULTATION`
* `WAITING_INVESTIGATION`
* `IN_INVESTIGATION`
* `REFERRED`
* `EMERGENCY`
* `INPATIENT`
* `BILLING`
* `COMPLETED`
* `DISCHARGED`
* `CANCELLED`

## Billing Workflow

All billing items must be created through:

`BillingService`

Do not create billing items directly in controllers or components.

Billing must support:

* Service billing
* Insurance split
* Cash and Carry fallback
* Department source
* Module source
* Payment status

## Insurance Workflow

All insurance rules must be handled through:

`InsuranceService`

Insurance must support:

* Limit per visit
* Limit per month
* Limit per year
* Limit by number of visits per month

During every billing action, the system must check whether insurance limits are still valid.

If any limit is reached:

* The current excess becomes Cash and Carry.
* Future billing becomes Cash and Carry.
* Previous billing lines must not be changed retroactively.

---

# UI / UX STANDARDIZATION RULES

Create or enforce a shared design system.

## Button Rules

Use the following button meanings everywhere:

```text
Save / Accept / Confirm / Submit = primary
Cancel / Back / Close = secondary or light
View / Details = info
Warning action = warning
Delete / Remove / Reject = danger
```

Do not use `info` for main save/accept actions.

## Shared Components

Create or standardize these components:

* AppLayout
* Sidebar
* PageHeader
* PatientHeader
* VisitStatusBadge
* InsuranceSummaryBadge
* VitalsPanel
* DataTable
* Modal
* ConfirmDialog
* ActionButton
* EmptyState
* LoadingSpinner

## Page Consistency

Relevant patient/visit pages must consistently display:

* Patient name
* Age
* Gender
* Phone
* Occupation
* Religion
* Marital status
* Current visit status
* Current department
* Current insurance
* Insurance remaining limits where applicable
* Triage score where applicable

---

# SPA-LIKE BEHAVIOR RULES

Clinical and operational pages must not reload unnecessarily.

The following screens must behave like SPA pages:

* Consultation
* Visit creation
* Triage
* Billing
* Pharmacy dispensing
* Investigation orders
* Insurance selection

When saving a tab or section:

```text
Save → update only the affected section → stay on the same tab
```

Do not reset the page to the first tab after saving.

Use the project’s existing frontend approach. If the project uses Vue, use Vue/Axios. If it uses Livewire, use Livewire. If it uses Blade + Alpine, use Alpine/AJAX. Do not introduce a conflicting frontend approach unless absolutely necessary.

---

# SIDEBAR FIX REQUIREMENTS

Analyze and fix the sidebar CSS issue.

The sidebar must:

* Render consistently.
* Not break CSS on load.
* Respect enabled/disabled modules.
* Respect user permissions.
* Highlight active menus correctly.
* Not reinitialize template JavaScript repeatedly.
* Avoid page-specific CSS overrides that break global layout.

Sidebar menu must be generated from:

* Enabled modules
* User role
* Permissions

---

# DASHBOARD REQUIREMENTS

The system currently has only the admin dashboard.

Add or plan role-based dashboards:

1. Admin Dashboard

   * total patients
   * visits today
   * revenue summary
   * enabled modules
   * system status

2. Doctor Dashboard

   * waiting consultations
   * in-consultation patients
   * referred patients
   * recent patient records

3. Triage/Nurse Dashboard

   * triage queue
   * emergency cases
   * pending vitals

4. Cashier Dashboard

   * unpaid bills
   * paid bills today
   * insurance/cash split
   * pending discharge bills

5. Investigation Dashboard

   * pending orders
   * completed results
   * critical results

6. Pharmacy Dashboard

   * pending prescriptions
   * dispensed prescriptions
   * low stock alerts

7. Claims Dashboard

   * pending claims
   * submitted claims
   * rejected claims

8. Store Dashboard

   * stock levels
   * low stock
   * transfers
   * purchases

---

# MODULAR SYSTEM REQUIREMENTS

Implement or plan a module feature flag system.

Create a table similar to:

```text
modules
- id
- name
- slug
- description
- is_core
- is_enabled
- depends_on
- created_at
- updated_at
```

Create:

* `ModuleService`
* module middleware
* module helper
* menu integration
* route protection

Rules:

* Core modules cannot be disabled.
* Optional modules can be disabled.
* Disabled modules must not show in the sidebar.
* Disabled modules must not break routes.
* Disabled modules must use safe fallback behavior.

---

# PERFORMANCE REQUIREMENTS

Always optimize for performance.

Implement or recommend:

* Eager loading
* Pagination
* Database indexes
* Query caching
* Sidebar/menu caching
* Module config caching
* Queue jobs for heavy tasks
* Lazy loading modal details
* Dashboard summary caching

Avoid:

* N+1 queries
* loading all previous visits upfront
* full page reloads
* repeated insurance calculations without need
* repeated permission/module queries on every request

Recommended database indexes:

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
patient_insurances.is_default
services.department_id
doctor_specialty.doctor_id
service_specialty.service_id
```

---

# IMPLEMENTATION PHASES

After generating the two Markdown reports, start implementing in this order.

## Phase 1: Stabilization

* Fix sidebar CSS/menu rendering issue.
* Standardize buttons.
* Create shared layout components.
* Remove full reload behavior from critical pages.
* Add shared patient/visit header components.

## Phase 2: Core Workflow Refactor

* Create or improve `VisitWorkflowService`.
* Add/verify VisitStatus enum.
* Add visit status logs.
* Ensure visit creation sends patient to triage.
* Ensure triage routes patient to consultation.

## Phase 3: Billing and Insurance Refactor

* Centralize billing in `BillingService`.
* Centralize insurance in `InsuranceService`.
* Ensure insurance fallback to Cash and Carry.
* Prevent duplicated billing logic.

## Phase 4: Consultation Overhaul

* Remove Vitals tab.
* Add static vitals panel.
* Add previous visits right panel.
* Add diagnosis edit/final/provisional logic.
* Add primary diagnosis logic.
* Remove Lab Request tab.
* Use unified Investigations tab.
* Save tab data asynchronously.

## Phase 5: Modularization

* Create modules table.
* Create `ModuleService`.
* Add module middleware.
* Hide disabled modules from menu.
* Add fallback logic for disabled modules.

## Phase 6: Role Dashboards

* Add doctor dashboard.
* Add triage/nurse dashboard.
* Add cashier dashboard.
* Add investigation dashboard.
* Add pharmacy dashboard.
* Add claims/store dashboards if modules are enabled.

## Phase 7: Performance Optimization

* Add indexes.
* Fix N+1 queries.
* Add caching.
* Add queues where needed.
* Optimize dashboard queries.

---

# OUTPUT REQUIREMENTS

Before changing code:

1. Inspect the project structure.
2. Identify the frontend approach currently used.
3. Identify routes, controllers, views/components, services, and models already implemented.
4. Generate the two Markdown reports.

Then implement gradually.

For each implementation step:

* Explain what you will change.
* Show files to be created/modified.
* Avoid breaking existing routes.
* Avoid introducing duplicate logic.
* Run or suggest relevant tests.
* Check for syntax errors.
* Keep backward compatibility where possible.

---

# IMPORTANT RULES

Do not make random changes.

Do not rewrite the whole project blindly.

Do not introduce a second frontend framework if one is already being used.

Do not duplicate billing logic.

Do not duplicate insurance logic.

Do not change visit status outside `VisitWorkflowService`.

Do not use inconsistent button styles.

Do not leave pages with full reloads where SPA behavior is required.

Do not allow optional modules to break the core workflow.

Always keep performance in mind.

Now begin by analyzing the codebase and generating:

1. `docs/UHMS_Full_Project_Analysis_Report.md`
2. `docs/UHMS_Optimum_Solutions_Implementation_Plan.md`

After the reports are generated, proceed with Phase 1 stabilization.
