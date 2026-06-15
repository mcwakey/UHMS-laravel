# UHMS Accounting Module Split and Gap Report

## Implemented Split

UHMS finance navigation is now divided into three ordered sidebar sections:

### Billing & Collections

- Billing dashboard
- Invoices and counter sales
- Receive payments and payment history
- Credit notes
- Corporate sponsors
- Patient statements
- Discount report
- Accounts receivable aging

This remains part of the core Billing module because patient charging and collection must continue even when either accounting tier is disabled.

### Basic Accounting

Module slug: `accounting_basic`

- Manual income
- Manual expenses
- Daily collection
- Cashier handover
- Operational reconciliation
- Income/expense categories

The module is intended for smaller facilities that need controlled cash records without operating a full general ledger.

### Advanced Accounting

Module slug: `accounting_advanced`

Dependency: `accounting_basic`

- Accounting dashboard
- Chart of accounts
- Manual journals
- General ledger and trial balance
- Cashbook
- Profit and loss
- Balance sheet
- Department revenue and expense reports
- Supplier payables, payments, and aging
- Fiscal years and accounting periods
- Accounting settings

Direct routes are protected with module middleware, not only hidden from the sidebar.

## Existing Accounting Coverage by UHMS Module

| UHMS area | Current accounting coverage |
|---|---|
| Billing and patient payments | Invoice and payment posting services, receivable allocation, reversals, credit notes |
| Insurance, sponsors, corporate clients | Separate receivable control accounts and aging foundation |
| Procurement and suppliers | Supplier payables, payments, statements, aging, and accounting posting |
| Inventory and pharmacy | Inventory valuation, COGS/expense posting, stock adjustment and damage posting |
| Clinical consumables | Consumption can post inventory expense through stock accounting |
| Payroll | Approved payroll journal preparation exists, but automatic posting is intentionally not connected |
| Claims | Receivable linkage exists through invoices; claim remittance and denial accounting need deeper workflows |
| Cashiers | Shift opening, closing, variance, verification, and daily collection are present |

## High-Priority Missing Capabilities

1. **Basic-to-Advanced posting bridge**

   Manual income and expense entries currently remain in the `financial_entries` operational ledger. They do not create balanced journal entries in the advanced general ledger. This is the most important gap because enabling both tiers can produce two financial views that require manual reconciliation.

2. **Bank accounts and bank reconciliation**

   There is no bank-account register, statement import, matching workflow, outstanding cheque/deposit tracking, or formal bank reconciliation statement. The existing Basic Accounting reconciliation is an operational income/expense summary, not bank reconciliation.

3. **Cash flow statement**

   Permission `accounting.reports.cash_flow` exists, but no route, controller action, service, or screen currently implements the report.

4. **Failed posting workbench**

   Failed-posting permissions and dashboard counts exist, but there is no dedicated searchable workbench showing failed source postings, error details, retry history, and resolution status.

5. **Payroll accounting posting**

   Payroll can prepare journal totals, but it does not yet post approved payroll, PAYE payable, pension payable, loans, or salary payment settlement through the accounting services.

6. **Budgeting and commitments**

   There are no annual budgets, departmental budgets, budget-vs-actual reports, purchase commitments, encumbrances, or approval limits tied to budgets.

7. **Fixed assets**

   There is no asset register, capitalization workflow, depreciation, disposal, impairment, asset locations, or custody tracking.

8. **Statutory tax accounting**

   PAYE calculation exists in payroll, but VAT/NHIL/GETFund, withholding tax, tax input/output ledgers, statutory returns, and tax-payment settlement are not implemented.

9. **Dedicated receivables workbench**

   AR aging and receivable models exist, but there is no unified collector workbench for payer statements, promises to pay, disputes, collection notes, write-off queues, and remittance matching.

10. **Claims settlement accounting**

    Claim submission exists, but insurer remittance advice, partial settlement allocation, denial/write-down accounting, resubmission differences, and reconciliation to insurer statements remain incomplete.

## Medium-Priority Missing Capabilities

- Multi-currency transactions and exchange gains/losses
- Cost centers, projects, grants, and donor-fund accounting
- Recurring journals and accrual schedules
- Prepayments and deferred revenue schedules
- Loan accounting integration with payroll
- Formal opening-balance import and migration workflow
- Consolidated branch/facility accounting
- Electronic payment and bank-file generation
- Financial report exports controlled by the existing `accounting.exports` permission
- Dedicated GL/subledger reconciliation dashboard for AR, AP, inventory, payroll, and cash

## Broader UHMS Module Inventory Gaps

The following functional areas are not represented as complete, independently managed modules in the current module catalogue.

### Existing Features Without a Dedicated Module Flag

- **Appointments and scheduling**: appointment workflows exist, but `appointments` is not seeded as a managed module.
- **Theatre and procedures**: substantial procedure/theatre workflows exist, but there is no dedicated `theatre` or `procedures` module toggle.
- **Accounting posting integrations**: billing, receivables, payables, and inventory posting are spread across their parent modules rather than exposed as a separately governed integration module.
- **Cashier operations**: handover and collection controls exist inside Basic Accounting but do not have an independent deployment flag.

### Missing Clinical and Hospital Operations Modules

- Radiology/imaging workflow with modality scheduling, reporting, and PACS/RIS integration
- Mortuary management
- Ambulance and transport dispatch
- CSSD/sterilization and instrument tracking
- Infection prevention and control surveillance
- Dietary, kitchen, and meal-order management
- Housekeeping, linen, and laundry management
- Biomedical equipment maintenance and calibration
- Facility maintenance and work orders
- Occupational health and staff clinic
- Quality assurance, incidents, complaints, and clinical risk management

### Missing Enterprise and Patient-Service Modules

- Fixed assets and depreciation
- Budgeting, grants, projects, and donor-fund accounting
- Contract and tender management
- Advanced staff rostering and workforce planning
- Staff loans, benefits, and performance appraisal
- Patient portal and mobile self-service
- Referral network and external-provider management
- Document management and electronic signatures
- Business intelligence/data warehouse
- Multi-facility consolidation and inter-branch transactions
- Integration hub for PACS, payment gateways, banks, tax filing, and national health platforms

These should not all be built at once. The practical next module candidates are bank reconciliation, fixed assets, budgets, radiology, CSSD, maintenance, and advanced rostering because they connect directly to workflows already present in UHMS.

## Recommended Delivery Order

1. Connect Basic Accounting entries to configurable balanced journal templates.
2. Build bank accounts, statement import, and formal bank reconciliation.
3. Add failed-posting and subledger reconciliation workbenches.
4. Connect approved payroll posting and salary-payment settlement.
5. Add cash flow reporting and accounting exports.
6. Add budgets and commitments.
7. Add fixed assets and depreciation.
8. Add statutory tax ledgers and returns.

## Safety Notes

- Billing remains independent from accounting module toggles.
- Advanced Accounting depends on Basic Accounting at module configuration level.
- Existing permissions remain the source of action-level authorization.
- Module middleware blocks direct access when a tier is disabled.
- No existing accounting posting logic or audit logging was weakened.
