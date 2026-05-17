You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

We need to redesign the **dashboard and sidebar/menu for consultation-type users** using the existing super-admin `SidebarMenuBuilder` as reference, but filtered and reshaped for clinical consultation work only.

The current super-admin menu contains many sections such as Patient Services, Clinical, Ward/Inpatient, Pharmacy, Investigations, Billing, Claims, Store, Accounts, HR, Reports, Administration, Notifications, and Settings. Consultation-type users should not see all of these. Their menu must be focused on their daily clinical work.

Focus only on consultation-type dashboard/menu/sidebar, permissions, route visibility, queue shortcuts, and clinical reports. Do not refactor unrelated modules.

---

# 1. Main Objective

Create a dedicated menu and dashboard experience for consultation-type staff.

Consultation-type users should see only menus relevant to:

* consultation queue
* active consultations
* referred patients
* patient clinical search
* previous visits
* prescriptions
* investigation requests/results
* procedure requests/reports
* follow-up appointments
* clinical tools
* doctor-specific reports
* notifications
* profile/settings

They must not see unrelated operational/admin areas unless explicitly permitted.

---

# 2. Consultation-Type User Definition

A consultation-type user may be identified by one or more of:

```text
role = doctor
role = physician_assistant
role = consultant
role = specialist
department.type = consultation
user has permission consultation.access
```

Do not hardcode only `doctor`.

Add or verify a helper on `User`:

```php
public function isConsultationUser(): bool
{
    return $this->hasAnyRole(['doctor', 'physician_assistant', 'consultant', 'specialist'])
        || optional($this->department)->type === 'consultation'
        || $this->can('consultation.access');
}
```

Adapt to the project’s existing role/permission conventions.

---

# 3. Menu Builder Rule

Update `SidebarMenuBuilder` or create a dedicated builder such as:

```php
ConsultationSidebarMenuBuilder
```

Recommended approach:

```php
if ($user->isConsultationUser() && ! $user->hasAnyRole(['super-admin', 'admin'])) {
    return $this->buildConsultationMenu($user, $currentRouteName, $unreadNotifications);
}
```

Rules:

* Super-admin/admin still get the full menu.
* Consultation-type users get the focused consultation menu.
* Users with multiple roles may receive full access only if they have admin-level role/permission.
* Do not hardcode user IDs or emails.
* Continue respecting modules, permissions, and active route patterns.

---

# 4. Remove These Menus for Consultation-Only Users

Consultation-type users should not see these menus unless explicitly permitted:

```text
Admin
Users
Roles & Permissions
Departments management
Designations
Store / Procurement
Products
Stock Locations
Stock Transfers
Stock Adjustments
Suppliers
Supplier Ledger
Billing / Invoices
Receive Payments
Claims & Insurance
Accounts & Finance
HR & Payroll
Payroll
System Settings
Modules
Pharmacy Dispensing
Drug Catalogue management
Investigation Result Entry
Investigation Catalogue management
Theatre Management
Ward Management
Bed Management
```

Important:

* A doctor may request investigations, but should not manage investigation result entry or investigation catalogue.
* A doctor may request procedures, but should not manage theatre workflow unless also a theatre user.
* A doctor may view reports/results, but should not manage billing, stock, HR, or system settings.

---

# 5. Final Consultation-Type Menu Structure

Build the consultation-type menu with these sections.

## Main Menu

```text
Main Menu
└── Consultation Dashboard
```

## Consultation Queue

```text
Consultation Queue
├── Waiting Queue
├── Currently Consulting
├── Referred Patients
└── My Department Queue
```

## Clinical Work

```text
Clinical Work
├── My Consultations
├── My Recent Patients
├── Patient Search
├── Follow-ups / Appointments
└── Previous Visits
```

## Requests & Results

```text
Requests & Results
├── Investigation Results
├── Procedure Reports
├── Prescription History
└── Pending Results
```

## Clinical Tools

