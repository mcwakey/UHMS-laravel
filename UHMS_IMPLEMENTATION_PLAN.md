# UHMS — Full Implementation Plan

## Ultimate Hospital Management System for Ghana

---

## TABLE OF CONTENTS

1. [Project Overview](#1-project-overview)
2. [Phase Breakdown](#2-phase-breakdown)
3. [Phase 0 — Foundation & Setup](#3-phase-0--foundation--setup)
4. [Phase 1 — Authentication & User Management](#4-phase-1--authentication--user-management)
5. [Phase 2 — Patient Module](#5-phase-2--patient-module)
6. [Phase 3 — Visit & Queue Module](#6-phase-3--visit--queue-module)
7. [Phase 4 — EHR Module (Doctor Consultation)](#7-phase-4--ehr-module-doctor-consultation)
8. [Phase 5 — Medical Pattern Engine](#8-phase-5--medical-pattern-engine)
9. [Phase 6 — Laboratory Module](#9-phase-6--laboratory-module)
10. [Phase 7 — Pharmacy Module](#10-phase-7--pharmacy-module)
11. [Phase 8 — Billing Module](#11-phase-8--billing-module)
12. [Phase 9 — Dashboards & Reports](#12-phase-9--dashboards--reports)
13. [Phase 10 — Settings, Audit & Polish](#13-phase-10--settings-audit--polish)
14. [Database Schema Overview](#14-database-schema-overview)
15. [Folder Structure](#15-folder-structure)
16. [Packages Required](#16-packages-required)
17. [Template Reuse Map](#17-template-reuse-map)
18. [Ghana-Specific Adaptations](#18-ghana-specific-adaptations)
19. [Security Checklist](#19-security-checklist)
20. [Testing Strategy](#20-testing-strategy)
21. [Deployment Notes](#21-deployment-notes)

---

## PROGRESS OVERVIEW

| Phase | Status | Completed |
|-------|--------|-----------|
| **Phase 0** — Foundation & Setup | ✅ DONE | 2026-04-11 |
| **Phase 1** — Authentication & User Management | ✅ DONE | 2026-04-11 |
| **Phase 2** — Patient Module | ✅ DONE | 2026-04-11 |
| **Phase 3** — Visit & Queue Module | ✅ DONE | 2026-04-11 |
| **Phase 4** — EHR Module | ✅ DONE | 2026-04-11 |
| **Phase 5** — Medical Pattern Engine | ✅ DONE | 2026-04-12 |
| **Phase 6** — Laboratory Module | ✅ DONE | 2026-04-12 |
| **Phase 7** — Pharmacy Module | ✅ DONE | 2026-04-12 |
| **Phase 8** — Billing Module | ⬜ Not Started | — |
| **Phase 9** — Dashboards & Reports | ⬜ Not Started | — |
| **Phase 10** — Settings, Audit & Polish | ⬜ Not Started | — |

### Files Created (Phase 0, 1, 2, 3, 4, 5, 6 & 7)

**Enums (12):** `Gender`, `UserStatus`, `VisitStatus`, `VisitType`, `Priority`, `BloodGroup`, `PaymentMethod`, `MaritalStatus`, `BillingType`, `InvoiceStatus`, `LabRequestStatus`, `PrescriptionStatus`

**Models (26):** `User` (modified), `Department`, `Designation`, `Patient`, `Visit`, `VisitStatusLog`, `QueueEntry`, `MedicalRecord`, `Complaint`, `Diagnosis`, `Investigation`, `Treatment`, `Prescription`, `PrescriptionItem`, `Vital`, `MedicalPattern`, `MedicalPatternItem`, `LabTestCategory`, `LabTest`, `LabRequest`, `LabRequestItem`, `LabResult`, `DrugCategory`, `Drug`, `DrugStock`, `DispensingRecord`

**Controllers (21):** `Admin/DashboardController`, `Admin/UserController`, `Admin/RoleController`, `Admin/DepartmentController`, `Admin/DesignationController`, `Admin/PatientController`, `Admin/VisitController`, `Admin/QueueController`, `Admin/VitalController`, `Admin/LabTestController`, `Admin/DrugController`, `Admin/DrugStockController`, `Doctor/ConsultationController`, `Doctor/PrescriptionController`, `Doctor/MedicalPatternController`, `Lab/LabRequestController`, `Lab/LabResultController`, `Pharmacy/DispensingController`, `Auth/LoginController`, `Auth/ForgotPasswordController`, `Auth/ResetPasswordController`

**Services (9):** `UserService`, `PatientService`, `VisitService`, `QueueService`, `ConsultationService`, `PrescriptionService`, `MedicalPatternService`, `LabService`, `PharmacyService`

**Middleware (1):** `EnsureUserHasRole`

**Form Requests (8):** `StoreUserRequest`, `UpdateUserRequest`, `StorePatientRequest`, `UpdatePatientRequest`, `StoreVisitRequest`, `StoreConsultationRequest`, `StoreVitalRequest`, `StorePrescriptionRequest`

**Migrations (26 custom):** `create_departments_table`, `create_designations_table`, `modify_users_table_for_uhms`, `create_patients_table`, `create_visits_table`, `create_visit_status_logs_table`, `create_queue_entries_table`, `create_medical_records_table`, `create_complaints_table`, `create_diagnoses_table`, `create_investigations_table`, `create_treatments_table`, `create_prescriptions_table`, `create_prescription_items_table`, `create_vitals_table`, `create_medical_patterns_table`, `create_medical_pattern_items_table`, `create_lab_test_categories_table`, `create_lab_tests_table`, `create_lab_requests_table`, `create_lab_request_items_table`, `create_lab_results_table`, `create_drug_categories_table`, `create_drugs_table`, `create_drug_stock_table`, `create_dispensing_records_table`

**Seeders (3):** `RoleSeeder` (8 roles, 42 permissions), `DepartmentSeeder` (16 depts), `AdminUserSeeder`

**Blade Views (41):**
- Layouts: `app.blade.php`, `auth.blade.php`, `partials/header.blade.php`, `partials/sidebar.blade.php`
- Auth: `login`, `forgot-password`, `reset-password`
- Dashboard: `admin`
- Users: `index`, `create`, `edit`
- Roles: `index`, `permissions`
- Departments: `index`
- Designations: `index`
- Patients: `index`, `create`, `edit`, `show`
- Visits: `index`, `create`, `show`
- Queue: `manage`, `board`
- Consultations: `index`, `show` (with pattern & lab integration), `history`
- Vitals: `record`
- Prescriptions: `index`, `show`
- Patterns: `index`, `create`
- Lab: `requests`, `process`, `results`, `tests`
- Pharmacy: `dispensing`, `dispense`, `history`, `drugs`, `stock`, `stock-alerts`

---

## 1. PROJECT OVERVIEW

### What We're Building
A production-grade hospital management system tailored for Ghanaian healthcare facilities. The system manages the complete patient journey from registration through consultation, lab work, pharmacy, and billing — with role-based access for hospital staff.

### Core Workflow
```
Patient Arrives → Registration → Visit Created → Queue (Waiting)
    → Triage (Nurse) → Doctor Consultation (EHR)
    → Lab (optional) → Back to Doctor (optional)
    → Pharmacy Dispensing → Billing/Payment → Visit Completed
```

### Tech Stack (Final)
| Layer | Technology |
|-------|------------|
| Backend | Laravel 12.x (PHP 8.2+) |
| Database | MySQL/MariaDB (XAMPP) |
| Frontend | Blade + Existing Admin Template |
| Auth | Custom Auth Controllers (LoginController, ForgotPasswordController, ResetPasswordController) |
| Roles | spatie/laravel-permission |
| Audit | spatie/laravel-activitylog |
| Excel/PDF | maatwebsite/excel, barryvdh/laravel-dompdf |
| Real-time | Laravel Echo + Pusher/Reverb (for queue board) |
| Search | Scout (optional, for patient search) |
| Build | Vite 7.x |

---

## 2. PHASE BREAKDOWN

| Phase | Module | Priority | Dependencies |
|-------|--------|----------|-------------|
| **0** | Foundation & Setup | 🔴 Critical | None |
| **1** | Authentication & User/Role Management | 🔴 Critical | Phase 0 |
| **2** | Patient Module | 🔴 Critical | Phase 1 |
| **3** | Visit & Queue Module | 🔴 Critical | Phase 2 |
| **4** | EHR Module (Doctor Consultation) | 🔴 Critical | Phase 3 |
| **5** | Medical Pattern Engine | 🟡 Important | Phase 4 |
| **6** | Laboratory Module | 🔴 Critical | Phase 3 |
| **7** | Pharmacy Module | 🔴 Critical | Phase 4 |
| **8** | Billing Module | 🔴 Critical | Phase 3, 6, 7 |
| **9** | Dashboards & Reports | 🟡 Important | Phase 2-8 |
| **10** | Settings, Audit & Polish | 🟡 Important | All |

---

## 3. PHASE 0 — FOUNDATION & SETUP

### 3.1 Objectives
- Set up development environment
- Install required packages
- Restructure template for dynamic data
- Configure database
- Establish coding conventions

### 3.2 Tasks

#### 3.2.1 Package Installation
```bash
# Auth
composer require laravel/breeze --dev
php artisan breeze:install blade

# Roles & Permissions
composer require spatie/laravel-permission

# Audit Logging
composer require spatie/laravel-activitylog

# PDF Generation
composer require barryvdh/laravel-dompdf

# Excel Export
composer require maatwebsite/excel

# IDE Helper (dev)
composer require barryvdh/laravel-ide-helper --dev
```

#### 3.2.2 Database Configuration
- Configure PostgreSQL in `.env`
- Set timezone to `Africa/Accra`
- Set locale to `en_GH` (or `en`)
- Configure currency to Ghana Cedi (GHS / ₵)

#### 3.2.3 Template Restructure
The current template uses Route::is() for conditional rendering. We must:

1. **Refactor `mainlayout.blade.php`** — Use `@stack('styles')` and `@stack('scripts')` instead of Route::is() conditionals
2. **Refactor `head-css.blade.php`** — Load only base CSS globally; push page-specific CSS from child views
3. **Refactor `footer-scripts.blade.php`** — Load only base JS globally; push page-specific JS from child views
4. **Refactor `sidebar.blade.php`** — Replace hardcoded sidebar with role-based dynamic menu
5. **Refactor `header.blade.php`** — Add dynamic user info and notifications
6. **Create separate layout files**:
   - `layouts/app.blade.php` — Main admin layout
   - `layouts/auth.blade.php` — Authentication pages layout
   - `layouts/doctor.blade.php` — Doctor portal layout
   - `layouts/patient.blade.php` — Patient portal layout (if needed)

#### 3.2.4 Base Service Class & Architecture Setup
Create the application structure:
```
app/
├── Enums/              → Status enums, role enums
├── Http/
│   ├── Controllers/
│   │   ├── Admin/      → Admin controllers
│   │   ├── Doctor/     → Doctor portal controllers
│   │   ├── Auth/       → Breeze auth controllers
│   │   └── Api/        → API controllers (if needed)
│   ├── Middleware/      → Role middleware, etc.
│   └── Requests/       → Form Request validation classes
├── Models/             → Eloquent models
├── Services/           → Business logic services
├── Traits/             → Shared traits (HasUuid, Auditable, etc.)
├── Observers/          → Model observers
└── Policies/           → Authorization policies
```

#### 3.2.5 Create Base Enums
```php
// app/Enums/VisitStatus.php
enum VisitStatus: string {
    case REGISTERED = 'registered';
    case WAITING = 'waiting';
    case TRIAGE = 'triage';
    case CONSULTING = 'consulting';
    case LAB = 'lab';
    case PHARMACY = 'pharmacy';
    case BILLING = 'billing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
```

### 3.3 Deliverables
- [x] All packages installed and configured
- [x] Database connection verified (MySQL/MariaDB via XAMPP)
- [x] Template layout refactored to use stacks
- [x] Folder structure created
- [x] Base enums created (11 enums)
- [x] `.env` configured for Ghana settings (Africa/Accra timezone)
- [x] Vite building successfully (pre-built assets via vite-plugin-static-copy)

> **Phase 0 COMPLETED** — 2026-04-11

---

## 4. PHASE 1 — AUTHENTICATION & USER MANAGEMENT

### 4.1 Objectives
- Implement full authentication (login, register, password reset)
- Implement role-based access control
- Create user management CRUD
- Seed default roles and admin user

### 4.2 Database Tables

#### `users` (modify existing)
```
- id (bigint, PK)
- first_name (string)
- last_name (string)
- email (string, unique)
- phone (string, nullable)
- password (string)
- avatar (string, nullable)
- gender (enum: male, female)
- date_of_birth (date, nullable)
- status (enum: active, inactive, suspended)
- employee_id (string, nullable, unique)
- department_id (FK, nullable)
- designation_id (FK, nullable)
- email_verified_at (timestamp)
- remember_token
- timestamps
- soft_deletes
```

#### `departments`
```
- id (bigint, PK)
- name (string)
- code (string, unique)
- description (text, nullable)
- status (enum: active, inactive)
- timestamps
```

#### `designations`
```
- id (bigint, PK)
- name (string)
- department_id (FK)
- description (text, nullable)
- timestamps
```

### 4.3 Roles to Seed
| Role | Description |
|------|-------------|
| Super Admin | Full system access |
| Admin | Administrative access |
| Doctor | Consultation, EHR, prescriptions |
| Nurse | Triage, vitals, patient care |
| Receptionist | Patient registration, visits, queue |
| Lab Technician | Lab requests, results |
| Pharmacist | Drug stock, dispensing |
| Accountant | Billing, payments, reports |

### 4.4 Files to Create
| Type | File | Purpose |
|------|------|---------|
| Migration | `create_departments_table` | Departments |
| Migration | `create_designations_table` | Designations |
| Migration | `modify_users_table` | Add UHMS fields |
| Model | `Department` | Department model |
| Model | `Designation` | Designation model |
| Controller | `Admin/UserController` | User CRUD |
| Controller | `Admin/DepartmentController` | Department CRUD |
| Controller | `Admin/DesignationController` | Designation CRUD |
| Controller | `Admin/RoleController` | Role management |
| Service | `UserService` | User business logic |
| Request | `StoreUserRequest` | Create user validation |
| Request | `UpdateUserRequest` | Update user validation |
| Seeder | `RoleSeeder` | Seed default roles |
| Seeder | `AdminUserSeeder` | Seed admin user |
| Seeder | `DepartmentSeeder` | Seed hospital departments |
| Middleware | `CheckRole` | Role-based access |
| Policy | `UserPolicy` | User authorization |
| View | Refactor `login.blade.php` | Dynamic login |
| View | Refactor `staffs.blade.php` | Dynamic user list |
| View | Refactor `roles-and-permissions.blade.php` | Dynamic roles |
| View | Refactor `permissions.blade.php` | Dynamic permissions |

### 4.5 Template Views to Reuse
- `login.blade.php` → Login page (adapt for Breeze)
- `register-basic.blade.php` → Register page (for initial admin setup only)
- `forgot-password-basic.blade.php` → Forgot password
- `reset-password-basic.blade.php` → Reset password
- `staffs.blade.php` → User management list
- `roles-and-permissions.blade.php` → Roles list
- `permissions.blade.php` → Permission matrix
- `hrm-departments.blade.php` → Departments
- `designation.blade.php` → Designations
- `profile-settings.blade.php` → Profile settings

### 4.6 Deliverables
- [x] Working login/logout (custom auth, NOT Breeze)
- [x] Role seeding (8 roles, 42 permissions)
- [x] User CRUD (create, list with filters, edit, toggle status)
- [x] Department CRUD (16 Ghana hospital departments seeded)
- [x] Designation CRUD (with department filter)
- [x] Role-based middleware protecting routes (`can:` gates + `EnsureUserHasRole`)
- [x] Permission matrix functional (grouped by module, check-all per group)
- [x] Admin user seeded (admin@uhms.local / password, Super Admin role)
- [x] Routes rewritten (34 routes replacing 200+ template closures)
- [x] Super Admin gate bypass via `Gate::before`
- [x] Storage link created for avatar uploads

> **Phase 1 COMPLETED** — 2026-04-11
>
> **Implementation Notes:**
> - Used custom auth controllers (NOT Laravel Breeze) for full control over login flow
> - Database: MySQL/MariaDB via XAMPP (required `Schema::defaultStringLength(191)` for key length compatibility)
> - Activity log migration fixed: `json` → `longText` for MariaDB compatibility
> - Login redirects based on role: Super Admin/Admin → admin.dashboard, Doctor → doctor.dashboard
> - UserService handles all business logic (list with filters, create with avatar upload, update, toggle status)
> - All views use the pre-built template's Bootstrap 5 + jQuery UI components

---

## 5. PHASE 2 — PATIENT MODULE

### 5.1 Objectives
- Patient registration with Ghana-specific fields
- Patient search (by name, Ghana Card, NHIS, phone)
- Patient profile view with visit history
- Patient edit/update

### 5.2 Database Tables

#### `patients`
```
- id (bigint, PK)
- patient_number (string, unique, auto-generated: PT-YYYYMMDD-XXXX)
- first_name (string)
- last_name (string)
- other_names (string, nullable)
- date_of_birth (date)
- gender (enum: male, female)
- blood_group (enum, nullable)
- marital_status (enum: single, married, divorced, widowed, nullable)
- phone (string)
- phone_secondary (string, nullable)
- email (string, nullable)
- ghana_card_number (string, nullable, unique)
- nhis_number (string, nullable)
- nhis_expiry_date (date, nullable)
- occupation (string, nullable)
- address (text, nullable)
- city (string, nullable)
- region (string, nullable) → Ghana regions
- digital_address (string, nullable) → Ghana Post GPS
- emergency_contact_name (string, nullable)
- emergency_contact_phone (string, nullable)
- emergency_contact_relationship (string, nullable)
- avatar (string, nullable)
- allergies (text, nullable)
- chronic_conditions (text, nullable)
- status (enum: active, inactive, deceased)
- registered_by (FK → users)
- timestamps
- soft_deletes
```

### 5.3 Files to Create
| Type | File | Purpose |
|------|------|---------|
| Migration | `create_patients_table` | Patients table |
| Model | `Patient` | Patient model with relationships |
| Controller | `Admin/PatientController` | Patient CRUD |
| Service | `PatientService` | Patient business logic |
| Request | `StorePatientRequest` | Create patient validation |
| Request | `UpdatePatientRequest` | Update patient validation |
| Seeder | `RegionSeeder` | Ghana regions/districts |
| View | Refactor `create-patient.blade.php` | Dynamic patient form |
| View | Refactor `patients.blade.php` | Dynamic patient list |
| View | Refactor `patient-details.blade.php` | Dynamic patient profile |
| View | Refactor `edit-patient.blade.php` | Dynamic edit form |

### 5.4 Template Views to Reuse
- `create-patient.blade.php` → Add Ghana Card, NHIS, Region, Digital Address fields
- `patients.blade.php` → Patient list with DataTable (make dynamic)
- `patients-grid.blade.php` → Optional grid view
- `patient-details.blade.php` → Patient profile (add visit history, EHR tabs)
- `edit-patient.blade.php` → Edit form

### 5.5 Key Features
1. **Auto-generated Patient Number**: `PT-20260411-0001` format
2. **Patient Search**: Search by name, phone, Ghana Card, NHIS number
3. **NHIS Status**: Display NHIS membership status and expiry
4. **Quick Registration**: Minimal required fields for emergency cases
5. **Visit History Tab**: On patient details page, show all visits

### 5.6 Deliverables
- [x] Patient registration form (Ghana-specific: Ghana Card, NHIS, Region, Digital Address)
- [x] Patient list with search/filter (name, phone, ID, Ghana Card, NHIS, gender, blood group, status)
- [x] Patient profile page with tabs (About, ID & Emergency, Medical Notes, Visit History placeholder, Registration Info)
- [x] Patient edit functionality
- [x] Auto-generated patient numbers (PT-YYYYMMDD-XXXX format)
- [x] Ghana regions dropdown (16 regions inline)
- [x] Dashboard updated with Total Patients card and Recent Patients table
- [x] Sidebar updated with active Patients link

> **Phase 2 COMPLETED** — 2026-04-11
>
> **Implementation Notes:**
> - Patient model uses GeneratesNumbers trait for PT-YYYYMMDD-XXXX format
> - NHIS active status computed via `is_nhis_active` accessor (checks expiry date)
> - PatientService handles list (with search/filters), create (with auto-number + avatar upload), update, toggleStatus
> - Form Requests: StorePatientRequest, UpdatePatientRequest with enum validation
> - Profile view matches template's patient-details.blade.php UI pattern (header card, about card, ID card, tabs)
> - All 16 Ghana regions available in dropdowns
> - Emergency contact with relationship dropdown
> - Medical notes: allergies and chronic conditions text areas
- [ ] NHIS validation

---

## 6. PHASE 3 — VISIT & QUEUE MODULE

### 6.1 Objectives
- Visit creation (receptionist)
- Patient queue management
- Status tracking through the workflow
- Real-time queue board display
- Visit history per patient

### 6.2 Database Tables

#### `visits`
```
- id (bigint, PK)
- visit_number (string, unique, auto: VST-YYYYMMDD-XXXX)
- patient_id (FK → patients)
- visit_type (enum: outpatient, inpatient, emergency)
- visit_date (date)
- status (enum: registered, waiting, triage, consulting, lab, pharmacy, billing, completed, cancelled)
- priority (enum: normal, urgent, emergency)
- department_id (FK → departments, nullable)
- assigned_doctor_id (FK → users, nullable)
- chief_complaint (text, nullable)
- notes (text, nullable)
- checked_in_at (timestamp, nullable)
- checked_out_at (timestamp, nullable)
- created_by (FK → users)
- timestamps
- soft_deletes
```

#### `visit_status_logs`
```
- id (bigint, PK)
- visit_id (FK → visits)
- from_status (string, nullable)
- to_status (string)
- changed_by (FK → users)
- notes (text, nullable)
- timestamp (timestamp)
```

#### `queue_entries`
```
- id (bigint, PK)
- visit_id (FK → visits)
- department_id (FK → departments)
- queue_number (integer)
- priority (enum: normal, urgent, emergency)
- status (enum: waiting, serving, completed, skipped)
- called_at (timestamp, nullable)
- served_at (timestamp, nullable)
- completed_at (timestamp, nullable)
- served_by (FK → users, nullable)
- timestamps
```

### 6.3 Files to Create
| Type | File | Purpose |
|------|------|---------|
| Migration | `create_visits_table` | Visits |
| Migration | `create_visit_status_logs_table` | Status audit trail |
| Migration | `create_queue_entries_table` | Queue management |
| Model | `Visit` | Visit model |
| Model | `VisitStatusLog` | Status log model |
| Model | `QueueEntry` | Queue entry model |
| Enum | `VisitStatus` | Visit status enum |
| Enum | `VisitType` | Visit type enum |
| Enum | `Priority` | Priority enum |
| Controller | `Admin/VisitController` | Visit CRUD and status management |
| Controller | `Admin/QueueController` | Queue management |
| Service | `VisitService` | Visit business logic |
| Service | `QueueService` | Queue business logic |
| Request | `StoreVisitRequest` | Create visit validation |
| Observer | `VisitObserver` | Auto-log status changes |
| Event | `VisitStatusChanged` | Broadcast status changes |
| View | **NEW** `visits/index.blade.php` | Visit list |
| View | **NEW** `visits/create.blade.php` | Create visit |
| View | **NEW** `visits/show.blade.php` | Visit details with timeline |
| View | **NEW** `queue/board.blade.php` | Queue display board |
| View | **NEW** `queue/manage.blade.php` | Queue management |

### 6.4 Template Views to Reuse
- `appointments.blade.php` → Adapt as Visit list (rename and modify)
- `new-appointment.blade.php` → Adapt as Create Visit form
- `appointment-consultations.blade.php` → Adapt for Visit consultations view
- `appointment-calendar.blade.php` → Optional calendar view of visits

### 6.5 NEW Views to Create (No Template Exists)
1. **Queue Board** (`queue/board.blade.php`)
   - Full-screen display for waiting area TV
   - Shows current queue numbers per department
   - Real-time updates (polling or WebSocket)
   - Color-coded by priority (normal=green, urgent=yellow, emergency=red)

2. **Visit Timeline** (component within visit details)
   - Visual status flow showing each step
   - Timestamps for each transition
   - Current position highlighted

3. **Receptionist Visit Creation**
   - Quick patient search
   - Visit type selection
   - Doctor/Department assignment
   - Auto-queue assignment

### 6.6 Visit Status Flow Diagram
```
REGISTERED → WAITING → TRIAGE → CONSULTING → LAB (optional)
                                     ↓              ↓
                                     ↓         CONSULTING (return)
                                     ↓              ↓
                                PHARMACY ← ← ← ← ←
                                     ↓
                                  BILLING
                                     ↓
                                 COMPLETED
```

### 6.7 Deliverables
- [x] Visit creation by receptionist (with patient search, department/doctor assignment)
- [x] Visit status tracking with full audit trail (VisitStatusLog with timestamps)
- [x] Queue board for waiting area display (auto-refresh, priority color-coded)
- [x] Queue management (call next, skip, re-queue, complete)
- [x] Visit timeline component (visual status flow on visit detail page)
- [x] Status transition validation (9-state machine with allowed transitions)
- [x] Visit list with filtering by status/date/doctor/type/department
- [x] Auto queue number generation per department per day
- [x] Dashboard updated with today's visit stats and recent visits table
- [x] Sidebar updated with active Visits and Queue links (submenu)

> **Phase 3 COMPLETED** — 2026-04-11
>
> **Implementation Notes:**
> - Visit model uses GeneratesNumbers trait for VST-YYYYMMDD-XXXX format
> - 9-state status machine: registered → waiting → triage → consulting → lab → pharmacy → billing → completed (+ cancelled)
> - Status transitions validated via VisitStatus::allowedTransitions() — prevents skipping steps
> - Visit creation auto-transitions to WAITING and creates queue entry
> - QueueEntry tracks per-department daily queue numbers with priority ordering (emergency > urgent > normal)
> - Queue board auto-refreshes every 30 seconds, color-coded by priority
> - Queue management: call next, serve, complete, skip, re-queue operations
> - Patient search AJAX endpoint for visit creation (search by name, ID, phone, Ghana Card)
> - VisitService injected into DashboardController for today's stats
> - 12 new routes (6 visit + 6 queue)

---

## 7. PHASE 4 — EHR MODULE (DOCTOR CONSULTATION)

### 7.1 Objectives
- Doctor records complaints, diagnoses, investigations, treatments
- Linked to specific visit
- Medical history viewable across visits
- Prescription generation

### 7.2 Database Tables

#### `medical_records`
```
- id (bigint, PK)
- visit_id (FK → visits)
- patient_id (FK → patients)
- doctor_id (FK → users)
- timestamps
```

#### `complaints`
```
- id (bigint, PK)
- medical_record_id (FK → medical_records)
- description (text)
- duration (string, nullable)
- severity (enum: mild, moderate, severe, nullable)
- timestamps
```

#### `diagnoses`
```
- id (bigint, PK)
- medical_record_id (FK → medical_records)
- icd_code (string, nullable) → ICD-10 code
- description (text)
- type (enum: provisional, final)
- notes (text, nullable)
- timestamps
```

#### `investigations`
```
- id (bigint, PK)
- medical_record_id (FK → medical_records)
- investigation_type (string) → e.g., "Blood Test", "X-Ray"
- description (text)
- urgency (enum: routine, urgent, emergency)
- status (enum: requested, in_progress, completed)
- notes (text, nullable)
- timestamps
```

#### `treatments`
```
- id (bigint, PK)
- medical_record_id (FK → medical_records)
- type (enum: medication, procedure, referral, advice)
- description (text)
- timestamps
```

#### `prescriptions`
```
- id (bigint, PK)
- medical_record_id (FK → medical_records)
- visit_id (FK → visits)
- patient_id (FK → patients)
- doctor_id (FK → users)
- prescription_number (string, unique)
- status (enum: pending, dispensed, partially_dispensed, cancelled)
- notes (text, nullable)
- timestamps
```

#### `prescription_items`
```
- id (bigint, PK)
- prescription_id (FK → prescriptions)
- drug_name (string)
- drug_id (FK → drugs, nullable)
- dosage (string)
- frequency (string)
- duration (string)
- quantity (integer)
- route (string) → oral, IV, IM, etc.
- instructions (text, nullable)
- is_dispensed (boolean, default: false)
- timestamps
```

#### `vitals`
```
- id (bigint, PK)
- visit_id (FK → visits)
- patient_id (FK → patients)
- recorded_by (FK → users)
- blood_pressure_systolic (integer, nullable)
- blood_pressure_diastolic (integer, nullable)
- heart_rate (integer, nullable)
- temperature (decimal, nullable)
- respiratory_rate (integer, nullable)
- spo2 (integer, nullable)
- weight (decimal, nullable)
- height (decimal, nullable)
- bmi (decimal, nullable)
- blood_sugar (decimal, nullable)
- notes (text, nullable)
- recorded_at (timestamp)
- timestamps
```

### 7.3 Files to Create
| Type | File | Purpose |
|------|------|---------|
| Migration | 7 migrations for above tables | Database |
| Model | `MedicalRecord`, `Complaint`, `Diagnosis`, `Investigation`, `Treatment`, `Prescription`, `PrescriptionItem`, `Vital` | Models |
| Controller | `Doctor/ConsultationController` | EHR recording |
| Controller | `Doctor/PrescriptionController` | Prescription management |
| Controller | `Admin/VitalController` | Vitals recording (nurse) |
| Service | `ConsultationService` | EHR business logic |
| Service | `PrescriptionService` | Prescription logic |
| Request | `StoreConsultationRequest` | Consultation validation |
| Request | `StoreVitalRequest` | Vitals validation |
| Request | `StorePrescriptionRequest` | Prescription validation |
| View | **NEW** `consultation/index.blade.php` | Doctor's consultation interface |
| View | **NEW** `consultation/history.blade.php` | Patient medical history |
| View | **NEW** `vitals/record.blade.php` | Nurse vitals recording |
| View | Refactor `doctors-prescriptions.blade.php` | Prescription list |
| View | Refactor `doctors-prescription-details.blade.php` | Prescription detail |

### 7.4 Consultation Interface (NEW — No Template Exists)
This is the **most critical custom view**. Layout:
```
┌─────────────────────────────────────────────────┐
│ Patient: John Doe (PT-20260411-0001)  Visit: #V │
│ Age: 34  |  NHIS: Active  |  Blood: O+          │
├──────────────┬──────────────────────────────────┤
│  SIDEBAR     │  MAIN CONTENT                    │
│              │                                   │
│ ● Vitals    │  [Complaints Tab]                 │
│ ● Complaints│  [Diagnoses Tab]                  │
│ ● Diagnoses │  [Investigations Tab]             │
│ ● Labs      │  [Treatments Tab]                 │
│ ● Treatments│  [Prescriptions Tab]              │
│ ● Rx        │                                   │
│ ● History   │  + Add Complaint                  │
│              │  ┌─────────────────────────┐     │
│  PATTERNS   │  │ ▸ Pattern Suggestions   │     │
│  SUGGESTIONS│  └─────────────────────────┘     │
└──────────────┴──────────────────────────────────┘
```

### 7.5 Deliverables
- [x] Consultation interface for doctors
- [x] Vitals recording (nurse triage)
- [x] Complaint recording with severity
- [x] Diagnosis entry with ICD-10 codes
- [x] Investigation/lab test requests
- [x] Treatment recording
- [x] Prescription creation with drug items
- [x] Medical history view (cross-visit)
- [x] Visit transitions from consultation

> **Phase 4 COMPLETED** — 2026-04-11

---

## 8. PHASE 5 — MEDICAL PATTERN ENGINE

### 8.1 Objectives
- Store frequently used combinations of complaints + diagnoses + treatments
- Suggest patterns during consultation
- Rank by frequency of use
- Doctor-specific and system-wide patterns

### 8.2 Database Tables

#### `medical_patterns`
```
- id (bigint, PK)
- name (string)
- doctor_id (FK → users, nullable) → null = system-wide
- usage_count (integer, default: 0)
- is_active (boolean, default: true)
- timestamps
```

#### `medical_pattern_items`
```
- id (bigint, PK)
- medical_pattern_id (FK → medical_patterns)
- type (enum: complaint, diagnosis, treatment, prescription_item)
- data (json) → stores the item details
- sort_order (integer, default: 0)
- timestamps
```

### 8.3 Files to Create
| Type | File | Purpose |
|------|------|---------|
| Migration | 2 migrations | Pattern tables |
| Model | `MedicalPattern`, `MedicalPatternItem` | Models |
| Controller | `Doctor/MedicalPatternController` | Pattern CRUD |
| Service | `MedicalPatternService` | Pattern matching logic |
| View | **NEW** `patterns/index.blade.php` | Pattern management |
| View | **NEW** `patterns/create.blade.php` | Pattern creation |

### 8.4 Pattern Matching Algorithm
```
1. Doctor enters complaint text
2. System searches medical_pattern_items where type='complaint'
3. Fuzzy match on description (LIKE or pg_trgm)
4. Return matching patterns ordered by usage_count DESC
5. Doctor clicks "Apply Pattern" → auto-fills diagnoses + treatments
6. On save, increment usage_count
```

### 8.5 Deliverables
- [x] Pattern creation from consultation data ("Save as Pattern" button on consultation view)
- [x] Pattern suggestion based on complaints (AJAX search with fuzzy LIKE matching)
- [x] One-click pattern application (applies complaints, diagnoses, treatments to record)
- [x] Pattern management (list, create, view detail modal, toggle active/inactive, delete)
- [x] Usage tracking and ranking (auto-increment on apply, sorted by usage_count)

> **Phase 5 COMPLETED** — 2026-04-12
>
> **Implementation Notes:**
> - 2 migrations: `medical_patterns` (name, doctor_id, usage_count, is_active) + `medical_pattern_items` (type, data as longText/JSON, sort_order)
> - Pattern items store structured JSON: complaint (description, duration, severity), diagnosis (icd_code, description, type, notes), treatment (type, description), prescription_item (drug_name, dosage, frequency, duration, quantity, route, instructions)
> - MedicalPatternService: create, createFromRecord, update, suggest (fuzzy LIKE on complaint data + pattern name), applyPattern (creates items on medical record), toggleActive, delete
> - MedicalPatternController: 10 routes (index, create, store, show, update, destroy, toggle, suggest, apply, from-record). All AJAX-compatible.
> - Consultation view enhanced: Patterns tab with search + frequent patterns, "Save as Pattern" modal in quick actions, one-click apply with page reload
> - Sidebar updated with Medical Patterns link under Clinic section
> - Scopes: personal (doctor_id set) or system-wide (doctor_id null). ForDoctor scope returns both.

---

## 9. PHASE 6 — LABORATORY MODULE

### 9.1 Objectives
- Lab test requests from doctors
- Lab test processing by technicians
- Result entry and validation
- Result delivery to doctor's consultation view

### 9.2 Database Tables

#### `lab_test_categories`
```
- id (bigint, PK)
- name (string)
- description (text, nullable)
- timestamps
```

#### `lab_tests`
```
- id (bigint, PK)
- category_id (FK → lab_test_categories)
- name (string)
- code (string, unique)
- normal_range (string, nullable)
- unit (string, nullable)
- price (decimal, nullable)
- is_active (boolean, default: true)
- timestamps
```

#### `lab_requests`
```
- id (bigint, PK)
- request_number (string, unique)
- visit_id (FK → visits)
- patient_id (FK → patients)
- requested_by (FK → users) → doctor
- department_id (FK → departments)
- clinical_info (text, nullable)
- urgency (enum: routine, urgent, emergency)
- status (enum: pending, processing, completed, cancelled)
- timestamps
```

#### `lab_request_items`
```
- id (bigint, PK)
- lab_request_id (FK → lab_requests)
- lab_test_id (FK → lab_tests)
- status (enum: pending, processing, completed)
- timestamps
```

#### `lab_results`
```
- id (bigint, PK)
- lab_request_item_id (FK → lab_request_items)
- lab_request_id (FK → lab_requests)
- result_value (text)
- is_abnormal (boolean, default: false)
- remarks (text, nullable)
- performed_by (FK → users)
- verified_by (FK → users, nullable)
- performed_at (timestamp)
- verified_at (timestamp, nullable)
- timestamps
```

### 9.3 Files to Create
| Type | File | Purpose |
|------|------|---------|
| Migration | 5 migrations | Lab tables |
| Model | `LabTestCategory`, `LabTest`, `LabRequest`, `LabRequestItem`, `LabResult` | Models |
| Controller | `Lab/LabRequestController` | Lab request management |
| Controller | `Lab/LabResultController` | Result entry |
| Controller | `Admin/LabTestController` | Lab test CRUD |
| Service | `LabService` | Lab business logic |
| Request | Validation requests | Form validation |
| View | **NEW** `lab/requests.blade.php` | Lab request queue |
| View | **NEW** `lab/process.blade.php` | Result entry form |
| View | **NEW** `lab/results.blade.php` | Results list |
| View | **NEW** `lab/tests.blade.php` | Lab test management |

### 9.4 Template Views to Reuse
- Use `services.blade.php` as base layout for lab test management
- Use `appointments.blade.php` style for lab request queue
- Use DataTable pattern for all list views

### 9.5 Deliverables
- [ ] Lab test catalog management
- [ ] Lab request from consultation
- [ ] Lab request queue for technicians
- [ ] Result entry with normal range flagging
- [ ] Result verification workflow
- [ ] Results visible in doctor's consultation
- [ ] Visit status auto-update on lab completion

---

## 10. PHASE 7 — PHARMACY MODULE

### 10.1 Objectives
- Drug/medication inventory management
- Prescription dispensing workflow
- Stock tracking with alerts
- Dispensing history

### 10.2 Database Tables

#### `drug_categories`
```
- id (bigint, PK)
- name (string)
- description (text, nullable)
- timestamps
```

#### `drugs`
```
- id (bigint, PK)
- category_id (FK → drug_categories)
- name (string)
- generic_name (string, nullable)
- brand_name (string, nullable)
- dosage_form (string) → tablet, capsule, syrup, injection, etc.
- strength (string, nullable) → e.g., "500mg"
- unit (string) → e.g., "tablets", "ml"
- price (decimal)
- requires_prescription (boolean, default: true)
- is_active (boolean, default: true)
- description (text, nullable)
- timestamps
- soft_deletes
```

#### `drug_stock`
```
- id (bigint, PK)
- drug_id (FK → drugs)
- batch_number (string)
- quantity (integer)
- unit_cost (decimal)
- selling_price (decimal)
- expiry_date (date)
- supplier (string, nullable)
- received_date (date)
- received_by (FK → users)
- reorder_level (integer, default: 10)
- timestamps
```

#### `dispensing_records`
```
- id (bigint, PK)
- prescription_id (FK → prescriptions)
- prescription_item_id (FK → prescription_items)
- drug_stock_id (FK → drug_stock)
- patient_id (FK → patients)
- visit_id (FK → visits)
- quantity_dispensed (integer)
- dispensed_by (FK → users)
- dispensed_at (timestamp)
- notes (text, nullable)
- timestamps
```

### 10.3 Files to Create
| Type | File | Purpose |
|------|------|---------|
| Migration | 4 migrations | Pharmacy tables |
| Model | `DrugCategory`, `Drug`, `DrugStock`, `DispensingRecord` | Models |
| Controller | `Pharmacy/DispensingController` | Dispensing workflow |
| Controller | `Admin/DrugController` | Drug catalog CRUD |
| Controller | `Admin/DrugStockController` | Stock management |
| Service | `PharmacyService` | Dispensing logic, stock management |
| Request | Validation requests | Form validation |
| View | **NEW** `pharmacy/dispensing.blade.php` | Dispensing queue |
| View | **NEW** `pharmacy/dispense.blade.php` | Dispense form |
| View | **NEW** `pharmacy/drugs.blade.php` | Drug catalog |
| View | **NEW** `pharmacy/stock.blade.php` | Stock management |
| View | **NEW** `pharmacy/stock-alerts.blade.php` | Low stock alerts |

### 10.4 Deliverables
- [ ] Drug catalog management
- [ ] Stock management (batch tracking, expiry)
- [ ] Dispensing queue (pending prescriptions)
- [ ] Dispense with stock deduction
- [ ] Low stock alerts
- [ ] Expiry date alerts
- [ ] Dispensing history
- [ ] Visit status auto-update after dispensing

---

## 11. PHASE 8 — BILLING MODULE

### 11.1 Objectives
- Auto-generate invoices from visit services
- Support NHIS billing vs cash billing
- Payment recording (cash, mobile money, insurance)
- Invoice printing
- Financial tracking

### 11.2 Database Tables

#### `service_catalog`
```
- id (bigint, PK)
- name (string)
- code (string, unique)
- category (enum: consultation, lab, pharmacy, procedure, other)
- price (decimal)
- nhis_price (decimal, nullable) → NHIS-approved price
- is_nhis_covered (boolean, default: false)
- is_active (boolean, default: true)
- timestamps
```

#### `invoices`
```
- id (bigint, PK)
- invoice_number (string, unique, auto: INV-YYYYMMDD-XXXX)
- visit_id (FK → visits)
- patient_id (FK → patients)
- billing_type (enum: cash, nhis, corporate, mixed)
- subtotal (decimal)
- tax_amount (decimal, default: 0)
- discount_amount (decimal, default: 0)
- nhis_amount (decimal, default: 0) → amount covered by NHIS
- total_amount (decimal)
- amount_paid (decimal, default: 0)
- balance (decimal)
- status (enum: draft, pending, partially_paid, paid, cancelled, refunded)
- due_date (date, nullable)
- notes (text, nullable)
- created_by (FK → users)
- timestamps
- soft_deletes
```

#### `invoice_items`
```
- id (bigint, PK)
- invoice_id (FK → invoices)
- service_catalog_id (FK → service_catalog, nullable)
- description (string)
- quantity (integer, default: 1)
- unit_price (decimal)
- total_price (decimal)
- is_nhis_covered (boolean, default: false)
- nhis_approved_amount (decimal, default: 0)
- timestamps
```

#### `payments`
```
- id (bigint, PK)
- payment_number (string, unique)
- invoice_id (FK → invoices)
- patient_id (FK → patients)
- amount (decimal)
- payment_method (enum: cash, mobile_money, card, bank_transfer, nhis, cheque)
- reference_number (string, nullable) → MoMo transaction ID, etc.
- received_by (FK → users)
- notes (text, nullable)
- paid_at (timestamp)
- timestamps
```

### 11.3 Files to Create
| Type | File | Purpose |
|------|------|---------|
| Migration | 4 migrations | Billing tables |
| Model | `ServiceCatalog`, `Invoice`, `InvoiceItem`, `Payment` | Models |
| Controller | `Billing/InvoiceController` | Invoice management |
| Controller | `Billing/PaymentController` | Payment recording |
| Controller | `Admin/ServiceCatalogController` | Service CRUD |
| Service | `BillingService` | Invoice generation, payment logic |
| Request | Validation requests | Form validation |
| View | Refactor `invoices.blade.php` | Invoice list |
| View | Refactor `add-invoices.blade.php` | Create invoice |
| View | Refactor `invoices-details.blade.php` | Invoice details/print |
| View | Refactor `payments.blade.php` | Payments list |
| View | Refactor `services.blade.php` | Service catalog |

### 11.4 Template Views to Reuse
- `invoices.blade.php` → Invoice list (add NHIS column, GHS currency)
- `add-invoices.blade.php` → Create invoice (add NHIS toggle per item)
- `invoices-details.blade.php` → Invoice details + print view
- `payments.blade.php` → Payment list (add MoMo as payment method)
- `services.blade.php` → Service catalog management

### 11.5 Key Features — Ghana-Specific
1. **NHIS Billing**: Toggle NHIS coverage per service item
2. **Mobile Money**: MTN MoMo, Vodafone Cash, AirtelTigo Money
3. **Ghana Cedi (GHS/₵)**: Currency formatting throughout
4. **Split Payment**: Part NHIS + part cash
5. **Invoice Print**: A4 format with hospital letterhead

### 11.6 Deliverables
- [ ] Service catalog with NHIS pricing
- [ ] Auto-invoice generation from visit
- [ ] NHIS vs cash billing toggle
- [ ] Payment recording (cash, MoMo, card, NHIS)
- [ ] Invoice printing (PDF)
- [ ] Payment receipts
- [ ] Outstanding balance tracking
- [ ] Visit auto-completion after payment

---

## 12. PHASE 9 — DASHBOARDS & REPORTS

### 12.1 Objectives
- Role-specific dashboards with real metrics
- Financial reports
- Clinical reports
- Operational reports

### 12.2 Dashboards

#### Admin Dashboard (Refactor `index.blade.php`)
| Metric | Source |
|--------|--------|
| Total Patients | `patients` count |
| Today's Visits | `visits` where date = today |
| Total Revenue (Month) | `payments` sum this month |
| Pending Lab Results | `lab_requests` pending count |
| Active Doctors | `users` with doctor role, active |
| Appointment Trend | `visits` grouped by day (chart) |
| Revenue Trend | `payments` grouped by day (chart) |
| Department Load | `visits` grouped by department |
| Low Stock Alerts | `drug_stock` below reorder level |

#### Doctor Dashboard (Refactor `doctor-dashboard.blade.php`)
| Metric | Source |
|--------|--------|
| Today's Patients | `visits` assigned to doctor today |
| Pending Consultations | `visits` where status = waiting/triage |
| Completed Today | `visits` completed today by doctor |
| Pending Lab Results | `lab_requests` for doctor's patients |
| Upcoming Schedule | `visits` scheduled for this week |

### 12.3 Reports
| Report | Description | Template |
|--------|-------------|----------|
| Income Report | Revenue by period, department, service | Refactor `income-report.blade.php` |
| Expense Report | Expenses by category | Refactor `expense-report.blade.php` |
| Profit & Loss | Revenue - Expenses | Refactor `profit-and-loss.blade.php` |
| Patient Report | Registration trends, demographics | Refactor `patient-report.blade.php` |
| Visit Report | Visit stats, department load | Refactor `appointment-report.blade.php` |
| Lab Report | Test frequency, turnaround time | **NEW** |
| Pharmacy Report | Drug usage, stock movement | **NEW** |
| NHIS Report | NHIS claims summary | **NEW** |

### 12.4 Deliverables
- [ ] Admin dashboard with real metrics and charts
- [ ] Doctor dashboard with patient queue
- [ ] Income/Expense/P&L reports
- [ ] Patient statistics report
- [ ] Visit/appointment report
- [ ] Excel export for all reports
- [ ] PDF export for all reports

---

## 13. PHASE 10 — SETTINGS, AUDIT & POLISH

### 13.1 Settings to Implement
| Setting | Template View | Priority |
|---------|---------------|----------|
| Organization Settings | `organization-settings.blade.php` | ✅ High |
| Profile Settings | `profile-settings.blade.php` | ✅ High |
| Invoice Settings | `invoice-settings.blade.php` | ✅ High |
| Email Settings | `email-settings.blade.php` | ⚠️ Medium |
| Working Hours | `working-hours-settings.blade.php` | ⚠️ Medium |
| Payment Methods | `payment-methods-settings.blade.php` | ✅ High |
| Tax Rates | `tax-rates-settings.blade.php` | ⚠️ Medium |
| Notification Settings | `notifications-settings.blade.php` | ⚠️ Medium |

### 13.2 Audit Log
Using `spatie/laravel-activitylog`:
- Log all patient record access
- Log all visit status changes
- Log all prescription creations
- Log all payment recordings
- Log all user login/logout events
- Viewable in `activities.blade.php`

### 13.3 Polish Tasks
- [ ] Error pages (404, 500) with proper styling
- [ ] Form error handling with proper feedback
- [ ] Loading states for AJAX operations
- [ ] Responsive testing on tablets (hospital staff often use tablets)
- [ ] Print stylesheets for invoices/prescriptions
- [ ] Data backup configuration
- [ ] Performance optimization (indexes, eager loading)

---

## 14. DATABASE SCHEMA OVERVIEW

### Complete Table Count: ~30 tables

```
Core:
├── users (modified)
├── departments
├── designations

Patient:
├── patients

Visit & Queue:
├── visits
├── visit_status_logs
├── queue_entries

EHR:
├── medical_records
├── complaints
├── diagnoses
├── investigations
├── treatments
├── prescriptions
├── prescription_items
├── vitals

Medical Patterns:
├── medical_patterns
├── medical_pattern_items

Laboratory:
├── lab_test_categories
├── lab_tests
├── lab_requests
├── lab_request_items
├── lab_results

Pharmacy:
├── drug_categories
├── drugs
├── drug_stock
├── dispensing_records

Billing:
├── service_catalog
├── invoices
├── invoice_items
├── payments

Spatie (auto-created):
├── roles
├── permissions
├── model_has_roles
├── model_has_permissions
├── role_has_permissions
├── activity_log
```

---

## 15. FOLDER STRUCTURE (Final)

```
app/
├── Console/
│   └── Commands/
├── Enums/
│   ├── VisitStatus.php
│   ├── VisitType.php
│   ├── Priority.php
│   ├── Gender.php
│   ├── BloodGroup.php
│   ├── PaymentMethod.php
│   ├── BillingType.php
│   ├── InvoiceStatus.php
│   ├── LabRequestStatus.php
│   ├── PrescriptionStatus.php
│   └── UserStatus.php
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── UserController.php
│   │   │   ├── RoleController.php
│   │   │   ├── DepartmentController.php
│   │   │   ├── DesignationController.php
│   │   │   ├── PatientController.php
│   │   │   ├── VisitController.php
│   │   │   ├── QueueController.php
│   │   │   ├── LabTestController.php
│   │   │   ├── DrugController.php
│   │   │   ├── DrugStockController.php
│   │   │   ├── ServiceCatalogController.php
│   │   │   ├── SettingsController.php
│   │   │   └── ReportController.php
│   │   ├── Doctor/
│   │   │   ├── DashboardController.php
│   │   │   ├── ConsultationController.php
│   │   │   ├── PrescriptionController.php
│   │   │   └── MedicalPatternController.php
│   │   ├── Lab/
│   │   │   ├── LabRequestController.php
│   │   │   └── LabResultController.php
│   │   ├── Pharmacy/
│   │   │   └── DispensingController.php
│   │   ├── Billing/
│   │   │   ├── InvoiceController.php
│   │   │   └── PaymentController.php
│   │   └── Auth/                          ← Breeze controllers
│   ├── Middleware/
│   │   └── EnsureUserHasRole.php
│   └── Requests/
│       ├── Patient/
│       │   ├── StorePatientRequest.php
│       │   └── UpdatePatientRequest.php
│       ├── Visit/
│       │   └── StoreVisitRequest.php
│       ├── Consultation/
│       │   └── StoreConsultationRequest.php
│       ├── Lab/
│       │   ├── StoreLabRequestRequest.php
│       │   └── StoreLabResultRequest.php
│       ├── Pharmacy/
│       │   └── DispenseRequest.php
│       ├── Billing/
│       │   ├── StoreInvoiceRequest.php
│       │   └── StorePaymentRequest.php
│       └── User/
│           ├── StoreUserRequest.php
│           └── UpdateUserRequest.php
├── Models/
│   ├── User.php
│   ├── Department.php
│   ├── Designation.php
│   ├── Patient.php
│   ├── Visit.php
│   ├── VisitStatusLog.php
│   ├── QueueEntry.php
│   ├── MedicalRecord.php
│   ├── Complaint.php
│   ├── Diagnosis.php
│   ├── Investigation.php
│   ├── Treatment.php
│   ├── Prescription.php
│   ├── PrescriptionItem.php
│   ├── Vital.php
│   ├── MedicalPattern.php
│   ├── MedicalPatternItem.php
│   ├── LabTestCategory.php
│   ├── LabTest.php
│   ├── LabRequest.php
│   ├── LabRequestItem.php
│   ├── LabResult.php
│   ├── DrugCategory.php
│   ├── Drug.php
│   ├── DrugStock.php
│   ├── DispensingRecord.php
│   ├── ServiceCatalog.php
│   ├── Invoice.php
│   ├── InvoiceItem.php
│   └── Payment.php
├── Observers/
│   ├── VisitObserver.php
│   └── InvoiceObserver.php
├── Policies/
│   ├── PatientPolicy.php
│   ├── VisitPolicy.php
│   └── InvoicePolicy.php
├── Providers/
│   └── AppServiceProvider.php
├── Services/
│   ├── UserService.php
│   ├── PatientService.php
│   ├── VisitService.php
│   ├── QueueService.php
│   ├── ConsultationService.php
│   ├── PrescriptionService.php
│   ├── MedicalPatternService.php
│   ├── LabService.php
│   ├── PharmacyService.php
│   └── BillingService.php
└── Traits/
    ├── GeneratesNumbers.php         ← Auto-generate PT-, VST-, INV- numbers
    └── HasAuditLog.php

resources/views/
├── layouts/
│   ├── app.blade.php               ← Main admin layout
│   ├── auth.blade.php              ← Auth pages layout
│   └── partials/
│       ├── header.blade.php
│       ├── sidebar.blade.php
│       ├── scripts.blade.php
│       └── styles.blade.php
├── auth/                            ← Breeze auth views
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── forgot-password.blade.php
│   └── reset-password.blade.php
├── dashboard/
│   ├── admin.blade.php
│   └── doctor.blade.php
├── patients/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── show.blade.php
│   └── edit.blade.php
├── visits/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── show.blade.php
├── queue/
│   ├── board.blade.php
│   └── manage.blade.php
├── consultation/
│   ├── index.blade.php              ← Main consultation interface
│   └── history.blade.php
├── vitals/
│   └── record.blade.php
├── prescriptions/
│   ├── index.blade.php
│   └── show.blade.php
├── lab/
│   ├── requests.blade.php
│   ├── process.blade.php
│   ├── results.blade.php
│   └── tests.blade.php
├── pharmacy/
│   ├── dispensing.blade.php
│   ├── dispense.blade.php
│   ├── drugs.blade.php
│   ├── stock.blade.php
│   └── stock-alerts.blade.php
├── billing/
│   ├── invoices/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   └── show.blade.php
│   ├── payments/
│   │   └── index.blade.php
│   └── services.blade.php
├── users/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── roles/
│   ├── index.blade.php
│   └── permissions.blade.php
├── departments/
│   └── index.blade.php
├── reports/
│   ├── income.blade.php
│   ├── expense.blade.php
│   ├── visits.blade.php
│   ├── patients.blade.php
│   └── nhis.blade.php
├── settings/
│   ├── organization.blade.php
│   ├── profile.blade.php
│   ├── invoice.blade.php
│   └── payment-methods.blade.php
├── patterns/
│   ├── index.blade.php
│   └── create.blade.php
└── components/
    ├── visit-timeline.blade.php
    ├── patient-search.blade.php
    ├── queue-number.blade.php
    └── nhis-badge.blade.php

routes/
├── web.php                          ← All web routes grouped by role
└── auth.php                         ← Breeze auth routes
```

---

## 16. PACKAGES REQUIRED

### Production Packages
```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "laravel/tinker": "^2.10",
        "laravel/breeze": "^2.0",
        "spatie/laravel-permission": "^6.0",
        "spatie/laravel-activitylog": "^4.0",
        "barryvdh/laravel-dompdf": "^3.0",
        "maatwebsite/excel": "^3.1"
    }
}
```

### Dev Packages
```json
{
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pail": "^1.2",
        "laravel/pint": "^1.13",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.6",
        "phpunit/phpunit": "^11.5",
        "barryvdh/laravel-ide-helper": "^3.0"
    }
}
```

---

## 17. TEMPLATE REUSE MAP

### Views We REUSE (with modifications)
| Template View | UHMS View | Modifications |
|---------------|-----------|---------------|
| `index.blade.php` | `dashboard/admin.blade.php` | Replace hardcoded data with Eloquent queries |
| `doctor-dashboard.blade.php` | `dashboard/doctor.blade.php` | Dynamic doctor metrics |
| `create-patient.blade.php` | `patients/create.blade.php` | Add Ghana fields, dynamic dropdowns |
| `patients.blade.php` | `patients/index.blade.php` | Dynamic DataTable, AJAX search |
| `patient-details.blade.php` | `patients/show.blade.php` | Add visit history, vitals, EHR tabs |
| `edit-patient.blade.php` | `patients/edit.blade.php` | Dynamic form with old values |
| `add-doctor.blade.php` | `users/create.blade.php` | Generalize for all user roles |
| `staffs.blade.php` | `users/index.blade.php` | Dynamic user list with role filter |
| `roles-and-permissions.blade.php` | `roles/index.blade.php` | Dynamic with Spatie |
| `permissions.blade.php` | `roles/permissions.blade.php` | Dynamic permission matrix |
| `appointments.blade.php` | `visits/index.blade.php` | Rename to Visits, add status flow |
| `new-appointment.blade.php` | `visits/create.blade.php` | Adapt for visit creation |
| `invoices.blade.php` | `billing/invoices/index.blade.php` | Add GHS, NHIS columns |
| `add-invoices.blade.php` | `billing/invoices/create.blade.php` | Add NHIS toggle |
| `invoices-details.blade.php` | `billing/invoices/show.blade.php` | Dynamic with print |
| `payments.blade.php` | `billing/payments/index.blade.php` | Add MoMo, GHS |
| `services.blade.php` | `billing/services.blade.php` | Service catalog with NHIS pricing |
| `hrm-departments.blade.php` | `departments/index.blade.php` | Dynamic departments |
| `designation.blade.php` | Department designations | Dynamic |
| `login.blade.php` | `auth/login.blade.php` | Breeze integration |
| `income-report.blade.php` | `reports/income.blade.php` | Dynamic charts |
| `expense-report.blade.php` | `reports/expense.blade.php` | Dynamic charts |
| `appointment-report.blade.php` | `reports/visits.blade.php` | Visit statistics |
| `patient-report.blade.php` | `reports/patients.blade.php` | Patient demographics |
| `profile-settings.blade.php` | `settings/profile.blade.php` | Dynamic user profile |
| `organization-settings.blade.php` | `settings/organization.blade.php` | Hospital settings |
| `activities.blade.php` | Audit log viewer | Dynamic activity log |
| `error-404.blade.php` | `errors/404.blade.php` | Keep as-is |
| `error-500.blade.php` | `errors/500.blade.php` | Keep as-is |

### Views We CREATE FROM SCRATCH
| UHMS View | Reason |
|-----------|--------|
| `consultation/index.blade.php` | No EHR view exists in template |
| `consultation/history.blade.php` | No medical history view |
| `vitals/record.blade.php` | No vitals recording view |
| `queue/board.blade.php` | No queue board view |
| `queue/manage.blade.php` | No queue management view |
| `lab/requests.blade.php` | No laboratory views |
| `lab/process.blade.php` | No lab result entry view |
| `lab/results.blade.php` | No lab results list |
| `lab/tests.blade.php` | No lab test catalog |
| `pharmacy/dispensing.blade.php` | No pharmacy views |
| `pharmacy/dispense.blade.php` | No dispensing form |
| `pharmacy/drugs.blade.php` | No drug catalog |
| `pharmacy/stock.blade.php` | No stock management |
| `pharmacy/stock-alerts.blade.php` | No stock alerts |
| `patterns/index.blade.php` | No medical patterns view |
| `patterns/create.blade.php` | No pattern creation view |
| `reports/nhis.blade.php` | No NHIS report view |
| `components/visit-timeline.blade.php` | No visit timeline |
| `components/patient-search.blade.php` | No quick search component |

### Views We DON'T NEED (Template extras to remove/ignore)
- All blog-related views (6 pages)
- Social feed, Chat, Video/Voice calls
- Kanban board, Notes, Todo (unless repurposed)
- All UI component demo pages (30+ pages)
- All chart demo pages (6 pages)
- All form demo pages (15+ pages)
- All icon demo pages (13 pages)
- Layout variant pages
- CMS pages (Pages, Testimonials, FAQ)
- Countries/States/Cities (use seeders instead)
- Newsletters

---

## 18. GHANA-SPECIFIC ADAPTATIONS

### 18.1 Patient Fields
| Field | Description |
|-------|-------------|
| Ghana Card Number | National ID (GHA-XXXXXXXXX-X) |
| NHIS Number | National Health Insurance Scheme membership |
| NHIS Expiry Date | Insurance validity |
| Region | 16 Ghana regions |
| District | Districts within regions |
| Digital Address | Ghana Post GPS address (e.g., GA-123-4567) |

### 18.2 Ghana Regions (Seeder Data)
1. Greater Accra
2. Ashanti
3. Western
4. Central
5. Eastern
6. Northern
7. Volta
8. Upper East
9. Upper West
10. Bono
11. Bono East
12. Ahafo
13. Western North
14. Oti
15. North East
16. Savannah

### 18.3 Currency
- Symbol: ₵ (or GH₵)
- Code: GHS
- Format: GH₵ 1,234.56

### 18.4 Payment Methods
| Method | Details |
|--------|---------|
| Cash | Physical cash payment |
| MTN Mobile Money | Most popular mobile money in Ghana |
| Vodafone Cash | Vodafone Ghana mobile money |
| AirtelTigo Money | AirtelTigo mobile money |
| Bank Transfer | Direct bank transfer |
| Card | Visa/Mastercard |
| NHIS | Insurance claim |
| Cheque | Bank cheque |

### 18.5 NHIS Integration Points
1. **Patient Registration**: Validate NHIS membership status
2. **Visit Creation**: Flag as NHIS or cash visit
3. **Service Pricing**: NHIS-approved rates vs standard rates
4. **Invoice Generation**: Auto-calculate NHIS covered amount
5. **Claims Summary**: Monthly NHIS claims report for submission

### 18.6 Common Departments (Ghana Hospitals)
1. General Medicine / OPD
2. Pediatrics
3. Obstetrics & Gynecology
4. Surgery
5. Orthopedics
6. Eye Clinic (Ophthalmology)
7. ENT
8. Dental
9. Psychiatry
10. Emergency / Casualty
11. Laboratory
12. Pharmacy
13. Radiology / X-Ray
14. Physiotherapy
15. Antenatal / Postnatal
16. Family Planning

---

## 19. SECURITY CHECKLIST

| Requirement | Implementation |
|-------------|----------------|
| Authentication | Laravel Breeze (session-based) |
| Authorization | Spatie permissions + Policies |
| RBAC | Role-based middleware on all routes |
| CSRF Protection | Laravel built-in (all forms) |
| XSS Prevention | Blade `{{ }}` auto-escaping |
| SQL Injection | Eloquent ORM (parameterized queries) |
| Mass Assignment | `$fillable` on all models |
| Password Hashing | bcrypt (Laravel default) |
| Rate Limiting | Laravel rate limiter on login |
| Session Security | Secure cookies, HTTPS recommended |
| Audit Trail | spatie/activity-log on all critical actions |
| Patient Data Access | Policy-based, logged access |
| Input Validation | Form Request classes on all endpoints |
| File Upload | Validate type/size, store outside web root |
| Error Handling | Custom 404/500, no stack traces in production |

---

## 20. TESTING STRATEGY

### 20.1 Feature Tests (Priority)
| Test | What It Covers |
|------|----------------|
| `AuthTest` | Login, logout, password reset |
| `PatientTest` | CRUD, search, validation |
| `VisitLifecycleTest` | Full visit flow: register → complete |
| `ConsultationTest` | EHR recording, prescriptions |
| `LabWorkflowTest` | Request → Process → Result |
| `DispensingTest` | Prescription → Dispense → Stock deduction |
| `BillingTest` | Invoice generation, payment, balance calculation |
| `NHISBillingTest` | NHIS pricing, split payments |
| `RoleAccessTest` | Role-based route access |
| `QueueTest` | Queue management, ordering |

### 20.2 Test Commands
```bash
php artisan test                          # Run all tests
php artisan test --filter=VisitLifecycle  # Run specific test
php artisan test --coverage               # With coverage
```

---

## 21. DEPLOYMENT NOTES

### 21.1 Server Requirements
- PHP 8.2+
- PostgreSQL 15+ (or MySQL 8+)
- Composer 2.x
- Node.js 18+ (for build)
- Redis (optional, for queues/cache)
- SSL Certificate (required for patient data)

### 21.2 Environment Variables (Key)
```env
APP_NAME="UHMS"
APP_ENV=production
APP_TIMEZONE=Africa/Accra
APP_LOCALE=en

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=uhms
DB_USERNAME=uhms_user
DB_PASSWORD=secure_password

CURRENCY_CODE=GHS
CURRENCY_SYMBOL=₵
```

### 21.3 Deployment Steps
```bash
composer install --optimize-autoloader --no-dev
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## WHAT I NEED FROM YOU TO START

Before we begin Phase 0, confirm:

1. **Database**: PostgreSQL or MySQL?
2. **Auth variant**: Use the `login.blade.php` (custom dark design) or `login-basic.blade.php` (simple white)?
3. **Multi-clinic**: Does one installation serve one hospital, or should it support multiple facilities?
4. **Inpatient**: Do we need bed/ward management, or is this outpatient only?
5. **NHIS priority**: Is NHIS billing critical from day one, or can we add it later?
6. **Real-time queue**: Do you want a live queue board (requires WebSocket/Pusher), or is periodic refresh sufficient?
7. **Deployment target**: Shared hosting (cPanel), VPS, or cloud (AWS/DigitalOcean)?

---

## SUMMARY

| Metric | Count |
|--------|-------|
| Total Phases | 11 (0-10) |
| Database Tables | ~30 |
| Models | ~28 |
| Controllers | ~20 |
| Services | ~10 |
| Form Requests | ~15 |
| Template Views Reused | ~28 |
| New Views to Create | ~19 |
| Migrations | ~25 |
| Seeders | ~8 |
| Enums | ~11 |

**Ready to start Phase 0 on your command.**
