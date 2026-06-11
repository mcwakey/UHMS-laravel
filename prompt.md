Next should be **Reports, Analytics & Export/Print Standardisation**.

Why this next? Because we now have: accounting foundation, billing/accounting postings, AR/AP, stock valuation, department dashboards, responsiveness, localisation. The next logical step is making sure UHMS can produce clean, printable, exportable reports for management, finance, departments, and auditors. Your responsiveness/localisation report already notes print/report templates and module-level translations still need follow-up work. 

You are working on UHMS — Ultimate Hospital Management System.

The following major phases are already implemented or planned:

- Accounting foundation
- Billing accounting posting
- Payments, discounts, credit notes, write-offs, refunds
- Sponsors, insurance, corporate receivables and AR aging
- Procurement, supplier ledger, accounts payable and AP aging
- Stock valuation, COGS, consumables expense and inventory accounting
- Department-type dashboards
- Responsiveness and localisation
- Role-based dashboard polish

Now proceed with:

Reports, Analytics, Export & Print Standardisation

Goal:
Create a clean, consistent, permission-aware, localised, printable, and exportable reporting layer across UHMS.

Reports must support hospital management, clinical departments, billing, accounting, pharmacy, stock, insurance, claims, emergency, admissions, and audit review.

Do not rewrite existing business logic.
Do not create fake report numbers.
Do not duplicate dashboard logic.
Do not bypass existing services.
Do not expose unauthorized clinical, financial, accounting, or stock cost data.
Do not introduce a new UI framework.
Do not introduce a new chart library unless already approved.

Use existing UHMS stack:

- Blade
- Bootstrap 5
- Tabler Icons
- existing Chart.js / ApexCharts if already bundled
- existing UI components
- existing permissions
- existing localisation infrastructure
- existing print layout component where available

---

# 1. Main Objective

Build a full reporting layer for:

1. Clinical activity
2. Patient services
3. Billing and collections
4. Accounting and finance
5. Receivables and payables
6. Stock and procurement
7. Pharmacy
8. Investigations
9. Theatre/procedures
10. Emergency
11. Admissions/wards
12. Insurance/claims
13. Audit/activity logs
14. Management overview

Reports must be:

- filterable
- printable
- exportable where practical
- localised English/French
- permission-aware
- module-aware
- responsive
- accurate
- based on existing source-of-truth services/tables

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
````

Do not put heavy report queries directly inside controllers or Blade views.

Each report should define:

```php
[
    'key' => 'billing.daily_collections',
    'title' => __('reports.billing.daily_collections'),
    'description' => __('reports.billing.daily_collections_description'),
    'permission' => 'reports.billing.daily_collections',
    'module' => 'billing',
    'filters' => [],
    'columns' => [],
    'exportable' => true,
    'printable' => true,
]
```

---

# 3. Report Menu