```text
Clinical Tools
├── Medical Patterns
├── ICD-10 Codes
├── Diagnosis Templates
└── Prescription Templates
```

## Reports

```text
Reports
└── Clinical Reports
```

## Notifications

```text
Notifications
└── Notifications
```

## Profile

```text
Profile
├── My Profile
└── Change Password
```

Only include an item if the route exists and the user has permission.

---

# 6. Suggested Menu Array

Implement a consultation menu similar to this, adapting route names to the existing project.

```php
protected function buildConsultationMenu(User $user, string $currentRouteName, int $unreadNotifications = 0): array
{
    $sections = [
        [
            'title' => 'Main Menu',
            'items' => [
                [
                    'label' => 'Consultation Dashboard',
                    'icon' => 'ti ti-layout-dashboard',
                    'route' => 'consultation.dashboard',
                    'active_patterns' => ['consultation.dashboard', 'doctor.dashboard'],
                    'permission' => 'consultation.dashboard.view',
                ],
            ],
        ],
        [
            'title' => 'Consultation Queue',
            'items' => [
                [
                    'label' => 'Waiting Queue',
                    'icon' => 'ti ti-list-numbers',
                    'route' => 'consultation.queue',
                    'route_params' => ['status' => 'waiting'],
                    'active_patterns' => ['consultation.queue'],
                    'permission' => 'consultation.queue.view',
                ],
                [
                    'label' => 'Currently Consulting',
                    'icon' => 'ti ti-stethoscope',
                    'route' => 'consultation.queue',
                    'route_params' => ['status' => 'consulting'],
                    'active_patterns' => ['consultation.queue'],
                    'permission' => 'consultation.queue.view',
                ],
                [
                    'label' => 'Referred Patients',
                    'icon' => 'ti ti-arrow-forward-up',
                    'route' => 'consultation.referred',
                    'active_patterns' => ['consultation.referred'],
                    'permission' => 'consultation.queue.view',
                ],
                [
                    'label' => 'My Department Queue',
                    'icon' => 'ti ti-building-hospital',
                    'route' => 'consultation.department-queue',
                    'active_patterns' => ['consultation.department-queue'],
                    'permission' => 'consultation.queue.view',
                ],
            ],
        ],
        [
            'title' => 'Clinical Work',
            'items' => [
                [
                    'label' => 'My Consultations',
                    'icon' => 'ti ti-notes',
                    'route' => 'consultation.my-consultations',
                    'active_patterns' => ['consultation.my-consultations'],
                    'permission' => 'consultations.view',
                ],
                [
                    'label' => 'My Recent Patients',
                    'icon' => 'ti ti-user-heart',
                    'route' => 'consultation.recent-patients',
                    'active_patterns' => ['consultation.recent-patients'],
                    'permission' => 'consultation.previous_visits.view',
                ],
                [
                    'label' => 'Patient Search',
                    'icon' => 'ti ti-user-search',
                    'route' => 'consultation.patients.search',
                    'active_patterns' => ['consultation.patients.*'],
                    'permission' => 'consultation.patient_search',
                ],
                [
                    'label' => 'Follow-ups / Appointments',
                    'icon' => 'ti ti-calendar-event',
                    'route' => 'consultation.followups',
                    'active_patterns' => ['consultation.followups.*'],
                    'permission' => 'consultation.appointments.view',
                    'module' => 'appointments',
                ],
                [
                    'label' => 'Previous Visits',
                    'icon' => 'ti ti-history',
                    'route' => 'consultation.previous-visits',
                    'active_patterns' => ['consultation.previous-visits.*'],
                    'permission' => 'consultation.previous_visits.view',
                ],
            ],
        ],
        [
            'title' => 'Requests & Results',
            'items' => [
                [
                    'label' => 'Investigation Results',
                    'icon' => 'ti ti-report-medical',
                    'route' => 'consultation.investigation-results',
                    'active_patterns' => ['consultation.investigation-results.*'],
                    'permission' => 'consultation.results.view',
                    'module' => 'investigations',
                ],
                [
                    'label' => 'Procedure Reports',
                    'icon' => 'ti ti-file-description',
                    'route' => 'consultation.procedure-reports',
                    'active_patterns' => ['consultation.procedure-reports.*'],
                    'permission' => 'consultation.procedure_reports.view',
                    'module' => 'procedures',
                ],
                [
                    'label' => 'Prescription History',
                    'icon' => 'ti ti-prescription',
                    'route' => 'consultation.prescription-history',
                    'active_patterns' => ['consultation.prescription-history.*'],
                    'permission' => 'prescriptions.view',
                    'module' => 'pharmacy',
                ],
                [
                    'label' => 'Pending Results',
                    'icon' => 'ti ti-clock-question',
                    'route' => 'consultation.pending-results',
                    'active_patterns' => ['consultation.pending-results.*'],
                    'permission' => 'consultation.results.view',
                    'module' => 'investigations',
                ],
            ],
        ],
        [
            'title' => 'Clinical Tools',
            'items' => [
                [
                    'label' => 'Medical Patterns',
                    'icon' => 'ti ti-template',
                    'route' => 'consultation.patterns.index',
                    'active_patterns' => ['consultation.patterns.*'],
                    'permission' => 'consultations.view',
                    'module' => 'medical-patterns',
                ],
                [
                    'label' => 'ICD-10 Codes',
                    'icon' => 'ti ti-medical-cross',
                    'route' => 'consultation.icd-codes.index',
                    'active_patterns' => ['consultation.icd-codes.*'],
                    'permission' => 'icd.view',
                ],
                [
                    'label' => 'Diagnosis Templates',
                    'icon' => 'ti ti-clipboard-text',
                    'route' => 'consultation.diagnosis-templates.index',
                    'active_patterns' => ['consultation.diagnosis-templates.*'],
                    'permission' => 'consultations.view',
                ],
                [
                    'label' => 'Prescription Templates',
                    'icon' => 'ti ti-prescription',
                    'route' => 'consultation.prescription-templates.index',
                    'active_patterns' => ['consultation.prescription-templates.*'],
                    'permission' => 'prescriptions.view',
                    'module' => 'pharmacy',
                ],
            ],
        ],
        [
            'title' => 'Reports',
            'items' => [
                [
                    'label' => 'Clinical Reports',
                    'icon' => 'ti ti-report',
                    'route' => 'consultation.reports.index',
                    'active_patterns' => ['consultation.reports.*'],
                    'permission' => 'consultation.reports.view',
                    'module' => 'reports',
                ],
            ],
        ],
        [
            'title' => null,
            'items' => [
                [
                    'label' => 'Notifications',
                    'icon' => 'ti ti-bell',
                    'route' => 'admin.notifications.index',
                    'active_patterns' => ['admin.notifications.*'],
                    'permission' => 'notifications.view',
                    'module' => 'notifications',
                    'badge' => $unreadNotifications > 0 ? $unreadNotifications : null,
                    'badge_class' => 'badge bg-danger rounded-pill ms-auto',
                ],
            ],
        ],
        [
            'title' => 'Profile',
            'items' => [
                [
                    'label' => 'My Profile',
                    'icon' => 'ti ti-user',
                    'route' => 'profile.edit',
                    'active_patterns' => ['profile.*'],
                ],
                [
                    'label' => 'Change Password',
                    'icon' => 'ti ti-lock',
                    'route' => 'profile.password',
                    'active_patterns' => ['profile.password'],
                ],
            ],
        ],
    ];

    return array_values(array_filter(array_map(
        fn (array $section) => $this->filterSection($section, $user, $currentRouteName),
        $sections,
    )));
}
```

