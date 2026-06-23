# UHMS — Ultimate Hospital Management System
## Full Project Analysis Report

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Project Overview](#2-project-overview)
3. [Technology Stack](#3-technology-stack)
4. [Architecture & Project Structure](#4-architecture--project-structure)
5. [Database Design](#5-database-design)
6. [Module Breakdown](#6-module-breakdown)
   - 6.1 [Authentication & Login](#61-authentication--login)
   - 6.2 [MDI Main Dashboard](#62-mdi-main-dashboard)
   - 6.3 [Records (Patient Registration)](#63-records-patient-registration)
   - 6.4 [Ward / Nursing Station](#64-ward--nursing-station)
   - 6.5 [Doctor's Consultation](#65-doctors-consultation)
   - 6.6 [Investigations (Lab, X-Ray, Scan)](#66-investigations-lab-x-ray-scan)
   - 6.7 [Pharmacy](#67-pharmacy)
   - 6.8 [Store & Inventory](#68-store--inventory)
   - 6.9 [Accounts & Cashier](#69-accounts--cashier)
   - 6.10 [Claims (NHIS & Private Insurance)](#610-claims-nhis--private-insurance)
   - 6.11 [HR Management](#611-hr-management)
   - 6.12 [Administration](#612-administration)
   - 6.13 [Analyzer Integration Module](#613-analyzer-integration-module)
   - 6.14 [Reports](#614-reports)
7. [Complete Patient Workflow](#7-complete-patient-workflow)
8. [Role-Based Access Control](#8-role-based-access-control)
9. [Notification System](#9-notification-system)
10. [Analyzer Integration — Deep Dive](#10-analyzer-integration--deep-dive)
11. [Third-Party Libraries](#11-third-party-libraries)
12. [Configuration & Settings](#12-configuration--settings)
13. [Known Technical Observations](#13-known-technical-observations)
14. [User Manual](#14-user-manual)

---

## 1. Executive Summary

**UHMS (Ultimate Hospital Management System)** is a desktop Windows Forms application built in **VB.NET** targeting **.NET Framework 4.6**. It is a comprehensive hospital management platform developed by **Click Software GH** that covers the full lifecycle of hospital operations — from patient registration, through clinical consultations, investigations, pharmacy dispensing, billing, insurance claims, to financial accounting and HR management.

The system is designed for deployment within a local hospital network, with a **MySQL** database backend. It features role-based access control for over 15 department types, a notification system for inter-department communication, and a sophisticated **Laboratory Analyzer Integration Module** supporting HL7 v2.x and ASTM protocols for automated lab result processing.

**Current Version:** 5.2.0 (Assembly Version 4.1.0.1)

---

## 2. Project Overview

| Property | Value |
|---|---|
| **Project Name** | UHMS (Ultimate Hospital Management System) |
| **Project Type** | Windows Forms Desktop Application (WinExe) |
| **Language** | Visual Basic .NET (VB.NET) |
| **Framework** | .NET Framework 4.6 |
| **Platform Target** | x86 |
| **Database** | MySQL (via MySql.Data connector) |
| **IDE** | Visual Studio 2012+ |
| **Publisher** | Click Software GH |
| **Copyright** | © Click Software GH 2019 |

---

## 3. Technology Stack

| Layer | Technology |
|---|---|
| **Frontend** | Windows Forms (WinForms) |
| **UI Components** | Bunifu UI v1.52, Guna.UI2, DevComponents DotNetBar2 |
| **Language** | VB.NET |
| **Runtime** | .NET Framework 4.6 |
| **Database** | MySQL |
| **ORM / Data Access** | Raw ADO.NET via `MySql.Data.MySqlClient` + custom `DbHelper` module |
| **JSON Handling** | Newtonsoft.Json 13.0.1 |
| **HTTP Client** | RestSharp |
| **Spell Checking** | NetSpell.SpellChecker |
| **Document Generation** | FreeSpire.Office 8.2.0 (Spire.Doc, Spire.Pdf, Spire.Barcode) |
| **Printing** | RichTextBoxPrintCtrl, System.Drawing.Printing |
| **Charting** | System.Windows.Forms.DataVisualization.Charting |
| **Serial Communication** | System.IO.Ports |
| **Network Communication** | System.Net.Sockets (TCP/IP) |
| **ICD-10 Coding** | Custom JSON-based ICD-10 lookup via Newtonsoft.Json |

---

## 4. Architecture & Project Structure

The application follows a **form-centric architecture** organized by hospital department. Each department has its own folder of Windows Forms, and a shared `DbHelper` module provides parameterized database access.

```
UHMS/
├── Forms/
│   ├── Admin/               # Login, Users, Services, Hospital Info, Patient Folders
│   │   ├── frmLogin.vb          # Authentication entry point
│   │   ├── frmUsers.vb          # User management (CRUD)
│   │   ├── frmServices.vb       # Hospital services/pricing
│   │   ├── frmHostpInfo.vb      # Hospital information
│   │   └── frmPatientsFolder.vb # Patient folder/history viewer
│   │
│   ├── Config/               # Application configuration & shell
│   │   ├── SplashScreen1.vb     # Splash screen on startup
│   │   ├── frmWelcome.vb        # Loading/transition screen
│   │   ├── frmMDI.vb            # Main MDI container (navigation hub)
│   │   └── frmSettings.vb       # Database/printer/path settings
│   │
│   ├── Records/              # Patient registration & records
│   │   ├── frmRecords.vb        # Patient search & registration
│   │   ├── frmRecordsView.vb    # Attendance/records view
│   │   ├── frmStatement.vb      # Patient statement generation
│   │   └── Form1.vb             # Auxiliary records form
│   │
│   ├── Ward/                 # Nursing station & ward management
│   │   ├── frmWard.vb           # Vitals, admission, bed management
│   │   └── frmTheater.vb        # Theater/procedure scheduling
│   │
│   ├── DoctorsNote/          # Clinical consultation
│   │   ├── Form2.vb             # Main consultation form (complaints, diagnosis, prescriptions)
│   │   ├── frmDoctor.vb         # Doctor queue/consultation list
│   │   ├── frmDoctorP.vb        # Doctor prescription pad
│   │   └── frmPhysio.vb         # Physiotherapy consultation
│   │
│   ├── Investigations/       # Lab, X-Ray, Scan departments
│   │   ├── frmLab.vb            # Generic investigation ordering (Lab/Scan/X-Ray/etc.)
│   │   ├── frmDepServices.vb    # Department-specific services
│   │   ├── frmDepResults.vb     # Department result entry
│   │   ├── frmDepView.vb        # Detailed investigation records
│   │   ├── frmDepViewSum.vb     # Summarized investigation records
│   │   ├── frmXray.vb           # Legacy X-Ray form
│   │   └── frmScan.vb           # Legacy Scan form
│   │
│   ├── Pharmacy/             # Pharmacy dispensing
│   │   ├── frmPharm.vb          # Prescription dispensing
│   │   ├── frmPharmView.vb      # Detailed pharmacy sales
│   │   ├── frmPharmViewSum.vb   # Summary pharmacy sales
│   │   └── frmPharmSupply.vb    # Pharmacy supply management
│   │
│   ├── Store/                # Inventory & stock management
│   │   ├── frmDrudInfo.vb       # Medicine/drug catalog
│   │   ├── frmPurchase.vb       # Purchase order/receiving
│   │   ├── frmTransfer.vb       # Stock transfer (store → pharmacy)
│   │   └── frmTransferView.vb   # Transfer records view
│   │
│   ├── Accounts/             # Financial management
│   │   ├── frmCashier.vb        # Billing & payment collection
│   │   ├── frmCashierView.vb    # Daily collection report
│   │   ├── frmCashierHand.vb    # Cashier handover
│   │   ├── frmExpenses.vb       # Expense tracking (petty cash)
│   │   ├── frmIncome.vb         # Income tracking
│   │   └── frmAccountView.vb    # Accounting reconciliation
│   │
│   ├── Claims/               # Insurance claims
│   │   ├── frmClaims.vb         # NHIS claims processing
│   │   ├── frmClaimView.vb      # Claims review
│   │   ├── frmPrivateClaimView.vb # Private insurance claims
│   │   └── Form3.vb             # Claims auxiliary form
│   │
│   └── HR/                   # Human Resources
│       ├── frmEmployees.vb      # Employee management (CRUD)
│       ├── frmAttendance.vb     # Staff attendance tracking
│       ├── frmLeave.vb          # Leave request management
│       └── frmPayroll.vb        # Payroll processing
│
├── Helpers/
│   └── DbHelper.vb           # Centralized database helper (parameterized queries)
│
├── Modules/
│   └── AnalyzerIntegration/   # Laboratory analyzer integration
│       ├── ModuleInitializer.vb
│       ├── Models/            # Data models (Analyzer, NormalizedResult, etc.)
│       ├── Protocols/         # Protocol adapters (HL7v2, ASTM)
│       ├── Listeners/         # Connection listeners (TCP, Serial)
│       ├── Services/          # Business logic (AnalyzerService, MessageFlow, ResultDispatch)
│       ├── Mappers/           # Test code mapping engine
│       ├── Validators/        # Result validation rules engine
│       ├── UI/                # Management & diagnostics forms
│       └── Tests/             # Protocol simulator
│
├── Icd10.vb                   # ICD-10 code lookup models
├── ApplicationEvents.vb       # Application lifecycle events
├── frmIpatients.vb            # Inpatient management
├── Form5.vb                   # Auxiliary form
└── My Project/                # Assembly info, settings, resources
```

### Key Architectural Patterns

1. **MDI Container Pattern** — `frmMDI` is the main shell; all department forms are loaded into a central panel (`pnlMDI`) via `OpenFormInPanel()`.
2. **Form-per-Feature** — Each hospital function has a dedicated WinForm.
3. **Shared DB Helper** — `DbHelper` module provides `ExecuteQuery`, `ExecuteNonQuery`, and `ExecuteScalar` with parameterized queries.
4. **Event-Driven Buttons** — Invisible "RELOAD" buttons trigger data refresh; hidden textboxes pass state between forms.
5. **Module-Based Integration** — The Analyzer Integration is a well-structured module with interfaces, services, and adapters.

---

## 5. Database Design

The system uses a **MySQL** database. Based on the SQL queries throughout the code, the following tables are identified:

### Core Tables

| Table | Purpose |
|---|---|
| `patients` | Patient demographics (PAT_ID, PatientName, OpdNo, PhoneNo, DOB, NOK, etc.) |
| `attendance` | Patient visits/encounters (ATT_ID, PAT_ID, AttDate, AttStatus, Status, DEP_ID, EmergStatus, ConsultationText) |
| `billing` | Billing records per attendance (ATT_ID, Status, DEP_ID) |
| `users` | System users (USER_ID, DEP_ID, FullName, UserName, Password, CanAdd/Edit/Delete/Print, Status) |
| `departements` | Hospital departments (DEP_ID, Departement, Billable) |
| `hospitalinfo` | Hospital metadata (CompanyName, LegalName, Address, PhoneNo, Email, Admn, PharmStore) |
| `services` | Hospital services catalog (SERV_ID, Service, Departement, Amount, Nhis, Private, Corp) |

### Clinical Tables

| Table | Purpose |
|---|---|
| `consult_prescriptions` | Prescriptions (PRES_ID, ATT_ID, MED_ID, Qty, Cash, Nhis, Private, Status, BillingStatus, PresDate, description) |
| `consult_diagnosis` | Diagnoses per consultation (ATT_ID, MdfId) |
| `consult_procedures` | Procedures per consultation (ATT_ID, BillingStatus) |
| `list_diagnosis` | Master diagnosis list (LDIAG_ID, Diagnosis, MdfId) |
| `list_procedures` | Master procedure list |
| `appointement` | Patient appointments (ATT_ID, AppDate, Departement) |

### Pharmacy & Store Tables

| Table | Purpose |
|---|---|
| `medicine` | Medicine catalog (MED_ID, MedCode, MedName, Category, Serve, MedType, DEP_ID) |
| `med_pharm` | Pharmacy stock (MED_ID, Qty, Cash, Nhis, Private, Treshold) |
| `med_store` | Store stock (MED_ID, Qty, Buy) |
| `batch` | Purchase batches (BatchNo, BatchStatus) |

### Accounts Tables

| Table | Purpose |
|---|---|
| `accounts` | General ledger entries (ACC_ID, IncDate, ReceiptNo, TITLE_ID, Details, Medium, Amount, ExpInc) |
| `acc_titles` | Account titles (TI_ID, Title) |
| `acc_petty` | Petty cash management (PET_ID, Amount, ReSet) |
| `bank` | Bank accounts (BANK_ACC_ID, AccName) |
| `beds` | Bed management (BED_ID, PAT_ID, BedName, BedDate, BedStatus) |
| `notifications` | Inter-department notifications (NOTID, ReqTo, ReqTitle, ReqDesc, ReqStatus) |

### HR Tables

| Table | Purpose |
|---|---|
| `hr_employees` | Employee records (EMP_ID, EmpNo, FullName, Gender, DOB, Phone, DEP_ID, Position, Salary, Status, etc.) |
| `hr_leave` | Leave requests (LEAVE_ID, EMP_ID, LeaveType, StartDate, EndDate, Days, Status) |
| `hr_payroll` | Payroll records (PAY_ID, EMP_ID, PayPeriod, BasicSalary, Allowances, Deductions, Tax, SSNIT_Deduction, NetPay) |

### Analyzer Integration Tables

| Table | Purpose |
|---|---|
| `analyzer_analyzers` | Registered analyzer devices (AnalyzerId, Name, Protocol, ConnectionType, IpAddress, Port, ComPort, BaudRate, IsActive) |
| `analyzer_protocols` | Protocol configurations (IsEnabled) |
| `analyzer_raw_messages` | Raw message audit log (RawMessageId, AnalyzerId, ProtocolId, Content, ReceivedAt, ProcessingStatus, ProcessingAttempts) |
| `analyzer_test_code_mappings` | Analyzer-to-UHMS test code mappings |
| `lab_orders` / `lab_order_details` | Lab orders for result matching (LabOrderId, SampleId, TestCode, ResultSource) |

### Settings Tables

| Table | Purpose |
|---|---|
| `sett_ocuupation` | Occupation lookup (OQP_ID, Occupation) |
| `sett_private` | Private insurance providers (PINS_ID, PrivateName, PrivateShort) |

---

## 6. Module Breakdown

### 6.1 Authentication & Login

**Form:** `frmLogin`

- User enters username and password.
- Credentials are validated against the `users` table joined with `departements`.
- If the user's `Status` = `"ACTIVE"`, the system proceeds to the welcome/loading screen.
- Disabled accounts display an error message.
- Hospital name is loaded from `hospitalinfo` on startup.
- The application version is displayed as `5.2.0`.

### 6.2 MDI Main Dashboard

**Form:** `frmMDI`

This is the central navigation hub of the entire application:

- **Menu Bar** — Contains top-level menus for every department: Records, Ward, Doctor, Lab, Scan, X-Ray, Pharmacy, Store, Accounts, Claims, HR, Reports, Information, Administration, Physio.
- **OpenFormInPanel()** — All child forms are rendered inside a central `pnlMDI` panel (not as separate windows).
- **Dashboard Widgets** — Displays total patients, admitted patients, and today's consultation count.
- **Clock & Heartbeat Animation** — Real-time clock display with an animated heart icon.
- **Notification System** — Background worker polls `notifications` table; desktop alerts appear via `DesktopAlert`.
- **Appointment Grid** — Shows upcoming appointments filtered by date.
- **Role-based menu activation** — Menus are enabled/disabled based on the logged-in user's department.
- **Analyzer Module Initialization** — On load, initializes the Analyzer Integration Module.

### 6.3 Records (Patient Registration)

**Form:** `frmRecords`

- **Patient Search** — Search by name, OPD number, phone number, or next-of-kin details.
- **New Patient Registration** — Captures demographics: name, DOB, gender, blood group, occupation, phone, address, insurance type, OPD number.
- **Folder Types** — Patients can be registered under folder types (e.g., NEW FOLDER).
- **Age Calculation** — Automatically calculates age in Days (D), Months (M), or Years (Y) format from DOB.
- **Insurance Linking** — Links patients to NHIS or private insurance providers.
- **Patient List** — ListBox showing all patients with click-to-select functionality.

### 6.4 Ward / Nursing Station

**Form:** `frmWard`

Operates in three modes controlled by `TextBox11.Text`:

1. **PATIENTS AWAITING VITALS** — Shows outpatients who have paid and are waiting for vital signs. Nurses record vitals here.
2. **PATIENTS ADMISSION** — Lists today's inpatient admissions. Staff can assign beds and admit patients.
3. **PATIENTS ADMITED** — Shows all currently admitted inpatients. Supports vitals recording, bed changes, complaints, and discharge initiation.

**Key Features:**
- Bed management (occupied/available beds grid).
- Barcode-based patient identification (auto-barcode toggle).
- Discharge workflow (changes status to `INPATIENT-DISCH`).
- Chart visualization (System.Windows.Forms.DataVisualization.Charting).

### 6.5 Doctor's Consultation

**Forms:** `Form2` (main consultation), `frmDoctor` (doctor queue), `frmDoctorP` (prescription pad)

The consultation form (`Form2`) is the most complex form in the system, with tabbed sections:

1. **Presenting Complaints** — Free-text entry of patient complaints.
2. **History of Presenting Complaints** — Detailed medical history.
3. **Diagnosis** — ICD-10 code lookup and selection (via JSON API using Newtonsoft.Json & RestSharp).
4. **Investigation** — Order lab tests, scans, X-rays, etc.
5. **Investigation Results** — View returned results from labs.
6. **Procedure** — Schedule and document procedures.
7. **Treatment/Prescriptions** — Prescribe medications.

**Key Features:**
- Supports both theater and physio consultation modes (controlled by `cmbProType`).
- Drawing pad for clinical diagrams (pen-on-bitmap).
- Consultation notes saved as RTF text to `ConsultationText` in the `attendance` table.
- Multiple file storage paths for different note categories (consult, scanlab, history, examine, treatment).
- Print-ready consultation notes with custom font styling.

### 6.6 Investigations (Lab, X-Ray, Scan)

**Form:** `frmLab` (generic, reused for all investigation departments)

A single form serves multiple investigation departments. The department is set via `TextBox4.Text`:
- **LAB**, **X-RAY**, **SCAN**, **THEATER**, **WARD**, **EYE**, **DENTAL**, **PHYSIO**, **ENT**, **CT-SCAN**

**Key Features:**
- Patient list (outpatient by date or inpatient by admission status).
- Service ordering from department-specific service catalog.
- Multi-tier pricing (Cash, NHIS, Private).
- Inpatient/outpatient toggle.
- Background worker for asynchronous patient list loading.

**Result Entry:** `frmDepResults` — Enter results for ordered investigations per department.

**Reporting:** `frmDepView` (detailed) and `frmDepViewSum` (summarized) for investigation records.

### 6.7 Pharmacy

**Form:** `frmPharm`

- Displays prescriptions ordered by doctors for each patient attendance.
- Shows drug stock levels alongside prescription details.
- Multi-tier pricing visible: Cash, NHIS, Private.
- Dispensing workflow updates `consult_prescriptions` status from `REQUESTED` to dispensed.
- Stock is checked against `med_pharm.Qty` before dispensing.

**Reporting:** `frmPharmView` (detailed sales), `frmPharmViewSum` (summary sales).

### 6.8 Store & Inventory

**Forms:** `frmDrudInfo` (catalog), `frmPurchase` (purchasing), `frmTransfer` (transfers)

- **Drug/Medicine Catalog** (`frmDrudInfo`) — Full CRUD for medicines. Shows both store and pharmacy stock quantities, buy/sell prices across three tiers. Configurable store-pharmacy split via `PharmStore` setting.
- **Purchasing** (`frmPurchase`) — Batch-based procurement. Temp batches are held until confirmed.
- **Stock Transfer** (`frmTransfer`) — Transfer stock from store to pharmacy or between departments. Generates transfer batch numbers. Department selection for destination.

### 6.9 Accounts & Cashier

**Forms:** `frmCashier` (billing), `frmCashierView` (daily report), `frmExpenses`, `frmIncome`, `frmAccountView`

- **Cashier/Billing** (`frmCashier`) — Main billing point. Shows outpatient queue (today) and inpatient discharge queue. Processes payments. Authorization-controlled (edit/delete privileges).
- **Daily Collection** (`frmCashierView`) — Daily cash collection report.
- **Expenses** (`frmExpenses`) — Track petty cash and expenses by category, receipt number, and medium.
- **Income** (`frmIncome`) — Record income entries.
- **Account View** (`frmAccountView`) — Financial reconciliation dashboard.

### 6.10 Claims (NHIS & Private Insurance)

**Forms:** `frmClaims` (NHIS), `frmClaimView`, `frmPrivateClaimView`

- NHIS claims submission with doctor assignment.
- Claims processing workflow (claim date ranges, status tracking).
- Private insurance claims handled separately.
- Linked to patient attendance records and billing data.
- Claims loaded by patient, with associated attendance visits.

### 6.11 HR Management

**Forms:** `frmEmployees`, `frmAttendance`, `frmLeave`, `frmPayroll`

- **Employee Management** — Full CRUD with fields for: employee number, name, gender, DOB, phone, email, address, department, position, hire date, salary, emergency contacts, bank details, SSNIT, TIN.
- **Attendance** — Staff clock-in/clock-out tracking.
- **Leave Management** — Support for: Annual, Sick, Maternity, Paternity, Compassionate, Study, and Unpaid leave types. Auto-calculates working days.
- **Payroll** — Monthly payroll processing with: basic salary, allowances, deductions, tax, SSNIT deductions, and net pay calculation. Pay period selection for current and previous year.

### 6.12 Administration

**Forms:** `frmUsers`, `frmServices`, `frmHostpInfo`, `frmSettings`

- **User Management** — Create/edit system users with department assignment and granular permissions (CanAdd, CanEdit, CanDelete, CanPrint). User status (ACTIVE/INACTIVE).
- **Services Management** — Define hospital services with multi-tier pricing (Cash, NHIS, Private) and department assignment. Corporate flag.
- **Hospital Info** — Configure hospital name, legal name, address, contact details.
- **Settings** — Database connection string, printer name, data folder path. Database connection test. Access to Analyzer Management and Diagnostics.

### 6.13 Analyzer Integration Module

See [Section 10](#10-analyzer-integration--deep-dive) for a detailed deep dive.

### 6.14 Reports

The Reports menu provides access to:
- Attendance records (`frmRecordsView`)
- Pharmacy sales (detailed and summarized)
- Investigation records (detailed and summarized per department)
- Daily collection reports
- Income and expense reports
- Patient folders/history
- Consultation statistics
- Patient statements (DIMS)

---

## 7. Complete Patient Workflow

The following diagram illustrates the end-to-end patient journey through UHMS:

```
┌─────────────┐
│  PATIENT     │
│  ARRIVES     │
└──────┬──────┘
       │
       ▼
┌─────────────────────────────────────────────────┐
│  1. RECORDS DEPARTMENT (frmRecords)             │
│  • Search existing patient OR register new      │
│  • Assign OPD number, capture demographics      │
│  • Create attendance record (AttStatus =        │
│    'OUTPATIENT', Status = 'VITALS')             │
│  • Patient pays consultation fee at CASHIER     │
└──────────────────────┬──────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────┐
│  2. CASHIER (frmCashier)                        │
│  • Patient pays registration/consultation fee   │
│  • Billing record created (Status = 'PAID')     │
│  • Notification sent to next department         │
└──────────────────────┬──────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────┐
│  3. NURSING STATION / WARD (frmWard)            │
│  Mode: "PATIENTS AWAITING VITALS"               │
│  • Nurse records vital signs (BP, Temp, etc.)   │
│  • Barcode scanning for patient ID              │
│  • Status updated to 'DOCTOR'                   │
│  • Notification sent to Doctor                  │
└──────────────────────┬──────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────┐
│  4. DOCTOR CONSULTATION (Form2 / frmDoctor)     │
│  • Doctor selects patient from queue            │
│  • Records: Presenting complaints               │
│  •          History of complaints               │
│  •          Diagnosis (ICD-10 lookup)           │
│  •          Orders investigations               │
│  •          Prescribes medications              │
│  •          Schedules procedures                │
│  • Saves consultation note (RTF)                │
│  • Notifications sent to relevant departments   │
└──────┬──────────────┬───────────────┬───────────┘
       │              │               │
       ▼              ▼               ▼
┌────────────┐ ┌────────────┐ ┌─────────────────┐
│ 5a. LAB /  │ │ 5b. PHARM  │ │ 5c. THEATER     │
│ SCAN /     │ │ (frmPharm) │ │ (frmTheater)    │
│ X-RAY      │ │            │ │                 │
│ (frmLab)   │ │ • Dispense │ │ • Schedule      │
│            │ │   drugs    │ │   procedure     │
│ • Perform  │ │ • Update   │ │ • Record        │
│   tests    │ │   stock    │ │   outcome       │
│ • Enter    │ │ • Bill     │ │ • Bill patient  │
│   results  │ │   patient  │ │                 │
│ • Auto     │ │            │ │                 │
│   results  │ │            │ │                 │
│   via      │ │            │ │                 │
│   Analyzer │ │            │ │                 │
└──────┬─────┘ └─────┬──────┘ └────────┬────────┘
       │             │                 │
       └──────┬──────┘                 │
              │                        │
              ▼                        │
┌─────────────────────────────────────┐│
│  6. BILLING / CASHIER               ││
│  • All services billed              ││
│  • Multi-tier pricing applied       │◄
│    (Cash / NHIS / Private)          │
│  • Payment collected                │
│  • Receipt printed                  │
└──────────────────────┬──────────────┘
                       │
                       ▼
           ┌──────────────────────┐
           │  7. DISCHARGE /      │
           │     CLAIMS           │
           │  • Outpatient: done  │
           │  • Inpatient:        │
           │    discharge process │
           │  • NHIS/Private      │
           │    claims generated  │
           └──────────────────────┘
```

### Outpatient Flow (Status Progression)

```
VITALS → DOCTOR → [investigations/pharmacy] → DISCHARGED
```

### Inpatient Flow (Status Progression)

```
ADMISSION → ADMISSION (bed assigned) → [treatment cycle] → DISCHARGING → INPATIENT-DISCH
```

### Billing Types

| Type | Description |
|---|---|
| **Cash and Carry** | Patient pays full amount in cash |
| **NHIS** | National Health Insurance Scheme covers cost |
| **Private Insurance** | Private insurance provider covers cost |
| **Staff/Protocol** | Internal staff pricing |
| **Corporate** | Corporate billing agreements |

---

## 8. Role-Based Access Control

Access to menu items is controlled by the user's department assignment. The system recognizes the following roles:

| Role / Department | Accessible Modules |
|---|---|
| **CEO** | All modules + authorization privileges |
| **DEVELOPER** | All modules + authorization privileges |
| **SUPERADMIN** | All modules + authorization privileges |
| **ADMINISTRATION** | Information, Administration, Reports, Records, Ward, Lab, Scan, X-Ray, Pharmacy, Store, Claims, Accounts |
| **DOCTORS/PHYSICIAN ASSISTANTS** | Doctor consultation only |
| **PHYSIO** | Physiotherapy module only |
| **RECORDS** | Records module only |
| **WARD** | Ward module only |
| **LAB** | Lab module only |
| **SCAN** | Scan module only |
| **X-RAY** | X-Ray module only |
| **STORE** | Store module only |
| **PHARMACY** | Pharmacy module only |
| **CLAIMS** | Claims module only |
| **NHIS** | Claims module only |
| **ACCOUNTS** | Accounts module + Income, Expenses, Account View + authorization |
| **CASHIER** | Accounts (cashier only, no income/expenses/account view) + authorization |

### Authorization Flags

A hidden `tbAuthorize` text field is set to `"YES"` for privileged users (CEO, DEVELOPER, SUPERADMIN, ACCOUNTS, CASHIER). This flag controls whether certain edit/delete/print actions are enabled across forms.

### Per-User Permissions

Each user also has granular permission flags:
- **CanAdd** — Create new records
- **CanEdit** — Modify existing records
- **CanDelete** — Delete records
- **CanPrint** — Print reports/receipts

---

## 9. Notification System

UHMS includes an inter-department notification system:

1. **Notifications Table** — `notifications` table stores: `NOTID`, `ReqTo` (target department), `ReqTitle`, `ReqDesc`, `ReqStatus` (UNCHECKED/CHECKED).
2. **Background Polling** — `frmMDI.BackgroundWorker1` periodically queries for unchecked notifications targeted at the current user's department.
3. **Desktop Alerts** — Uses `DevComponents.DotNetBar.DesktopAlert` to show pop-up notifications. Color-coded by department (e.g., Green for Accounts, Gold for Doctors, Red for Pharmacy).
4. **Click-to-Navigate** — Clicking a notification navigates to the relevant department form and marks the notification as checked.

---

## 10. Analyzer Integration — Deep Dive

The **Analyzer Integration Module** is a sophisticated subsystem for connecting laboratory analyzers (e.g., hematology, chemistry, immunology machines) to UHMS. It follows a clean, modular architecture.

### 10.1 Architecture Overview

```
┌──────────────┐     ┌──────────────────┐     ┌──────────────────┐
│  LABORATORY   │     │   LISTENERS      │     │  PROTOCOL ENGINE │
│  ANALYZER     │────▶│  (TCP / Serial)  │────▶│  (HL7 / ASTM)   │
│  (Device)     │     │                  │     │                  │
└──────────────┘     └──────────────────┘     └────────┬─────────┘
                                                       │
                                                       ▼
┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│  RESULT DISPATCH │◀────│  MESSAGE FLOW    │◀────│  NORMALIZED      │
│  SERVICE         │     │  CONTROLLER      │     │  RESULTS         │
│  (Match to       │     │  (Buffer, Retry, │     │  (Universal      │
│   Lab Orders)    │     │   Dedup)         │     │   Format)        │
└──────┬───────────┘     └──────────────────┘     └──────────────────┘
       │
       ▼
┌──────────────────┐     ┌──────────────────┐
│  LAB ORDERS      │     │  VALIDATION &    │
│  (UHMS Database) │     │  AUTO-VALIDATION │
│                  │     │  ENGINE          │
└──────────────────┘     └──────────────────┘
```

### 10.2 Components

| Component | Class | Purpose |
|---|---|---|
| **Module Initializer** | `ModuleInitializer` | Bootstraps the module on app startup. Verifies DB tables, registers protocol adapters, initializes services. |
| **Analyzer Service** | `AnalyzerService` | Manages analyzer lifecycle (start/stop). Creates appropriate listeners based on connection type. Wires events. |
| **Listeners** | `TcpServerListener`, `SerialListener` | Receive raw data from analyzer devices. TCP listens on configured port; Serial monitors COM port. |
| **Protocol Adapters** | `HL7Adapter`, `ASTMAdapter` | Parse raw messages into structured data. **HL7v2** supports ORU^R01 (Observation Results); **ASTM E1394** supports H/P/O/R/L records. |
| **Protocol Registry** | `ProtocolRegistry` | Central registry of available protocol adapters. Lookup by name. |
| **Message Flow Controller** | `MessageFlowController` | Manages message pipeline: buffering (ConcurrentQueue), duplicate detection (content hashing), retry queue (max 3 attempts), and async processing. |
| **Test Code Mapper** | `TestCodeMapper` | Maps analyzer-specific test codes to UHMS internal test codes. Supports analyzer-specific and global mappings, unit conversion factors. |
| **Validation Rules Engine** | `ValidationRulesEngine` | Validates normalized results. Built-in rules: missing patient/sample ID, invalid units, critical/panic values, duplicate results. Configurable per lab. |
| **Result Dispatch Service** | `ResultDispatchService` | Matches normalized results to lab orders via sample ID or patient ID. Applies test code mapping, unit conversion, auto-validation. Updates lab order status. Audit logging. |

### 10.3 Message Processing Pipeline

```
1. Analyzer sends data → Listener receives raw bytes/text
2. Listener raises RawDataReceived event
3. AnalyzerService.OnRawDataReceived creates RawMessage object
4. MessageFlowController.EnqueueMessage:
   a. Compute content hash
   b. Check for duplicates (skip if seen in last 24h)
   c. Persist raw message to DB immediately
   d. Add to processing buffer
5. ProcessSingleMessage:
   a. Look up protocol adapter via ProtocolRegistry
   b. adapter.Parse() → ParsedMessage
   c. adapter.Validate() → ValidationResult
   d. adapter.Normalize() → List(Of NormalizedResult)
   e. ResultDispatchService.DispatchResults()
6. DispatchSingleResult:
   a. TestCodeMapper maps analyzer code → UHMS code
   b. Unit conversion if needed
   c. ValidationRulesEngine.Validate() (panic values, etc.)
   d. Match to lab order by SampleId or PatientId
   e. Update lab order with result
   f. Audit trail logged
```

### 10.4 Supported Protocols

| Protocol | Adapter | Standards | Message Types |
|---|---|---|---|
| **HL7 v2.x** | `HL7Adapter` | HL7 v2.5.1 | ORU^R01 (Observation Results), ACK |
| **ASTM** | `ASTMAdapter` | ASTM E1381/E1394 | H (Header), P (Patient), O (Order), R (Result), L (Terminator) |

### 10.5 Connection Types

| Type ID | Connection | Implementation |
|---|---|---|
| 0, 1 | TCP/IP Server | `TcpServerListener` — Listens on port (default 2575) |
| 2 | Serial (RS232) | `SerialListener` — COM port with configurable baud rate, data bits, parity, stop bits |
| 3 | File Watch | Planned (not yet implemented) |

### 10.6 UI

- **Analyzer Management** (`frmAnalyzerManagement`) — Configure and manage analyzer devices.
- **Analyzer Diagnostics** (`frmAnalyzerDiagnostics`) — Monitor connections, view raw messages, troubleshoot.
- **Protocol Simulator** (`ProtocolSimulator`) — Test protocol parsing without a real analyzer.

---

## 11. Third-Party Libraries

| Library | Version | Purpose |
|---|---|---|
| **Bunifu UI** | v1.52 | Modern WinForms controls (date pickers, toggles, gradients) |
| **Guna.UI2** | — | Modern button and toggle controls |
| **DevComponents DotNetBar2** | — | Advanced controls (RichTextBoxEx, ComboBoxEx, DesktopAlert) |
| **MySql.Data** | — | MySQL database connector |
| **Newtonsoft.Json** | 13.0.1 | JSON serialization/deserialization (ICD-10 API) |
| **RestSharp** | — | HTTP REST client (ICD-10 code lookup) |
| **NetSpell.SpellChecker** | — | Spell checking for clinical notes |
| **FreeSpire.Office** | 8.2.0 | Document generation (Spire.Doc, Spire.Pdf, Spire.Barcode) |
| **CircularProgressBar** | — | Loading animation on welcome screen |
| **RichTextBoxPrintCtrl** | — | RTF document printing |
| **Microsoft.VisualBasic.PowerPacks** | — | Visual Basic power pack controls |

---

## 12. Configuration & Settings

The application stores settings in `My.Settings`:

| Setting | Purpose |
|---|---|
| `data_str` | MySQL connection string |
| `PRINTER` | Default printer name |
| `dataFolder` | File storage path for consultation documents (subfolders: consult/, scanlab/, history/, examine/, treatmt/) |

Settings are managed via `frmSettings` and persisted to the user config file.

---

## 13. Known Technical Observations

| # | Observation | Impact |
|---|---|---|
| 1 | **Plain-text password storage** — Passwords are stored and compared as plain text in the `users` table. | Security risk |
| 2 | **SQL injection in some forms** — Some older forms concatenate user input directly into SQL strings (e.g., `frmLogin`, `frmPharm`, `frmTheater`). Newer code uses `DbHelper` with parameterized queries. | Security risk |
| 3 | **Mixed data access patterns** — Some forms use the newer `DbHelper` module; others use direct `MySqlConnection`/`MySqlCommand` with manual open/close. | Maintenance complexity |
| 4 | **Connection management** — Some forms reuse a single class-level `MySqlConnection`, leading to potential connection state issues. `DbHelper` properly uses `Using` statements. | Potential runtime errors |
| 5 | **`CheckForIllegalCrossThreadCalls = False`** — Used in `frmCashier` to suppress cross-thread exceptions. This hides real threading bugs. | Potential UI corruption |
| 6 | **Hidden controls for state** — Invisible textboxes and buttons are used to pass state between forms and trigger actions. | Unconventional but functional |
| 7 | **Analyzer Integration is well-architected** — Uses interfaces, dependency injection, concurrent collections, and async processing. Contrasts with the more legacy style of other forms. | Positive |

---

## 14. User Manual

### 14.1 Getting Started

#### System Requirements
- Windows 7 or later (x86/x64)
- .NET Framework 4.6 or later
- MySQL Server (local or network)
- Network connectivity for multi-user deployment

#### First Launch
1. Launch **UHMS.exe**. A splash screen appears showing version info.
2. The **Login Screen** appears. Enter your **Username** and **Password** provided by your system administrator.
3. Click **LOGIN**. If credentials are valid and your account is active, a loading screen appears and the main dashboard opens.

---

### 14.2 Main Dashboard (MDI)

After login, you will see the main dashboard with:
- **Top Menu Bar** — Navigate to any department module. Only menus relevant to your role are enabled.
- **Dashboard Cards** — Quick stats: Total Patients, Admitted Patients, Today's Consultations.
- **Appointment Grid** — Select a date to view scheduled appointments.
- **Clock** — Current time and date display.
- **Notification Alerts** — Pop-up notifications from other departments appear in the bottom-right corner. Click to navigate.

> **Tip:** Click the **gear icon** (bottom-left) to access Settings.

---

### 14.3 Patient Registration (Records Department)

1. Navigate to **Records → Search Patient** or **Records → Register Patient**.
2. **Search:** Type a name, OPD number, or phone number in the search box. Select a patient from the list.
3. **New Registration:**
   - Select folder type (e.g., "NEW FOLDER").
   - Fill in: Patient Name, Date of Birth, Gender, Blood Group, Phone, Address, Insurance Type.
   - Click **Save** to register.
4. The patient is now in the system and can be checked in for an attendance/visit.

---

### 14.4 Patient Check-In & Vitals (Ward)

1. Navigate to **Ward → Nurses Station**.
2. The screen shows **"PATIENTS AWAITING VITALS"** — patients who have paid and are waiting.
3. Select a patient from the list.
4. Record vital signs (Blood Pressure, Temperature, Pulse, Weight, Height, etc.).
5. Click **Save**. The patient status advances to "DOCTOR" and appears in the doctor's queue.

---

### 14.5 Doctor Consultation

1. Navigate to **Doctor** from the menu.
2. Select a patient from the waiting queue.
3. Use the tabbed interface to record:
   - **Presenting Complaints** — What the patient reports.
   - **History** — Medical history details.
   - **Diagnosis** — Search ICD-10 codes and select diagnosis.
   - **Investigation** — Order lab tests, scans, or X-rays.
   - **Treatment** — Prescribe medications (search drug list, set quantity and dosage).
   - **Procedure** — Schedule surgical or clinical procedures.
4. Click **Save** to save the consultation note.
5. Notifications are automatically sent to the relevant departments (Lab, Pharmacy, etc.).

---

### 14.6 Investigations (Lab / Scan / X-Ray)

1. Navigate to the appropriate department menu (e.g., **Lab → Requests**).
2. Select a patient from today's list or search by name.
3. View ordered investigations in the grid.
4. Perform tests and enter results via **Lab → Enter Results**.
5. If an **analyzer is connected**, results may arrive automatically (see Section 14.11).

---

### 14.7 Pharmacy

1. Navigate to **Pharmacy → Dispense**.
2. Select a patient from the list.
3. View the doctor's prescriptions in the grid (drug name, quantity, price).
4. Check stock availability.
5. Click **Dispense** to confirm. Stock is deducted automatically.

---

### 14.8 Billing & Payments (Cashier)

1. Navigate to **Accounts → Cashier**.
2. **Outpatient Queue** — Select a patient from today's attendance list.
3. **Inpatient Discharge Queue** — Select a patient marked for discharge.
4. View all billed services and amounts.
5. Collect payment and record the payment method.
6. Print receipt.

---

### 14.9 Claims Processing

1. Navigate to **Claims → NHIS Claims** or **Claims → Private Claims**.
2. Select a patient and date range.
3. Review billed services eligible for claim.
4. Assign a doctor for claim verification.
5. Submit claim. Track claim status.

---

### 14.10 Store & Inventory Management

1. **View Stock:** Navigate to **Store → Medicine List** to see all drugs with store and pharmacy quantities.
2. **Purchase Stock:** Navigate to **Store → Purchase** to create a purchase batch and receive stock.
3. **Transfer Stock:** Navigate to **Store → Transfer** to move stock from store to pharmacy. Select the destination department and items to transfer.

---

### 14.11 Analyzer Integration (Lab)

For labs with connected analyzers:

1. **Setup:** Go to **Settings → Analyzer Management**.
   - Add a new analyzer: provide name, protocol (HL7 or ASTM), connection type (TCP or Serial), IP/port or COM port.
   - Map analyzer test codes to UHMS test codes.
2. **Start Analyzer:** Enable the analyzer. The system begins listening for results.
3. **Automatic Results:** When the analyzer sends results:
   - Results are received, parsed, validated, and matched to existing lab orders.
   - Matched results appear automatically in the Lab Results form.
   - Critical/panic values are flagged for immediate review.
4. **Diagnostics:** Go to **Settings → Analyzer Diagnostics** to monitor connections, view raw messages, and troubleshoot issues.

---

### 14.12 HR Management

1. **Employees:** Navigate to **HR → Employees** to add/edit staff records.
2. **Attendance:** Navigate to **HR → Attendance** to track clock-in/out.
3. **Leave:** Navigate to **HR → Leave** to submit and approve leave requests.
4. **Payroll:** Navigate to **HR → Payroll** to process monthly payroll. Select an employee, review salary components (basic, allowances, deductions, tax, SSNIT), and generate payslips.

---

### 14.13 Administration

1. **Users:** Navigate to **Administration → Users** to create/edit system accounts. Assign departments and permissions.
2. **Services:** Navigate to **Information → Services** to manage the hospital service catalog and pricing.
3. **Hospital Info:** Navigate to **Information → Hospital Information** to update hospital details.
4. **Settings:** Click the gear icon or go to **Information → Setup** to configure the database connection, printer, and file paths.

---

### 14.14 Keyboard Shortcuts & Tips

| Tip | Description |
|---|---|
| **Search anywhere** | Most list forms have a search box at the top. Start typing to filter. |
| **Date filtering** | Use date pickers to filter records by date. |
| **Print** | Most report forms include a Print button. Ensure the correct printer is configured in Settings. |
| **Logout** | Click the **Logout** button (top area) to return to the login screen. |
| **Notifications** | Watch for pop-up alerts — click them to navigate directly to the relevant form. |

---

*Report generated from source code analysis of UHMS v5.2.0*
*Project by Click Software GH © 2019*
