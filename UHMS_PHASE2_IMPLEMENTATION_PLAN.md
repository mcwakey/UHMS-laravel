# UHMS — Phase 2 Implementation Plan

## Gap Analysis & Full Roadmap for Feature Parity with Legacy System

---

## TABLE OF CONTENTS

1. [Executive Summary](#1-executive-summary)
2. [What Has Been Built (Phases 0–10)](#2-what-has-been-built-phases-010)
3. [Gap Analysis: Legacy vs Current](#3-gap-analysis-legacy-vs-current)
4. [Architecture Decisions](#4-architecture-decisions)
5. [Phase 11 — Inpatient & Ward Management](#5-phase-11--inpatient--ward-management)
6. [Phase 12 — Appointments & Scheduling](#6-phase-12--appointments--scheduling)
7. [Phase 13 — Claims & Insurance Processing](#7-phase-13--claims--insurance-processing)
8. [Phase 14 — Store, Procurement & Stock Transfers](#8-phase-14--store-procurement--stock-transfers)
9. [Phase 15 — Accounts & Financial Management](#9-phase-15--accounts--financial-management)
10. [Phase 16 — HR & Payroll](#10-phase-16--hr--payroll)
11. [Phase 17 — Notification System](#11-phase-17--notification-system)
12. [Phase 18 — ICD-10, Procedures & Clinical Enhancements](#12-phase-18--icd-10-procedures--clinical-enhancements)
13. [Phase 19 — Analyzer Integration (Lab Instruments)](#13-phase-19--analyzer-integration-lab-instruments)
14. [Phase 20 — Advanced Reports & Document Generation](#14-phase-20--advanced-reports--document-generation)
15. [Phase 21 — Final Polish, Testing & Deployment](#15-phase-21--final-polish-testing--deployment)
16. [Database Schema Additions](#16-database-schema-additions)
17. [Priority Matrix](#17-priority-matrix)
18. [Risk Assessment](#18-risk-assessment)

---

## 1. EXECUTIVE SUMMARY

Phases 0–10 delivered the **core patient workflow** — registration, visits, queue, triage, consultation, lab, pharmacy, billing, reports, and settings. This represents roughly **55–60%** of the legacy UHMS feature set.

The remaining **40%** consists of critical operational modules that the old system relied on daily:

| Category | Missing Modules | Business Impact |
|----------|----------------|-----------------|
| **Clinical** | Inpatient/Ward, Bed management, Procedures, ICD-10 | Cannot manage admitted patients |
| **Operational** | Appointments, Notifications, Analyzer integration | No scheduling, no inter-dept alerts, no auto lab results |
| **Financial** | Claims processing, Expense/Income tracking, Cashier handover | Cannot bill NHIS, no financial controls |
| **Supply Chain** | Purchase orders, Stock transfers (store→pharmacy) | Cannot restock pharmacy |
| **Administrative** | HR, Payroll, Employee attendance, Leave management | No staff management |

This plan covers **11 new phases (11–21)** to reach full feature parity and beyond.

---

## 2. WHAT HAS BEEN BUILT (PHASES 0–10)

### Completed Infrastructure
| Component | Count |
|-----------|-------|
| Models | 31 |
| Controllers | 30 |
| Services | 11 |
| Migrations | 31 |
| Blade Views | 57 |
| Routes | 143 |
| Enums | 12 |
| Roles | 8 |
| Permissions | 42 |

### Completed Modules
| Module | Status | Key Features |
|--------|--------|-------------|
| **Auth & Users** | ✅ | Login, password reset, RBAC, user CRUD |
| **Patients** | ✅ | Registration, search, profile, Ghana-specific fields |
| **Visits & Queue** | ✅ | Visit creation, status workflow, queue board, real-time |
| **Triage (Vitals)** | ✅ | Vital sign recording by nurses |
| **Consultation (EHR)** | ✅ | Complaints, diagnosis, investigations, treatments, notes |
| **Medical Patterns** | ✅ | Template engine for reusable clinical entries |
| **Laboratory** | ✅ | Test catalog, requests, results, verification |
| **Pharmacy** | ✅ | Drug catalog, stock management, dispensing, alerts |
| **Billing** | ✅ | Invoices, payments, receipts, service catalog |
| **Reports** | ✅ | Income, patient, visit, NHIS summary reports |
| **Settings** | ✅ | Organization, invoice, payment methods, profile, audit log |

### Current Patient Workflow
```
Register → Visit → Queue (Waiting) → Triage → Consulting → Lab → Pharmacy → Billing → Completed
```

### What's Missing from the Legacy Workflow
```
❌ Pre-visit payment at cashier before vitals (legacy: pay first, then triage)
❌ Inpatient admission → bed assignment → daily rounds → discharge
❌ Post-consultation investigation billing (per-department pricing)
❌ NHIS/Private claims processing after billing
❌ Stock transfer from store to pharmacy
❌ Inter-department notifications (desktop alerts)
❌ Appointment scheduling
❌ Analyzer auto-results
```

---

## 3. GAP ANALYSIS: LEGACY VS CURRENT

### Module-by-Module Comparison

| Legacy Module (Old UHMS) | Current Status | Gap Severity | Phase |
|--------------------------|---------------|-------------|-------|
| **Patient Registration** | ✅ Complete | — | — |
| **Ward / Nursing (Vitals)** | ⚠️ Vitals only | 🔴 No beds, no inpatient | 11 |
| **Ward / Admission** | ❌ Missing | 🔴 Critical | 11 |
| **Ward / Bed Management** | ❌ Missing | 🔴 Critical | 11 |
| **Ward / Discharge** | ❌ Missing | 🔴 Critical | 11 |
| **Doctor Consultation** | ✅ Complete | — | — |
| **ICD-10 Diagnosis Codes** | ❌ Missing | 🟡 Medium | 18 |
| **Procedures** | ❌ Missing | 🟡 Medium | 18 |
| **Investigations (Lab)** | ✅ Complete | — | — |
| **Investigations (X-Ray, Scan, etc.)** | ⚠️ Partial | 🟡 Only Lab, not multi-dept | 18 |
| **Analyzer Integration** | ❌ Missing | 🟡 Important | 19 |
| **Pharmacy Dispensing** | ✅ Complete | — | — |
| **Store / Purchase Orders** | ❌ Missing | 🔴 Critical | 14 |
| **Store / Stock Transfers** | ❌ Missing | 🔴 Critical | 14 |
| **Cashier / Billing** | ✅ Complete | — | — |
| **Cashier Handover** | ❌ Missing | 🟡 Medium | 15 |
| **Daily Collection Report** | ⚠️ Basic | 🟡 Needs expansion | 15 |
| **Expense Tracking** | ❌ Missing | 🟡 Medium | 15 |
| **Income Tracking** | ❌ Missing | 🟡 Medium | 15 |
| **Account Reconciliation** | ❌ Missing | 🟡 Medium | 15 |
| **NHIS Claims** | ❌ Missing | 🔴 Critical | 13 |
| **Private Insurance Claims** | ❌ Missing | 🔴 Critical | 13 |
| **Appointments** | ❌ Missing | 🟡 Medium | 12 |
| **HR / Employees** | ❌ Missing | 🟡 Medium | 16 |
| **HR / Attendance** | ❌ Missing | 🟢 Low | 16 |
| **HR / Leave** | ❌ Missing | 🟢 Low | 16 |
| **HR / Payroll** | ❌ Missing | 🟡 Medium | 16 |
| **Notifications** | ❌ Missing | 🔴 Critical | 17 |
| **Reports (Advanced)** | ⚠️ Basic | 🟡 Needs more reports | 20 |
| **Patient Statements** | ❌ Missing | 🟡 Medium | 20 |
| **Document Generation** | ⚠️ Invoice print only | 🟡 Medium | 20 |

### Feature Count Summary
| Category | Legacy Features | Built | Remaining |
|----------|----------------|-------|-----------|
| Clinical | 12 | 8 | 4 |
| Operational | 8 | 3 | 5 |
| Financial | 10 | 4 | 6 |
| Supply Chain | 4 | 1 | 3 |
| Administrative | 6 | 2 | 4 |
| **Total** | **40** | **18** | **22** |

---

## 4. ARCHITECTURE DECISIONS

### Principles Carried Forward
- **Service layer** for all business logic (thin controllers)
- **Form Requests** for validation
- **Eloquent models** with relationships and enum casts
- **Spatie permissions** for RBAC
- **Spatie activitylog** for audit trails
- **Bootstrap 5 + jQuery + Tabler Icons** template
- **`@extends('layouts.app')`** for all views
- **`GeneratesNumbers`** trait for auto-numbered records
- **`Schema::defaultStringLength(191)`** for MariaDB compatibility
- **`longText`** instead of `json` columns for MariaDB

### New Patterns for Phase 2
| Pattern | Purpose |
|---------|---------|
| **Laravel Notifications** | Replace legacy polling with database + broadcast notifications |
| **Events & Listeners** | Decouple module interactions (e.g., visit status change → notification) |
| **Jobs/Queues** | Analyzer message processing, claims batch processing |
| **Observers** | Auto-trigger on model lifecycle (admission created → notify ward) |
| **Policies** | Fine-grained authorization for claims approval |
| **Exports (maatwebsite/excel)** | Reports and claims export |
| **PDF (dompdf)** | Patient statements, payslips, claims forms |

### New Roles & Permissions to Add
```
Existing roles: Super Admin, Admin, Doctor, Nurse, Receptionist, Lab Technician, Pharmacist, Accountant

New roles:
- Store Keeper           → Purchase orders, stock transfers
- Claims Officer         → NHIS/private claims processing
- HR Manager             → Employee management, payroll, leave

New permissions (~35 additional):
- ward.view, ward.manage, ward.admit, ward.discharge
- beds.view, beds.manage
- appointments.view, appointments.create, appointments.edit, appointments.delete
- claims.view, claims.create, claims.approve, claims.export
- expenses.view, expenses.create, expenses.edit
- income.view, income.create
- accounts.reconcile
- cashier.handover
- hr.employees.view, hr.employees.create, hr.employees.edit
- hr.leave.view, hr.leave.create, hr.leave.approve
- hr.payroll.view, hr.payroll.process
- hr.attendance.view, hr.attendance.manage
- store.purchase.view, store.purchase.create, store.purchase.approve
- store.transfer.view, store.transfer.create
- notifications.view, notifications.manage
- analyzer.manage
```

---

## 5. PHASE 11 — INPATIENT & WARD MANAGEMENT

> **Priority: 🔴 CRITICAL** — The old system's Ward module (frmWard) handled vitals, admissions, bed management, and discharge. Our current system only handles vitals.

### 5.1 Objectives
- Bed/ward catalog management
- Patient admission workflow (from doctor or emergency)
- Bed assignment with availability tracking
- Daily ward rounds (nurse notes, vitals per round)
- Discharge workflow (doctor initiates → billing → completed)
- Inpatient list with current bed/ward info

### 5.2 Database Tables

#### `wards`
```
- id (bigint, PK)
- name (string)                    — e.g., "Male Ward", "Female Ward", "Pediatric Ward", "ICU"
- code (string, unique)
- department_id (FK → departments)
- capacity (integer)
- floor (string, nullable)
- description (text, nullable)
- is_active (boolean, default true)
- timestamps
```

#### `beds`
```
- id (bigint, PK)
- ward_id (FK → wards)
- bed_number (string)              — e.g., "MW-001", "ICU-003"
- bed_type (enum: standard, semi_private, private, icu, pediatric)
- status (enum: available, occupied, maintenance, reserved)
- daily_rate (decimal 10,2)
- notes (text, nullable)
- timestamps
```

#### `admissions`
```
- id (bigint, PK)
- admission_number (string, unique, auto: ADM-YYYYMMDD-XXXX)
- visit_id (FK → visits)
- patient_id (FK → patients)
- bed_id (FK → beds)
- admitted_by (FK → users)         — Doctor who ordered admission
- admitting_diagnosis (text, nullable)
- admission_date (datetime)
- expected_discharge_date (date, nullable)
- actual_discharge_date (datetime, nullable)
- discharged_by (FK → users, nullable)
- discharge_summary (text, nullable)
- discharge_instructions (text, nullable)
- status (enum: admitted, on_leave, discharged, transferred, deceased)
- timestamps
- soft_deletes
```

#### `ward_rounds`
```
- id (bigint, PK)
- admission_id (FK → admissions)
- recorded_by (FK → users)
- round_date (datetime)
- notes (text)
- instructions (text, nullable)
- timestamps
```

### 5.3 Legacy Workflow Mapping

| Old System (frmWard) | New System |
|----------------------|------------|
| Mode: "PATIENTS AWAITING VITALS" | Already built: VitalController |
| Mode: "PATIENTS ADMISSION" | New: AdmissionController@create + bed assignment |
| Mode: "PATIENTS ADMITED" | New: AdmissionController@index (current inpatients) |
| Bed grid (occupied/available) | New: BedController@index (visual bed map) |
| Discharge button → `INPATIENT-DISCH` | New: AdmissionController@discharge |
| Chart visualization | New: Ward dashboard widgets |

### 5.4 VisitStatus Changes
Add to `VisitStatus` enum:
```php
case ADMITTED = 'admitted';
case DISCHARGING = 'discharging';      // Doctor initiated discharge, pending billing
case DISCHARGED = 'discharged';        // Billing complete, patient released
```

Update transitions:
```
CONSULTING → ADMITTED               (doctor admits patient)
ADMITTED → CONSULTING              (daily rounds / follow-up consultation)
ADMITTED → LAB, PHARMACY            (inpatient orders)
ADMITTED → DISCHARGING              (doctor initiates discharge)
DISCHARGING → BILLING               (final bill calculation)
BILLING → DISCHARGED                (payment cleared)
```

### 5.5 Files to Create
| Type | File | Purpose |
|------|------|---------|
| Migration | `create_wards_table` | Wards |
| Migration | `create_beds_table` | Beds |
| Migration | `create_admissions_table` | Admissions |
| Migration | `create_ward_rounds_table` | Ward rounds |
| Migration | `add_inpatient_visit_statuses` | Add admitted/discharging/discharged |
| Model | `Ward` | Ward model |
| Model | `Bed` | Bed model |
| Model | `Admission` | Admission model |
| Model | `WardRound` | Ward round model |
| Enum | `BedStatus` | Bed status enum |
| Enum | `BedType` | Bed type enum |
| Enum | `AdmissionStatus` | Admission status enum |
| Controller | `Admin/WardController` | Ward/Bed CRUD |
| Controller | `Admin/AdmissionController` | Admission workflow |
| Service | `WardService` | Ward business logic |
| Service | `AdmissionService` | Admission/discharge logic |
| Request | `StoreAdmissionRequest` | Admission validation |
| View | `wards/index` | Ward list & bed map |
| View | `wards/beds` | Bed management |
| View | `admissions/index` | Current inpatients |
| View | `admissions/create` | Admit patient form |
| View | `admissions/show` | Admission detail (rounds, orders) |
| View | `admissions/discharge` | Discharge form |

### 5.6 Deliverables
- [ ] Ward CRUD (create/edit wards, visual floor layout)
- [ ] Bed management (add/remove beds, set types and rates, status toggle)
- [ ] Admission from visit (doctor selects bed, records admitting diagnosis)
- [ ] Inpatient list (filterable by ward, bed, status)
- [ ] Ward round recording (nurse notes per round with vitals)
- [ ] Discharge workflow (doctor initiates → billing clears charges → released)
- [ ] Bed availability dashboard (visual grid: green=available, red=occupied)
- [ ] Visit status transitions updated for inpatient flow
- [ ] Audit logging on admissions

---

## 6. PHASE 12 — APPOINTMENTS & SCHEDULING

> **Priority: 🟡 IMPORTANT** — The old system's frmMDI had an appointment grid. Patients could be scheduled for follow-up visits.

### 6.1 Objectives
- Schedule patient appointments with specific doctors/departments
- Appointment calendar views (daily, weekly, monthly)
- Appointment status tracking (scheduled, confirmed, checked-in, completed, no-show, cancelled)
- Doctor's appointment list for the day
- Auto-create visit from confirmed appointment

### 6.2 Database Tables

#### `appointments`
```
- id (bigint, PK)
- appointment_number (string, unique, auto: APT-YYYYMMDD-XXXX)
- patient_id (FK → patients)
- doctor_id (FK → users, nullable)
- department_id (FK → departments)
- appointment_date (date)
- start_time (time)
- end_time (time, nullable)
- visit_type (enum: outpatient, inpatient, emergency, follow_up)
- reason (text, nullable)
- notes (text, nullable)
- status (enum: scheduled, confirmed, checked_in, in_progress, completed, no_show, cancelled)
- visit_id (FK → visits, nullable)         — Linked when patient checks in
- created_by (FK → users)
- cancelled_by (FK → users, nullable)
- cancellation_reason (text, nullable)
- timestamps
- soft_deletes
```

### 6.3 Files to Create
| Type | File | Purpose |
|------|------|---------|
| Migration | `create_appointments_table` | Appointments |
| Model | `Appointment` | Appointment model |
| Enum | `AppointmentStatus` | Appointment statuses |
| Controller | `Admin/AppointmentController` | Appointment CRUD + calendar |
| Service | `AppointmentService` | Appointment logic + conflict checking |
| Request | `StoreAppointmentRequest` | Validation |
| View | `appointments/index` | Appointment list + calendar |
| View | `appointments/create` | Schedule appointment form |
| View | `appointments/show` | Appointment details |

### 6.4 Deliverables
- [ ] Schedule appointments (patient, doctor, department, date/time, reason)
- [ ] Calendar view (week view default, toggle to list)
- [ ] Doctor's daily appointment list
- [ ] Check-in from appointment (auto-create visit)
- [ ] Appointment status tracking (scheduled → confirmed → checked-in → completed)
- [ ] No-show tracking
- [ ] Time conflict detection (double-booking prevention)
- [ ] Dashboard widget: Today's Appointments

---

## 7. PHASE 13 — CLAIMS & INSURANCE PROCESSING

> **Priority: 🔴 CRITICAL** — The old system had full NHIS and private insurance claims processing. This is a revenue-critical module for Ghanaian hospitals.

### 7.1 Objectives
- NHIS claims generation from patient visits/invoices
- Private insurance claims with separate workflows
- Claims batch processing (monthly submission)
- Claims status tracking (draft, submitted, approved, rejected, paid)
- Claims export for NHIS portal submission
- Doctor assignment for claims verification

### 7.2 Database Tables

#### `insurance_providers`
```
- id (bigint, PK)
- name (string)                     — e.g., "NHIA", "Nationwide Insurance", "Star Assurance"
- short_name (string)               — e.g., "NHIS", "NW", "STAR"
- type (enum: nhis, private, corporate)
- contact_phone (string, nullable)
- contact_email (string, nullable)
- address (text, nullable)
- contract_number (string, nullable)
- is_active (boolean, default true)
- timestamps
```

#### `claims`
```
- id (bigint, PK)
- claim_number (string, unique, auto: CLM-YYYYMM-XXXX)
- insurance_provider_id (FK → insurance_providers)
- patient_id (FK → patients)
- visit_id (FK → visits)
- invoice_id (FK → invoices, nullable)
- claim_date (date)
- period_from (date)
- period_to (date)
- total_amount (decimal 12,2)
- approved_amount (decimal 12,2, nullable)
- status (enum: draft, submitted, under_review, approved, partially_approved, rejected, paid, appealed)
- submitted_at (datetime, nullable)
- reviewed_at (datetime, nullable)
- reviewer_notes (text, nullable)
- assigned_doctor_id (FK → users, nullable)
- created_by (FK → users)
- timestamps
- soft_deletes
```

#### `claim_items`
```
- id (bigint, PK)
- claim_id (FK → claims)
- service_name (string)
- service_type (enum: consultation, investigation, procedure, medication, bed_charge, other)
- quantity (integer, default 1)
- unit_price (decimal 10,2)
- total_price (decimal 10,2)
- approved_amount (decimal 10,2, nullable)
- status (enum: pending, approved, rejected)
- rejection_reason (text, nullable)
- timestamps
```

### 7.3 Legacy Workflow Mapping

| Old System (frmClaims) | New System |
|------------------------|------------|
| NHIS claims by patient & date range | New: ClaimController@create with auto-populate from invoices |
| Doctor assignment for verification | New: Claims requires `assigned_doctor_id` before submission |
| Claim status tracking | New: ClaimStatus enum with full lifecycle |
| Private insurance claims (frmPrivateClaimView) | New: Same controller, filtered by provider type |
| Claims export | New: Excel export via maatwebsite/excel |

### 7.4 Files to Create
| Type | File | Purpose |
|------|------|---------|
| Migration | `create_insurance_providers_table` | Providers |
| Migration | `create_claims_table` | Claims |
| Migration | `create_claim_items_table` | Claim line items |
| Model | `InsuranceProvider` | Provider model |
| Model | `Claim` | Claim model |
| Model | `ClaimItem` | Claim item model |
| Enum | `ClaimStatus` | Claim statuses |
| Enum | `ClaimItemStatus` | Claim item statuses |
| Enum | `InsuranceType` | nhis, private, corporate |
| Controller | `Admin/InsuranceProviderController` | Provider CRUD |
| Controller | `Admin/ClaimController` | Claims CRUD + submission |
| Service | `ClaimService` | Claims logic + auto-generation |
| Request | `StoreClaimRequest` | Validation |
| Export | `ClaimsExport` | Excel export for NHIS submission |
| View | `claims/index` | Claims list (filterable by status, provider, date) |
| View | `claims/create` | Create claim from visit |
| View | `claims/show` | Claim detail with items |
| View | `claims/review` | Review & approval interface |
| View | `insurance/index` | Insurance provider management |

### 7.5 Deliverables
- [ ] Insurance provider management (NHIS seeded, private CRUD)
- [ ] Auto-generate claims from NHIS/private invoices
- [ ] Claims list with status filters and date range
- [ ] Claim detail view with line items (services + amounts)
- [ ] Doctor assignment for claim verification
- [ ] Claims submission workflow (draft → submitted → reviewed → approved/rejected → paid)
- [ ] Partial approval support (approve/reject individual items)
- [ ] Claims batch export to Excel (for NHIS portal)
- [ ] Claims reports (monthly summary, rejection analysis)
- [ ] Link patient NHIS number to insurance_providers

---

## 8. PHASE 14 — STORE, PROCUREMENT & STOCK TRANSFERS

> **Priority: 🔴 CRITICAL** — The old system had a full store module (frmDrudInfo, frmPurchase, frmTransfer) for purchasing drugs and transferring stock to pharmacy.

### 8.1 Objectives
- Purchase order creation and receiving
- Batch-based stock receiving
- Stock transfer from central store to pharmacy
- Supplier management
- Purchase history and tracking
- Store vs pharmacy stock separation

### 8.2 Database Tables

#### `suppliers`
```
- id (bigint, PK)
- name (string)
- contact_person (string, nullable)
- phone (string, nullable)
- email (string, nullable)
- address (text, nullable)
- is_active (boolean, default true)
- timestamps
```

#### `purchase_orders`
```
- id (bigint, PK)
- po_number (string, unique, auto: PO-YYYYMMDD-XXXX)
- supplier_id (FK → suppliers)
- order_date (date)
- expected_date (date, nullable)
- received_date (date, nullable)
- total_amount (decimal 12,2)
- status (enum: draft, submitted, approved, partially_received, received, cancelled)
- notes (text, nullable)
- created_by (FK → users)
- approved_by (FK → users, nullable)
- timestamps
- soft_deletes
```

#### `purchase_order_items`
```
- id (bigint, PK)
- purchase_order_id (FK)
- drug_id (FK → drugs)
- quantity_ordered (integer)
- quantity_received (integer, default 0)
- unit_cost (decimal 10,2)
- total_cost (decimal 10,2)
- batch_number (string, nullable)
- expiry_date (date, nullable)
- timestamps
```

#### `stock_transfers`
```
- id (bigint, PK)
- transfer_number (string, unique, auto: TRF-YYYYMMDD-XXXX)
- from_location (enum: store, pharmacy)    — In old system: store → pharmacy
- to_location (enum: store, pharmacy)
- transferred_by (FK → users)
- approved_by (FK → users, nullable)
- transfer_date (datetime)
- status (enum: pending, approved, completed, cancelled)
- notes (text, nullable)
- timestamps
```

#### `stock_transfer_items`
```
- id (bigint, PK)
- stock_transfer_id (FK)
- drug_id (FK → drugs)
- quantity (integer)
- batch_number (string, nullable)
- timestamps
```

### 8.3 Legacy Mapping

| Old System | New System |
|-----------|------------|
| frmDrudInfo — Drug catalog + dual stock view | Existing DrugController + new store stock column |
| frmPurchase — Batch purchasing | New: PurchaseOrderController |
| frmTransfer — Store→Pharmacy transfer | New: StockTransferController |
| frmTransferView — Transfer history | New: StockTransferController@index |
| `med_store.Qty` + `med_pharm.Qty` | Modify DrugStock to support location (store vs pharmacy) |

### 8.4 Files to Create
| Type | File | Purpose |
|------|------|---------|
| Migration | `create_suppliers_table` | Suppliers |
| Migration | `create_purchase_orders_table` | Purchase orders |
| Migration | `create_purchase_order_items_table` | PO items |
| Migration | `create_stock_transfers_table` | Transfers |
| Migration | `create_stock_transfer_items_table` | Transfer items |
| Migration | `add_location_to_drug_stock` | Store vs pharmacy stock |
| Model | `Supplier` | Supplier model |
| Model | `PurchaseOrder` | PO model |
| Model | `PurchaseOrderItem` | PO item model |
| Model | `StockTransfer` | Transfer model |
| Model | `StockTransferItem` | Transfer item model |
| Controller | `Admin/SupplierController` | Supplier CRUD |
| Controller | `Admin/PurchaseOrderController` | PO workflow |
| Controller | `Admin/StockTransferController` | Transfer workflow |
| Service | `ProcurementService` | PO + receiving logic |
| Service | `StockTransferService` | Transfer logic + stock updates |
| View | `store/suppliers` | Supplier management |
| View | `store/purchase-orders/index` | PO list |
| View | `store/purchase-orders/create` | Create PO |
| View | `store/purchase-orders/show` | PO detail + receive |
| View | `store/transfers/index` | Transfer list |
| View | `store/transfers/create` | Create transfer |

### 8.5 Deliverables
- [ ] Supplier management (CRUD)
- [ ] Purchase order creation (select supplier, add drug items with quantity + cost)
- [ ] PO approval workflow (draft → approved → receiving)
- [ ] Partial receiving (receive items in batches with batch numbers + expiry)
- [ ] Stock transfer creation (store→pharmacy, select items and quantities)
- [ ] Transfer approval and completion (auto-update stock levels)
- [ ] Store stock vs pharmacy stock separation (dual views)
- [ ] Purchase history report
- [ ] Low stock alerts trigger from both store and pharmacy

---

## 9. PHASE 15 — ACCOUNTS & FINANCIAL MANAGEMENT ✅ COMPLETED

> **Priority: 🟡 IMPORTANT** — The old system had expense tracking, income recording, daily collection reports, cashier handover, and account reconciliation. Our current system only has basic invoice payments.
>
> **Status: ✅ COMPLETED** — Implemented expense tracking, income recording, cashier handover/shift management, daily collection report, financial reconciliation dashboard, and account categories. 2 enums, 3 migrations, 3 models, 1 service, 3 form requests, 3 controllers, 17 routes, 5 permissions, 8 views, sidebar navigation updated.

### 9.1 Objectives
- Expense tracking (petty cash, procurement, utilities)
- Income recording (non-invoice income sources)
- Cashier handover/shift management
- Daily collection summary
- Financial reconciliation dashboard
- Account categories and chart of accounts

### 9.2 Database Tables

#### `account_categories`
```
- id (bigint, PK)
- name (string)                     — e.g., "Utilities", "Procurement", "Maintenance"
- type (enum: income, expense)
- description (text, nullable)
- is_active (boolean, default true)
- timestamps
```

#### `financial_entries`
```
- id (bigint, PK)
- entry_number (string, unique, auto: FIN-YYYYMMDD-XXXX)
- category_id (FK → account_categories)
- type (enum: income, expense)
- amount (decimal 12,2)
- payment_method (enum: cash, momo, bank_transfer, cheque)
- reference_number (string, nullable)
- receipt_number (string, nullable)
- description (text)
- entry_date (date)
- recorded_by (FK → users)
- approved_by (FK → users, nullable)
- timestamps
- soft_deletes
```

#### `cashier_shifts`
```
- id (bigint, PK)
- user_id (FK → users)
- shift_date (date)
- started_at (datetime)
- ended_at (datetime, nullable)
- opening_balance (decimal 10,2, default 0)
- expected_closing (decimal 10,2, nullable)    — Calculated
- actual_closing (decimal 10,2, nullable)      — Entered by cashier
- variance (decimal 10,2, nullable)
- notes (text, nullable)
- status (enum: open, closed, verified)
- verified_by (FK → users, nullable)
- timestamps
```

### 9.3 Files to Create
| Type | File | Purpose |
|------|------|---------|
| Migration | `create_account_categories_table` | Categories |
| Migration | `create_financial_entries_table` | Income/expense entries |
| Migration | `create_cashier_shifts_table` | Handover tracking |
| Model | `AccountCategory` | Category model |
| Model | `FinancialEntry` | Entry model |
| Model | `CashierShift` | Shift model |
| Controller | `Admin/AccountCategoryController` | Category CRUD |
| Controller | `Admin/FinancialEntryController` | Income/expense CRUD |
| Controller | `Admin/CashierShiftController` | Handover management |
| Service | `AccountingService` | Financial calculations |
| View | `accounts/categories` | Category management |
| View | `accounts/expenses/index` | Expense list |
| View | `accounts/expenses/create` | Record expense |
| View | `accounts/income/index` | Income list |
| View | `accounts/income/create` | Record income |
| View | `accounts/handover` | Cashier handover form |
| View | `accounts/reconciliation` | Financial reconciliation dashboard |
| View | `accounts/daily-collection` | Daily collection report |

### 9.4 Deliverables
- [ ] Account category management (income/expense types)
- [ ] Expense recording (amount, category, payment method, description, receipt)
- [ ] Income recording (non-patient revenue sources)
- [ ] Cashier shift management (open shift → collect payments → close shift → handover)
- [ ] Daily collection report (all payments + cash/momo/card breakdown)
- [ ] Shift variance reporting (expected vs actual)
- [ ] Financial reconciliation dashboard (income vs expenses, by period)
- [ ] Petty cash tracking

---

## 10. PHASE 16 — HR & PAYROLL

> **Priority: 🟡 MEDIUM** — The old system had employee management, attendance, leave, and payroll. This is important but less urgent than clinical modules.

### 10.1 Objectives
- Employee records management (distinct from system users)
- Staff attendance tracking (clock in/out)
- Leave request and approval workflow
- Monthly payroll processing with Ghana-specific deductions (SSNIT, Tax)
- Payslip generation

### 10.2 Database Tables

#### `employees`
```
- id (bigint, PK)
- employee_number (string, unique, auto: EMP-XXXX)
- user_id (FK → users, nullable)       — Linked to system user if applicable
- first_name (string)
- last_name (string)
- gender (enum)
- date_of_birth (date)
- phone (string)
- email (string, nullable)
- address (text, nullable)
- department_id (FK → departments)
- position (string)
- hire_date (date)
- basic_salary (decimal 10,2)
- bank_name (string, nullable)
- bank_account (string, nullable)
- bank_branch (string, nullable)
- ssnit_number (string, nullable)      — Ghana SSNIT
- tin_number (string, nullable)        — Ghana TIN
- emergency_contact_name (string, nullable)
- emergency_contact_phone (string, nullable)
- status (enum: active, on_leave, terminated, retired)
- timestamps
- soft_deletes
```

#### `employee_attendance`
```
- id (bigint, PK)
- employee_id (FK → employees)
- date (date)
- clock_in (time, nullable)
- clock_out (time, nullable)
- hours_worked (decimal 4,2, nullable)
- status (enum: present, absent, late, half_day, holiday)
- notes (text, nullable)
- timestamps
```

#### `leave_requests`
```
- id (bigint, PK)
- employee_id (FK → employees)
- leave_type (enum: annual, sick, maternity, paternity, compassionate, study, unpaid)
- start_date (date)
- end_date (date)
- days (integer)
- reason (text, nullable)
- status (enum: pending, approved, rejected, cancelled)
- approved_by (FK → users, nullable)
- approved_at (datetime, nullable)
- rejection_reason (text, nullable)
- timestamps
```

#### `payroll_records`
```
- id (bigint, PK)
- employee_id (FK → employees)
- pay_period (string)                  — e.g., "2026-04"
- basic_salary (decimal 10,2)
- allowances (decimal 10,2, default 0)
- gross_pay (decimal 10,2)
- ssnit_employee (decimal 10,2)        — 5.5% employee contribution
- ssnit_employer (decimal 10,2)        — 13% employer contribution
- tax (decimal 10,2)                   — Ghana PAYE tax
- other_deductions (decimal 10,2, default 0)
- net_pay (decimal 10,2)
- status (enum: draft, approved, paid)
- processed_by (FK → users)
- paid_at (datetime, nullable)
- timestamps
```

### 10.3 Ghana-Specific Payroll
| Deduction | Rate | Notes |
|-----------|------|-------|
| SSNIT (Employee) | 5.5% of basic | Mandatory for formal employees |
| SSNIT (Employer) | 13% of basic | Employer contributes; not deducted from employee |
| PAYE Tax | Progressive | GRA tax brackets (annually updated) |

### 10.4 Files to Create
| Type | File |
|------|------|
| Migration | `create_employees_table` |
| Migration | `create_employee_attendance_table` |
| Migration | `create_leave_requests_table` |
| Migration | `create_payroll_records_table` |
| Model | `Employee`, `EmployeeAttendance`, `LeaveRequest`, `PayrollRecord` |
| Enum | `LeaveType`, `LeaveStatus`, `EmployeeStatus`, `PayrollStatus` |
| Controller | `Admin/EmployeeController`, `Admin/AttendanceController`, `Admin/LeaveController`, `Admin/PayrollController` |
| Service | `HRService`, `PayrollService` |
| View | `hr/employees/*`, `hr/attendance/*`, `hr/leave/*`, `hr/payroll/*` |
| Export | `PayslipPdf` (dompdf) |

### 10.5 Deliverables
- [ ] Employee CRUD (distinct from users, linked optionally)
- [ ] Staff attendance tracking (daily clock in/out)
- [ ] Leave request submission (employee) and approval (manager)
- [ ] Leave balance tracking (annual allocation vs used)
- [ ] Monthly payroll processing (auto-calculate SSNIT + tax)
- [ ] Payslip PDF generation
- [ ] Attendance report (monthly summary)
- [ ] Payroll report (monthly, by department)

---

## 11. PHASE 17 — NOTIFICATION SYSTEM

> **Priority: 🔴 CRITICAL** — The old system used a polling-based notification system (BackgroundWorker + notifications table) with desktop alerts color-coded by department. This is the inter-departmental communication backbone.

### 11.1 Objectives
- Real-time in-app notifications (database + optional broadcast)
- Notification triggers on key workflow events
- Notification bell with unread count
- Click-to-navigate to relevant page
- Department-targeted notifications

### 11.2 Implementation Approach

Use **Laravel's built-in notification system**:
- `DatabaseNotification` model (uses `notifications` table — ships with Laravel)
- `BroadcastNotification` channel (optional, for real-time via Echo)
- Events + Listeners for decoupled triggers

### 11.3 Notification Events
| Event | Who Gets Notified | Trigger |
|-------|-------------------|---------|
| Patient registered | Receptionist, Ward nurses | Patient created |
| Visit created | Assigned doctor, Ward nurses | Visit saved |
| Vitals recorded | Assigned doctor | VitalController@store |
| Doctor ordered lab | Lab Technicians | Lab request created |
| Doctor prescribed meds | Pharmacists | Prescription created |
| Lab results ready | Assigned doctor | Lab result saved |
| Pharmacy dispensed | Accountants (for billing) | Dispensing completed |
| Invoice created | Accountants | Invoice created |
| Payment received | Accountants, Admin | Payment recorded |
| Patient admitted | Ward nurses, Assigned doctor | Admission created |
| Discharge requested | Accountants (final billing) | Discharge initiated |
| Appointment reminder | Patient's assigned doctor | Scheduled (cron job) |
| Stock low alert | Pharmacists, Store keeper | Stock falls below threshold |
| Leave request submitted | HR Manager | Leave request created |
| Claim ready for review | Claims officer | Claim submitted |

### 11.4 Files to Create
| Type | File |
|------|------|
| Migration | Laravel's default `create_notifications_table` |
| Notification | `LabRequestNotification` |
| Notification | `PrescriptionNotification` |
| Notification | `PaymentNotification` |
| Notification | `StockAlertNotification` |
| Notification | `AdmissionNotification` |
| Notification | `GeneralNotification` |
| Event | `LabRequestCreated`, `PrescriptionCreated`, `PaymentRecorded`, etc. |
| Listener | Corresponding listeners for each event |
| Controller | Update `header.blade.php` for notification dropdown |
| View | Notification dropdown (already has placeholder) |
| View | `notifications/index` — Full notification history |

### 11.5 Deliverables
- [ ] Laravel notifications table migrated
- [ ] Notification classes for all key workflow events
- [ ] Events fired from service layer on state changes
- [ ] Header bell shows unread count (AJAX polling or Echo broadcast)
- [ ] Notification dropdown with recent notifications
- [ ] Click notification → navigates to relevant page
- [ ] Mark as read (individual + mark all)
- [ ] Full notification history page
- [ ] Department-targeted routing (e.g., lab request → all Lab Technicians)

---

## 12. PHASE 18 — ICD-10, PROCEDURES & CLINICAL ENHANCEMENTS

> **Priority: 🟡 IMPORTANT** — The old system had ICD-10 lookup (via REST API), a procedures module (theater scheduling), and multi-department investigations. These enhance clinical accuracy.

### 12.1 Objectives
- ICD-10 code database with search (offline, not API-dependent)
- Procedure catalog and scheduling
- Multi-department investigation support (not just Lab — include X-Ray, Scan, Dental, etc.)
- Enhanced consultation with ICD-10 linked diagnoses

### 12.2 Database Tables

#### `icd_codes`
```
- id (bigint, PK)
- code (string, unique, indexed)     — e.g., "J18.9"
- description (string)               — e.g., "Pneumonia, unspecified organism"
- category (string)                  — e.g., "Diseases of the respiratory system"
- chapter (string)                   — ICD-10 chapter
- is_billable (boolean)
- timestamps
```

#### `procedures`
```
- id (bigint, PK)
- name (string)
- code (string, nullable)
- department_id (FK → departments)
- category (enum: surgical, diagnostic, therapeutic, other)
- description (text, nullable)
- default_price (decimal 10,2)
- nhis_price (decimal 10,2, nullable)
- requires_consent (boolean, default false)
- is_active (boolean, default true)
- timestamps
```

#### `patient_procedures`
```
- id (bigint, PK)
- visit_id (FK → visits)
- patient_id (FK → patients)
- procedure_id (FK → procedures)
- performed_by (FK → users)
- scheduled_date (datetime)
- performed_date (datetime, nullable)
- status (enum: scheduled, in_progress, completed, cancelled)
- notes (text, nullable)
- outcome (text, nullable)
- consent_signed (boolean, default false)
- timestamps
```

### 12.3 Files to Create
| Type | File |
|------|------|
| Migration | `create_icd_codes_table` |
| Migration | `create_procedures_table` |
| Migration | `create_patient_procedures_table` |
| Migration | `add_icd_code_to_diagnoses` |
| Model | `IcdCode`, `Procedure`, `PatientProcedure` |
| Controller | `Admin/IcdCodeController` (search API) |
| Controller | `Admin/ProcedureController` (catalog CRUD) |
| Seeder | `IcdCodeSeeder` (import ICD-10 dataset) |
| View | Updates to consultation form for ICD-10 search |
| View | `procedures/index`, `procedures/schedule` |

### 12.4 Deliverables
- [ ] ICD-10 codes imported (13,000+ codes from WHO dataset)
- [ ] ICD-10 search in consultation (AJAX typeahead)
- [ ] Diagnoses linked to ICD-10 codes
- [ ] Procedure catalog (CRUD with department, pricing)
- [ ] Procedure scheduling from consultation
- [ ] Procedure outcome recording
- [ ] Investigation departments expanded (X-Ray, Scan, CT-Scan, Eye, Dental, ENT)
- [ ] Department-specific investigation pricing (Cash, NHIS, Private tiers)

---

## 13. PHASE 19 — ANALYZER INTEGRATION (LAB INSTRUMENTS)

> **Priority: 🟡 IMPORTANT** — The old system had a sophisticated analyzer integration module supporting HL7 v2.x and ASTM protocols for auto-receiving lab results from machines.

### 13.1 Objectives
- Register and configure lab analyzer devices
- Support HL7 v2.x and ASTM E1394 protocols
- TCP/IP and Serial (COM port) listeners
- Auto-match incoming results to lab orders
- Raw message storage and audit
- Diagnostics and troubleshooting UI

### 13.2 Implementation Approach

This is the most complex technical module. It requires:
- **Queue-based processing** (Laravel Jobs + Redis/database queue)
- **TCP server** (ReactPHP or custom socket listener running as a daemon)
- **Protocol parsers** (HL7 and ASTM message parsing)
- **Background workers** (Supervisor on production)

### 13.3 Database Tables

#### `analyzers`
```
- id (bigint, PK)
- name (string)
- model (string, nullable)
- manufacturer (string, nullable)
- protocol (enum: hl7, astm)
- connection_type (enum: tcp, serial)
- ip_address (string, nullable)
- port (integer, nullable)
- com_port (string, nullable)
- baud_rate (integer, nullable)
- is_active (boolean, default false)
- last_connected_at (datetime, nullable)
- timestamps
```

#### `analyzer_test_mappings`
```
- id (bigint, PK)
- analyzer_id (FK → analyzers)
- analyzer_test_code (string)
- lab_test_id (FK → lab_tests)
- unit_conversion_factor (decimal, nullable)
- timestamps
```

#### `analyzer_raw_messages`
```
- id (bigint, PK)
- analyzer_id (FK → analyzers)
- protocol (string)
- direction (enum: inbound, outbound)
- content (longText)
- content_hash (string, indexed)
- processing_status (enum: received, processing, processed, failed, duplicate)
- processing_attempts (integer, default 0)
- error_message (text, nullable)
- received_at (datetime)
- processed_at (datetime, nullable)
- timestamps
```

### 13.4 Files to Create
| Type | File |
|------|------|
| Migration | `create_analyzers_table` |
| Migration | `create_analyzer_test_mappings_table` |
| Migration | `create_analyzer_raw_messages_table` |
| Model | `Analyzer`, `AnalyzerTestMapping`, `AnalyzerRawMessage` |
| Service | `AnalyzerService` (lifecycle, listeners) |
| Service | `HL7Parser` (HL7 v2.x message parsing) |
| Service | `ASTMParser` (ASTM E1394 parsing) |
| Service | `ResultDispatchService` (match results to lab orders) |
| Job | `ProcessAnalyzerMessage` (queue-based processing) |
| Controller | `Admin/AnalyzerController` (device CRUD + diagnostics) |
| View | `analyzers/index`, `analyzers/create`, `analyzers/diagnostics` |
| Command | `analyzer:listen` (artisan command for daemon) |

### 13.5 Deliverables
- [ ] Analyzer device registration (name, protocol, connection)
- [ ] Test code mapping (analyzer code → UHMS lab test)
- [ ] HL7 v2.x parser (ORU^R01 observation results)
- [ ] ASTM E1394 parser (H/P/O/R/L records)
- [ ] TCP listener as artisan command (configurable port)
- [ ] Message queuing and processing pipeline
- [ ] Auto-match results to lab orders by sample ID
- [ ] Raw message audit log
- [ ] Critical/panic value flagging
- [ ] Duplicate detection (content hashing)
- [ ] Diagnostics view (connection status, recent messages, errors)

---

## 14. PHASE 20 — ADVANCED REPORTS & DOCUMENT GENERATION

> **Priority: 🟡 IMPORTANT** — The old system had comprehensive reports across all departments, patient statements, and document printing.

### 14.1 Objectives
- Expand report catalog to match legacy system
- Patient statement generation (financial summary)
- Printable documents (consultation notes, lab reports, prescriptions)
- Export to Excel and PDF
- Dashboard analytics enhancements

### 14.2 New Reports
| Report | Description | Export |
|--------|-------------|--------|
| **Pharmacy Sales (Detailed)** | Drug-level dispensing report with prices | Excel, PDF |
| **Pharmacy Sales (Summary)** | Aggregated pharmacy revenue by period | Excel |
| **Investigation Revenue** | Revenue by investigation department | Excel |
| **Consultation Statistics** | Consultations by doctor, department, period | Excel |
| **Daily Collection** | All payments received per day with breakdown | PDF |
| **Patient Statement** | Per-patient financial summary (all visits, charges, payments) | PDF |
| **Admission Report** | Admissions by ward, period, diagnosis | Excel |
| **Discharge Report** | Discharges with length of stay analysis | Excel |
| **Leave Report** | Staff leave summary by type and department | Excel |
| **Payroll Summary** | Monthly payroll by department | Excel, PDF |
| **Claims Report** | Claims by status, provider, amount | Excel |
| **Stock Valuation** | Drug stock value (store + pharmacy) | Excel |
| **Expired Stock** | Drugs approaching/past expiry | Excel |

### 14.3 Files to Create
| Type | File |
|------|------|
| Controller updates | ReportController — add new report methods |
| Service | `StatementService` (patient statement generation) |
| Export | Multiple Laravel Excel export classes |
| PDF | Multiple dompdf views |
| View | `reports/pharmacy-sales`, `reports/daily-collection`, etc. |
| View | `patients/statement` (financial statement per patient) |

### 14.4 Deliverables
- [ ] 13+ new report views
- [ ] Patient statement PDF
- [ ] Excel export on all report pages
- [ ] PDF export on financial reports
- [ ] Printable consultation note
- [ ] Printable lab report
- [ ] Printable prescription

---

## 15. PHASE 21 — FINAL POLISH, TESTING & DEPLOYMENT

> **Priority: 🔴 CRITICAL** — Production readiness.

### 15.1 Objectives
- Comprehensive testing
- Performance optimization
- Security hardening
- Deployment configuration
- User acceptance testing support

### 15.2 Testing
| Category | Scope |
|----------|-------|
| **Feature Tests** | All CRUD operations, workflow transitions |
| **Auth Tests** | Login, permissions, role-based access |
| **Service Tests** | Business logic validation |
| **Browser Tests** | Critical user journeys (patient registration → discharge) |
| **Load Testing** | Concurrent users simulation |

### 15.3 Performance
- [ ] Database indexing audit (all FK columns, frequently queried columns)
- [ ] Eager loading audit (N+1 query prevention)
- [ ] Query optimization (use `select()` to limit columns)
- [ ] Cache strategy (settings, lookup tables, dashboard counts)
- [ ] Asset optimization (minified CSS/JS, lazy loading images)

### 15.4 Security Hardening
- [ ] CSRF protection verified on all forms
- [ ] XSS prevention (Blade `{{ }}` escaping audit)
- [ ] SQL injection prevention (no raw queries without bindings)
- [ ] Rate limiting on auth routes
- [ ] Session security (secure cookies, HTTPS enforcement)
- [ ] File upload validation (type, size, path traversal)
- [ ] Authorization audit (every route has permission check)

### 15.5 Deployment
- [ ] Production `.env` template
- [ ] Nginx configuration (or Apache for XAMPP)
- [ ] Database migration strategy (seed production data)
- [ ] Backup configuration (database + uploaded files)
- [ ] SSL certificate setup
- [ ] Queue worker configuration (Supervisor for analyzers + notifications)
- [ ] Monitoring setup (Laravel Telescope or log monitoring)

### 15.6 Deliverables
- [ ] Test suite covering critical paths
- [ ] Performance benchmarks documented
- [ ] Security checklist completed
- [ ] Deployment guide written
- [ ] Production environment configured
- [ ] User training materials

---

## 16. DATABASE SCHEMA ADDITIONS

### New Tables Summary (Phases 11–21)

```
Ward Management (Phase 11):
├── wards
├── beds
├── admissions
└── ward_rounds

Appointments (Phase 12):
└── appointments

Claims (Phase 13):
├── insurance_providers
├── claims
└── claim_items

Procurement (Phase 14):
├── suppliers
├── purchase_orders
├── purchase_order_items
├── stock_transfers
└── stock_transfer_items

Accounts (Phase 15):
├── account_categories
├── financial_entries
└── cashier_shifts

HR (Phase 16):
├── employees
├── employee_attendance
├── leave_requests
└── payroll_records

Notifications (Phase 17):
└── notifications (Laravel default)

Clinical (Phase 18):
├── icd_codes
├── procedures
└── patient_procedures

Analyzer (Phase 19):
├── analyzers
├── analyzer_test_mappings
└── analyzer_raw_messages

Total new tables: ~25
Running total: ~57 tables
```

---

## 17. PRIORITY MATRIX

### Implementation Order (Recommended)

| Order | Phase | Module | Priority | Reasoning |
|-------|-------|--------|----------|-----------|
| 1 | **11** | Inpatient & Ward | 🔴 Critical | Hospitals cannot function without ward management |
| 2 | **13** | Claims & Insurance | 🔴 Critical | NHIS billing is the primary revenue source for most Ghana hospitals |
| 3 | **17** | Notifications | 🔴 Critical | Departments cannot coordinate without notifications |
| 4 | **14** | Store & Procurement | 🔴 Critical | Pharmacy cannot be restocked without purchase/transfer |
| 5 | **15** | Accounts & Finance | 🟡 Important | Financial controls and cashier management |
| 6 | **12** | Appointments | 🟡 Important | Improves patient flow but not blocking |
| 7 | **18** | ICD-10 & Procedures | 🟡 Important | Clinical accuracy improvement |
| 8 | **20** | Advanced Reports | 🟡 Important | Operational intelligence |
| 9 | **16** | HR & Payroll | 🟡 Medium | Can use manual processes temporarily |
| 10 | **19** | Analyzer Integration | 🟡 Medium | Only needed if hospital has connected instruments |
| 11 | **21** | Polish & Deployment | 🔴 Critical | Required before go-live |

### Estimated Scope

| Phase | New Models | New Controllers | New Views | New Migrations | New Routes (est.) |
|-------|-----------|----------------|-----------|----------------|-------------------|
| 11 | 4 | 2 | 6 | 5 | ~20 |
| 12 | 1 | 1 | 3 | 1 | ~8 |
| 13 | 3 | 2 | 5 | 3 | ~15 |
| 14 | 4 | 3 | 6 | 6 | ~18 |
| 15 | 3 | 3 | 8 | 3 | ~15 |
| 16 | 4 | 4 | 12 | 4 | ~25 |
| 17 | 0* | 1 | 2 | 1 | ~5 |
| 18 | 3 | 2 | 4 | 3 | ~12 |
| 19 | 3 | 1 | 3 | 3 | ~8 |
| 20 | 0 | updates | 13 | 0 | ~15 |
| 21 | 0 | 0 | 0 | 0 | 0 |
| **Total** | **25** | **19** | **62** | **29** | **~141** |

*Phase 17 uses Laravel's built-in Notification model

**Final totals after all phases:**
- Models: ~56
- Controllers: ~49
- Views: ~119
- Migrations: ~60
- Routes: ~284
- Tables: ~57

---

## 18. RISK ASSESSMENT

| Risk | Probability | Impact | Mitigation |
|------|------------|--------|------------|
| Analyzer integration complexity | High | Medium | Implement last, can operate manually |
| NHIS API/portal changes | Medium | High | Build export format, not direct API integration |
| Payroll tax rate changes | Medium | Low | Store rates in settings, not hardcoded |
| Performance at scale | Medium | High | Index audit, eager loading, caching |
| Data migration from old system | High | High | Build import scripts, validate data integrity |
| Scope creep per phase | Medium | Medium | Strict phase boundaries, complete one before starting next |
| MariaDB compatibility | Low | Medium | Already handled (longText, defaultStringLength) |

---

## PROGRESS OVERVIEW

| Phase | Module | Status | Completed |
|-------|--------|--------|-----------|
| **Phase 11** | Inpatient & Ward | ✅ Complete | 2026-04-12 |
| **Phase 12** | Appointments & Scheduling | ✅ Complete | 2026-04-12 |
| **Phase 13** | Claims & Insurance | ✅ Complete | 2026-04-12 |
| **Phase 14** | Store & Procurement | ✅ Complete | 2026-04-12 |
| **Phase 15** | Accounts & Finance | ✅ Complete | 2026-04-12 |
| **Phase 16** | HR & Payroll | ✅ Complete | 2026-04-12 |
| **Phase 17** | Notification System | ⬜ Not Started | — |
| **Phase 18** | ICD-10 & Procedures | ⬜ Not Started | — |
| **Phase 19** | Analyzer Integration | ⬜ Not Started | — |
| **Phase 20** | Advanced Reports | ⬜ Not Started | — |
| **Phase 21** | Final Polish & Deployment | ⬜ Not Started | — |

---

*Generated: 2026-04-12*
*Based on: Legacy UHMS v5.2.0 analysis + current Laravel build (Phases 0–10)*
