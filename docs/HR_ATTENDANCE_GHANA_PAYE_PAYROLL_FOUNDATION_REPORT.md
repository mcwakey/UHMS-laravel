# UHMS HR Attendance + Ghana PAYE Payroll Foundation

## Summary

This phase replaces the legacy hardcoded payroll calculation path with configurable attendance, shift, payroll-run, payslip, and effective-dated Ghana PAYE foundations. Raw attendance is processed and reviewed before payroll draft generation. Payroll then moves through draft, review, approval, payslip generation, release, posting, and payment states.

## Database Changes

- Added `hr_policy_settings`, `hr_shifts`, `employee_shift_assignments`, and `attendance_exceptions`.
- Extended `employee_attendance` with shift, source, exception minutes, and review metadata.
- Extended `employees` with salary, payment, tax residency, relief, SSNIT, pension, allowance, and deduction profile fields.
- Added `payroll_tax_tables`, `payroll_tax_bands`, and `payroll_tax_calculations`.
- Added `payroll_runs` and `payroll_payslips`.
- Extended `payroll_records` with traceable earnings, deductions, chargeable income, and calculation snapshots.

## Models and Services

Added models for HR policies, shifts, assignments, attendance exceptions, payroll runs, payslips, tax tables, tax bands, and tax calculations.

Added:

- `AttendanceProcessingService`
- `LeaveImpactService`
- `PayrollDraftService`
- `PayrollTaxCalculationService`
- `PayrollApprovalService`
- `PayslipGenerationService`
- `PayrollAccountingService`

The legacy `PayrollService` is now a compatibility/query facade and delegates PAYE and draft generation to the new services.

## Commands

- `php artisan hr:process-attendance --date=YYYY-MM-DD`
- `php artisan hr:generate-payroll-draft --period=YYYY-MM`

Both processing paths are idempotent.

## Permissions

Added granular attendance approval, shift, payroll generation/review/approval/posting, payslip, tax-table, employee-tax-profile, and payroll-tax permissions. HR Manager receives operational HR/payroll permissions; Super Admin and Admin retain the complete permission catalogue.

## UI Screens

- Enhanced attendance list with review state and approval action.
- Enhanced payroll list with separate generation, review, and approval actions.
- Added HR configuration screen for policy values, shifts, and effective-dated tax-table visibility.
- Extended employee forms with Ghana tax residency, TIN, relief, and employee SSNIT rate.
- Enhanced payslip with gross taxable income and chargeable income.

## Workflows

Attendance processing resolves employee or department shift assignments, applies grace and overnight-shift rules, calculates late/early/overtime minutes, respects approved leave, marks absences/off days, and creates review exceptions. Payroll refuses missing or unapproved attendance.

Payroll generation creates one run per month and one record per employee. It calculates salary, configured allowances/deductions, absence impact, employee/employer pension, chargeable income, PAYE, total deductions, and net pay. Review is required before approval. Approval generates payslip snapshots; release remains a separate guarded action.

## Ghana PAYE

The seeded GRA table is effective from January 1, 2024:

- Monthly: 490 at 0%, 110 at 5%, 130 at 10%, 3,166.67 at 17.5%, 16,000 at 25%, 30,520 at 30%, excess at 35%.
- Annual: 5,880 at 0%, 1,320 at 5%, 1,560 at 10%, 38,000 at 17.5%, 192,000 at 25%, 366,240 at 30%, excess at 35%.
- Non-resident: configurable flat rate, seeded at 25%.
- Exempt: zero PAYE.

Formula:

`gross taxable income - employee statutory/pre-tax deductions - reliefs = chargeable income`

The progressive engine calculates each band without intermediate currency rounding and rounds the final PAYE amount to two decimals.

Examples:

- GHS 490.00 => GHS 0.00
- GHS 600.00 => GHS 5.50
- GHS 730.00 => GHS 18.50
- GHS 20,296.67 => GHS 4,692.67

Every payroll tax calculation stores the selected tax-table ID, table metadata, inputs, band breakdown, result, and calculation timestamp. Approved payroll retains its record and payslip snapshots when future tables are added.

## Accounting Preparation

`PayrollAccountingService` prepares, but does not post, salary expense, employer pension expense, payroll payable, PAYE payable, and pension payable totals. Automatic posting is intentionally deferred until the existing accounting posting workflow is connected.

## Audit Logging

Attendance approval, payroll draft generation, payroll approval/payment, payslip release, shift creation, and policy changes use `ActivityLogService`. Calculation snapshots provide an additional immutable audit trail.

## Tests and Verification

Added focused tests for:

- Ghana PAYE seed and progressive examples
- non-resident and exempt tax
- attendance timing and idempotency
- approved leave impact
- approved-attendance payroll gating
- stored PAYE snapshots

Commands completed:

- Focused HR and localization tests: 21 passed, 80 assertions
- Full suite: 630 passed, 2,252 assertions
- `php artisan view:cache`: passed
- `php artisan route:list --path=admin/hr`: passed
- `php scripts/localisation-audit.php`: active runtime candidates 0
- `git diff --check`: passed

## Remaining Risks and Next Phase

- Holiday calendars, rotational roster planning, loans, allowance/deduction catalogues, overtime monetary rules, and employee self-service payslip release require the next phase.
- PAYE tables are based on GRA rates effective January 1, 2024. Administrators must create a new effective-dated version when GRA publishes replacement rates.
- Accounting journal posting remains intentionally disabled.
- Recommended next phase: roster calendar and holiday management, payroll component catalogues, loans, self-service released payslips/PDF downloads, and accounting posting approval.
