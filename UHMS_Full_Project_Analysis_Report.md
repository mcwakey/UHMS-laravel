# UHMS Project Full Analysis Report

**Project:** UHMS — Ultimate Hospital Management System  
**Stack Direction:** Laravel-based web application  
**Report Type:** Workflow, feature, data repetition, UI/UX, modularity, and performance analysis  
**Generated:** 2026-05-02

---

## 1. Executive Summary

UHMS is being rebuilt from a mature legacy hospital system into a modern Laravel application. The project already contains important domain decisions around patients, visits, triage, consultation, insurance, billing, investigations, pharmacy, claims, and administration.

The main issue at this stage is not lack of features. The risk is **feature duplication, inconsistent workflow enforcement, inconsistent UI behavior, and tight coupling between optional modules and core workflow**.

The system must now be overhauled around these principles:

1. **Core workflow must never break.**
2. **Optional modules must be disableable.**
3. **Billing, insurance, and visit status logic must be centralized.**
4. **All pages must behave consistently as SPA-like screens.**
5. **UI components must be standardized.**
6. **Performance must be considered from the beginning.**

---

## 2. Current Known State

At the moment, the system has:

- Admin dashboard only.
- Initial implementation plan already implemented.
- Some SPA-like behavior, but some pages still reload fully.
- Sidebar menu CSS instability when loading.
- Inconsistent button styles and action colors.
- Growing feature scope around:
  - Patients
  - Visits
  - Triage
  - Consultation
  - Insurance
  - Billing
  - Investigations
  - Pharmacy
  - Departments
  - Doctors
  - Services
  - Specialties

---

## 3. Target Business Workflow

The intended workflow is:

```text
Patient Registration
        ↓
Visit Creation
        ↓
Automatic Triage Queue
        ↓
Vitals + Triage Score
        ↓
Triage directs patient to a consultation department/service
        ↓
Billing line is created for selected service
        ↓
Consultation
        ↓
Doctor may:
    - complete visit
    - send to investigation department
    - refer to another consultation department
    - admit patient
        ↓
Billing / Insurance Enforcement
        ↓
Completion / Discharge
```

---

## 4. Visit Workflow Analysis

### 4.1 Visit Creation

Expected behavior:

- Every visit must begin at triage.
- The patient should automatically enter the triage queue.
- Default valid insurance should be selected automatically.
- If default insurance is invalid or exhausted, Cash and Carry should be applied.
- User should still be able to manually choose another valid insurance.

### 4.2 Triage

Expected behavior:

- Triage personnel records vitals.
- Triage score is calculated.
- Triage score may change visit priority/status:
  - Routine
  - Urgent
  - Emergency
  - Inpatient candidate

### 4.3 Consultation Routing

Expected behavior:

- Triage selects one billed consultation service/department.
- Patient is routed to that department queue.
- A billing item is created immediately.

### 4.4 Consultation

Expected behavior:

- Doctor works without full page reloads.
- Vitals remain visible.
- Previous visits are visible in a right-side panel.
- Complaints, diagnoses, investigations, treatments, and notes are saved asynchronously.
- Diagnosis supports:
  - provisional/final status
  - primary diagnosis selection
  - editable diagnosis status

### 4.5 Investigation

Expected behavior:

- No separate Lab Request tab.
- Investigations tab should contain all investigation departments:
  - Lab
  - X-ray
  - Scan
  - CT-scan
  - Others
- User selects investigation department, then services under it.

---

## 5. Insurance Workflow Analysis

The insurance engine must enforce four constraints:

1. Amount per visit.
2. Amount per month.
3. Amount per year.
4. Number of visits per month.

At each billing operation:

- Check remaining per-visit limit.
- Check remaining monthly amount.
- Check remaining yearly amount.
- Check remaining monthly visit count.
- If any limit is reached, insurance stops covering further items.
- Remaining billing automatically falls back to Cash and Carry.
- Previous billing lines must not be changed retroactively.

### Key Risk

Insurance logic can easily be duplicated across:

- Visit creation
- Billing item creation
- Pharmacy
- Investigation requests
- Consultation service billing
- Claims

This must be avoided by centralizing logic in an `InsuranceService`.

---

## 6. Possible Data Repetition Problems

### 6.1 Patient Data Repetition

Risk:
- Occupation, religion, marital status, next of kin, and insurance data may be repeated in visit records.

Recommended:
- Keep patient profile data in patient-related tables.
- Snapshot only critical visit-time values where needed for legal/financial consistency.

### 6.2 Insurance Data Repetition

Risk:
- Insurance limits and usage may be recalculated or stored inconsistently.

Recommended:
- Store insurance policy limits in `patient_insurances`.
- Store usage in `insurance_usages` or derive from finalized billing items.
- Use one service to calculate remaining limits.

### 6.3 Billing Data Repetition

Risk:
- Pharmacy, lab, consultation, and investigations may each create independent billing logic.

Recommended:
- All modules should send billable events to one `BillingService`.
- Billing items should store:
  - visit_id
  - service_id
  - module_source
  - department_id
  - insurance_amount
  - patient_amount
  - total_amount
  - pricing_context

### 6.4 Department / Service / Specialty Repetition

Risk:
- Doctors, departments, services, and specialties may be directly linked in too many ways.

Recommended:
- Use specialties as bridge where appropriate.
- Department has services.
- Services can have specialties.
- Doctors have specialties.
- Department may have specialties.
- Avoid hardcoding doctor-service logic.

### 6.5 Investigation and Lab Duplication

Risk:
- Separate Lab tab and Investigation tab create duplicated request logic.

Recommended:
- Treat Lab as one investigation department.
- Use one Investigation module with department-specific services.

### 6.6 Consultation Data Repetition

