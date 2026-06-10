Good. This should be the next cross-cutting phase: **App Responsiveness + Localisation**.

We should treat it as a full-system UI/UX pass, not just fixing one page. Since UHMS is Blade-dominant with Bootstrap 5 + Tabler Icons, the responsive work should improve the current layout instead of introducing Tailwind or another framework. The uploaded UI rules already make this clear: UHMS must keep Blade + Bootstrap 5 + Tabler Icons, reuse shared components, avoid new frameworks, and follow the standard layout/checklist for every edited page.   

You are working on UHMS — Ultimate Hospital Management System.

We now want to improve full-system responsiveness and localisation.

This is a cross-cutting UI/UX and internationalisation phase.

Goal:
Make UHMS usable on desktop, laptop, tablet, and mobile screens, and prepare the system for multi-language support, starting with English and French.

Do not redesign the whole system.
Do not introduce Tailwind.
Do not introduce a second CSS framework.
Do not break existing workflows.
Do not rewrite unrelated modules.
Do not remove existing Blade pages.
Do not convert everything to Vue/Inertia unless the page already uses Vue/Inertia.

UHMS is mainly a Blade + Bootstrap 5 + Tabler Icons application. Continue using the existing UI architecture.

---

# 1. Main Objectives

Implement two major improvements:

1. Responsiveness
   - desktop
   - laptop
   - tablet
   - mobile
   - printable views

2. Localisation
   - English
   - French
   - language files
   - translated UI labels
   - translated validation messages
   - translated status labels
   - translated menus
   - translatable reports where practical

---

# 2. Responsiveness Scope

Review and improve responsive behavior across major UHMS modules:

```text
Dashboard
Patients
Visits
Consultation
Emergency
Admissions / Wards
Pharmacy
Investigations / Lab
Theatre / Procedures
Billing
Insurance / Claims
Stock / Store / Procurement
Accounting / Finance
Reports
Administration
Settings
````

Prioritize the most-used and highest-risk pages first:

```text
Patients
Visits
Consultation
Emergency
Pharmacy
Investigations
Billing
Admissions
Stock
Accounting
Dashboards
```

---

# 3. Responsive UI Rules

Follow existing UHMS UI rules.

Use:

```text
Bootstrap 5 grid
Bootstrap responsive utilities
.table-responsive
cards with g-3 spacing
stacked forms on mobile
responsive action buttons
offcanvas/drawer only where already supported or easy
existing Blade components
```

Do not use:

```text
Tailwind
new CSS framework
random inline CSS
unapproved layout libraries
new chart library
```

Every edited page must follow:

```text
PageHeader
KPIs if any
Filters if any
Main content
Secondary content
Pagination
```

Use:

```blade
<x-page-header>
<x-status-badge>
<x-empty-state>
<x-stat-card>
<x-filter-bar>
<x-data-table>
<x-action-menu>
<x-confirm-form>
```

where available.

---

# 4. Responsive Breakpoints

Target Bootstrap breakpoints:

```text
xs: mobile portrait
sm: mobile landscape
md: tablets
lg: laptops
xl: desktops
xxl: large screens
```

Check layouts at minimum:

```text
375px mobile
414px mobile
768px tablet
1024px tablet/laptop
1366px desktop
1920px large desktop
```

---

# 5. Sidebar / Navigation Responsiveness

Improve navigation behavior.

Requirements:

* sidebar collapses correctly on tablet/mobile
* menu is scrollable when long
* active section remains visible
* department dashboards remain accessible
* no menu item overlaps content
* module-disabled items do not leave broken gaps
* topbar actions fit small screens
* notification/profile dropdowns work on mobile

Do not hardcode menu links directly in Blade if the system uses `SidebarMenuBuilder`.

---

# 6. Tables Responsiveness

Audit all major tables.

Rules:

* wrap wide tables in `.table-responsive`
* avoid horizontal page overflow
* keep action column visible where possible
* use compact text on mobile
* avoid too many columns on small screens
* use stacked card layout only for critical mobile workflows where table scrolling is poor
* show `<x-empty-state>` when empty
* use `<x-status-badge>` for statuses

High priority tables:

```text
Patient list
Visit list
Consultation queue
Emergency cases
Admission list
Bed map
Prescription queue
Dispensing list
Investigation requests
Investigation results
Procedure requests
Invoice list
Payments
Claims
Stock balances
Stock movements
Purchase orders
Supplier ledger
Journal entries
General ledger
AR aging
AP aging
```

---

# 7. Forms Responsiveness

Audit major forms.

Rules:

* forms stack cleanly on mobile
* labels stay above inputs
* long selects use Select2/searchable select
* date pickers work on mobile
* submit/cancel buttons remain visible
* no form fields overflow card boundaries
* validation errors show under fields
* required fields are clear
* modals fit mobile screens

High priority forms:

```text
Patient registration
Visit creation
Emergency case creation
Triage
Consultation clinical forms
Prescription
Lab result entry
Procedure scheduling
Invoice/payment forms
Insurance/claims forms
Stock receiving
Stock transfer
Purchase order
Journal entry
User/role forms
```

---

# 8. Dashboard Responsiveness

Department-type dashboards must work well on:

```text
desktop
tablet
mobile
```

Rules:

* KPI cards should wrap naturally
* work queues should be scrollable or stack gracefully
* quick actions should become compact buttons
* charts should resize
* alert cards should remain readable
* no KPI text overflow
* no hidden critical alerts

---

# 9. Clinical Page Responsiveness

Clinical pages need special care.

Review:

```text
Consultation page
Patient profile
Patient timeline
Emergency case page
Admission detail
MAR chart
Investigation result entry
Theatre/procedure workflow
```

Rules:

* do not cram large clinical content into small modals
* use cards/sections/tabs/accordion where appropriate
* patient context must remain visible
* critical status badges must remain visible
* action buttons must not disappear
* timelines must be readable on mobile
* MAR grid may scroll horizontally if needed
* clinical safety beats visual compactness

---

# 10. Financial Page Responsiveness

Review:

```text
Invoices
Payments
Cashier shift
Claims
AR aging
AP aging
Journal entries
General ledger
Trial balance
Supplier ledger
```

Rules:

* numbers must remain readable
* totals must remain visible
* action buttons must be permission-aware
* accounting status must be visible to authorized users
* wide reports can scroll horizontally
* print/export must remain available
* financial totals must not be hidden on mobile

---

# 11. Print Responsiveness

Improve print layouts where relevant.

Priority print views:

```text
Invoice
Receipt
Claim form
Lab result
Prescription
Discharge summary
Consultation summary
Procedure report
Stock report
Trial balance
General ledger
AR aging
AP aging
```

Rules:

* black on white
* hide sidebar/topbar/buttons
* show hospital name/logo
* show patient/visit context where relevant
* show printed by and printed at
* show signatures where needed
* avoid broken tables across pages where practical

Use `<x-print-layout>` where available.

---

# 12. Localisation Scope

Prepare UHMS for multiple languages.

Initial languages:

```text
en
fr
```

Default language:

```text
en
```

French should be selectable.

Do not translate database content automatically unless content is system-defined.

Translate:

```text
Menus
Page titles
Buttons
Labels
Placeholders
Validation messages
Flash messages
Status labels
Empty states
Confirmation messages
Report headings
Dashboard titles
KPI labels
Table headings
Form section titles
```

Do not translate:

```text
Patient names
Doctor names
Supplier names
Department names unless configured
Product names unless configured
Service names unless configured
Clinical notes entered by users
Uploaded documents
```

---

# 13. Laravel Localisation Structure

Use Laravel localisation files.

Recommended:

```text
lang/en/
lang/fr/
```

Files:

```text
lang/en/common.php
lang/en/menu.php
lang/en/patients.php
lang/en/visits.php
lang/en/consultation.php
lang/en/emergency.php
lang/en/admissions.php
lang/en/pharmacy.php
lang/en/investigations.php
lang/en/procedures.php
lang/en/billing.php
lang/en/claims.php
lang/en/stock.php
lang/en/accounting.php
lang/en/reports.php
lang/en/auth.php
lang/en/validation.php
lang/en/statuses.php

lang/fr/common.php
lang/fr/menu.php
lang/fr/patients.php
lang/fr/visits.php
lang/fr/consultation.php
lang/fr/emergency.php
lang/fr/admissions.php
lang/fr/pharmacy.php
lang/fr/investigations.php
lang/fr/procedures.php
lang/fr/billing.php
lang/fr/claims.php
lang/fr/stock.php
lang/fr/accounting.php
lang/fr/reports.php
lang/fr/auth.php
lang/fr/validation.php
lang/fr/statuses.php
```

Use existing Laravel conventions if the project already has lang files.

---

# 14. Translation Key Rules

Do not scatter random translation keys.

Use structured keys.

Examples:

```php
__('common.save')
__('common.cancel')
__('common.delete')
__('common.confirm')
__('common.search')
__('common.filter')
__('common.reset')
__('common.actions')

