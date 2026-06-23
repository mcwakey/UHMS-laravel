# UHMS — Full Gap Analysis & Implementation Plan

**Generated:** April 13, 2026  
**Source:** PROJECT_ANALYSIS.md (Vue 3 + Django template workflow specification)  
**Target:** Current Laravel monolith (Blade + jQuery, server-rendered)

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Architecture Comparison](#2-architecture-comparison)
3. [Module-by-Module Gap Analysis](#3-module-by-module-gap-analysis)
4. [Prioritized Implementation Plan](#4-prioritized-implementation-plan)
5. [Phase Details](#5-phase-details)
6. [Migration & Data Considerations](#6-migration--data-considerations)
7. [Estimated Complexity](#7-estimated-complexity)
8. [Progress Tracker](#8-progress-tracker)

---

## 1. Executive Summary

The PROJECT_ANALYSIS.md describes a **Vue 3 + TypeScript SPA frontend** backed by a **Django REST Framework API**. The current UHMS project is a **Laravel 12 monolith** using server-rendered **Blade templates + jQuery**. This creates two categories of gaps:

### A. Architecture Gap (Strategic Decision Required)

| Aspect | Template Spec | Current Project |
|--------|---------------|-----------------|
| **Frontend** | Vue 3 + TypeScript + Pinia + Vite SPA | Blade + jQuery + Bootstrap 5 (server-rendered) |
| **Backend** | Django REST Framework (JWT API) | Laravel 12 (session-based, Blade views) |
| **Auth** | JWT tokens + localStorage + axios interceptors | Session cookies + Laravel auth guards |
| **Data Loading** | REST API → Pinia stores → reactive rendering | Controller → Blade `compact()` → server render |
| **State** | Client-side Pinia stores with factory pattern | Server-side sessions, no client state |
| **i18n** | Vue i18n (English + French) | None (hardcoded English) |

**Recommendation:** The current Laravel monolith is **fully functional** with 57 models, 48 controllers, 26 services, and comprehensive Blade views. Rebuilding as a Vue SPA would be a **complete rewrite** (6+ months). Instead, this plan implements the **missing functional features** from the template spec within the existing Laravel architecture, and adds an **optional API layer** for future SPA migration.

### B. Feature Gaps (This Implementation Plan)

| Category | Features Matching Spec | Features Missing | Gap % |
|----------|----------------------|------------------|-------|
| **Patient Management** | Registration, profiles, avatar, medical history | Multi-insurance, multi-NOK, cascading insurance dropdowns | 30% |
| **Appointments** | CRUD, calendar, status flow, cancel, no-show | Reschedule tracking, doctor transfer, book-by-service | 35% |
| **Consultation** | Notes, complaints, diagnoses, treatments, investigations, prescriptions | Telehealth, TODO tasks, rich text editor, consultation modes | 40% |
| **Vitals** | 10 of 11 vital signs | Pain level | 5% |
| **HRM** | Employees, departments, attendance, leave, payroll | Holidays management | 10% |
| **Finance** | Invoices, payments, expenses, income, cashier shifts | Asset management, transaction ledger | 20% |
| **Dashboards** | Admin, Doctor | Nurse, Pharmacy, Lab, Investigation, Consultation dept dashboards | 70% |
| **Content CMS** | None | Pages, blogs, FAQs — full CMS | 100% |
| **i18n** | None | Multi-language support | 100% |
| **API Layer** | None (a few JSON endpoints) | Full REST API for SPA consumption | 100% |

---

## 2. Architecture Comparison

### 2.1 What the Template Spec Expects

```
Vue 3 SPA ──▶ Axios + JWT ──▶ Django REST API ──▶ PostgreSQL
                                    │
               Pinia Stores ◀───────┘
               (client-side state)
```

- **Every page** is a Vue component fetching data via REST API
- **DataTableStore factory** creates per-endpoint paginated stores
- **Modal-driven CRUD** — all create/edit operations happen in modals, not full-page forms
- **Offcanvas detail views** — clicking a table row opens a slide-in panel, not a new page
- **Real-time status updates** — appointment status changes reflected immediately

### 2.2 What Currently Exists

```
Browser ──▶ Laravel Router ──▶ Controller ──▶ Blade View ──▶ HTML Response
                                    │
               Service Layer ◀──────┘
               (server-side logic)
```

- **Every page** is a full Blade template with jQuery/DataTables
- **Full-page forms** for create/edit operations
- **Dedicated show pages** for detail views
- **Page reload** after every form submission (redirect with flash message)
- **SweetAlert** for confirmations

### 2.3 Recommended Hybrid Approach

Rather than rewriting the entire frontend, adopt a **progressive enhancement** strategy:

```
Phase 1: Implement missing features in current Blade architecture
Phase 2: Add API endpoints alongside existing routes
Phase 3: (Future) Gradually replace Blade views with Vue components
```

---

## 3. Module-by-Module Gap Analysis

### 3.1 Patient Management

| Template Feature | Current Status | Gap Level |
|-----------------|----------------|-----------|
| Patient CRUD (personal, contact, address) | ✅ Complete | — |
| Avatar/photo upload | ✅ Complete | — |
| Emergency contact (single) | ✅ Complete | — |
| Ghana Card / NHIS number | ✅ Complete | — |
| Allergies / chronic conditions | ✅ Complete | — |
| Medical history via MedicalRecord | ✅ Complete | — |
| Multiple insurance policies per patient | ❌ Missing | HIGH |
| Multiple emergency contacts (repeatable) | ❌ Missing | MEDIUM |
| Insurance cascade (type → company → plan) | ❌ Missing | HIGH |
| Patient search with filterablefields | ✅ Complete (PatientService::list) | — |

**Implementation needed:**
- `patient_insurances` pivot table + model (patient_id, insurance_provider_id, membership_number, policy_number, scheme, issue_date, expiry_date, is_active)
- `emergency_contacts` table + model (patient_id, name, phone, phone_secondary, relationship, is_primary)
- Migrate existing flat fields to new tables
- Insurance type/plan management on InsuranceProvider (add `type` enum: NHIS/PRIVATE/CORPORATE, `plans` JSON or separate table)
- Update patient create/edit forms with repeatable insurance and NOK sections

### 3.2 Appointment System

| Template Feature | Current Status | Gap Level |
|-----------------|----------------|-----------|
| Appointment CRUD | ✅ Complete | — |
| Status state machine (6 states) | ✅ Complete (7 states — more granular) | — |
| Calendar view (FullCalendar) | ✅ Complete | — |
| Book by Doctor + Department | ✅ Complete | — |
| Cancel with reason | ✅ Complete | — |
| No-Show tracking | ✅ Complete | — |
| Check-in → creates Visit | ✅ Complete | — |
| Conflict detection | ✅ Complete | — |
| Book by Service (select service → get available doctors) | ❌ Missing | MEDIUM |
| Reschedule with tracking | ❌ Missing | MEDIUM |
| Doctor transfer with reason | ❌ Missing | MEDIUM |
| Offcanvas detail sidebar | ❌ Missing (has full show page) | LOW |
| Summary card before booking | ❌ Missing | LOW |

**Implementation needed:**
- Add `service_catalog_id` FK to appointments table + update form
- Add `rescheduled_from_id` self-referencing FK + `RESCHEDULED` status + reschedule tracking
- Add `transferred_from_doctor_id`, `transfer_reason` fields + transfer endpoint
- AJAX endpoint to fetch available doctors by service
- Update create form with dual-flow booking (by service or by doctor)

### 3.3 Consultation System

| Template Feature | Current Status | Gap Level |
|-----------------|----------------|-----------|
| MedicalRecord per visit | ✅ Complete | — |
| Complaints CRUD | ✅ Complete | — |
| Diagnoses (with ICD-10) | ✅ Complete | — |
| Investigations | ✅ Complete | — |
| Treatments | ✅ Complete | — |
| Prescriptions | ✅ Complete | — |
| Patient history (last 10 visits) | ✅ Complete | — |
| Medical patterns (templates) | ✅ Complete (EXTRA — not in spec) | — |
| Rich text editor (Quill) for clinical notes | ❌ Missing (plain textarea) | MEDIUM |
| TODO task management during consultation | ❌ Missing | MEDIUM |
| Telehealth / virtual consultation mode | ❌ Missing | HIGH |
| Consultation mode flag (IN_PERSON/TELEHEALTH) | ❌ Missing | LOW |

**Implementation needed:**
- Initialize Quill editor on consultation note textareas (Quill JS/CSS already loaded globally)
- `consultation_tasks` table + model (medical_record_id, title, description, priority, status, assigned_to, due_date)
- ConsultationTask CRUD endpoints + UI within consultation view
- `consultation_mode` enum column on visits or appointments (IN_PERSON/TELEHEALTH/VIRTUAL)
- Telehealth: at minimum, add video call link field + meeting room URL; full WebRTC integration is a separate project

### 3.4 Vitals

| Template Feature | Current Status | Gap Level |
|-----------------|----------------|-----------|
| Temperature (°C/°F) | ✅ Complete | — |
| Blood Pressure (systolic + diastolic) | ✅ Complete | — |
| Heart Rate | ✅ Complete | — |
| Respiratory Rate | ✅ Complete | — |
| SpO2 | ✅ Complete | — |
| Weight | ✅ Complete | — |
| Height | ✅ Complete | — |
| BMI (auto-calculated) | ✅ Complete | — |
| Blood Sugar / Glucose | ✅ Complete (`blood_sugar`) | — |
| Notes | ✅ Complete | — |
| Pain Level (0–10 scale) | ❌ Missing | LOW |

**Implementation needed:**
- Add `pain_level` integer column to vitals table
- Update vitals form + show views

### 3.5 Visit & Queue System

| Template Feature | Current Status | Gap Level |
|-----------------|----------------|-----------|
| Visit management | ✅ Complete (12-state machine — exceeds spec) | — |
| Queue management with board | ✅ Complete | — |
| Priority handling | ✅ Complete | — |
| Check-in from appointment | ✅ Complete | — |
| Walk-in visits | ✅ Complete | — |

**No gaps.** The current visit/queue system is **more advanced** than the template spec (12 states vs 6 in the template's appointment system).

### 3.6 HRM

| Template Feature | Current Status | Gap Level |
|-----------------|----------------|-----------|
| Staff/Employee management | ✅ Complete | — |
| Departments | ✅ Complete | — |
| Designations | ✅ Complete | — |
| Attendance | ✅ Complete | — |
| Leave management | ✅ Complete | — |
| Payroll | ✅ Complete | — |
| Holidays management | ❌ Missing | LOW |
| Shift management | ❌ Missing (enum exists, no model) | LOW |

**Implementation needed:**
- `holidays` table + model (name, date, type: public/restricted, is_recurring, description)
- HolidayController CRUD + admin UI
- Optional: `shifts` table + model for shift scheduling

### 3.7 Finance & Accounts

| Template Feature | Current Status | Gap Level |
|-----------------|----------------|-----------|
| Invoices (CRUD + print) | ✅ Complete | — |
| Payments (against invoices) | ✅ Complete | — |
| Expenses (with categories) | ✅ Complete (FinancialEntry type=expense) | — |
| Income tracking | ✅ Complete (FinancialEntry type=income) | — |
| Cashier shifts | ✅ Complete (CashierShift model) | — |
| Daily collection reports | ✅ Complete | — |
| Reconciliation | ✅ Complete | — |
| Asset management | ❌ Missing | MEDIUM |
| Transaction ledger view | ❌ Missing (data exists via payments + entries) | LOW |

**Implementation needed:**
- `assets` table + model (name, asset_number, category, purchase_date, purchase_price, current_value, depreciation_rate, location, assigned_to, status, notes)
- AssetController CRUD
- Unified transaction view combining Payment + FinancialEntry records

### 3.8 Dashboards

| Template Feature | Current Status | Gap Level |
|-----------------|----------------|-----------|
| Admin Dashboard | ✅ Complete | — |
| Doctor Dashboard | ✅ Complete | — |
| Consultation Dashboard | ❌ Missing | MEDIUM |
| Investigation (Lab) Dashboard | ❌ Missing | MEDIUM |
| Nursing Dashboard | ❌ Missing | MEDIUM |
| Pharmacy Dashboard | ❌ Missing | MEDIUM |
| Treatment Dashboard | ❌ Missing | LOW |
| Procedure Dashboard | ❌ Missing | LOW |
| Medication Dashboard | ❌ Missing | LOW |
| Emergency Dashboard | ❌ Missing | LOW |
| Patient Portal Dashboard | ❌ Missing | HIGH |

**Implementation needed:**
- Minimum 5 new dashboards: Nurse, Pharmacy, Lab, Billing, Patient
- Each needs: role-based routing, dedicated controller, stats aggregation, Blade view
- Patient portal: self-service appointment viewing, test results, prescriptions

### 3.9 Content Management (CMS)

| Template Feature | Current Status | Gap Level |
|-----------------|----------------|-----------|
| Pages management | ❌ Template views only | HIGH |
| Blogs (CRUD + categories + comments) | ❌ Template views only | HIGH |
| FAQs | ❌ Template views only | MEDIUM |

**Implementation needed:**
- `pages` table + model (title, slug, content, status, meta_title, meta_description, author_id)
- `blog_posts` table + model (title, slug, content, excerpt, featured_image, status, category_id, author_id, published_at)
- `blog_categories` table + model (name, slug, description)
- `blog_comments` table + model (blog_post_id, user_id, name, email, content, status, parent_id)
- `faqs` table + model (question, answer, category, sort_order, is_active)
- Controllers + CRUD views for each
- Optional: Rich text editor for page/blog content (Quill/TinyMCE)

### 3.10 i18n / Multi-Language

| Template Feature | Current Status | Gap Level |
|-----------------|----------------|-----------|
| English translation | ❌ Hardcoded strings | HIGH |
| French translation | ❌ Not available | HIGH |
| Translation switching | ❌ No mechanism | HIGH |

**Implementation needed:**
- Extract all UI strings to Laravel lang files (`lang/en/`, `lang/fr/`)
- Replace hardcoded Blade strings with `__()` / `@lang()` calls
- Language switcher in header with session/cookie persistence
- Minimum: English + French

### 3.11 API Layer (For Future SPA Migration)

| Template Feature | Current Status | Gap Level |
|-----------------|----------------|-----------|
| RESTful API endpoints | ❌ No API routes file | HIGH |
| JWT authentication | ❌ Session-based only | HIGH |
| Paginated JSON responses | ❌ Partial (few JSON endpoints) | HIGH |
| API resource transformers | ❌ Not needed yet | HIGH |

**Implementation needed (Optional — for SPA readiness):**
- `routes/api.php` with versioned endpoints (v1/)
- Laravel Sanctum or JWT auth package
- API Resource classes for all models
- Standardized JSON response format matching DRF pagination contract

---

## 4. Prioritized Implementation Plan

### Priority Legend

| Priority | Criteria |
|----------|---------|
| **P0 — Critical** | Core clinical workflow gap that breaks patient care continuity |
| **P1 — High** | Major feature gap visible to end users daily |
| **P2 — Medium** | Important feature for completeness and parity with spec |
| **P3 — Low** | Nice-to-have, cosmetic, or future-oriented |

### Phase Overview

| Phase | Name | Priority | Features | Est. Effort |
|-------|------|----------|----------|-------------|
| **Phase A** | Patient Insurance & NOK | P0 | Multi-insurance, multi-NOK, cascade dropdowns | Large |
| **Phase B** | Appointment Enhancements | P1 | Reschedule tracking, doctor transfer, book-by-service | Medium |
| **Phase C** | Consultation Enhancements | P1 | Rich text editor, TODO tasks, consultation modes | Medium |
| **Phase D** | Department Dashboards | P1 | 5 new role-based dashboards | Medium |
| **Phase E** | Vitals & Holiday Gaps | P2 | Pain level, holidays CRUD | Small |
| **Phase F** | Asset Management | P2 | Financial assets CRUD | Small |
| **Phase G** | Content CMS | P2 | Pages, blogs, FAQs | Medium |
| **Phase H** | i18n Multi-Language | P3 | English + French extraction | Large |
| **Phase I** | API Layer | P3 | REST API + Sanctum auth | Very Large |
| **Phase J** | Patient Portal | P3 | Self-service patient-facing portal | Large |

---

## 5. Phase Details

### Phase A — Patient Insurance & Emergency Contacts

**Objective:** Enable multiple insurance policies per patient with cascading type → provider → plan selection, and multiple emergency contacts.

#### A.1 Database Changes

**Migration: `create_patient_insurances_table`**
```
patient_insurances
├── id
├── patient_id (FK → patients)
├── insurance_provider_id (FK → insurance_providers)
├── insurance_type (enum: nhis, private, corporate)
├── membership_number
├── policy_number
├── scheme
├── issue_date
├── expiry_date
├── is_primary (boolean, default false)
├── is_active (boolean, default true)
├── timestamps
```

**Migration: `create_emergency_contacts_table`**
```
emergency_contacts
├── id
├── patient_id (FK → patients)
├── name
├── phone
├── phone_secondary (nullable)
├── relationship
├── is_primary (boolean, default false)
├── timestamps
```

**Migration: `add_plans_to_insurance_providers`**
```
Add to insurance_providers:
├── plans (JSON — array of plan names/tiers)
├── insurance_type (enum: nhis, private, corporate)
```

**Migration: `migrate_patient_flat_fields`**
- Copy `patients.nhis_number` → `patient_insurances` row with NHIS provider
- Copy `patients.emergency_contact_*` → `emergency_contacts` row
- Drop flat fields after verification

#### A.2 Models & Relationships

- `PatientInsurance` model (belongsTo Patient, belongsTo InsuranceProvider)
- `EmergencyContact` model (belongsTo Patient)
- Patient model: `hasMany(PatientInsurance)`, `hasMany(EmergencyContact)`
- InsuranceProvider model: add `hasMany(PatientInsurance)`

#### A.3 Controller & Routes

- `PatientInsuranceController`: store, update, destroy, setPrimary
- `EmergencyContactController`: store, update, destroy, setPrimary
- AJAX endpoint: `GET /api/insurance-providers?type={type}` — filter providers by type
- AJAX endpoint: `GET /api/insurance-providers/{id}/plans` — get plans for provider
- Routes nested under patients: `admin/patients/{patient}/insurances`, `admin/patients/{patient}/emergency-contacts`

#### A.4 View Changes

- Patient show page: replace flat emergency contact section with repeatable list
- Patient show page: add insurance policies carousel/table with Add/Edit modals
- Patient create/edit: dynamic repeatable sections for insurance and NOK
- Insurance add modal: cascading dropdowns (type → provider → plan)

#### A.5 Data Migration

- Artisan command to migrate existing flat fields to new tables
- Reversible: keep old columns until verified

---

### Phase B — Appointment Enhancements

**Objective:** Add service-based booking, reschedule tracking, and doctor transfer features.

#### B.1 Database Changes

**Migration: `enhance_appointments_table`**
```
Add to appointments:
├── service_catalog_id (FK → service_catalog, nullable)
├── rescheduled_from_id (FK → appointments, nullable, self-ref)
├── rescheduled_at (timestamp, nullable)
├── rescheduled_reason (text, nullable)
├── transferred_from_doctor_id (FK → users, nullable)
├── transfer_reason (text, nullable)
├── transferred_at (timestamp, nullable)
```

**Update `AppointmentStatus` enum:**
```php
case RESCHEDULED = 'rescheduled';
```

#### B.2 Service-Based Booking

- Add `service_catalog_id` to appointment create form
- AJAX endpoint: `GET /admin/appointments/doctors-by-service?service_id={id}` — return doctors who offer the selected service
- Dual-mode form: user selects "Book by Service" or "Book by Doctor" tab
- When service is selected → populate doctor dropdown with qualified doctors (doctors linked to the service's department/category)

#### B.3 Reschedule Feature

- New `reschedule()` method on AppointmentController
- Creates NEW appointment with `rescheduled_from_id` pointing to original
- Original appointment marked `RESCHEDULED`
- Reschedule modal: new date, new time, reason
- Show view: display reschedule chain if `rescheduled_from_id` exists

#### B.4 Doctor Transfer

- New `transferDoctor()` method on AppointmentController
- Updates `doctor_id`, stores `transferred_from_doctor_id` + `transfer_reason` + `transferred_at`
- Transfer modal: select new doctor, enter reason
- Activity log entry for audit trail

#### B.5 View Changes

- Create form: add service selection tab + cascading doctor dropdown
- Show page: add reschedule history section
- Show page: add transfer history section
- Index/Calendar: new `RESCHEDULED` status badge

---

### Phase C — Consultation Enhancements

**Objective:** Add rich text clinical notes, TODO task management, and consultation mode tracking.

#### C.1 Rich Text Editor

- Initialize Quill on consultation note textareas (JS/CSS already loaded)
- Store HTML content in existing `notes` text columns
- Add toolbar config: bold, italic, lists, headers, links
- Apply to: complaint description, diagnosis notes, treatment description, investigation notes

#### C.2 TODO Task Management

**Migration: `create_consultation_tasks_table`**
```
consultation_tasks
├── id
├── medical_record_id (FK → medical_records)
├── title
├── description (nullable)
├── priority (enum: low, medium, high)
├── status (enum: pending, in_progress, completed, cancelled)
├── assigned_to (FK → users, nullable)
├── due_date (date, nullable)
├── completed_at (timestamp, nullable)
├── created_by (FK → users)
├── timestamps
```

- `ConsultationTask` model (belongsTo MedicalRecord, belongsTo assignedUser, belongsTo creator)
- CRUD within consultation view (AJAX or inline form)
- Task list panel on consultation show page
- Allow mark complete, delete, edit priority

#### C.3 Consultation Modes

**Migration: `add_consultation_mode_to_visits`**
```
Add to visits:
├── consultation_mode (enum: in_person, telehealth, virtual — default: in_person)
├── meeting_link (string, nullable — for telehealth)
```

- Update visit create/appointment check-in flow to set mode
- Telehealth: store external meeting link (Zoom, Google Meet)
- Display mode badge on visit show/index pages
- Full WebRTC integration deferred to Phase J+

---

### Phase D — Department Dashboards

**Objective:** Create role-specific dashboards for Nurse, Pharmacy, Lab, Billing, and a combined Consultation dashboard.

#### D.1 Dashboard Controllers

| Dashboard | Controller | Key Stats |
|-----------|-----------|-----------|
| **Nurse** | `Nurse\DashboardController` | Today's triage queue, pending vitals, patients waiting, recent vitals recorded |
| **Pharmacy** | `Pharmacy\DashboardController` | Pending prescriptions, today's dispensing count, low stock alerts, expiring drugs |
| **Lab** | `Lab\DashboardController` | Pending requests, in-progress tests, today's completed, abnormal results, analyzer status |
| **Billing** | `Billing\DashboardController` | Today's invoices, pending payments, revenue today/this week/month, outstanding balance |
| **Consultation** | `Consultation\DashboardController` | Doctor's queue, today's consultations, pending investigations, follow-ups due |

#### D.2 Routing & Middleware

- Each dashboard at: `/{role}/dashboard`
- Middleware: `role:Nurse`, `role:Pharmacist`, `role:Lab Technician`, `role:Accountant`
- Login redirect updated in `LoginController` to route each role to their dashboard
- Fallback: users without specific dashboard → admin dashboard

#### D.3 Views

- 5 new Blade views: `dashboard/nurse.blade.php`, `dashboard/pharmacy.blade.php`, `dashboard/lab.blade.php`, `dashboard/billing.blade.php`, `dashboard/consultation.blade.php`
- Each with: summary cards (4-6 stat blocks), today's activity table, quick-action buttons, alerts section
- ApexCharts for trends (reuse existing chart patterns from admin dashboard)

#### D.4 Service Methods

- `ReportService`: Add `nurseDashboardStats()`, `pharmacyDashboardStats()`, `labDashboardStats()`, `billingDashboardStats()`, `consultationDashboardStats()`
- Each aggregates domain-specific counts, trends, and alerts

---

### Phase E — Vitals & Holiday Management

**Objective:** Fill minor gaps — add pain level to vitals and implement holiday management.

#### E.1 Pain Level in Vitals

- Migration: `ADD pain_level integer nullable` to vitals table
- Update `StoreVitalRequest` validation: `'pain_level' => 'nullable|integer|min:0|max:10'`
- Update vitals form: add 0–10 slider/select for pain level
- Update vitals show/history views

#### E.2 Holidays CRUD

**Migration: `create_holidays_table`**
```
holidays
├── id
├── name
├── date
├── type (enum: public, restricted, optional)
├── is_recurring (boolean — repeats yearly)
├── description (nullable)
├── timestamps
```

- `Holiday` model
- `Admin\HolidayController`: index, store, update, destroy
- View: `hr/holidays/index.blade.php` — table with inline add/edit
- Integration: attendance summary should factor in holidays (exclude from working days)

---

### Phase F — Asset Management

**Objective:** Financial asset tracking and depreciation.

#### F.1 Database

**Migration: `create_assets_table`**
```
assets
├── id
├── asset_number (unique, auto-gen AST-xxxxx)
├── name
├── category (enum: furniture, equipment, vehicle, technology, property, other)
├── description (nullable)
├── purchase_date
├── purchase_price (decimal)
├── current_value (decimal)
├── depreciation_rate (decimal — annual %)
├── manufacturer (nullable)
├── serial_number (nullable)
├── location (nullable)
├── assigned_to (FK → users, nullable)
├── status (enum: active, maintenance, retired, disposed)
├── notes (nullable)
├── timestamps
├── soft_deletes
```

#### F.2 Implementation

- `Asset` model
- `Admin\AssetController`: index, create, store, show, edit, update, toggleStatus
- Annual depreciation calculation method
- Views: index (DataTable with filters), create/edit forms, show page
- Report: asset register with current values + depreciation schedule

---

### Phase G — Content Management System

**Objective:** Implement pages, blogs, and FAQs with backend CRUD to back the existing template views.

#### G.1 Pages

**Migration: `create_pages_table`**
```
pages
├── id
├── title
├── slug (unique)
├── content (longtext)
├── status (enum: draft, published, archived)
├── meta_title (nullable)
├── meta_description (nullable)
├── author_id (FK → users)
├── published_at (nullable)
├── timestamps
├── soft_deletes
```

- `Page` model
- `Admin\PageController`: index, create, store, edit, update, destroy, toggleStatus
- Public route: `GET /pages/{slug}` for rendering

#### G.2 Blogs

**Migration: `create_blog_tables`** (3 tables)
```
blog_categories: id, name, slug, description, is_active, timestamps

blog_posts: id, title, slug, content, excerpt, featured_image, 
            category_id (FK), author_id (FK → users), 
            status (draft/published/archived), 
            published_at, timestamps, soft_deletes

blog_comments: id, blog_post_id (FK), user_id (FK, nullable), 
               author_name, author_email, content, 
               status (pending/approved/spam), 
               parent_id (self-ref, nullable), timestamps
```

- `BlogCategory`, `BlogPost`, `BlogComment` models
- `Admin\BlogController`: index, create, store, edit, update, destroy
- `Admin\BlogCategoryController`: index, store, update, destroy
- Comment moderation: approve, reject, delete

#### G.3 FAQs

**Migration: `create_faqs_table`**
```
faqs
├── id
├── question
├── answer (longtext)
├── category (nullable)
├── sort_order (integer, default 0)
├── is_active (boolean, default true)
├── timestamps
```

- `Faq` model
- `Admin\FaqController`: index, store, update, destroy, reorder
- Public FAQ page rendering

---

### Phase H — i18n Multi-Language Support

**Objective:** Extract all hardcoded UI strings and support English + French.

#### H.1 Lang File Structure

```
lang/
├── en/
│   ├── auth.php
│   ├── common.php
│   ├── patients.php
│   ├── appointments.php
│   ├── consultations.php
│   ├── vitals.php
│   ├── billing.php
│   ├── pharmacy.php
│   ├── lab.php
│   ├── hr.php
│   ├── reports.php
│   ├── settings.php
│   └── dashboard.php
└── fr/
    └── (same structure)
```

#### H.2 Implementation Steps

1. Create English lang files by extracting all Blade hardcoded strings
2. Replace Blade strings with `{{ __('module.key') }}` calls
3. Translate to French (professional translation recommended)
4. Language switcher component in header layout
5. Middleware to set locale from session/cookie/user preference
6. Store user preferred language in users table

#### H.3 Scope

- ~200+ Blade view files to update
- Estimated 2000–3000 translation keys
- Priority: start with sidebar/header/common terms, then module by module

---

### Phase I — API Layer (SPA Readiness)

**Objective:** Add a full REST API alongside existing Blade routes for future Vue SPA migration.

#### I.1 Authentication

- Install Laravel Sanctum
- API token authentication (alternative to session)
- JWT-compatible token response format matching DRF contract:
  ```json
  { "access": "token", "refresh": "token" }
  ```

#### I.2 API Routes (`routes/api.php`)

```
api/v1/
├── auth/
│   ├── POST   login
│   ├── POST   refresh
│   ├── POST   logout
│   └── POST   forgot-password
├── patients/
│   ├── GET    / (paginated, search, filter)
│   ├── POST   /
│   ├── GET    /{id}
│   ├── PUT    /{id}
│   ├── GET    /{id}/insurances
│   ├── POST   /{id}/insurances
│   ├── GET    /{id}/appointments
│   └── GET    /{id}/visits
├── appointments/
│   ├── GET    /
│   ├── POST   /
│   ├── GET    /{id}
│   ├── PATCH  /{id}
│   ├── PATCH  /{id}/reschedule
│   ├── PATCH  /{id}/transfer-doctor
│   └── PATCH  /{id}/status
├── visits/
├── consultations/
├── vitals/
├── invoices/
├── staff/
├── services/
└── insurance/
    ├── GET    types
    └── GET    companies?type={id}
```

#### I.3 API Resources

- Create `App\Http\Resources\` for each model
- Standardize pagination format:
  ```json
  { "results": [...], "count": 150, "next": "...", "previous": "..." }
  ```
- Match the template spec's DRF pagination contract

#### I.4 Scope

- ~25 resource classes
- ~80 API endpoints mirroring existing web routes
- Reuse existing Service layer (no business logic duplication)

---

### Phase J — Patient Portal

**Objective:** Self-service patient-facing portal.

#### J.1 Features

| Feature | Description |
|---------|-------------|
| Patient login | Separate auth for patients (vs staff) |
| View appointments | List own upcoming/past appointments |
| Book appointment | Patient-initiated booking (limited) |
| View test results | Lab results for own visits |
| View prescriptions | Prescription history |
| View invoices | Outstanding and paid invoices |
| Profile management | Update contact info, NOK, insurance |
| Dashboard | Summary: next appointment, pending results, outstanding balance |

#### J.2 Implementation

- Patient auth (separate guard or role-based)
- `Patient\` controller namespace with restricted queries (own data only)
- `resources/views/patient-portal/` view directory
- Simplified layout (patient header/sidebar)
- Mobile-responsive priority

---

## 6. Migration & Data Considerations

### Data Migration Strategy

| Phase | Migration Risk | Strategy |
|-------|---------------|----------|
| A (Insurance/NOK) | **HIGH** — existing flat data must be migrated | Run data migration artisan command, keep old columns for 30 days, then drop |
| B (Appointments) | **LOW** — additive columns only | No data migration needed |
| C (Consultation) | **LOW** — new tables only | No data migration needed |
| D (Dashboards) | **NONE** — views only | No schema changes |
| E (Vitals/Holidays) | **LOW** — additive column + new table | Existing vitals get NULL pain_level |
| F (Assets) | **NONE** — entirely new module | No existing data affected |
| G (CMS) | **NONE** — entirely new module | No existing data affected |

### Rollback Plan

- Every migration has `down()` method
- Feature flags for new features (Settings table key-value pairs)
- Git branch per phase for easy revert

---

## 7. Estimated Complexity

| Phase | New Models | New Migrations | New Controllers | New Views | Modified Files | Complexity |
|-------|-----------|---------------|----------------|-----------|---------------|------------|
| **A** | 2 | 4 | 2 | 0 (modify existing) | ~10 | Large |
| **B** | 0 | 1 | 0 (modify existing) | 0 (modify existing) | ~8 | Medium |
| **C** | 1 | 2 | 1 | 1 (modify existing) | ~6 | Medium |
| **D** | 0 | 0 | 5 | 5 | ~4 | Medium |
| **E** | 1 | 2 | 1 | 2 | ~4 | Small |
| **F** | 1 | 1 | 1 | 4 | ~2 | Small |
| **G** | 4 | 3 | 4 | ~12 | ~3 | Medium |
| **H** | 0 | 1 | 0 | ~200+ | ~5 | Large |
| **I** | 0 | 0 | ~25 | 0 | ~10 | Very Large |
| **J** | 0 | 1 | ~8 | ~10 | ~5 | Large |
| **Total** | **9** | **14** | **~47** | **~34** | **~57** | — |

---

## 8. Progress Tracker

| Phase | Name | Status | Completed |
|-------|------|--------|-----------|
| **Phase A** | Patient Insurance & NOK | ⬜ Not Started | — |
| **Phase B** | Appointment Enhancements | ⬜ Not Started | — |
| **Phase C** | Consultation Enhancements | ⬜ Not Started | — |
| **Phase D** | Department Dashboards | ⬜ Not Started | — |
| **Phase E** | Vitals & Holidays | ⬜ Not Started | — |
| **Phase F** | Asset Management | ⬜ Not Started | — |
| **Phase G** | Content CMS | ⬜ Not Started | — |
| **Phase H** | i18n Multi-Language | ⬜ Not Started | — |
| **Phase I** | API Layer (SPA Readiness) | ⬜ Not Started | — |
| **Phase J** | Patient Portal | ⬜ Not Started | — |

---

### Key Findings Summary

1. **The current Laravel project already covers ~75% of the template spec's clinical functionality** — patient management, appointments, consultations, vitals, visit/queue, lab, pharmacy, billing, claims, HRM, and ward management are all fully built.

2. **The largest gaps are architectural** (SPA vs server-rendered) **and in auxiliary features** (CMS, i18n, patient portal, department dashboards) rather than in core clinical workflows.

3. **The current project EXCEEDS the template spec** in several areas: 12-state visit flow (vs 6 in spec), queue management system, lab analyzer integration, medical patterns, procurement/stock management, claims workflow, ward rounds — none of which exist in the Vue template.

4. **Phases A–E** close all clinical workflow gaps and should be prioritized.

5. **Phases H–J** are large undertakings suited for a separate project cycle.

---

*Generated: April 13, 2026*  
*Based on: PROJECT_ANALYSIS.md (Vue 3 template spec) vs current Laravel UHMS codebase*