Add or update Reports menu.

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
└── Custom / Saved Reports
```

Hide unavailable sections if module is disabled or user lacks permission.

---

# 4. Common Report Layout

Every report page should use:

```text
<x-page-header>
Filter section
Summary KPI cards
Report table / chart
Export / print actions
Pagination if needed
```

Use existing components:

```blade
<x-page-header>
<x-filter-bar>
<x-stat-card>
<x-status-badge>
<x-empty-state>
<x-data-table>
<x-print-layout>
```

Report filters should preserve query strings.

---

# 5. Common Filters

Support common filters where relevant:

```text
Date From
Date To
Department
Branch if multi-branch exists
Patient
Visit Type
Payment Type
Insurance Provider
Sponsor
Corporate Client
Supplier
Stock Location
Product Type
Service Type
Status
User / Staff
```

Rules:

* filters must validate safely
* date range must not be unlimited for expensive reports unless paginated/exported
* default date range should be Today or Current Month depending report
* preserve filters during pagination/export/print

---

# 6. Export Requirements

Support exports where practical:

```text
CSV
Excel if existing package supports it
PDF/Print
```

If Excel package is not installed, use CSV first.

Do not add new package unless approved.

Export must:

* respect filters
* respect permissions
* respect locale for headings
* include generated by
* include generated at
* include hospital identity
* not expose unauthorized fields

---

# 7. Print Requirements

Standardise print views.

Use:

```blade
<x-print-layout>
```

where available.

Print must show:

```text
Hospital name/logo
Report title
Filter summary
Generated by
Generated at
Page date/time
Signature area if needed
```

Print must hide:

```text
Sidebar
Topbar
Action buttons
Search inputs
Pagination controls
Debug info
```

Print must be:

* black-on-white
* readable
* table-safe
* not broken on A4
* translated where labels are system labels

---

# 8. Localisation Requirements

Create/update:

```text
lang/en/reports.php
lang/fr/reports.php
```

Translate:

```text
report titles
descriptions
filter labels
column headings
summary labels
export buttons
print buttons
empty states
generated by
generated at
totals
```

Do not translate:

```text
patient names
doctor names
supplier names
product names
service names unless system-defined
clinical free text
audit event codes
permission names
route names
```

---

# 9. Clinical Reports

Implement or improve:

```text
Patient Visit Summary
Daily Visit Register
Consultation Activity Report
Diagnosis Report
Prescription Report
Clinical Follow-up Report
Deceased Patients Report
```

Filters:

```text
Date range
Doctor
Department
Visit type
Diagnosis
Patient status
```

Permissions:

```text
reports.clinical.view
reports.clinical.export
```

Clinical reports must not expose sensitive clinical details to unauthorized users.

---

# 10. Emergency Reports

Implement or improve:

```text
Emergency Case Summary
Emergency Triage Report
Emergency Admissions Report
Emergency Disposition Report
Emergency Consumables Report
Emergency Billing Report
```

KPIs:

```text
Total emergency cases
Red/orange triage cases
Admitted from emergency
Transferred cases
Completed/discharged cases
Emergency revenue
Emergency consumables
```

Emergency must remain based on Visit workflow, not a separate patient lifecycle.

---

# 11. Admission / Ward Reports

Implement or improve:

```text
Admission Register
Discharge Register
Bed Occupancy Report
Ward Census
MAR Overdue Report
Ward Consumables Report
Admission Billing Report
```

KPIs:

```text
Current admissions
Discharges
Average length of stay
Bed occupancy rate
Pending discharge clearance
```

---

# 12. Pharmacy Reports

Implement or improve:

```text
Prescription Report
Dispensing Report
Drug Sales Report
Partial Dispensing Report
Out-of-Stock Report
Low Stock Report
Expired Drug Report
Pharmacy Revenue Report
Pharmacy COGS Report if accounting enabled
```

Rules:

* do not show stock cost to unauthorized users
* dispensing and MAR administration must remain distinct
* pharmacy revenue comes from billing
* pharmacy cost comes from inventory accounting

---

# 13. Investigation / Lab Reports

Implement or improve:

```text
Investigation Request Report
Pending Results Report
Verified Results Report
Rejected/Cancelled Tests Report
Urgent Investigation Report
Lab Consumables Usage Report
Lab Revenue Report
```

Filters:

```text
Date range
Department
Test/service
Status
Requested by
Verified by
```

---

# 14. Theatre / Procedure Reports

Implement or improve:

```text
Procedure Request Report
Scheduled Procedures Report
Completed Procedures Report
Cancelled Procedures Report
Theatre Utilisation Report
Procedure Consumables Report
Procedure Revenue Report
```

---

# 15. Billing Reports

Implement or improve:

```text
Daily Collections
Cashier Shift Report
Invoice Register
Unpaid Invoices
Partially Paid Invoices
Discount Report
Credit Note Report
Write-off Report
Refund Report
Payment Method Summary
Revenue by Department
Revenue by Service Type
Revenue by Payer Type
```

Rules:

* payment is not revenue
* revenue comes from invoice/billing posting
* discounts, credit notes, write-offs, refunds must be shown separately
* financial values require permission

---

# 16. Insurance / Claims Reports

Implement or improve:

```text
Claims Prepared Report
Claims Submitted Report
Claims Approved Report
Claims Rejected Report
Claims Paid Report
Claim Aging Report
Insurance Receivables Report
CCC / Verification Report
```

Rules:

* do not hardcode NHIS
* NHIS is just one insurance provider/type
* use insurance provider filters
* show rejected claims needing action

---

# 17. Receivables Reports

Implement or improve:

```text
AR Aging
Patient Receivables
Insurance Receivables
Sponsor Receivables
Corporate Receivables
Receivable Payments
Written-off Receivables
Overdue Receivables
```

Reports must reconcile with invoice receivables.

Buckets:

```text
Current / Not Due
0–30
31–60
61–90
91–120
120+
```

---

# 18. Payables Reports

Implement or improve:

```text
AP Aging
Supplier Payables
Supplier Statement
Supplier Payments
Supplier Returns
Outstanding Supplier Balances
```

Reports must reconcile with supplier ledger/payables.

---

# 19. Accounting Reports

Implement or improve:

```text
General Ledger
Trial Balance
Profit & Loss
Balance Sheet
Cashbook
Journal Entry Report
Failed Accounting Postings
Revenue Report
Expense Report
Inventory Valuation Reconciliation
```

Rules:

* use posted journal entries only
* exclude drafts from official reports
* reversals should naturally offset originals
* do not expose accounting reports to unauthorized users

---

# 20. Stock / Procurement Reports

Implement or improve:

```text
Stock Balance Report
Stock Movement Report
Inventory Valuation Report
Low Stock Report
Out-of-Stock Report
Expired/Damaged Stock Report
Stock Transfer Report
Purchase Order Report
Goods Receiving Report
Purchase Return Report
Supplier Ledger Report
```

Rules:

* current stock must come from stock balances/services
* stock movement is operational source
* stock valuation uses accounting/valuation service
* do not mix incompatible ledgers blindly
* do not show cost unless authorized

---

# 21. Audit / Activity Reports

Implement or improve:

```text
Activity Log Report
User Action Report
High-Risk Action Report
Financial Action Report
Clinical Action Report
Permission Change Report
Login / Security Report if available
```

Filters:

```text
Date range
User
Module
Action
Patient
Visit
Source type
Risk level
```

Rules:

* use ActivityLogService data
* patient-context logs should link to patient timeline where permitted
* global logs remain facility/system logs
* do not log report views unless policy requires it

---

# 22. Management Overview Reports

Implement:

```text
Hospital Activity Summary
Department Performance
Revenue Summary
Clinical Workload
Financial Position Summary
Stock Risk Summary
Receivables/Payables Summary
Claims Summary
Emergency Summary
Admission Summary
```

Management reports must aggregate data and avoid exposing detailed sensitive clinical data unless the user has permission.

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

If not practical now, document as TODO.

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

Do not rely only on hiding UI.

---

# 25. Performance Rules

Reports must be safe on large data.

Rules:

* paginate large result sets
* use aggregate queries
* avoid N+1
* use date range defaults
* avoid loading all records before export if huge
* use chunking/cursor for large CSV export if practical
* cache expensive summary reports if safe
* never calculate stock from all movements for every request if stock_balances exists

---

# 26. Manual Verification

Manual verification required:

1. Open Reports menu as admin.
2. Confirm report sections appear based on permissions.
3. Confirm unauthorized user cannot access restricted reports.
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
23. Confirm financial/stock cost data is hidden from unauthorized users.
24. Confirm performance is acceptable.
25. Confirm existing workflows still work.

---

# 27. Documentation

Create:

```text
docs/REPORTS_ANALYTICS_EXPORT_PRINT_REPORT.md
```

Include:

* reports implemented
* report services added
* permissions added
* filters supported
* export behavior
* print behavior
* localisation keys added
* performance notes
* manual verification completed
* remaining TODOs

---

# 28. Acceptance Criteria

This phase is complete when:

* report registry exists
* major report groups exist
* reports are permission-aware
* reports are module-aware
* reports support filters
* reports support print
* reports support export where practical
* report labels are translated
* financial/clinical/stock sensitive data is protected
* dashboards link to real reports
* reports do not duplicate business logic
* large reports are paginated or safely exported
* documentation is updated
* manual verification is documented

---

# 29. Important Rules

Do not introduce Tailwind.
Do not introduce a new chart library.
Do not create fake numbers.
Do not duplicate accounting calculations.
Do not duplicate stock calculations.
Do not expose unauthorized clinical details.
Do not expose unauthorized financial values.
Do not expose stock cost to unauthorized users.
Do not hardcode NHIS.
Do not translate internal codes.
Do not bypass existing services.
Do not bypass permissions.
Do not break existing workflows.

Proceed with Reports, Analytics, Export & Print Standardisation now.

```
```