Risk:
- Complaints, diagnosis, treatment, notes, and investigation orders may be stored in overlapping tables.

Recommended:
- Store each clinical concept once.
- Link all consultation data to visit_id.
- Use typed records instead of repeated text blobs.

---

## 7. Feature Repetition Problems

### 7.1 Separate Lab Request vs Investigation

Problem:
- Lab request duplicates investigation logic.

Decision:
- Remove Lab Request as separate consultation tab.
- Use unified Investigations.

### 7.2 Vitals Tab vs Static Vitals Section

Problem:
- Vitals should not be hidden in a tab because doctors need it always.

Decision:
- Remove Vitals tab.
- Display vitals permanently under patient header.

### 7.3 Multiple Billing Entry Points

Problem:
- Billing may be created from triage, consultation, investigation, pharmacy, and claims.

Decision:
- Keep many billing triggers, but only one billing engine.

### 7.4 Role Dashboards

Problem:
- Admin dashboard exists, but other roles do not have focused workflows.

Required:
- Doctor dashboard.
- Triage/Nurse dashboard.
- Cashier dashboard.
- Pharmacy dashboard.
- Investigation dashboard.
- Claims dashboard.
- Store dashboard.

---

## 8. UI/UX Analysis

### 8.1 Sidebar CSS Breakage

Known issue:
- Sidebar menu tweaks break CSS while loading.

Likely causes:
- Template CSS conflicts.
- Dynamic menu rendering changes classes.
- Sidebar state loaded after layout rendering.
- Custom CSS overriding vendor CSS unpredictably.

Impact:
- Poor first impression.
- Navigation instability.
- Difficult debugging.

### 8.2 Full Page Reloads

Known issue:
- Some forms reload the entire page instead of behaving like SPA screens.

Impact:
- Users lose active tab.
- Clinical entry becomes slow.
- Consultation workflow becomes frustrating.
- Increased server load.

### 8.3 Button Inconsistency

Known issue:
- Accept/save buttons use different styles on different pages:
  - primary
  - info
  - other variants

Impact:
- Users cannot build muscle memory.
- Design feels unfinished.
- Actions may be misinterpreted.

### 8.4 Information Inconsistency

Risk:
- Patient header may show different fields on different pages.
- Insurance status may appear in some places and not others.
- Visit state may not always be visible.

Required:
- Shared patient header component.
- Shared insurance summary component.
- Shared visit status badge.
- Shared action buttons.

---

## 9. Core vs Optional Module Analysis

The system should be divided into **core features** and **disableable modules**.

### 9.1 Core Features

These must always exist:

- Authentication and users.
- Roles and permissions.
- Patients.
- Visits.
- Triage.
- Departments.
- Services.
- Billing engine.
- Basic cashier/payment.
- Basic consultation.
- Audit logs.
- Settings.

### 9.2 Optional Modules

These can be enabled/disabled:

- Insurance.
- Claims.
- Pharmacy.
- Store/inventory.
- Laboratory.
- X-ray.
- Scan.
- Analyzer integration.
- HR.
- Payroll.
- Reports advanced module.
- Medical pattern engine.
- Real-time notifications.

### 9.3 Critical Rule

Disabling an optional module must not break the visit workflow.

Example:
- If Insurance is disabled, all visits default to Cash and Carry.
- If Pharmacy is disabled, prescriptions can still be recorded but not dispensed.
- If Lab is disabled, investigation departments exclude Lab.
- If Claims is disabled, bills remain payable by Cash/Private without claims workflow.

---

## 10. Architecture Analysis

### Current Risk

As the project grows, controllers may become too large, and logic may scatter across pages.

### Required Architecture

Use service-driven architecture:

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

### Required Services

- VisitService
- TriageService
- ConsultationService
- BillingService
- InsuranceService
- DepartmentService
- ServiceCatalogService
- InvestigationService
- PharmacyService
- ModuleService
- DashboardService

---

## 11. Performance Analysis

### Performance Risks

- N+1 queries when loading patient, insurance, visits, services, departments, doctors.
- Repeated insurance limit calculation.
- Heavy dashboard queries.
- Full page reloads.
- Uncached sidebar/module permissions.
- Large previous visit records loaded upfront.

### Required Performance Strategy

- Eager-load relationships intentionally.
- Cache module settings and permissions.
- Paginate previous visits.
- Load previous visit details only when preview modal opens.
- Use AJAX for tab content.
- Use queues for heavy tasks.
- Use database indexes on workflow-heavy columns.

Important indexes:

- visits.patient_id
- visits.status
- visits.current_department_id
- visits.created_at
- billing_items.visit_id
- billing_items.patient_insurance_id
- billing_items.created_at
- patient_insurances.patient_id
- patient_insurances.is_default
- departments.type
- services.department_id

---

## 12. Main Risks

| Risk | Impact | Severity |
|---|---|---|
| Workflow logic scattered in UI | Broken patient routing | High |
| Insurance logic duplicated | Wrong billing | High |
| Optional modules tightly coupled | Disabling modules breaks app | High |
| Inconsistent UI | Poor usability | Medium |
| Full page reloads | Slow clinical workflow | High |
| Sidebar CSS instability | Navigation issues | Medium |
| Poor query design | Performance problems | High |

---

## 13. Conclusion

UHMS must now be treated as a modular hospital ERP platform. The biggest priority is not adding more screens; it is stabilizing the foundation:

1. Central workflow engine.
2. Central billing engine.
3. Central insurance engine.
4. Unified UI design system.
5. Modular feature flags.
6. Role-based dashboards.
7. SPA-like behavior across all clinical and operational screens.
8. Performance-first data loading.

This will allow the project to scale without becoming difficult to maintain.
