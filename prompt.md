You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no docs/UHMS_IMPLEMENTATION_SKILL.md file in this project.
Do not try to read it.
Follow the instructions in this prompt directly.

We have completed the localisation and responsiveness track:

* Localisation Phase 1: base EN/FR infrastructure
* Localisation Phase 2: high-traffic screen translation, reports hub, controller flash messages
* Localisation Phase 3: reports, analytics, export and print translation layer
* Localisation Phase 4: remaining operational screens bulk translation
* Localisation Phase 5: final QA, JavaScript, emails, controller messages and dynamic label cleanup

Current localisation status:

* Zero hardcoded controller flash message literals remain
* EN/FR parity remains clean across all 25 language modules
* All new consultation, lab and theatre workflow flash keys exist in both EN and FR
* PHP lint passes for language files
* Caches have been cleared
* Bootstrap 5 + Tabler Icons remain the UI standard
* No parallel localisation system was created

Now proceed with:

# Reports, Analytics, Export & Print Standardisation

## Goal

Create a clean, consistent, permission-aware, localised, printable, and exportable reporting layer across UHMS.

Reports must support:

1. Hospital management
2. Clinical departments
3. Patient and visit activity
4. Emergency
5. Admissions and wards
6. Pharmacy
7. Investigations and laboratory
8. Theatre and procedures
9. Billing and collections
10. Insurance and claims
11. Receivables
12. Payables
13. Accounting and finance
14. Stock and procurement
15. Audit and activity review

Do not rewrite existing business logic.
Do not create fake report numbers.
Do not duplicate dashboard logic.
Do not bypass existing services.
Do not expose unauthorized clinical, financial, accounting, or stock cost data.
Do not introduce a new UI framework.
Do not introduce a new chart library unless already present and approved.
Do not introduce a new export package unless already present and approved.

Use existing UHMS stack:

* Laravel
* Blade
* Bootstrap 5
* Tabler Icons
* existing Chart.js / ApexCharts if already bundled
* existing UI components
* existing permissions
* existing localisation infrastructure
* existing `lang/en/reports.php`
* existing `lang/fr/reports.php`
* existing print layout component where available
* existing report hub if already present

---

# 1. Audit First

Before implementing, audit the current project and identify what already exists.

Check for:

* existing report controllers
* existing report services
* existing report routes
* existing Reports Hub
* existing `ReportRegistryService`
* existing operational reports
* existing dashboard analytics logic
* existing export helpers
* existing print layouts
* existing permission names
* existing localisation keys
* existing accounting reports
* existing billing reports
* existing stock reports
* existing pharmacy reports
* existing audit/activity log views
* existing Chart.js or ApexCharts usage
* existing CSV/PDF/Excel export packages

Do not duplicate existing report systems.
Reuse and improve what exists.

After the audit, continue implementation using the existing architecture.

---

# 2. Report Architecture

Create or update reporting services.

Recommended services:

```php
ReportRegistryService
ReportPermissionService
ReportFilterService
ReportExportService
ReportPrintService
ClinicalReportService
BillingReportService
AccountingReportService
ReceivablesReportService
PayablesReportService
StockReportService
PharmacyReportService
InvestigationReportService
TheatreReportService
EmergencyReportService
AdmissionReportService
ClaimsReportService
AuditReportService
ManagementReportService
```

If some services already exist, extend them instead of duplicating them.

Do not put heavy report queries directly inside controllers or Blade views.

Each report should define metadata similar to:

```php
[
    'key' => 'billing.daily_collections',
    'title' => __('reports.billing.daily_collections'),
    'description' => __('reports.billing.daily_collections_description'),
    'permission' => 'reports.billing.view',
    'module' => 'billing',
    'filters' => [],
    'columns' => [],
    'exportable' => true,
    'printable' => true,
]
```

Controllers should stay thin.
Services should handle report data.
Views should only render data.

---

# 3. Report Menu

Add or update the Reports menu.

Recommended structure:

```text
Reports
├── Management Overview
├── Clinical Reports
├── Patient / Visit Reports
├── Emergency Reports
├── Admission / Ward Reports
├── Pharmacy Reports
├── Investigation Reports
├── Theatre / Procedure Reports
├── Billing Reports
├── Insurance / Claims Reports
├── Accounting Reports
├── Receivables Reports
├── Payables Reports
├── Stock / Procurement Reports
├── Audit Logs
└── Saved Reports / Custom Reports
```

Hide unavailable report sections if:

* the module is disabled
* the user lacks permission
* the feature does not exist yet

Backend authorization must still be enforced.
Do not rely only on hiding UI.

---

# 4. Common Report Layout

Every report page should use existing UHMS layout conventions.