__('menu.patients')
__('menu.visits')
__('menu.billing')
__('menu.accounting')

__('patients.title')
__('patients.create')
__('patients.search_placeholder')

__('visits.create_visit')
__('visits.visit_type')
__('visits.patient_search')

__('billing.invoice')
__('billing.payment')
__('billing.outstanding_balance')

__('statuses.invoice.paid')
__('statuses.invoice.partially_paid')
__('statuses.visit.emergency')
```

Avoid keys like:

```php
__('Save Button Text On Patient Page')
```

Keep keys reusable and predictable.

---

# 15. Status Localisation

Centralize status labels.

Statuses should not display raw enum values like:

```text
WAITING_CONSULTATION
PARTIALLY_PAID
IN_PROGRESS
```

They should display translated human labels:

```text
Waiting Consultation
Partially Paid
In Progress
```

French examples:

```text
En attente de consultation
Partiellement payé
En cours
```

Update `<x-status-badge>` if needed so it can use translation keys.

Example:

```php
__('statuses.visit.waiting_consultation')
__('statuses.invoice.partially_paid')
__('statuses.payment.refunded')
```

---

# 16. Menu Localisation

Update `SidebarMenuBuilder` labels to use translation keys.

Example:

```php
'label' => __('menu.patients')
```

or if menu arrays are generated before translation, store translation keys:

```php
'label_key' => 'menu.patients'
```

and render with:

```php
__($item['label_key'])
```

Do not hardcode English labels in menu builder after this phase.

---

# 17. Language Switcher

Add a language switcher.

Location:

```text
Topbar user dropdown
or settings/profile page
```

Supported languages:

```text
English
Français
```

Behavior:

* user can switch language
* selected language persists in session
* if user profile has locale field, persist to user profile
* fallback to app locale if no user preference
* middleware sets locale on every request

Suggested middleware:

```php
SetLocale
```

Logic:

```text
1. Authenticated user locale if set
2. Session locale
3. Browser locale if allowed
4. config('app.locale')
```

---

# 18. Database Update for User Locale

If not already present, add:

```text
users.locale nullable string default null
```

Allowed values:

```text
en
fr
```

Do not allow arbitrary unsafe locale values.

---

# 19. Validation Localisation

Translate validation messages.

Use:

```text
lang/en/validation.php
lang/fr/validation.php
```

Ensure custom request validation messages are translatable.

Do not leave mixed English/French validation on the same page.

---

# 20. Flash / Toast / Error Localisation

Translate:

```text
Saved successfully
Updated successfully
Deleted successfully
Payment recorded successfully
Invoice created successfully
Unauthorized action
Something went wrong
No records found
Are you sure?
This action cannot be undone
Reason is required
```

Friendly error pages should also be translatable:

```text
403
404
500
419 session expired
```

---

# 21. Date, Time, Currency Formatting

Add locale-aware formatting helpers.

Requirements:

* date format can adapt to locale
* time format can adapt to locale
* currency formatting should remain safe and consistent
* do not break accounting reports
* allow hospital/system setting for currency symbol

Examples:

```text
English: Jun 10, 2026
French: 10 juin 2026
```

Currency example:

```text
GHS 1,250.00
1 250,00 GHS
```

For now, keep currency format consistent if changing it risks breaking reports.

Document formatting decisions.

---

# 22. Search and Filters Localisation

Translate placeholders and filter labels.

Examples:

```text
Search patients...
Filter by department
Date from
Date to
Apply filters
Reset
```

French:

```text
Rechercher des patients...
Filtrer par département
Date début
Date fin
Appliquer les filtres
Réinitialiser
```

---

# 23. Confirmation Messages

All destructive/high-risk confirmations must be translatable.

Examples:

```text
Are you sure you want to cancel this visit?
Are you sure you want to reverse this payment?
Please provide a reason.
This action cannot be undone.
```

French translations must be provided.

---

# 24. Localisation of Reports

Translate report UI:

```text
Report title
Filters
Column headings
Summary labels
Print button
Export button
Generated by
Generated at
```

Do not translate raw data unless it is a system label/status.

---

# 25. Localisation of Dashboards

All department-type dashboards must use translation keys for:

```text
Dashboard title
KPI labels
Queue headings
Alert labels
Quick actions
Empty states
```

Example:

```php
__('dashboards.pharmacy.title')
__('dashboards.pharmacy.prescriptions_waiting')
__('dashboards.emergency.active_cases')
```

Add:

```text
lang/en/dashboards.php
lang/fr/dashboards.php
```

---

# 26. Code Audit Targets

Search and replace hardcoded UI strings in priority order:

```text
SidebarMenuBuilder
layouts
dashboard pages
patient pages
visit pages
billing pages
pharmacy pages
investigation pages
emergency pages
admission pages
stock pages
accounting pages
report pages
auth pages
common components
```

Do not attempt to translate every single legacy/vendor template page first.

Prioritize live UHMS workflows.

---

# 27. Localisation Safety Rules

Do not translate route names.

Do not translate permission names.

Do not translate database enum stored values.

Do not translate internal event names.

Do not translate audit log event codes.

Do not translate class names, model names, or config keys.

Translate only user-facing labels.

---

# 28. Responsiveness Manual Verification

Do not write the full automated test suite yet if we are still in implementation flow.

Manual verification required:

1. Check dashboard on mobile/tablet/desktop.
2. Check patient list on mobile/tablet/desktop.
3. Check visit creation on mobile/tablet/desktop.
4. Check consultation page on mobile/tablet/desktop.
5. Check emergency page on mobile/tablet/desktop.
6. Check pharmacy dispensing on mobile/tablet/desktop.
7. Check investigation result entry on mobile/tablet/desktop.
8. Check billing invoice/payment screens on mobile/tablet/desktop.
9. Check stock balance and stock movement pages on mobile/tablet/desktop.
10. Check accounting reports on mobile/tablet/desktop.
11. Confirm no horizontal page overflow except intentional table scroll.
12. Confirm buttons remain accessible.
13. Confirm modals fit small screens.
14. Confirm critical information remains visible.
15. Confirm print views render cleanly.

---

# 29. Localisation Manual Verification

Manual verification required:

1. Switch language to English.
2. Confirm menus display in English.
3. Confirm major page titles display in English.
4. Confirm buttons and labels display in English.
5. Switch language to French.
6. Confirm menus display in French.
7. Confirm major page titles display in French.
8. Confirm buttons and labels display in French.
9. Confirm validation messages display in selected language.
10. Confirm status badges display translated labels.
11. Confirm dashboard labels display translated labels.
12. Confirm flash messages display translated labels.
13. Confirm reports display translated headings.
14. Confirm user-entered clinical notes are not translated.
15. Confirm route names and permissions are not translated.
16. Confirm language preference persists after refresh/login.

---

# 30. Documentation

Create:

```text
docs/RESPONSIVENESS_LOCALISATION_REPORT.md
```

Include:

* pages audited
* responsive fixes completed
* localisation architecture
* language files created
* translation key structure
* language switcher behavior
* middleware behavior
* user locale persistence
* formatting decisions
* manual verification completed
* remaining untranslated pages
* known TODOs

---

# 31. Acceptance Criteria

This phase is complete when:

* major UHMS pages work on desktop/tablet/mobile
* wide tables are safely responsive
* major forms stack correctly
* dashboards are responsive
* clinical pages remain usable on smaller screens
* financial reports remain readable
* language switcher exists
* English and French are supported
* user language preference persists
* menus are translatable
* statuses are translatable
* validation messages are translatable
* dashboard labels are translatable
* major module labels are translatable
* reports have translated headings
* no new UI framework is introduced
* existing workflows are not broken
* documentation is created
* manual verification is documented

---

# 32. Important Rules

Do not introduce Tailwind.
Do not introduce a second CSS framework.
Do not redesign UHMS from scratch.
Do not translate internal codes.
Do not translate permissions.
Do not translate route names.
Do not translate database enum values.
Do not translate user-entered clinical notes.
Do not expose raw technical errors.
Do not hide critical clinical/financial/stock information on mobile.
Do not break existing dashboards.
Do not break existing SidebarMenuBuilder.
Do not skip permission checks.
Do not bypass existing UI components.
Do not write the full automated test suite yet.

Proceed with full-system Responsiveness and Localisation implementation now.

```

After this, the next clean phase should be **Role-Based Dashboard Polish + Module Reports**, because responsive/localised dashboards will make the whole system feel much more professional.
```
