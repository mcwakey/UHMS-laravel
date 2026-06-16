You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS Accounting Execution Phase I — Statutory Tax Ledgers, Returns & Tax Payment Controls

## Goal

Implement statutory tax accounting ledgers, tax return preparation, tax payment tracking, tax reconciliation, and exportable statutory schedules for UHMS.

This phase must build on:

```text
Phase 0 — Shared posting controls and idempotency
Phase A — Basic-to-Advanced posting bridge
Phase B — Bank accounts and reconciliation
Phase C — Failed posting workbench
Phase D — Subledger reconciliation workbench
Phase E — Payroll accounting posting
Phase E2 — PAYE and Pension / SSNIT settlement
Phase F — Cash flow and exports
Phase G — Budgets and commitments
Phase H — Fixed assets and depreciation
```

Do not create a parallel tax or accounting engine.

Do not hardcode Ghana tax rates into accounting logic.

Tax calculation and tax accounting must remain separate.

---

## 1. Required Context

Read:

```text
docs/ACCOUNTING_GAP_EXECUTION_MASTER_PLAN.md
docs/ACCOUNTING_PHASE_0_SHARED_CONTROLS_AND_READINESS_REPORT.md
docs/ACCOUNTING_PHASE_A_BASIC_TO_ADVANCED_POSTING_BRIDGE_REPORT.md
docs/ACCOUNTING_PHASE_B_BANK_ACCOUNTS_AND_RECONCILIATION_REPORT.md
docs/ACCOUNTING_PHASE_C_FAILED_POSTING_WORKBENCH_REPORT.md
docs/ACCOUNTING_PHASE_D_SUBLEDGER_RECONCILIATION_WORKBENCH_REPORT.md
docs/ACCOUNTING_PHASE_E_PAYROLL_ACCOUNTING_POSTING_REPORT.md
docs/ACCOUNTING_PHASE_E2_STATUTORY_PAYROLL_SETTLEMENT_REPORT.md
docs/ACCOUNTING_PHASE_F_CASH_FLOW_AND_EXPORTS_REPORT.md
docs/ACCOUNTING_PHASE_G_BUDGETS_AND_COMMITMENTS_REPORT.md
docs/ACCOUNTING_PHASE_H_FIXED_ASSETS_AND_DEPRECIATION_REPORT.md
docs/ACCOUNTING_MODULE_SPLIT_AND_GAP_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Current status:

```text
PAYE and pension liabilities are already recognised through payroll posting.
PAYE and pension remittances are already posted through Phase E2.
Bank/cash settlement data exists.
Cash flow exists.
Fixed asset accounting exists.
Tax ledgers and statutory returns are still missing.
```

Important testing instruction:

```text
Do not run the wide full application test suite after this individual phase.
The wide full-suite test is deferred until all accounting-gap implementation phases in this batch are complete.
Run only necessary safety checks: migrations, route list, view cache, localisation audit, permission audit, logs:audit, PHP lint where practical, focused accounting checks where needed, and git diff check.
```

---

## 2. Scope of This Phase

Implement:

```text
tax types
tax registrations
tax account mappings
tax ledger entries
tax return periods
tax returns
tax return lines
tax payment records
tax payment allocations
withholding certificates
PAYE tax ledger integration
Pension / SSNIT ledger integration
withholding tax ledger foundation
VAT/NHIL/GETFund ledger foundation where source data exists
input tax ledger
output tax ledger
tax return preparation
tax return approval
tax return export
tax payment allocation
tax reconciliation
failed posting workbench integration
subledger reconciliation integration
cash flow integration
close readiness integration
permissions
audit logging
localisation
documentation
```

Do not implement:

```text
direct GRA electronic filing API
direct SSNIT API filing
automatic external tax submission
tax rate legal advice
new billing tax calculation engine unless required by existing source data
new payroll PAYE calculation engine
```

---

## 3. Core Rules

Tax accounting must be:

```text
configurable
effective-dated
auditable
permission-aware
module-aware
reconcilable
exportable
settlement-aware
```

Rules:

```text
Do not hardcode tax rates.
Do not mix tax calculation with tax accounting.
Source modules calculate tax amounts.
Accounting records tax payable, tax receivable, tax settlement, and return balances.
Posted tax journals are immutable.
Corrections use reversal, adjustment, or replacement.
Tax returns are versioned and approval-gated.
Tax payments cannot over-allocate unless explicit credit balance handling exists.
```

---

## 4. Separation of Concerns

Tax calculation belongs to source modules:

```text
Payroll calculates PAYE.
Payroll calculates pension / SSNIT deductions.
Billing calculates output tax only if tax-enabled invoices already exist.
Procurement/AP calculates input tax or withholding only if source data exists.
Supplier payment may calculate withholding tax if configured.
```

Tax accounting records:

```text
tax ledger entries
tax returns
tax payable / receivable balances
tax payments
tax payment allocations
tax reconciliation
tax certificates
```

Do not invent tax source amounts where the source module does not currently store them.

---

## 5. Database Tables

Create additive tables:

```text
tax_types
tax_registrations
tax_account_mappings
tax_ledger_entries
tax_return_periods
tax_returns
tax_return_lines
tax_payments
tax_payment_allocations
withholding_certificates
```

Use string statuses with application validation.

Do not use database enums.

Use `DECIMAL(18,2)` for money.

Use `LONGTEXT` for snapshots where needed.

Use explicit short MariaDB-safe index names.

Do not cascade-delete financial history.

---

## 6. tax_types

Fields:

```text
id
code
name
country_code
tax_category
description
is_active
effective_from
effective_to
notes
created_by
updated_by
timestamps
```

Tax categories:

```text
payroll_tax
social_security
withholding_tax
vat
input_tax
output_tax
levy
other
```

Initial configurable tax type examples:

```text
PAYE
Pension / SSNIT
Withholding Tax
VAT Output
VAT Input
NHIL
GETFund Levy
COVID / other levy placeholder if source configuration requires it
```

Do not seed rates unless the project already has verified tax-rate configuration.

---

## 7. tax_registrations

Fields:

```text
id
tax_type_id
registration_number
registration_name
country_code
authority_name
filing_frequency
currency
is_active
effective_from
effective_to
notes
created_by
updated_by
timestamps
```

Filing frequencies:

```text
monthly
quarterly
annual
custom
```

Purpose:

```text
Track facility/company tax registration details without hardcoding one authority into the system.
```

---

## 8. tax_account_mappings

Fields:

```text
id
tax_type_id
mapping_purpose
account_id
effective_from
effective_to
priority
is_active
notes
created_by
updated_by
timestamps
```

Mapping purposes:

```text
payable
receivable
expense
income
settlement_bank
withholding_payable
input_tax_receivable
output_tax_payable
```

Rules:

```text
Mappings are effective-dated.
Missing mappings block posting/return finalisation where accounting impact is required.
Do not pick arbitrary accounts.
Historical mappings remain visible.
```

Use Phase 0 account mappings if cleaner, but avoid duplicate conflicting mapping logic.

---

## 9. tax_ledger_entries

Purpose:

```text
Create a tax subledger independent of the GL but reconciled to GL control accounts.
```

Fields:

```text
id
tax_type_id
tax_registration_id nullable
source_module
source_type
source_id
source_reference
tax_period_start
tax_period_end
tax_date
direction
taxable_amount
tax_amount
settled_amount
outstanding_amount
currency
status
journal_entry_id nullable
settlement_journal_entry_id nullable
metadata_snapshot
created_by
updated_by
timestamps
```

Directions:

```text
payable
receivable
credit
debit
```

Statuses:

```text
draft
recognised
included_in_return
settled
partially_settled
reversed
cancelled
```

Rules:

```text
Tax ledger entries must link to a source record.
Tax ledger entries must not be duplicated for the same source tax event.
Tax ledger entries must reconcile to GL control accounts.
```

---

## 10. tax_return_periods

Fields:

```text
id
tax_type_id
tax_registration_id nullable
period_start
period_end
due_date nullable
status
opened_by
opened_at
closed_by nullable
closed_at nullable
notes
timestamps
```

Statuses:

```text
open
prepared
approved
filed
paid
closed
cancelled
```

---

## 11. tax_returns

Fields:

```text
id
tax_return_period_id
return_number
tax_type_id
tax_registration_id nullable
status
taxable_amount_total
tax_amount_total
adjustment_amount_total
payment_amount_total
outstanding_amount
prepared_by
prepared_at
approved_by nullable
approved_at nullable
filed_by nullable
filed_at nullable
filing_reference nullable
filing_notes nullable
cancelled_by nullable
cancelled_at nullable
cancellation_reason nullable
metadata_snapshot
timestamps
```

Statuses:

```text
draft
prepared
approved
filed
paid
closed
cancelled
superseded
```

Rules:

```text
Prepared return can be reviewed.
Approved return cannot be silently edited.
Filed return requires filing reference or note.
Corrections use amendment/supersession, not destructive edits.
```

---

## 12. tax_return_lines

Fields:

```text
id
tax_return_id
tax_ledger_entry_id nullable
line_type
description
taxable_amount
tax_amount
adjustment_amount
metadata_snapshot
timestamps
```

Line types:

```text
source_tax
adjustment
payment
credit_balance
rounding
```

---

## 13. tax_payments

Fields:

```text
id
payment_reference
tax_type_id
tax_registration_id nullable
payment_date
amount
payment_account_id
tax_account_id
journal_entry_id nullable
reversal_journal_entry_id nullable
status
approved_by nullable
approved_at nullable
posted_by nullable
posted_at nullable
reversed_by nullable
reversed_at nullable
reversal_reason nullable
notes
metadata_snapshot
created_by
updated_by
timestamps
```

Statuses:

```text
draft
approved
posted
allocated
partially_allocated
reversed
cancelled
failed
```

Journal pattern:

```text
Dr Tax Payable
Cr Bank/Cash
```

For recoverable tax credits, use configured receivable/payable accounts.

---

## 14. tax_payment_allocations

Fields:

```text
id
tax_payment_id
tax_return_id nullable
tax_ledger_entry_id nullable
allocated_amount
metadata_snapshot
timestamps
```

Rules:

```text
Allocation cannot exceed payment amount.
Allocation cannot exceed return or ledger outstanding amount unless explicit credit balance is supported.
Allocations are retained after posting.
```

---

## 15. withholding_certificates

Fields:

```text
id
certificate_number
tax_type_id
supplier_id nullable
payer_name nullable
source_module
source_type
source_id
certificate_date
taxable_amount
withheld_amount
status
issued_by nullable
issued_at nullable
cancelled_by nullable
cancelled_at nullable
cancellation_reason nullable
metadata_snapshot
timestamps
```

Statuses:

```text
draft
issued
cancelled
reissued
```

Do not generate certificates from missing source data.

---

## 16. Services To Add

Create:

```text
TaxTypeService
TaxLedgerService
TaxReturnService
TaxPaymentService
TaxReconciliationService
WithholdingCertificateService
TaxExportService
```

Use existing:

```text
JournalEntryService
AccountingPostingService
AccountingPostingAttemptService
AccountingCloseReadinessService
ActivityLogService
AccountingExportService
```

Do not put tax logic in controllers or Blade.

---

## 17. TaxLedgerService

Responsibilities:

```text
create tax ledger entry from recognised source tax event
prevent duplicate source tax event
update outstanding tax amount
reverse tax ledger entry where source is reversed
link to GL journal where applicable
prepare tax ledger summaries
```

Source integration:

```text
payroll PAYE liabilities from Phase E/E2
pension / SSNIT liabilities from Phase E/E2
supplier withholding where source data exists
billing output tax where source data exists
procurement input tax where source data exists
```

If a source does not yet store tax amount fields, mark as unavailable and document.

---

## 18. TaxReturnService

Responsibilities:

```text
open tax return period
prepare return from tax ledger entries
create return lines
calculate totals
approve return
mark return filed with filing reference
cancel/supersede draft or prepared returns
prevent duplicate active return for same tax type and period
```

Return preparation must be repeatable.

Prepared return must use a snapshot so later source changes do not silently alter the approved return.

---

## 19. TaxPaymentService

Responsibilities:

```text
create tax payment
approve payment
post payment journal
allocate payment to return or ledger entries
prevent over-allocation
reverse payment
restore outstanding balances after reversal
create posting attempt
retain accounting errors
```

Posting identity examples:

```text
source_type = tax_payment
posting_type = tax_payment
posting_version = 1
```

Do not duplicate Phase E2 payroll statutory settlement records. Either integrate them into tax ledgers/returns or map them as existing payroll statutory settlements.

---

## 20. TaxReconciliationService

Compare:

```text
tax ledger outstanding
tax return outstanding
posted tax payments
```

against:

```text
tax GL payable / receivable control accounts
```

Domains:

```text
PAYE
Pension / SSNIT
Withholding tax
VAT input
VAT output
Other configured taxes
```

Classify differences:

```text
unposted_tax_event
failed_tax_posting
payment_not_allocated
manual_journal
return_not_prepared
source_data_missing
mapping_issue
unknown_difference
```

Integrate with Phase D subledger reconciliation.

---

## 21. PAYE and Pension / SSNIT Integration

Phase E2 already posts payroll statutory settlements.

For this phase:

```text
create tax ledger entries for PAYE and pension liabilities from posted payroll runs
create tax ledger settlement links from Phase E2 statutory settlement records
allow PAYE and pension return preparation
allow filing reference and filing notes
ensure reconciliation subtracts posted settlements
```

Do not create duplicate payment journals for Phase E2 settlements.

---

## 22. Withholding Tax Integration

If supplier payment or procurement source data already supports withholding:

```text
create withholding tax ledger entries
allow return preparation
allow payment settlement
allow certificate generation
```

If not:

```text
create the configuration and ledger foundation
mark source integration as deferred
document missing source fields
```

Do not fabricate withholding amounts.

---

## 23. VAT / NHIL / GETFund Integration

If invoice/procurement source data already stores applicable tax components:

```text
create output tax ledger entries from invoices
create input tax ledger entries from procurement/AP
prepare return schedules
track payment or credit balance
```

If source data does not yet store tax components:

```text
create configurable tax types and account mappings only
mark ledger automation as not_available
document required future billing/procurement source fields
```

Do not hardcode Ghana VAT/NHIL/GETFund rates.

Do not infer tax amounts from gross totals without explicit tax fields.

---

## 24. UI Screens

Add Advanced Accounting tax screens:

```text
Tax Dashboard
Tax Types
Tax Registrations
Tax Account Mappings
Tax Ledger
Tax Return Periods
Tax Returns
Prepare Tax Return
Approve Tax Return
Mark Filed
Tax Payments
Tax Payment Allocation
Tax Reconciliation
Withholding Certificates
Tax Reports
```

Use Bootstrap 5 and Tabler Icons only.

Do not introduce new frontend frameworks.

---

## 25. Permissions

Add:

```text
accounting.tax_types.view
accounting.tax_types.manage
accounting.tax_registrations.view
accounting.tax_registrations.manage
accounting.tax_ledgers.view
accounting.tax_returns.view
accounting.tax_returns.prepare
accounting.tax_returns.approve
accounting.tax_returns.file
accounting.tax_payments.view
accounting.tax_payments.create
accounting.tax_payments.approve
accounting.tax_payments.post
accounting.tax_payments.reverse
accounting.tax_reconciliation.view
accounting.tax_reconciliation.run
accounting.withholding_certificates.view
accounting.withholding_certificates.issue
accounting.tax_reports.export
```

Suggested defaults:

```text
Accountant:
- view tax types/registrations/ledgers/returns/payments
- prepare returns
- create payments
- run reconciliation