If some route names do not exist, either:

1. map them to existing routes, or
2. create the missing routes/controllers/pages, or
3. temporarily omit the menu item until the feature exists.

Do not leave broken menu links.

---

# 7. Dashboard Requirements

Create/update consultation dashboard route and page.

Suggested route:

```php
Route::get('/consultation-dashboard', [ConsultationDashboardController::class, 'index'])
    ->name('consultation.dashboard');
```

Dashboard should include cards:

```text
Waiting Consultation
Currently Consulting
Referred Patients
My Consultations Today
Completed Today
Pending Investigation Results
Pending Procedure Reports
Follow-ups Today
```

Dashboard main list should show consultation queue.

Queue row should include:

```text
Patient Name
Age / Gender
Visit Number
Triage Score
Priority / Emergency Status
Department / Service
Assigned Doctor
Insurance / Cash and Carry
Waiting Time
Status
Action
```

Action button logic:

```text
WAITING_CONSULTATION → Start Consultation
CONSULTING → Continue Consultation
```

---

# 8. Queue Rules

Consultation queue must only load visits with statuses:

```text
WAITING_CONSULTATION
CONSULTING
```

Do not show:

```text
TRIAGE
COMPLETED
CANCELLED
DISCHARGED
BILLING
PHARMACY_ONLY
INVESTIGATION_ONLY
```

