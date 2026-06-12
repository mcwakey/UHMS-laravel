You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no docs/UHMS_IMPLEMENTATION_SKILL.md file in this project.
Do not try to read it.
Follow the instructions in this prompt directly.

We have been completing UHMS localisation in active route/page batches.

Previous active-page batches covered:

* appointments, products, service renderings, visit Inertia index
* consultations, theatre/procedures, lab/investigations, medication administration, blood bank
* store/stock/procurement/suppliers and HR/payroll/attendance/leave

Now proceed with:

# UHMS Localisation Phase 12 — Active Pages Translation Batch 5

## Goal

Complete the next active localisation batch for the remaining financial, administrative, workflow, reporting, and shared UI surfaces.

This batch focuses on:

1. Billing
2. Invoices
3. Payments
4. Cashier / counter-sale / shifts
5. Claims
6. Sponsors / insurance / corporate receivables
7. Accounting
8. Reports
9. Settings
10. Queues
11. Wards / bed management
12. Shared workflow components and layouts
13. Inertia/frontend leftovers
14. Service event titles/messages that are confirmed user-facing
15. Localisation audit allowlist cleanup

Do not translate demo/template/sample pages.
Do not translate backup-route-only pages.
Do not translate files only referenced by `routes/web.php.bak`.
Do not change billing logic.
Do not change accounting logic.
Do not change claims logic.
Do not change payment logic.
Do not change permissions.
Do not expose restricted financial, clinical, stock-cost, payroll, or accounting data.
Do not create a new localisation system.
Do not introduce new packages.
Do not introduce Tailwind.

---

# 1. Source Reports

Use:

```text
docs/LOCALISATION_PHASE_7_COMPLETE_ACTIVE_PAGE_TRANSLATION_REPORT.md
docs/LOCALISATION_PHASE_8_ACTIVE_PAGES_BATCH_1_REPORT.md
docs/LOCALISATION_PHASE_9_ACTIVE_PAGES_BATCH_2_REPORT.md
docs/LOCALISATION_PHASE_11_ACTIVE_PAGES_BATCH_4_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Continue from the active backlog.

Do not restart the full localisation project from scratch.

---

# 2. Target Area A — Billing / Invoices / Payments / Cashier

Check active route-linked views and partials under actual project paths, including:

```text
resources/views/billing/
resources/views/invoices/
resources/views/payments/
resources/views/cashier/
resources/views/counter-sale/
resources/js/Pages/Billing/
resources/js/Pages/Invoices/
resources/js/Pages/Payments/
```

depending on actual active route/controller/frontend usage.

Translate visible UI text in:

* invoice list
* invoice create/edit/show
* invoice item sections
* invoice print/PDF labels
* receipt pages
* payment receive screens
* payment history
* payment method labels
* cashier shift pages
* cashier handover pages
* counter-sale pages
* patient statement pages
* sponsor/insurance/corporate billing panels
* refund/credit note/write-off labels
* AR aging labels
* table headers
* filter labels
* modal labels
* action buttons
* empty states
* print/export labels
* inline JavaScript strings
* Inertia/frontend strings

Use or extend:

```text
lang/en/billing.php
lang/fr/billing.php
lang/en/invoices.php
lang/fr/invoices.php
lang/en/payments.php
lang/fr/payments.php
lang/en/reports.php
lang/fr/reports.php
lang/en/common.php
lang/fr/common.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/messages.php
lang/fr/messages.php
```

Rules:

* Payment is not revenue.
* Revenue comes from invoice/billing posting.
* Credit note, write-off, discount, and refund must remain separate concepts.
* Do not hardcode NHIS.
* Do not hardcode sponsors.
* Do not hardcode insurance providers.
* Do not expose financial values without permission.
* Do not move billing/accounting logic into Blade.

---

# 3. Target Area B — Claims / Sponsors / Insurance / Receivables

Check active route-linked views and partials for:

```text
resources/views/claims/
resources/views/sponsors/
resources/views/insurance/
resources/views/billing/
resources/views/reports/
```

depending on actual active route/controller usage.

Translate visible UI text in:

* claims list
* claim creation/preparation pages
* claim item labels
* claim status labels
* claim submission labels
* claim reconciliation labels
* sponsor billing screens
* insurance billing screens
* corporate billing screens
* receivables screens
* AR aging screens
* payer-type labels
* filters
* table headers
* action buttons
* modal labels
* empty states
* print/export labels
* inline JavaScript strings

Use or extend:

```text
lang/en/claims.php
lang/fr/claims.php
lang/en/billing.php
lang/fr/billing.php
lang/en/reports.php
lang/fr/reports.php
lang/en/common.php
lang/fr/common.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/messages.php
lang/fr/messages.php
```

Create `claims.php` only if active claims text is large enough to justify it.

Rules:

* NHIS is just one insurance provider.
* Do not create hardcoded NHIS-only localisation.
* Do not hardcode insurance providers.
* Do not hardcode sponsors.
* Do not change claims workflow logic.
* Do not expose financial values without permission.

---

# 4. Target Area C — Accounting

Check active accounting views and frontend files:

```text
resources/views/accounting/
resources/views/accounting/reports/
resources/views/reports/
resources/js/Pages/Accounting/
```

Translate visible UI text in:

* chart of accounts
* journal entries
* journal entry show/create pages
* trial balance
* general ledger
* cashbook
* profit and loss
* balance sheet
* department accounting reports
* failed accounting postings
* posting status labels
* reversal labels
* accounting filters
* table headers
* print/export labels
* modal labels
* empty states
* inline JavaScript strings

Use or extend:

```text
lang/en/accounting.php
lang/fr/accounting.php
lang/en/reports.php
lang/fr/reports.php
lang/en/common.php
lang/fr/common.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/messages.php
lang/fr/messages.php
```

Rules:

* Official accounting reports must use posted journal entries only.
* Draft/unposted entries must not appear in official reports unless explicitly filtered.
* Do not change accounting calculations.
* Do not change posting/reversal logic.
* Do not expose restricted accounting data without permission.
* Do not calculate reports in Blade.

---

# 5. Target Area D — Reports

Check active report views, print templates, export controls, and report frontend strings:

```text
resources/views/reports/
resources/views/accounting/reports/
resources/views/billing/reports/
resources/js/Pages/Reports/
```

Translate visible UI text in:

* report hub
* management reports
* operational reports
* clinical reports
* billing reports
* claims reports
* accounting reports
* stock reports
* HR/payroll reports
* audit reports
* report filters
* chart labels
* KPI labels
* table headers
* empty states
* print pages
* PDF templates
* CSV/export labels
* generated by/generated at labels
* permission warning labels
* inline JavaScript strings

Use or extend:

```text
lang/en/reports.php
lang/fr/reports.php
lang/en/common.php
lang/fr/common.php
lang/en/statuses.php
lang/fr/statuses.php
```

Rules:

* Reports must respect permissions.
* Export and print must respect permissions.
* Restricted columns must remain redacted.
* Do not introduce a new chart/export package.
* Do not duplicate report services in Blade.

---

# 6. Target Area E — Settings / Admin / Users / Roles / Departments

Check active views:

```text
resources/views/settings/
resources/views/users/
resources/views/roles/
resources/views/admin/
resources/views/departments/
resources/views/modules/
```

Translate visible UI text in:

* organization settings
* invoice settings
* ward settings
* payment method settings
* notification settings
* profile settings
* activity log settings
* log retention settings
* users list/create/edit
* roles/permissions screens
* departments
* modules
* service/catalog admin pages not already completed
* table headers
* form labels
* help text
* modal labels
* action buttons
* empty states
* inline JavaScript strings

Use or extend:

```text
lang/en/settings.php
lang/fr/settings.php
lang/en/users.php
lang/fr/users.php
lang/en/roles.php
lang/fr/roles.php
lang/en/common.php
lang/fr/common.php
lang/en/messages.php
lang/fr/messages.php
```

Rules:

* Do not change permission logic.
* Do not change module enable/disable logic.
* Do not expose restricted admin controls to unauthorized users.

---

# 7. Target Area F — Queues / Wards / Bed Management / Shared Workflow Components

Check active views:

```text
resources/views/queue/
resources/views/queues/
resources/views/wards/
resources/views/beds/
resources/views/admissions/
resources/views/emergency/
resources/views/components/
resources/views/partials/
resources/views/layouts/
resources/views/layout/
```

Translate visible UI text in:

* queue board
* queue management
* visit queue/session panels
* ward list
* ward show
* bed map
* bed management
* admission request panels
* emergency/ward shared components
* shared status cards
* shared action buttons
* shared empty states
* shared modals
* shared alerts
* layout/header/footer/sidebar text not already handled

Use or extend:

```text
lang/en/common.php
lang/fr/common.php
lang/en/menu.php
lang/fr/menu.php
lang/en/admissions.php
lang/fr/admissions.php
lang/en/emergency.php
lang/fr/emergency.php
lang/en/visits.php
lang/fr/visits.php
lang/en/statuses.php
lang/fr/statuses.php
```

Rules:

* SidebarMenuBuilder may intentionally store raw labels before downstream translation.
* If it already translates through `translateLabel()`, document it as audit false positive.
* Do not break permissions, module visibility, route names, or menu hierarchy.

---

# 8. Target Area G — Inertia / Frontend Leftovers

Check frontend files:

```text
resources/js/Pages/
resources/js/pages/
resources/js/components/
resources/js/Components/
resources/js/views/
resources/js/
public/js/
```

Translate visible strings in active frontend pages:

* billing Inertia pages
* visit Inertia pages
* report frontend pages
* shared Vue/React/Inertia components
* DataTables strings
* Select2 placeholders
* date range labels
* modal strings
* chart labels
* alert/confirm/loading/empty-state strings

Use existing localisation bridge:

```text
window.UHMS_I18N
useTrans()
module-level Blade i18n bridge
```

Do not introduce a new frontend i18n package unless explicitly approved.

Document frontend strings that cannot be safely localised yet.

---

# 9. Target Area H — Service Event Titles / Messages

Review remaining audit findings in services such as:

```text
app/Services/AdmissionBedBillingService.php
app/Services/AdmissionService.php
app/Services/BloodCrossmatchService.php
app/Services/BloodDonationService.php
app/Services/BloodIssueService.php
app/Services/ClinicalTaskService.php
app/Services/ConsultationNextPatientService.php
app/Services/EmergencyBedBillingService.php
app/Services/EmergencyCaseService.php
app/Services/EmergencyDispositionService.php
app/Services/EmergencyTriageService.php
app/Services/FinancialReportService.php
app/Services/InvestigationRequestService.php
app/Services/JournalEntryService.php
app/Services/LabService.php
app/Services/OutpatientSessionAutoCloseService.php
app/Services/PharmacyBillingSelectionService.php
app/Services/PharmacyService.php
app/Services/ProcedureReportService.php
app/Services/ProcedureRequestService.php
app/Services/ProcedureScheduleService.php
app/Services/ProcedureWorkflowService.php
app/Services/QueueService.php
app/Services/ServiceRenderingService.php
```

Classify each string as:

```text
A. User-facing timeline/notification/report/API label — translate now
B. Internal audit/event code — do not translate
C. Stored canonical event title — risky, document
D. SQL/internal expression — false positive
E. Already translated downstream — false positive
```

Translate only high-confidence user-facing strings.

Use named placeholders for dynamic values.

Do not change stored business values, workflow status, accounting semantics, or audit semantics.

---

# 10. Localisation Audit Allowlist Cleanup

Improve the localisation audit report usefulness.

Add or update an allowlist for:

```text
demo/template views
routes/web.php.bak-only views
language files themselves
SidebarMenuBuilder raw labels translated downstream
SQL expressions
CSS classes
JS selectors
known format examples
UHMS brand strings
N/A fallback
clinical units
currency symbols
blood group codes
commented-out code
```

Do not hide real active runtime strings.

The goal is to make future audit reports actionable by reducing noise.

---

# 11. Dynamic Labels

Search target files for:

```php
->label()
->statusLabel()
->typeLabel()
getLabelAttribute()
displayName()
humanName()
```

Where displayed to users and safe, replace with:

```php
->translatedLabel()
```

or an existing shared component:

```blade
<x-status-badge>
```

Do not change stored enum values.
Do not change enum constants.
Do not change workflow/status transitions.

---

# 12. Responsive Cleanup While Translating

Fix obvious responsive issues while touching these pages:

```text
table overflow
filter wrapping
action button overflow
modal sizing
tab overflow
long French labels breaking layout
billing/accounting/report tables overflowing on mobile
```

Use Bootstrap 5 utilities only.
Do not introduce Tailwind.

---

# 13. Verification

Run:

```bash
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:list
php artisan view:cache
php artisan view:clear
```

Run language lint:

```bash
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done
```

Run nested EN/FR parity verification.

Run localisation audit again:

```bash
php scripts/localisation-audit.php
```

The raw candidate count may remain high if demo/template files are still included.

But the report must specifically state:

```text
billing/invoice/payment/cashier pages cleaned
claims/sponsors/insurance/receivables pages cleaned
accounting pages cleaned
reports pages cleaned
settings/admin pages cleaned
queues/wards/shared workflow components cleaned
Inertia/frontend leftovers checked
service event titles/messages classified
audit allowlist improved
remaining active pages after this batch
```

---

# 14. Documentation

Create:

```text
docs/LOCALISATION_PHASE_12_ACTIVE_PAGES_BATCH_5_REPORT.md
```

Include:

```text
active routes/views checked in this batch
billing/invoice/payment/cashier files translated
claims/sponsors/insurance files translated
accounting files translated
reports files translated
settings/admin files translated
queues/wards/shared component files translated
frontend/Inertia files translated or deferred
service event strings translated/classified
audit allowlist changes
language files added/updated
JavaScript strings translated
dynamic labels updated
responsive fixes made
EN/FR parity result
PHP lint result
cache/route/view-cache verification result
localisation audit result
remaining active untranslated pages
```

---

# 15. Acceptance Criteria

This phase is complete when:

* active billing/invoice/payment/cashier pages are checked and translated or documented
* active claims/sponsors/insurance/receivables pages are checked and translated or documented
* active accounting pages are checked and translated or documented
* active report pages are checked and translated or documented
* active settings/admin pages are checked and translated or documented
* active queues/wards/shared workflow components are checked and translated or documented
* active frontend/Inertia strings are checked and translated or documented
* service event titles/messages are translated or classified
* audit allowlist is improved without hiding real active strings
* EN/FR parity remains clean
* touched files pass lint
* caches clear
* route list works
* view cache works
* no business logic changed
* no workflows changed
* no duplicate localisation system created
* no new packages introduced

Proceed with UHMS Localisation Phase 12 — Active Pages Translation Batch 5 now.