Finance Manager:
- approve returns
- mark filed
- approve/post/reverse payments
- issue withholding certificates
- manage tax configuration

Administrator / Super Admin:
- all
```

Do not grant to broad clinical roles.

---

## 26. Audit Logging

Use `ActivityLogService`.

Audit:

```text
TAX_TYPE_CREATED
TAX_TYPE_UPDATED
TAX_REGISTRATION_CREATED
TAX_REGISTRATION_UPDATED
TAX_LEDGER_ENTRY_CREATED
TAX_LEDGER_ENTRY_REVERSED
TAX_RETURN_PERIOD_OPENED
TAX_RETURN_PREPARED
TAX_RETURN_APPROVED
TAX_RETURN_FILED
TAX_RETURN_CANCELLED
TAX_PAYMENT_CREATED
TAX_PAYMENT_APPROVED
TAX_PAYMENT_POSTED
TAX_PAYMENT_ALLOCATED
TAX_PAYMENT_REVERSED
TAX_RECONCILIATION_RUN
WITHHOLDING_CERTIFICATE_ISSUED
WITHHOLDING_CERTIFICATE_CANCELLED
TAX_REPORT_EXPORTED
```

Run:

```bash
php artisan logs:audit --json
```

Fix new missing/needs-review audit gaps.

---

## 27. Localisation

All new labels must be localised EN/FR.

Use or extend:

```text
lang/en/accounting.php
lang/fr/accounting.php
lang/en/reports.php
lang/fr/reports.php
```

Required keys include:

```text
statutory_taxes
tax_type
tax_types
tax_registration
tax_registrations
tax_ledger
tax_ledger_entries
tax_return
tax_returns
tax_return_period
prepare_tax_return
approve_tax_return
mark_tax_return_filed
filing_reference
tax_payment
tax_payments
tax_payment_allocation
tax_reconciliation
withholding_tax
withholding_certificate
withholding_certificates
input_tax
output_tax
tax_payable
tax_receivable
taxable_amount
tax_amount
settled_amount
outstanding_tax
tax_authority
filing_frequency
return_due_date
tax_report
```

Maintain EN/FR parity.

Run localisation audit scanner:

```bash
php scripts/localisation-audit.php
```

Active runtime candidates must remain:

```text
0
```

---

## 28. Navigation

Add Advanced Accounting navigation:

```text
Tax Accounting
Tax Ledger
Tax Returns
Tax Payments
Tax Reconciliation
Withholding Certificates
```

Routes must remain protected by module middleware and permissions.

---

## 29. Focused Tests To Add

Add focused tests but do not run the wide full suite.

Required coverage:

```text
tax type can be created
tax registration can be created
tax account mapping is effective-dated
PAYE ledger entries can be created from posted payroll liabilities
pension ledger entries can be created from posted payroll liabilities
Phase E2 settlements link without duplicate journals
tax return can be prepared from ledger entries
approved return cannot be edited silently
tax return can be marked filed with reference
tax payment posts balanced journal
tax payment cannot over-allocate
payment reversal restores outstanding tax
withholding certificate cannot issue without source amount
tax reconciliation detects manual journal difference
missing mapping creates controlled failure
permissions protect tax actions
module middleware protects tax routes
audit events are recorded
localisation keys exist
```

Do not run:

```bash
php artisan test
```

during this phase unless explicitly instructed.

The wide full-suite test will be run after all accounting implementation phases in this batch are complete.

---

## 30. Minimal Verification Commands

Run only necessary safety checks:

```bash
php artisan migrate --force
php artisan route:list
php artisan view:cache
php artisan view:clear
php scripts/localisation-audit.php
php artisan logs:audit --json
php artisan permissions:audit --strict
git diff --check
```

Also run PHP lint on changed files if practical:

```bash
find app database routes lang resources/views -name "*.php" -print0 | xargs -0 -n1 php -l
```

Do not run the full application test suite yet.

---

## 31. Documentation

Create:

```text
docs/ACCOUNTING_PHASE_I_STATUTORY_TAX_LEDGERS_AND_RETURNS_REPORT.md
```

Include:

```text
summary
database changes
models added
services added
permissions added
routes/controllers/views added
tax type configuration
tax registration behavior
tax ledger behavior
PAYE/Pension integration
withholding tax integration
VAT/NHIL/GETFund readiness
tax return workflow
tax payment workflow
allocation rules
reconciliation behavior
failed posting integration
cash flow integration
close readiness integration
audit logging
localisation audit result
minimal verification commands run
tests added but not fully executed
known limitations
next recommended phase
```

---

## 32. Acceptance Criteria

Phase I is complete only when:

```text
tax types can be configured
tax registrations can be configured
tax account mappings are effective-dated
PAYE and pension ledger entries can be built from payroll liabilities
Phase E2 settlements are visible in tax ledger/return context without duplicate journals
tax returns can be prepared
tax returns can be approved
tax returns can be marked filed with reference
tax payments can be posted where not already handled by Phase E2
tax payment allocation prevents over-allocation
payment reversal restores outstanding tax
withholding foundation exists
VAT/NHIL/GETFund readiness is honest and non-hardcoded
tax reconciliation exists
close readiness reports tax exceptions
permissions are enforced
module middleware protects routes
ActivityLogService is used
EN/FR localisation parity is maintained
active runtime candidates remain 0
route list works
view cache compiles
logs:audit has no new missing/needs-review gaps
permissions audit is clean
documentation report is created
full test suite is intentionally deferred to the final wide accounting test phase
```

Proceed with Accounting Execution Phase I now.