Queue filters:

```text
All
Waiting
Consulting
Emergency
Referred
My Patients
Department Patients
Today
Date Range
Search
```

Search should support:

```text
patient name
visit number
phone number
OPD number / patient number
```

---

# 9. Start / Continue Consultation

When `Start Consultation` is clicked:

* validate visit status is `WAITING_CONSULTATION`
* assign current doctor if not already assigned
* change status to `CONSULTING`
* log the status transition
* open consultation page

When `Continue Consultation` is clicked:

* open consultation page
* keep status as `CONSULTING`

Use:

```php
VisitWorkflowService::startConsultation(...)
```

Do not update visit status directly in Vue or controller.

---

# 10. Clinical Search and Previous Visits

Consultation users should be able to search patients clinically.

They may view:

```text
patient demographics
previous visits
diagnosis history
consultation notes
prescriptions
investigation results
procedure reports
follow-ups
```

They should not edit administrative patient details unless permitted.

---

# 11. Requests and Results

Consultation users need doctor-side access to results/reports.

They should be able to:

* view investigation results for their patients
* view procedure reports for their patients
* view prescription history
* view pending requested results
* request investigations from consultation page
* request procedures from consultation page

They should not manage:

```text
investigation result entry
investigation catalogue
theatre dashboard
procedure catalogue
pharmacy dispensing
```

unless they have those specific permissions.

---

# 12. Clinical Reports

Create doctor-relevant clinical reports only.

Recommended reports:

```text
My Consultations
Diagnosis Summary
Investigation Requests
Procedure Requests
Prescriptions Given
Completed Visits
Follow-up Appointments
```

Do not show:

```text
financial reports
billing reports
stock reports
supplier reports
claims reports
payroll reports
HR reports
daily collection
pharmacy sales
```

---

# 13. Permissions

Add or verify consultation permissions:

```text
consultation.access
consultation.dashboard.view
consultation.queue.view
consultation.start
consultation.continue
consultation.patient_search
consultation.previous_visits.view
consultation.appointments.view
consultation.results.view
consultation.procedure_reports.view
consultation.reports.view
consultation.referred.view
```

Existing useful permissions may include:

```text
consultations.view
prescriptions.view
notifications.view
icd.view
```

Consultation-only users should not automatically get:

```text
billing.manage
payments.create
stock.manage
product.create
pharmacy.dispensing.view
lab.results.entry
procedure_catalogue.manage
theatre.manage
hr.manage
settings.manage
modules.manage
users.view
```

---

# 14. Backend Services

Create/update:

```text
ConsultationDashboardService
ConsultationQueueService
ConsultationMenuService
```

## ConsultationDashboardService

Should return:

* dashboard counts
* queue summary
* pending result counts
* today’s consultation counts
* follow-up counts

## ConsultationQueueService

