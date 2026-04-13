# UHMS (Unified Hospital Management System) — Full Project Analysis

**Version:** 0.0.0 (Template/Development)  
**Analysis Date:** April 13, 2026  
**Stack:** Vue 3 + TypeScript + Pinia + Vite  
**Backend:** Django REST Framework (JWT Auth, RESTful API)

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Architecture Overview](#2-architecture-overview)
3. [Technology Stack](#3-technology-stack)
4. [Authentication & Authorization](#4-authentication--authorization)
5. [Application Modules](#5-application-modules)
6. [Patient Lifecycle](#6-patient-lifecycle)
7. [Appointment Workflow](#7-appointment-workflow)
8. [Consultation Workflow](#8-consultation-workflow)
9. [Data Flow Patterns](#9-data-flow-patterns)
10. [State Management](#10-state-management)
11. [API Contract Summary](#11-api-contract-summary)
12. [Navigation & Routing Architecture](#12-navigation--routing-architecture)
13. [Component Architecture](#13-component-architecture)
14. [Current Status & Maturity Assessment](#14-current-status--maturity-assessment)

---

## 1. Executive Summary

UHMS is a **hospital management system frontend template** built with Vue 3 (Composition API) and TypeScript. It provides a multi-department, role-based clinical management platform covering the full patient lifecycle — from registration through appointment booking, vitals capture, consultation (in-person and telehealth), and post-visit management.

The system is designed as a **multi-tenant admin panel** where different user roles (Super Admin, Admin, Consultation, Investigation, Nursing) access department-specific dashboards and features. The frontend communicates with a Django REST Framework backend via JWT-authenticated RESTful APIs.

### Key Capabilities

| Domain | Features |
|--------|----------|
| **Patient Management** | Registration, profiles, insurance management, medical history, vital signs |
| **Appointment Management** | Booking (by service or doctor), calendar view, status tracking, rescheduling, doctor transfer |
| **Consultation** | In-person consultations, telehealth/virtual consultations, clinical notes, TODO tracking |
| **Vitals** | Temperature, BP, heart rate, respiratory rate, SpO2, weight, height, BMI, glucose, pain scale |
| **Staff/HRM** | Staff management, departments, designations, attendance, leave, payroll |
| **Finance** | Expenses, income, invoices, payments, transactions, assets |
| **Administration** | Roles & permissions, reports (income, expense, P&L, appointment, patient) |
| **Content** | Pages, blogs, FAQs |

---

## 2. Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        UHMS Frontend                            │
│                     (Vue 3 + TypeScript)                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐       │
│  │  Views/   │  │ Layouts  │  │Components│  │  Modals  │       │
│  │  Pages    │  │ (Header, │  │ (Common, │  │ (CRUD    │       │
│  │          │  │ Sidebar, │  │ Filters, │  │ dialogs) │       │
│  │          │  │ Footer)  │  │ Canvas)  │  │          │       │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘       │
│       │              │              │              │             │
│  ┌────▼──────────────▼──────────────▼──────────────▼─────┐     │
│  │                  Pinia Stores                          │     │
│  │  authStore │ patientStore │ appointmentStore │ staffStore│   │
│  │            │ dataTableStore (factory)                   │    │
│  └────────────────────────┬───────────────────────────────┘    │
│                           │                                     │
│  ┌────────────────────────▼───────────────────────────────┐    │
│  │              Axios Instance (utils/axios.ts)            │    │
│  │  • Bearer token injection    • 401 auto-refresh         │    │
│  │  • Base URL configuration    • Error interceptors       │    │
│  └────────────────────────┬───────────────────────────────┘    │
│                           │                                     │
├───────────────────────────┼─────────────────────────────────────┤
│                           ▼                                     │
│              Django REST Framework Backend                      │
│         (JWT Auth, RESTful API, PostgreSQL)                     │
└─────────────────────────────────────────────────────────────────┘
```

### Directory Structure

```
src/
├── assets/          # Static assets: CSS, fonts, images, JSON configs, SCSS
├── components/      # Reusable components (shared across views)
│   ├── common/      #   Filters, pagination, canvas sidebars
│   └── modals/      #   CRUD modals organized by domain
│       ├── appointment/   #   8+ appointment modals
│       ├── patient/       #   Insurance & NOK modals
│       ├── doctor/        #   Doctor-specific modals
│       ├── service/       #   Service CRUD modals
│       └── general/       #   Contacts and general modals
├── composables/     # Vue composables (usePagination)
├── layouts/         # App shell: headers, sidebars, footers
├── locales/         # i18n translation files (en, fr)
├── router/          # Route definitions + auth guard
├── stores/          # Pinia state management
├── types/           # TypeScript type definitions
├── utils/           # Axios config, toast notifications
└── views/           # Page components organized by domain
    ├── admin/       #   Admin-scoped views
    │   ├── clinic/  #     Patients, appointments, consultation, vitals
    │   ├── hrm/     #     Staff, departments
    │   └── finance/ #     Expenses, income, invoices
    ├── appointments/#   User-facing appointment views
    ├── auth/        #   Login, forgot password
    ├── doctor/      #   Doctor portal views
    ├── patient/     #   Patient portal views
    ├── reports/     #   Reporting views
    └── settings/    #   Application settings
```

---

## 3. Technology Stack

### Core Framework

| Technology | Version | Purpose |
|------------|---------|---------|
| Vue 3 | ^3.5.24 | UI framework (Composition API + `<script setup>`) |
| TypeScript | ^5.9.3 | Type safety (gradual migration, `strict: false`) |
| Vite | ^6.0.5 | Build tool & dev server |
| Pinia | ^3.0.4 | State management |
| Vue Router | ^4.6.3 | Client-side routing |

### UI Libraries

| Library | Purpose |
|---------|---------|
| Bootstrap 5 | Grid system, utility classes |
| Ant Design Vue | Data tables, form components, modals |
| FullCalendar | Calendar appointment view |
| Vue Toastification | Toast notifications |
| Vue Sweetalert2 | Confirmation dialogs |
| ApexCharts | Dashboard charts |
| Quill | Rich text editor (consultation notes) |

### Form & Validation

| Library | Purpose |
|---------|---------|
| Vee-Validate | Form field validation |
| Yup | Schema-based validation rules |
| Vue Tel Input | Phone number input with country codes |
| Vue Multiselect | Searchable dropdowns |
| Vue3 Select | Alternative select component |

### Authentication

| Library | Purpose |
|---------|---------|
| jwt-decode | Client-side JWT decoding |
| pinia-plugin-persistedstate | Token persistence in localStorage |
| Axios | HTTP client with interceptors |

---

## 4. Authentication & Authorization

### Authentication Flow

```
┌──────────┐     POST /auth/token/      ┌──────────┐
│  Login   │ ─────────────────────────→  │  Django  │
│  Form    │                             │  Backend │
│          │  ← { access, refresh }  ──  │          │
└────┬─────┘                             └──────────┘
     │
     ▼
┌──────────────────┐
│ JWT Decode        │
│ Extract: uuid,    │
│ email, role,      │
│ is_superuser,     │
│ department        │
└────┬─────────────┘
     │
     ▼
┌──────────────────┐
│ Pinia authStore   │
│ • Persist to      │
│   localStorage    │
│ • Set Bearer      │
│   token header    │
└────┬─────────────┘
     │
     ▼
┌──────────────────┐
│ Route Redirect    │
│ Based on role/    │
│ department        │
└──────────────────┘
```

### Token Lifecycle

1. **Login** → Receive `access` + `refresh` JWT tokens
2. **Every request** → Axios interceptor injects `Authorization: Bearer {access}`
3. **401 response** → Interceptor attempts silent refresh via `POST /auth/token/refresh`
4. **Refresh success** → Update stored token, retry original request
5. **Refresh failure** → Clear localStorage, redirect to `/login`
6. **Logout** → `DELETE /auth/token/blacklist/` to invalidate refresh token server-side

### Role-Based Access Control (RBAC)

```
Permission Hierarchy:
─────────────────────
Super Admin ──→ Full access to ALL routes and features
    │
    ├── Admin ──→ Access to admin routes + assigned department
    │
    ├── Consultation Department ──→ Consultation-specific views
    │
    ├── Investigation Department ──→ Investigation-specific views
    │
    └── Nursing Department ──→ Nursing-specific views
```

**Route meta flags** control access:

| Meta Flag | Controls |
|-----------|----------|
| `requiresAuth` | Must be logged in |
| `guestOnly` | Only accessible when NOT logged in |
| `isSuperAdmin` | Super admin exclusive |
| `isAdmin` | Admin + super admin |
| `isConsultation` | Consultation dept + super admin |
| `isInvestigation` | Investigation dept + super admin |
| `isNursing` | Nursing dept + super admin |

**Department-based dashboard routing:**

| Department | Dashboard Route |
|------------|----------------|
| admin | `AdminDashboard` |
| consultation | `ConsultationDashboard` |
| investigation | `InvestigationDashboard` |
| nursing | `NursingDashboard` |

### Password Reset Flow

```
Forgot Password → POST /auth/password-reset-token { email }
                        ↓
              Email sent with token
                        ↓
Token Verify  → PATCH /auth/token/verification/ { token }
                        ↓
              Returns user UUID
                        ↓
Set Password  → PATCH /auth/{uuid}/password { password, password2, token }
                        ↓
              Redirect to login
```

---

## 5. Application Modules

### Module Map

```
UHMS
├── 🏥 Clinic
│   ├── Patient Management
│   ├── Appointment Management
│   ├── Consultation (In-Person + Telehealth)
│   ├── Vitals Recording
│   ├── Doctors Directory
│   ├── Services Catalog
│   ├── Specializations
│   └── Locations
│
├── 👥 HRM (Human Resource Management)
│   ├── Staff Management
│   ├── Departments
│   ├── Designations
│   ├── Attendance
│   ├── Leave Management
│   ├── Holidays
│   └── Payroll
│
├── 💰 Finance & Accounts
│   ├── Expenses (+ categories)
│   ├── Income
│   ├── Invoices
│   ├── Payments
│   ├── Transactions
│   └── Assets
│
├── 🔐 Administration
│   ├── Roles & Permissions
│   ├── User Account Management
│   └── Reports
│       ├── Income Report
│       ├── Expense Report
│       ├── Profit & Loss
│       ├── Appointment Report
│       └── Patient Report
│
├── 📝 Content
│   ├── Pages
│   ├── Blogs
│   └── FAQs
│
└── 📊 Multi-Department Dashboards
    ├── Admin Dashboard
    ├── Consultation Dashboard
    ├── Investigation Dashboard
    ├── Treatment Dashboard
    ├── Procedure Dashboard
    ├── Medication Dashboard
    ├── Emergency Services Dashboard
    ├── Nursing Care Dashboard
    ├── Support Services Dashboard
    └── Patient Dashboard
```

---

## 6. Patient Lifecycle

The patient lifecycle represents the complete journey of a patient through the system — from initial registration to ongoing care management.

### Lifecycle Diagram

```
 ┌─────────────────────────────────────────────────────────────────────┐
 │                     PATIENT LIFECYCLE                                │
 └─────────────────────────────────────────────────────────────────────┘

 ① REGISTRATION                ② PROFILE SETUP
 ─────────────                 ───────────────
 ┌──────────────┐              ┌──────────────────────┐
 │ Create Patient│             │ Add Insurance         │
 │ Form         │────────────→│ Add Emergency Contact │
 │ (Personal,   │             │ Upload Avatar         │
 │  Contact,    │             │ Medical History       │
 │  Address)    │             └──────────┬───────────┘
 └──────────────┘                        │
                                         ▼
 ③ APPOINTMENT BOOKING         ④ PRE-CONSULTATION
 ──────────────────            ─────────────────
 ┌──────────────────┐          ┌──────────────────┐
 │ Book Appointment │          │ Record Vitals     │
 │ (By Service or   │────────→│ • Temperature     │
 │  By Doctor)      │          │ • Blood Pressure  │
 │ Select Insurance │          │ • Heart Rate      │
 └──────────────────┘          │ • SpO2, BMI, etc  │
                               └────────┬─────────┘
                                        │
                                        ▼
 ⑤ CONSULTATION                ⑥ POST-VISIT
 ─────────────                 ──────────
 ┌──────────────────┐          ┌──────────────────────┐
 │ In-Person OR     │          │ Update Status         │
 │ Telehealth       │────────→│ (COMPLETED)           │
 │ • Clinical Notes │          │ Medical History Update│
 │ • TODO Tasks     │          │ Schedule Follow-up    │
 │ • Diagnosis      │          │ Invoice Generation    │
 └──────────────────┘          └──────────────────────┘
```

### Phase 1: Patient Registration

**Route:** `/admin/clinic/patients/create`  
**View:** `patients-create.vue`  
**Store Action:** `patientStore.createPatient()`

**Data Collected:**

| Section | Fields |
|---------|--------|
| **Personal** | First name, last name, middle name, other names, date of birth, gender, blood group, marital status, religion, occupation |
| **Contact** | Phone, other phones, email |
| **Address** | Address line 1 & 2, city, state, country, zip code |
| **Emergency (NOK)** | Contact name, phone, other phone, relationship (repeatable) |
| **Insurance** | Type → Company → Plan (cascading dropdowns), membership #, serial #, scheme, issue/expiry dates |
| **Profile** | Avatar image upload |
| **Medical** | Medical history notes, allergies, current medications |

**Validation Rules:**
- Required: first name, last name, DOB, gender, phone, address, state, city, country
- Conditional: insurance fields required based on selected type (NHIA/PRIVATE vs CASH&CARRY)
- Phone format validation via `vue-tel-input`

**Insurance Cascade:**
```
Select Insurance Type → GET /insurance/types/
        ↓
Select Company       → GET /insurance/companies/?type={typeId}
        ↓
Select Plan          → GET {company._links.plans}
```

### Phase 2: Patient Profile Management

**Route:** `/admin/clinic/patients/:id`  
**View:** `patient-view.vue`

The patient profile page provides a comprehensive view with:

- **Demographics tab** — Personal information, contact details
- **Insurance carousel** — All active insurance policies with Add/Edit modals
- **Vital signs grid** — Latest recorded vitals
- **Medical history** — Conditions, allergies, medications
- **Appointments list** — Filtered by date range with status badges

**Available Actions:**
- Edit patient details → `/admin/clinic/patients/:id/edit`
- Add/Edit insurance → `AddInsuranceModal` / `EditInsuranceModal`
- Edit next of kin → `EditNextOfKinModal`
- Book appointment → `SetAppointmentModal`
- View appointment history

### Phase 3: Appointment Booking

**Trigger:** "Set Appointment" button from patient profile or appointment list  
**Component:** `SetAppointmentModal.vue`

Two booking flows:

```
Flow A: Book by Service                 Flow B: Book by Doctor
─────────────────────                   ──────────────────────
Select Service                          Select Doctor
      ↓                                       ↓
Auto-populate available doctors         Auto-populate offered services
      ↓                                       ↓
Select Doctor from filtered list        Select Service from filtered list
      ↓                                       ↓
Select Date                             Select Date
      ↓                                       ↓
(Optional) Select Insurance             (Optional) Select Insurance
      ↓                                       ↓
Review Summary Card                     Review Summary Card
      ↓                                       ↓
POST /appointments/                     POST /appointments/
```

**Data Submitted:**
```json
{
  "patient_id": "uuid",
  "staff_id": "uuid",
  "service_id": "number",
  "start_date": "YYYY-MM-DD",
  "insurance_id": "number (optional)",
  "notes": "string (optional)"
}
```

### Phase 4: Vitals Recording (Pre-Consultation)

**Route:** `/admin/clinic/appointments/:id/vitals`  
**View:** `vitals-index.vue`

Typically performed by nursing staff before the doctor consultation begins.

**Vital Signs Captured:**

| Vital | Unit | Type |
|-------|------|------|
| Temperature | °C / °F | Number |
| Blood Pressure (Systolic) | mmHg | Number |
| Blood Pressure (Diastolic) | mmHg | Number |
| Heart Rate | bpm | Number |
| Respiratory Rate | breaths/min | Number |
| Oxygen Saturation (SpO2) | % | Number |
| Weight | kg / lbs | Number |
| Height | cm / in | Number |
| BMI | calculated | Number |
| Glucose Level | mg/dL | Number |
| Pain Level | 0–10 scale | Number |

### Phase 5: Consultation

The system supports two consultation modes:

#### In-Person Consultation

**Route:** `/admin/clinic/appointments/:id/consultation`  
**View:** `inperson-consultation.vue`

- Patient details sidebar
- Clinical notes editor (rich text)
- TODO task management (add, view, delete tasks)
- Diagnosis and treatment recording

#### Telehealth / Virtual Consultation

**Route:** `/admin/clinic/appointments/:id/telehealth`  
**View:** `online-consultation.vue`

- Same clinical features as in-person
- Virtual meeting integration capabilities
- Remote patient interaction support

### Phase 6: Post-Visit

After consultation completion:

1. **Status Update** → Appointment marked as `COMPLETED`
2. **Medical Record** → Clinical notes persisted to patient history
3. **Follow-up** → New appointment can be scheduled
4. **Billing** → Invoice generation (Finance module)

---

## 7. Appointment Workflow

### Status State Machine

```
                    ┌────────────┐
                    │  SCHEDULED │ ← Initial state on creation
                    └─────┬──────┘
                          │
              ┌───────────┼───────────┐
              │           │           │
              ▼           ▼           ▼
      ┌──────────┐ ┌────────────┐ ┌──────────────┐
      │ CANCELLED│ │IN-PROGRESS │ │ RESCHEDULED  │
      └──────────┘ └─────┬──────┘ └──────┬───────┘
                         │               │
                         │               │ (Creates new
                         │               │  SCHEDULED apt)
                         ▼               │
                  ┌────────────┐         │
                  │ COMPLETED  │         │
                  └────────────┘         │
                                         │
                  ┌────────────┐         │
                  │   MISSED   │ ←───────┘ (if not attended)
                  └────────────┘
```

### Status Definitions

| Status | Description | Available Actions |
|--------|-------------|-------------------|
| `SCHEDULED` | Newly booked appointment | Start, Reschedule, Cancel, Transfer Doctor |
| `IN-PROGRESS` | Consultation has begun | Record Vitals, Continue to Consultation, Complete, Cancel |
| `COMPLETED` | Visit finished | View Details, View Notes |
| `CANCELLED` | Appointment cancelled | View Details, Rebook |
| `RESCHEDULED` | Moved to new date/time | View Details |
| `MISSED` | Patient did not attend | View Details, Rebook |

### Visual Status Indicators

| Status | Badge Color | Icon Context |
|--------|-------------|-------------|
| SCHEDULED | 🔵 Blue | Calendar/clock |
| IN-PROGRESS | 🟡 Yellow/Orange | Active/spinning |
| COMPLETED | 🟢 Green | Checkmark |
| CANCELLED | 🔴 Red | X/cross |
| RESCHEDULED | ⚪ Gray | Calendar-arrow |
| MISSED | 🔴 Red (variant) | Clock-alert |

### Appointment Actions by Status

```
SCHEDULED:
  ├── [Start Appointment]  → PATCH status=IN-PROGRESS
  ├── [Reschedule]         → Open RescheduleModal
  ├── [Cancel]             → PATCH status=CANCELLED
  ├── [Transfer Doctor]    → Open ChangeDoctorModal
  └── [View Details]       → AppointmentDetailsCanvas

IN-PROGRESS:
  ├── [Record Vitals]      → Navigate to /appointments/:id/vitals
  ├── [Continue to...]     → Navigate to consultation or telehealth
  ├── [Complete]           → PATCH status=COMPLETED
  └── [Cancel]             → PATCH status=CANCELLED

COMPLETED:
  └── [View Details]       → AppointmentDetailsCanvas
```

### Reschedule Flow

```
AppointmentDetailsCanvas
    │ (click Reschedule)
    ▼
RescheduleModal
    ├── New Date (min: today)
    ├── New Service (optional change)
    ├── Priority update
    ├── Duration update
    └── Additional notes
    │
    ▼ (Save)
PATCH /appointments/{id}/
  Body: { status: 'RESCHEDULED', start_date, service_id, duration, priority, notes }
    │
    ▼
Refresh appointment list/calendar
```

### Doctor Transfer Flow

```
AppointmentDetailsCanvas
    │ (click Transfer Doctor)
    ▼
ChangeDoctorModal
    ├── Select new doctor
    └── Transfer reason
    │
    ▼ (Save)
PATCH /appointments/{id}/
  Body: { staff_id: newDoctorUuid }
    │
    ▼
Refresh appointment list
```

### Calendar View

**Route:** `/admin/clinic/appointments/calendar`  
**Component:** FullCalendar (Day Grid + Time Grid)

- Color-coded events by appointment status
- Click event → Opens `AppointmentDetailsCanvas` sidebar
- Supports reschedule, cancel, doctor transfer from canvas
- Date navigation (month/week/day views)

---

## 8. Consultation Workflow

### In-Person Consultation Flow

```
┌──────────────────────────────────────────────────────────┐
│                 CONSULTATION VIEW                         │
├───────────────────┬──────────────────────────────────────┤
│                   │                                      │
│  Patient Sidebar  │  Main Content Area                   │
│  ─────────────    │  ────────────────                    │
│  • Name           │  ┌────────────────────────────────┐  │
│  • Age/Gender     │  │ Clinical Notes (Rich Text)     │  │
│  • Blood Group    │  │ • Presenting Complaint         │  │
│  • Phone          │  │ • History of Illness           │  │
│  • OPD Number     │  │ • Examination Findings         │  │
│  • Allergies      │  │ • Diagnosis                    │  │
│  • Last Vitals    │  │ • Treatment Plan               │  │
│                   │  └────────────────────────────────┘  │
│                   │                                      │
│  Quick Actions    │  ┌────────────────────────────────┐  │
│  ─────────────    │  │ TODO Tasks                     │  │
│  • View Vitals    │  │ • Add Task (TodoAddModal)      │  │
│  • View History   │  │ • View Tasks                   │  │
│  • Add Notes      │  │ • Complete/Delete Tasks         │  │
│                   │  └────────────────────────────────┘  │
└───────────────────┴──────────────────────────────────────┘
```

### Telehealth Consultation

Same structure as in-person with additional:
- Virtual meeting room integration
- Mode flag: `TELEHEALTH` or `VIRTUAL`
- Remote-capable UI adaptations

### TODO Management (During Consultation)

Doctors can create task items during consultation:

| Component | Purpose |
|-----------|---------|
| `TodoAddModal` | Create new task with title, description, priority |
| `TodoViewModal` | View task details |
| `TodoDeleteModal` | Confirm task deletion |

---

## 9. Data Flow Patterns

### Pattern 1: Table Data Loading (DataTableStore Factory)

The `useTableStore()` factory creates reusable paginated data stores per endpoint:

```
Component Mount
    │
    ▼
useTableStore('/appointments/')
    │
    ▼
GET /appointments/?page=1&page_size=10&search=&...filters
    │
    ▼
Response: { results: [...], count: 50 }
    │
    ▼
Store: data=results, totalCount=count
    │
    ▼
Table renders with pagination controls
    │
    ▼ (page change)
handleTableChange({ current: 2, pageSize: 10 })
    │
    ▼
GET /appointments/?page=2&page_size=10&...
```

### Pattern 2: Detail View Loading

```
Route: /admin/clinic/patients/:id
    │
    ▼
onMounted → patientStore.fetchPatient(route.params.id)
    │
    ▼
GET /patients/{uuid}/
    │
    ▼
Store: patient = response.data
    │
    ▼
Template renders patient profile
    │
    ▼ (sub-resource fetch)
GET /patients/{uuid}/insurances/
GET /patients/{uuid}/appointments/
```

### Pattern 3: Modal CRUD Operation

```
Parent Component (Table/Detail View)
    │
    ├── Opens Modal (passes props: selectedItem, visible)
    │
    ▼
Modal Component
    ├── Form with validation
    ├── Submit → POST/PUT/PATCH to API
    ├── Success → emit('item-created', data) or emit('save', data)
    └── Parent handler: refetch table data, show toast, close modal
```

### Pattern 4: Offcanvas Details (Appointment Canvas)

```
Table Row Click
    │
    ▼
Parent sets selectedAppointment + shows canvas
    │
    ▼
AppointmentDetailsCanvas (offcanvas-end)
    ├── Displays: patient info, service, doctor, status, dates
    ├── Status-aware action buttons
    └── Emits: 'reschedule', 'cancel', 'transfer-doctor', 'start', 'complete'
    │
    ▼
Parent handles emitted events → calls appropriate modal or API action
```

---

## 10. State Management

### Store Architecture

```
Pinia Stores
├── authStore (Persisted)
│   ├── State: user, token, refreshToken, isAuthenticated
│   ├── Getters: isSuperAdmin, isAdmin, isConsultation, isInvestigation, isNursing
│   └── Actions: login, logout, refreshAccessToken, forgotPassword, handleRouteChange
│
├── patientStore
│   ├── State: patient (single), loading, error
│   └── Actions: fetchPatient, createPatient, updatePatient, clearPatient
│
├── appointmentStore
│   ├── State: appointments (list), selectedAppointment, loading, error
│   └── Actions: fetchAppointments, fetchAppointment, updateAppointment,
│                updateAppointmentStatus, updateAppointmentMode, rescheduleAppointment
│
├── staffStore
│   ├── State: staff (single), loading, error
│   └── Actions: fetchStaff
│
└── dataTableStore (Factory Pattern)
    ├── Created per endpoint: useTableStore('/patients/'), useTableStore('/appointments/')
    ├── State: data[], detailedItem, loading, currentPage, perPage, totalCount, searchQuery, filters
    └── Actions: fetchData, fetchItemDetails, fetchItem, handleTableChange
```

### Persistence Strategy

- **authStore:** Persisted to `localStorage` via `pinia-plugin-persistedstate`
- **Other stores:** In-memory only (reset on page reload)
- **Token storage:** JWT access + refresh tokens in localStorage under `authStore` key

---

## 11. API Contract Summary

### Authentication Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `POST` | `/auth/token/` | Login (returns access + refresh JWT) |
| `POST` | `/auth/token/refresh` | Refresh access token |
| `DELETE` | `/auth/token/blacklist/` | Logout (invalidate refresh token) |
| `POST` | `/auth/password-reset-token` | Request password reset email |
| `PATCH` | `/auth/token/verification/` | Verify password reset token |
| `PATCH` | `/auth/{uuid}/password` | Set new password |

### Patient Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `GET` | `/patients/` | List patients (paginated, searchable) |
| `POST` | `/patients/` | Create new patient |
| `GET` | `/patients/{id}/` | Get patient details |
| `PUT` | `/patients/{id}/` | Update patient |
| `GET` | `/patients/{id}/insurances/` | List patient's insurance policies |
| `POST` | `/patients/{id}/insurances/` | Add insurance to patient |
| `PUT` | `/patients/{id}/insurances/{id}/` | Update insurance policy |

### Appointment Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `GET` | `/appointments/` | List appointments (paginated, filterable) |
| `POST` | `/appointments/` | Create new appointment |
| `GET` | `/appointments/{id}/` | Get appointment details |
| `PATCH` | `/appointments/{id}/` | Update appointment (status, date, service, doctor) |

### Supporting Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `GET` | `/services/` | List available services |
| `GET` | `/staff/` | List staff/doctors |
| `GET` | `/staff/{id}/` | Get staff details |
| `GET` | `/insurance/types/` | List insurance types |
| `GET` | `/insurance/companies/?type={id}` | List companies by insurance type |

### Pagination Contract

**Request:** `?page=1&page_size=10&search=term&filterKey=value`  
**Response:**
```json
{
  "results": [...],
  "count": 150,
  "next": "http://api/endpoint/?page=2",
  "previous": null
}
```

---

## 12. Navigation & Routing Architecture

### Sidebar Navigation Structure

```
📊 Main
│   └── Dashboard
│       ├── Admin Dashboard
│       ├── Consultation Dashboard
│       ├── Investigation Dashboard
│       ├── Treatment Dashboard
│       ├── Procedure Dashboard
│       ├── Medication Dashboard
│       ├── Emergency Services Dashboard
│       ├── Nursing Care Dashboard
│       ├── Support Services Dashboard
│       └── Patient Dashboard
│
🏥 Clinic
│   ├── Patients
│   │   ├── Patient List
│   │   └── Create Patient
│   ├── Appointments
│   │   ├── Appointment List
│   │   └── Calendar
│   ├── Services
│   ├── Specializations
│   └── Locations
│
👥 HRM
│   ├── Staffs (List + Create)
│   ├── Departments
│   ├── Designation
│   ├── Attendance
│   ├── Leaves (List + Leave Type)
│   ├── Holidays
│   └── Payroll
│
💰 Finance & Accounts
│   ├── Expenses (List + Category)
│   ├── Income
│   ├── Invoices (List + Details)
│   ├── Payments
│   ├── Transactions
│   └── Assets
│
🔐 Administration
│   ├── Users (Roles & Permissions, Delete Requests)
│   └── Reports (Income, Expense, P&L, Appointment, Patient)
│
📝 Content
│   ├── Pages
│   ├── Blogs (Add, List, Details)
│   └── FAQs
```

### Route Guard Pipeline

```
Navigation Request
    │
    ▼
┌─────────────────────────────┐
│ 1. Is route Super Admin     │──→ No super admin? → /unauthorized
│    only? (meta.isSuperAdmin)│
└─────────────┬───────────────┘
              │ pass
              ▼
┌─────────────────────────────┐
│ 2. Is route Admin only?     │──→ Not admin or super admin? → /unauthorized
│    (meta.isAdmin)           │
└─────────────┬───────────────┘
              │ pass
              ▼
┌─────────────────────────────┐
│ 3. Requires auth?           │──→ Not logged in? → /login (save returnUrl)
│    (meta.requiresAuth)      │
└─────────────┬───────────────┘
              │ pass
              ▼
┌─────────────────────────────┐
│ 4. Department check?        │──→ Wrong dept? → Redirect to own dashboard
│    (meta.isConsultation/    │
│     isInvestigation/        │
│     isNursing)              │
└─────────────┬───────────────┘
              │ pass
              ▼
┌─────────────────────────────┐
│ 5. Guest only?              │──→ Already logged in? → Redirect to dashboard
│    (meta.guestOnly)         │
└─────────────┬───────────────┘
              │ pass
              ▼
         Allow access ✅
```

---

## 13. Component Architecture

### Global Components (Registered in main.js)

| Component | Registration | Purpose |
|-----------|-------------|---------|
| `layouts-header` | Global | Top navigation bar |
| `layouts-sidebar` | Global | Main sidebar navigation |
| `patients-header` | Global | Patient portal header |
| `patients-sidebar` | Global | Patient portal sidebar |
| `doctor-header` | Global | Doctor portal header |
| `doctor-sidebar` | Global | Doctor portal sidebar |
| `sidebar-menu` | Global | Sidebar menu renderer |
| `filter-index` | Global | Reusable filter wrapper |
| `DataTablePagination` | Global | Pagination controls |
| `theme-settings` | Global | Theme customization panel |

### Reusable Common Components

| Component | Purpose |
|-----------|---------|
| `AppointmentDetailsCanvas` | Offcanvas sidebar showing appointment details with status-aware actions |
| `AppointmentFilter` | Filter dropdowns for appointment lists (doctor, service, status, mode) |
| `PatientsFilter` | Filter controls for patient lists |
| `DateRangePicker` | Reusable date range selection |
| `DataTablePagination` | Ant Design table pagination wrapper |

### Modal Components (15+)

**Appointment Modals:**
| Modal | Purpose |
|-------|---------|
| `SetAppointmentModal` | Book new appointment (by service or doctor) |
| `RescheduleModal` | Reschedule existing appointment |
| `ChangeDoctorModal` | Transfer appointment to different doctor |
| `CalendarModal` | Calendar event creation |
| `TodoAddModal` | Add consultation task |
| `TodoViewModal` | View consultation task details |
| `TodoDeleteModal` | Confirm task deletion |

**Patient Modals:**
| Modal | Purpose |
|-------|---------|
| `AddInsuranceModal` | Add insurance policy to patient |
| `EditInsuranceModal` | Edit existing insurance policy |
| `EditNextOfKinModal` | Edit emergency contact |
| `PatientDetailsModal` | Quick view patient details |

**Service Modals:**
| Modal | Purpose |
|-------|---------|
| `AddServiceModal` | Create new service |
| `EditServiceModal` | Edit existing service |
| `ViewServiceModal` | View service details |

### Composables

| Composable | Purpose |
|------------|---------|
| `usePagination` | Generic pagination logic (current page, page size, total, computed page slicing) |

---

## 14. Current Status & Maturity Assessment

### Implementation Status

| Feature | Status | Notes |
|---------|--------|-------|
| Authentication (Login/Logout) | ✅ Complete | JWT with auto-refresh |
| Password Reset | ✅ Complete | Email token flow |
| Patient CRUD | ✅ Complete | Create, Read, Update |
| Patient Insurance Management | ✅ Complete | Cascading dropdowns, CRUD |
| Appointment CRUD | ✅ Complete | Book, view, update, cancel |
| Appointment Calendar | ✅ Complete | FullCalendar integration |
| Appointment Status Flow | ✅ Complete | Full state machine |
| Vitals Recording | ✅ Complete | Comprehensive vital signs |
| In-Person Consultation | ✅ Complete | Notes, TODO tasks |
| Telehealth Consultation | ⚠️ Partial | UI present, meeting integration TBD |
| Staff Management | ✅ Complete | List, create, view |
| Department Management | ✅ Complete | CRUD |
| Role-Based Access | ✅ Complete | Multi-level RBAC |
| Multi-language (i18n) | ✅ Complete | English + French |
| Finance Module | ⚠️ Template | Views exist, integration varies |
| Reporting | ⚠️ Template | Route structure defined |
| Patient Delete | ❌ Not implemented | No delete action in store |
| Appointment Delete | ❌ Not implemented | No delete action |
| Doctor Dashboard | ⚠️ Template | Views exist, data TBD |
| Patient Portal | ⚠️ Template | Views exist, data TBD |

### Architecture Strengths

1. **Clean separation of concerns** — Types, stores, components, and views are well-organized
2. **Factory pattern for data tables** — `useTableStore()` eliminates boilerplate for paginated lists
3. **Comprehensive type definitions** — Patient, Appointment, Staff types cover real clinical data
4. **Robust auth flow** — JWT with silent refresh, token blacklisting, RBAC
5. **Modal-driven UX** — Consistent pattern for all CRUD operations
6. **i18n-ready** — All UI text uses translation functions
7. **Persistent auth state** — Survives page reloads via localStorage

### Areas for Improvement

1. **No patient deletion** — Store lacks `deletePatient()` action
2. **Debug logging in production** — `authGuard.js` contains `console.log` statements (controlled by env var but present in guard)
3. **Mixed JavaScript/TypeScript** — Router and some views still in `.js` (gradual migration noted in style guide)
4. **Client-side pagination in some views** — Some components paginate client-side while others use server-side
5. **No WebSocket/real-time** — No live updates for appointment status changes
6. **No offline support** — No service worker or offline data caching
7. **Token in localStorage** — Vulnerable to XSS; `httpOnly` cookies would be more secure for production

---

*This analysis was generated from the UHMS template source code as of April 2026.*