Use or create reusable Blade components only if consistent with the current project:

```blade
<x-page-header>
<x-filter-bar>
<x-stat-card>
<x-status-badge>
<x-empty-state>
<x-data-table>
<x-print-layout>
```

Every standard report should contain:

* page header
* filter section
* summary KPI cards where useful
* table and/or chart
* export action
* print action
* pagination where needed
* empty state

Report filters must preserve query strings during:

* pagination
* export
* print
* language switching where practical

Use existing translation keys from:

* `lang/en/reports.php`
* `lang/fr/reports.php`
* `lang/en/common.php`
* `lang/fr/common.php`

Add new keys only when necessary and maintain EN/FR parity.

---

# 5. Common Filters

Support common filters where relevant:

* Date From
* Date To
* Department
* Branch if multi-branch exists
* Patient
* Visit Type
* Payment Type
* Insurance Provider
* Sponsor
* Corporate Client
* Supplier
* Stock Location
* Product Type
* Service Type
* Status
* User / Staff
* Doctor
* Requested By
* Verified By
* Approved By
* Posted By

Rules:

* filters must validate safely
* default date range should be Today or Current Month depending on report
* expensive reports must not run unlimited by default
* preserve filters during pagination/export/print
* avoid loading all records into memory
* never trust query filters without validation

---

# 6. Export Requirements

Support exports where practical:

* CSV
* Excel only if an existing package already supports it
* PDF/Print using existing print/PDF tools

If no Excel package exists, implement CSV first.

Do not install a new package unless explicitly approved.

Exports must:

* respect filters
* respect permissions
* respect locale for headings
* include generated by
* include generated at
* include hospital identity
* avoid unauthorized fields
* avoid clinical sensitive details unless permitted
* avoid stock cost unless permitted
* avoid financial values unless permitted

Large CSV exports should use chunking/cursor where practical.

---

# 7. Print Requirements

Standardise print views.

Use:

```blade
<x-print-layout>
```

where available.

Print pages must show:

* hospital name/logo
* report title
* filter summary
* generated by
* generated at
* page date/time
* signature area if needed

Print must hide:

* sidebar
* topbar
* action buttons
* search inputs
* pagination controls
* debug info

Print must be:

* black-on-white
* readable
* table-safe
* A4-friendly
* translated where labels are system labels

Do not break existing invoice, receipt, prescription, lab, claims, or accounting print views.

---

# 8. Localisation Requirements

Use the existing completed localisation system.

Create or update only when necessary:

```text
lang/en/reports.php
lang/fr/reports.php
```

Translate any new:

* report titles
* report descriptions
* filter labels
* column headings
* summary labels
* export buttons
* print buttons
* empty states
* generated by
* generated at
* totals
* subtotals
* chart labels

Do not translate:

* patient names
* doctor names
* supplier names
* product names
* service names unless system-defined
* clinical free text
* audit event codes
* permission names
* route names
* database values unless mapped through status labels

Maintain EN/FR parity.
Run parity verification after changes.

---

# 9. Clinical Reports

Implement or improve:

* Patient Visit Summary
* Daily Visit Register
* Consultation Activity Report
* Diagnosis Report
* Prescription Report
* Clinical Follow-up Report
* Deceased Patients Report

Filters:

* date range
* doctor
* department
* visit type
* diagnosis
* patient status

Permissions:

* reports.clinical.view
* reports.clinical.export
* reports.clinical_sensitive.view

Clinical reports must not expose sensitive clinical details to unauthorized users.

---

# 10. Emergency Reports

Implement or improve:

* Emergency Case Summary
* Emergency Triage Report
* Emergency Admissions Report
* Emergency Disposition Report
* Emergency Consumables Report
* Emergency Billing Report

KPIs:

* total emergency cases
* red/orange triage cases
* admitted from emergency
* transferred cases
* completed/discharged cases
* emergency revenue
* emergency consumables

Rules:

* emergency must remain based on the Visit workflow
* do not create a separate patient lifecycle
* do not hardcode emergency services
* emergency billing must use existing billing/services logic

---

# 11. Admission / Ward Reports

Implement or improve:

* Admission Register
* Discharge Register
* Bed Occupancy Report
* Ward Census
* MAR Overdue Report
* Ward Consumables Report
* Admission Billing Report

KPIs:

* current admissions
* discharges
* average length of stay
* bed occupancy rate
* pending discharge clearance

---

# 12. Pharmacy Reports

Implement or improve:

* Prescription Report
* Dispensing Report
* Drug Sales Report
* Partial Dispensing Report
* Out-of-Stock Report
* Low Stock Report
* Expired Drug Report
* Pharmacy Revenue Report
* Pharmacy COGS Report if accounting is enabled