Should return filtered queue list with eager-loaded data.

Load relationships:

```text
patient
department
service
assignedDoctor
latestVitals
triageScore
activeInsurance
```

Avoid loading full patient history on dashboard.

## ConsultationMenuService

Optional, but recommended if the menu logic becomes large.

Should build focused menu for consultation-type users.

---

# 15. Frontend / Inertia Pages

Create or update:

```text
resources/js/Pages/Dashboard/Consultation.vue
resources/js/Pages/Consultation/Queue.vue
resources/js/Pages/Consultation/MyConsultations.vue
resources/js/Pages/Consultation/Referred.vue
resources/js/Pages/Consultation/PatientSearch.vue
resources/js/Pages/Consultation/Reports/Index.vue
resources/js/Components/Consultation/ConsultationDashboardCards.vue
resources/js/Components/Consultation/ConsultationQueueTable.vue
resources/js/Components/Consultation/PatientClinicalSummary.vue
```

Use existing page names if already present.

---

# 16. SPA Behavior

Consultation dashboard and queue should behave like SPA pages.

Requirements:

* no unnecessary full page reloads
* filters update smoothly
* search preserves page state
* Start Consultation shows loading state
* validation errors display clearly
* active filters are preserved
* queue updates after action

Use Inertia partial reloads or Vue state where appropriate.

---

# 17. Performance Rules

* Paginate consultation queue.
* Eager-load required relationships.
* Do not load full patient history on dashboard.
* Load clinical summary only when opened.
* Cache menu for user if safe.
* Avoid N+1 queries.
* Add/use indexes where needed:

  * visits.status
  * visits.current_department_id
  * visits.assigned_doctor_id
  * visits.created_at
  * patients.name
  * patients.patient_number / opd_number

---

# 18. Data Integrity Rules

* Consultation users should only act on consultation workflow patients.
* Do not show triage patients before triage completion.
* Do not show completed/cancelled/discharged visits in active queue.
* Start Consultation must go through `VisitWorkflowService`.
* Do not expose admin/store/billing/HR menus to consultation-only users.
* Do not allow unrelated department queue access unless permission allows it.
* Do not show broken menu routes.

---

# 19. Testing / Verification

Add or update tests for:

1. Consultation user receives focused consultation menu.
2. Super-admin still receives full menu.
3. Consultation user does not see Store/Billing/HR/Admin/Settings menus.
4. Consultation dashboard loads.
5. Queue only shows `WAITING_CONSULTATION` and `CONSULTING`.
6. Waiting patient shows `Start Consultation`.
7. Consulting patient shows `Continue Consultation`.
8. Start Consultation changes status through `VisitWorkflowService`.
9. Patient search respects permissions.
10. Clinical reports exclude finance/stock/HR reports.
11. Menu hides disabled modules.
12. No menu item points to missing routes.

---

# 20. Deliverables

Provide:

1. Gap analysis of current menu/dashboard.
2. Updated `SidebarMenuBuilder` or new `ConsultationSidebarMenuBuilder`.
3. User consultation helper logic.
4. Updated routes.
5. Updated controllers.
6. Updated services.
7. Updated Inertia/Vue pages.
8. Updated permissions/seeders.
9. Updated tests or verification notes.
10. Confirmation that consultation staff menu is focused and complete.
11. Confirmation that super-admin full menu still works.
12. Files modified.
13. Remaining TODOs if any.

---

# 21. Important Rules

Do not give consultation users the super-admin menu.

Do not remove super-admin menu.

Do not hardcode only `doctor`.

Do not show unrelated modules to consultation-only users.

Do not show broken menu links.

Do not bypass module/permission checks.

Do not update visit status outside `VisitWorkflowService`.

Do not load full patient history by default.

Do not refactor unrelated modules.

Now inspect the existing `SidebarMenuBuilder`, routes, permissions, and consultation pages, then implement a focused, useful dashboard and menu for consultation-type staff.
