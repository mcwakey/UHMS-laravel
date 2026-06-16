# Accounting Execution Phase I: Statutory Tax Accounting

## Scope

Phase I adds the statutory tax accounting layer. It does not calculate taxes for payroll, billing or procurement. Source modules remain responsible for tax bases and calculations. Accounting receives immutable tax events, prepares returns, records payments and reconciles balances.

## Delivered

- Added tax accounting schema:
  - `tax_types`
  - `tax_registrations`
  - `tax_account_mappings`
  - `tax_ledger_entries`
  - `tax_return_periods`
  - `tax_returns`
  - `tax_return_lines`
  - `tax_payments`
  - `tax_payment_allocations`
  - `withholding_certificates`
- Added chart accounts for:
  - Input Tax Receivable
  - Withholding Tax Payable
  - Output Tax Payable
- Added optional `tax_accounting` module dependent on `accounting_advanced`.
- Added Tax Accounting workbench under Accounting.
- Added tax ledger and tax return services.
- Added default Ghana-oriented tax types/mappings:
  - PAYE
  - Pension / SSNIT
  - Withholding Tax
  - Output VAT / Levy
  - Input VAT / Levy
- Payroll posting now syncs PAYE and pension source events to the tax ledger.
- Statutory payroll settlement now reduces open tax ledger balances.
- Tax returns are prepared from ledger entries for a period.
- Tax payments post `Dr Tax Payable / Cr Bank`.
- Payment allocations cannot exceed payment unallocated balance or return balance due.

## Verification

Covered by `tests/Feature/Accounting/TaxAccountingPhaseITest.php`.