Rules:

* do not show stock cost to unauthorized users
* dispensing and MAR administration must remain distinct
* pharmacy revenue comes from billing
* pharmacy cost comes from inventory accounting
* do not calculate cost directly in Blade views

---

# 13. Investigation / Lab Reports

Implement or improve:

* Investigation Request Report
* Pending Results Report
* Verified Results Report
* Rejected / Cancelled Tests Report
* Urgent Investigation Report
* Lab Consumables Usage Report
* Lab Revenue Report

Filters:

* date range
* department
* test/service
* status
* requested by
* verified by

---

# 14. Theatre / Procedure Reports

Implement or improve:

* Procedure Request Report
* Scheduled Procedures Report
* Completed Procedures Report
* Cancelled Procedures Report
* Theatre Utilisation Report
* Procedure Consumables Report
* Procedure Revenue Report

---

# 15. Billing Reports

Implement or improve:

* Daily Collections
* Cashier Shift Report
* Invoice Register
* Unpaid Invoices
* Partially Paid Invoices
* Discount Report
* Credit Note Report
* Write-off Report
* Refund Report
* Payment Method Summary
* Revenue by Department
* Revenue by Service Type
* Revenue by Payer Type

Rules:

* payment is not revenue
* revenue comes from invoice/billing posting
* discounts, credit notes, write-offs, and refunds must be shown separately
* financial values require permission
* do not duplicate accounting calculations

---

# 16. Insurance / Claims Reports

Implement or improve:

* Claims Prepared Report
* Claims Submitted Report
* Claims Approved Report
* Claims Rejected Report
* Claims Paid Report
* Claim Aging Report
* Insurance Receivables Report
* CCC / Verification Report

Rules:

* do not hardcode NHIS
* NHIS is only one insurance provider/type
* use insurance provider filters
* show rejected claims needing action
* do not create NHIS-only architecture

---

# 17. Receivables Reports

Implement or improve:

* AR Aging
* Patient Receivables
* Insurance Receivables
* Sponsor Receivables
* Corporate Receivables
* Receivable Payments
* Written-off Receivables
* Overdue Receivables

Reports must reconcile with invoice receivables.

Aging buckets:

* Current / Not Due
* 0–30
* 31–60
* 61–90
* 91–120
* 120+

Rules:

* use existing receivables/accounting services
* do not invent balances
* do not double-count sponsor or insurance balances

---

# 18. Payables Reports

Implement or improve:

* AP Aging
* Supplier Payables
* Supplier Statement
* Supplier Payments
* Supplier Returns
* Outstanding Supplier Balances

Reports must reconcile with supplier ledger/payables.

---

# 19. Accounting Reports

Implement or improve:

* General Ledger
* Trial Balance
* Profit & Loss
* Balance Sheet
* Cashbook
* Journal Entry Report
* Failed Accounting Postings
* Revenue Report
* Expense Report
* Inventory Valuation Reconciliation

Rules:

* use posted journal entries only
* exclude drafts from official reports
* reversals should naturally offset originals
* do not expose accounting reports to unauthorized users
* do not duplicate journal calculations in controllers or views

---

# 20. Stock / Procurement Reports

Implement or improve:

* Stock Balance Report
* Stock Movement Report
* Inventory Valuation Report
* Low Stock Report
* Out-of-Stock Report
* Expired / Damaged Stock Report
* Stock Transfer Report
* Purchase Order Report
* Goods Receiving Report
* Purchase Return Report
* Supplier Ledger Report

Rules:

* current stock must come from stock balances/services
* stock movement is operational source
* stock valuation uses accounting/valuation service
* do not mix incompatible ledgers blindly
* do not show cost unless authorized
* products are physical stock items
* services are billable activities

---

# 21. Audit / Activity Reports

Implement or improve:

* Activity Log Report
* User Action Report
* High-Risk Action Report
* Financial Action Report
* Clinical Action Report
* Permission Change Report
* Login / Security Report if available

Filters:

* date range
* user
* module
* action
* patient
* visit
* source type
* risk level

Rules:

* use ActivityLogService data
* patient-context logs should link to patient timeline where permitted
* global logs remain facility/system logs
* do not bypass ActivityLogService
* do not log report views unless policy requires it

---

# 22. Management Overview Reports

Implement:

* Hospital Activity Summary
* Department Performance
* Revenue Summary
* Clinical Workload
* Financial Position Summary
* Stock Risk Summary
* Receivables / Payables Summary
* Claims Summary
* Emergency Summary
* Admission Summary

Rules:

* management reports should aggregate data
* avoid exposing detailed sensitive clinical data unless permitted
* dashboard cards should link to real reports where practical
* do not duplicate dashboard logic unnecessarily

---

# 23. Saved Reports

If practical, add saved report filters.

Recommended table:

```text
saved_reports
```

Fields:

```text
id
user_id
report_key
name
filters json
is_shared boolean default false
created_at
updated_at
```

If not practical in this phase, document it clearly as TODO in the report documentation.

Do not add this table if the current project structure makes it risky. Documentation as TODO is acceptable.

---

# 24. Permissions

Add or verify permissions:

```text
reports.view
reports.export
reports.print
reports.clinical.view
reports.emergency.view
reports.admissions.view
reports.pharmacy.view
reports.investigations.view
reports.procedures.view
reports.billing.view
reports.claims.view
reports.receivables.view
reports.payables.view
reports.accounting.view
reports.stock.view
reports.audit.view
reports.management.view
reports.financial_values.view
reports.stock_cost.view
reports.clinical_sensitive.view
```

Backend must enforce permissions.
Do not rely only on hiding menu items or buttons.

---

# 25. Performance Rules

Reports must be safe on large data.

Rules:

* paginate large result sets
* use aggregate queries
* avoid N+1 queries
* use date range defaults
* avoid loading all records before export if huge
* use chunking/cursor for large CSV export where practical
* cache expensive summary reports only if safe
* never calculate stock from all movements on every request if stock_balances exists
* add indexes only when clearly needed and safe
* avoid destructive migrations

---

# 26. Manual Verification

Manual verification required:

1. Open Reports menu as admin.
2. Confirm report sections appear based on permissions.
3. Confirm unauthorized user cannot access restricted reports by URL.
4. Run Daily Collections report.
5. Run Invoice Register report.
6. Run AR Aging report.
7. Run AP Aging report.
8. Run Trial Balance.
9. Run General Ledger.
10. Run Stock Balance report.
11. Run Inventory Valuation report.
12. Run Pharmacy Dispensing report.
13. Run Investigation Pending Results report.
14. Run Emergency Summary report.
15. Run Admission Register.
16. Run Claims Rejected report.
17. Run Activity Log report.
18. Confirm filters work.
19. Confirm pagination preserves filters.
20. Confirm CSV/export respects filters.
21. Confirm print layout is clean.
22. Switch to French and confirm report labels translate.
23. Confirm financial values are hidden from unauthorized users.
24. Confirm stock cost is hidden from unauthorized users.
25. Confirm sensitive clinical details are hidden from unauthorized users.
26. Confirm performance is acceptable.
27. Confirm existing workflows still work.

---

# 27. Verification Commands

Run:

```bash
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:list
php artisan test
```

Run PHP syntax checks where relevant:

```bash
find app -name "*.php" -print0 | xargs -0 -n1 php -l
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done
```

Run EN/FR parity verification after adding or changing language keys.

If full tests are too broad, run relevant tests and document manual checks.

---

# 28. Documentation

Create:

```text
docs/REPORTS_ANALYTICS_EXPORT_PRINT_REPORT.md
```

Include:

* audit findings
* reports implemented
* report services added or updated
* controllers added or updated
* routes added or updated
* views added or updated
* permissions added or verified
* filters supported
* export behavior
* print behavior
* localisation keys added
* performance notes
* manual verification completed
* remaining TODOs

---

# 29. Acceptance Criteria

This phase is complete when:

* report registry exists or existing report structure is standardised
* major report groups exist
* reports are permission-aware
* reports are module-aware
* reports support filters
* reports support print
* reports support export where practical
* report labels are translated
* financial data is protected
* clinical sensitive data is protected
* stock cost data is protected
* dashboards link to real reports where practical
* reports do not duplicate business logic
* reports do not create fake numbers
* reports use existing services/source-of-truth data
* large reports are paginated or safely exported
* documentation is updated
* manual verification is documented

---

# 30. Important UHMS Rules

Do not introduce Tailwind.
Do not introduce a new chart library.
Do not create fake numbers.
Do not duplicate accounting calculations.
Do not duplicate stock calculations.
Do not expose unauthorized clinical details.
Do not expose unauthorized financial values.
Do not expose stock cost to unauthorized users.
Do not hardcode NHIS.
Do not hardcode sponsors.
Do not hardcode insurance providers.
Do not hardcode emergency services.
Do not translate internal codes.
Do not bypass existing services.
Do not bypass permissions.
Do not bypass ActivityLogService.
Do not break existing workflows.
Do not create parallel systems.
Do not create duplicate report modules if existing ones can be reused.
Do not move business logic into Blade views.
Use Bootstrap 5 and Tabler Icons only.

Proceed with Reports, Analytics, Export & Print Standardisation now.
