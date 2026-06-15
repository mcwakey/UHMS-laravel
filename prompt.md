You are working on UHMS — Ultimate Hospital Management System.

# UHMS HR Automation Phase 1 — Attendance Processing, Ghana PAYE Payroll & Payslip Foundation

## Goal

Build the HR automation foundation for UHMS covering:

```text id="h0jqah"
Attendance processing
Shift/roster support
Leave impact on attendance
Payroll draft generation
Ghana PAYE salary tax deduction
Payslip generation
Payroll approval workflow
Future accounting integration
```

Do not build this as hardcoded hospital-specific logic.

Attendance, payroll, deductions, PAYE, approvals, and payslips must be configurable, auditable, permission-aware, and safe for future changes.

---

# 1. Core HR Flow

The system must follow this workflow:

```text id="29138k"
Raw attendance
→ Attendance processing
→ Attendance review
→ Approved attendance
→ Payroll draft generation
→ PAYE and deductions calculation
→ Payroll review
→ Payroll approval
→ Payslip generation
→ Payslip release
→ Accounting posting later
```

Do not allow raw attendance to directly create final payroll or released payslips.

Payroll must be generated as a draft first.

---

# 2. HR Policy Configuration

Create configurable HR policy settings for:

```text id="2zaavt"
attendance grace period
late arrival rule
early exit rule
absence rule
overtime rule
night shift rule
weekend rule
public holiday rule
leave pay rule
payroll period
salary structure
allowance rules
deduction rules
PAYE tax table
approval workflow
```

Use database configuration.

Do not hardcode hospital-specific attendance or payroll policies.

---

# 3. Shift / Roster Support

Add or improve shift configuration.

A shift should support:

```text id="h4v7km"
name
start_time
end_time
grace_minutes
break_minutes
is_night_shift
is_active
```

Employees should be assignable to:

```text id="4wwh7p"
fixed shifts
rotational shifts
department shifts
individual shift overrides
```

Do not overbuild advanced roster planning yet, but make the schema ready.

---

# 4. Attendance Records

Attendance records should track:

```text id="cn8t5i"
employee_id
attendance_date
shift_id
clock_in_time
clock_out_time
source
status
late_minutes
early_exit_minutes
overtime_minutes
review_status
reviewed_by
reviewed_at
notes
```

Attendance sources should support now or later:

```text id="ou44a1"
manual entry
biometric import
QR check-in
mobile/geolocation check-in
shift roster clock-in/out
```

Build manual attendance first, but keep the model extensible for biometric/QR/mobile later.

Attendance statuses:

```text id="7zuxp8"
present
absent
late
half_day
on_leave
sick_leave
holiday
weekend
off_day
```

Review statuses:

```text id="8qedg6"
pending
approved
rejected
adjusted
```

---

# 5. Daily Attendance Processing Command

Create an artisan command:

```bash id="ofh5jp"
php artisan hr:process-attendance
```

The command should:

```text id="0eink8"
check expected employees for the day
compare shift schedule with clock-in/out
mark absences
calculate late minutes
calculate early exit minutes
calculate overtime minutes
respect approved leave
respect holidays/off days where configured
create attendance exceptions for HR review
```

The command must be safe to run multiple times.

No duplicate attendance rows.

Use update-or-create logic where appropriate.

---

# 6. Leave Impact

Approved leave must affect attendance.

If an employee has approved leave for a date:

```text id="dxy0xm"
do not mark absent
mark attendance as on_leave or sick_leave depending on leave type
apply paid/unpaid rules later in payroll
```

Attendance only records facts.

Payroll calculates money.

Do not deduct salary directly inside attendance processing.

---

# 7. Employee Payroll Profile

Add or improve employee payroll profile fields:

```text id="72avj0"
basic_salary
salary_type
payment_method
bank_name
bank_account_number
mobile_money_number
tax_identification_number
tax_residency_status
paye_exempt
tax_relief_amount
ssnit_number
employee_ssnit_rate
employer_ssnit_rate
pension_scheme
default_allowances
default_deductions
```

At minimum, support tax residency:

```text id="4al49u"
resident
non_resident
exempt
```

Rules:

```text id="kgtsgm"
resident → Ghana progressive PAYE table
non_resident → configurable flat tax rate, default 25%
exempt → PAYE 0
```

Do not hardcode special staff categories directly into payroll logic.

---

# 8. Ghana PAYE Tax Engine

Add Ghana PAYE salary tax deduction support.

PAYE must be:

```text id="yeh2m2"
configurable
effective-dated
auditable
versioned
safe for future GRA changes
```

Do not hardcode PAYE rates inside payroll calculation logic.

Create a tax-table engine that calculates PAYE from payroll chargeable income using progressive tax bands.

---

# 9. Ghana PAYE Initial Table

Seed the current Ghana resident PAYE table as an effective-dated tax table.

Support monthly and annual tax tables, but monthly payroll should normally use monthly bands.

Initial monthly Ghana PAYE bands:

```text id="65r79t"
First 490.00              → 0%
Next 110.00               → 5%
Next 130.00               → 10%
Next 3,166.67             → 17.5%
Next 16,000.00            → 25%
Next 30,520.00            → 30%
Exceeding remaining value → 35%
```

Equivalent annual bands:

```text id="7d0509"
First 5,880.00             → 0%
Next 1,320.00              → 5%
Next 1,560.00              → 10%
Next 38,000.00             → 17.5%
Next 192,000.00            → 25%
Next 366,240.00            → 30%
Exceeding 600,000.00       → 35%
```

Default currency:

```text id="dizgok"
GHS
```

Do not assume these rates will never change.

When rates change, the system must allow a new effective-dated PAYE table without altering old approved payrolls.

---

# 10. Payroll Tax Database Design

Add payroll tax configuration tables if they do not already exist.

Recommended tables:

```text id="ibphy5"
payroll_tax_tables
payroll_tax_bands
payroll_tax_calculations
```

## payroll_tax_tables

Fields:

```text id="rkgth1"
id
country_code
name
tax_type
period_basis
resident_type
currency
effective_from
effective_to
is_active
notes
created_by
updated_by
timestamps
```

Example:

```text id="vjxppz"
country_code = GH
name = Ghana PAYE Resident Monthly
tax_type = paye
period_basis = monthly
resident_type = resident
currency = GHS
effective_from = 2024-01-01
effective_to = null
is_active = true
```

## payroll_tax_bands

Fields:

```text id="p0c77r"
id
payroll_tax_table_id
band_order
band_label
lower_bound
upper_bound
band_amount
rate_percent
fixed_tax_amount
cumulative_tax
is_excess_band
timestamps
```

## payroll_tax_calculations

Store the PAYE calculation result per payroll/payslip.

Fields:

```text id="pdq3ce"
id
payroll_run_id
payroll_payslip_id
employee_id
payroll_tax_table_id
gross_taxable_income
pre_tax_deductions
reliefs_total
chargeable_income
tax_amount
calculation_snapshot_json
calculated_at
calculated_by
timestamps
```

If JSON columns are unsafe for MariaDB compatibility, use LONGTEXT with JSON-encoded content.

Use decimal columns for money and rates.

Avoid database enum if the project normally avoids enums for compatibility.

---

# 11. PAYE Calculation Formula

PAYE calculation must follow this flow:

```text id="n1k2vk"
Gross taxable income
- allowable pre-tax deductions
- statutory employee deductions where configured
- approved reliefs where configured
= chargeable income
→ progressive PAYE calculation
= PAYE deduction
```

For each PAYE band:

```text id="8zzraf"
taxable_in_band = min(remaining_income, band_amount)
tax_for_band = taxable_in_band * rate_percent
remaining_income -= taxable_in_band
```

For excess band:

```text id="j3osvw"
tax_for_excess = remaining_income * excess_rate
```

Do not round each band in a way that creates material differences.

Round final PAYE amount using the project money rounding policy.

Return a detailed breakdown:

```text id="l7oztc"
band label
taxable amount in band
rate
tax amount
cumulative tax
```

---

# 12. Payroll Services

Do not put business logic in controllers or Blade.

Create or extend services such as:

```text id="dacvtt"
AttendanceProcessingService
LeaveImpactService
PayrollDraftService
PayrollTaxCalculationService
PayslipGenerationService
PayrollApprovalService
PayrollAccountingService
```

Controllers should call services.

---

# 13. Payroll Draft Generation

Create an artisan command:

```bash id="w2h254"
php artisan hr:generate-payroll-draft --period=YYYY-MM
```

The command should generate payroll drafts based on:

```text id="sbucw5"
employee salary
approved attendance
approved leave
overtime
allowances
deductions
loans
taxable allowances
non-taxable allowances
employee SSNIT/pension if configured
PAYE
unpaid absences
late penalties
```

Payroll statuses:

```text id="yd3mv8"
draft
under_review
approved
posted
paid
cancelled
```

Do not generate final/released payslips until payroll is approved.

---

# 14. Payroll Calculation Fields

Payroll draft should calculate and store:

```text id="nc1m7i"
basic_salary
taxable_allowances
non_taxable_allowances
gross_pay
gross_taxable_income
employee_pension_deduction
other_pre_tax_deductions
tax_reliefs
chargeable_income
paye_tax
other_deductions
total_deductions
net_pay
```

Do not subtract PAYE before calculating chargeable income.

PAYE is the output tax deduction.

Payroll formulas must be traceable.

---

# 15. Payroll Approval Workflow

Add approval workflow:

```text id="8p4auh"
draft generated by HR/payroll officer
reviewed by HR manager
approved by finance/admin
posted to accounting
released to staff
```

Only users with proper permission should approve payroll.

Do not weaken permissions.

---

# 16. Payslip Generation

Payslips should be generated from approved payroll records.

Payslip should include:

```text id="prn13y"
employee details
department
position
payroll period
basic salary
allowances
overtime
gross pay
gross taxable income
PAYE deduction
other deductions
tax
pension/social security
net pay
payment method
approval status
generated date
```

Support:

```text id="h7tcqk"
PDF
print view
staff portal view
email later
```

Do not expose payslips to staff until payroll is approved/released.

Staff should only see their own released payslips.

---

# 17. Payslip PAYE Breakdown

Payslip should show:

```text id="4g8bz3"
Gross taxable income
Chargeable income
PAYE deduction
Other statutory deductions
Total deductions
Net pay
```

Optional detail view:

```text id="kd49lm"
PAYE band breakdown
tax table version
calculation date
```

Do not expose other employees’ tax details to unauthorized users.

---

# 18. Accounting Integration Preparation

Do not fully post accounting automatically unless the existing accounting services are ready.

Prepare payroll records for future journal posting:

```text id="v1wc19"
Dr Salaries Expense
Dr Employer Pension Expense
Cr Payroll Payable
Cr PAYE Tax Payable
Cr Pension Payable
Cr Staff Loan Receivable
```

When salaries are paid later:

```text id="1eavh8"
Dr Payroll Payable
Cr Cash/Bank
```

Create a service skeleton if appropriate:

```text id="u33jog"
PayrollAccountingService
```

Do not post automatically without payroll approval.

Do not bypass existing accounting services.

---

# 19. Permissions

Add permissions:

```text id="7iv2pq"
hr.attendance.view
hr.attendance.manage
hr.attendance.approve
hr.shifts.view
hr.shifts.manage
hr.payroll.view
hr.payroll.generate
hr.payroll.review
hr.payroll.approve
hr.payroll.post
hr.payslips.view
hr.payslips.release
hr.payslips.download
hr.tax_tables.view
hr.tax_tables.manage
hr.employee_tax_profile.view
hr.employee_tax_profile.manage
hr.payroll_tax.view
hr.payroll_tax.recalculate
```

Only authorized HR/payroll/finance users should manage payroll, PAYE tables, and employee tax profiles.

---

# 20. UI Screens

Add or improve screens for:

```text id="p9dj4n"
HR policy settings
shift settings
employee shift assignment
attendance list
attendance review
attendance exceptions
payroll periods
payroll draft
payroll approval
payslip view
payroll tax tables
PAYE bands
employee tax profile
payroll tax breakdown
```

Use Bootstrap 5 and Tabler Icons only.

Do not introduce Tailwind or new frontend frameworks.

---

# 21. Audit Logging

Use `ActivityLogService`.

Audit:

```text id="ocn75t"
attendance created
attendance adjusted
attendance approved
attendance rejected
payroll draft generated
payroll reviewed
payroll approved
payroll cancelled
payslip generated
payslip released
PAYE table created
PAYE table updated
PAYE table activated
PAYE table deactivated
PAYE band changed
employee tax profile changed
payroll PAYE calculated
payroll PAYE recalculated
```

Do not bypass audit logging.

---

# 22. Localisation

All new UI labels must be localised EN/FR.

Use or create:

```text id="bcqitc"
lang/en/hr.php
lang/fr/hr.php
lang/en/payroll.php
lang/fr/payroll.php
```

Add keys for attendance, payroll, payslip, and PAYE labels.

Required examples:

```text id="xmz9u4"
attendance
shift
clock_in
clock_out
late_minutes
overtime_minutes
review_status
payroll
payroll_draft
payslip
gross_pay
net_pay
paye
paye_tax
tax_table
tax_tables
tax_band
tax_bands
chargeable_income
gross_taxable_income
pre_tax_deductions
tax_reliefs
tax_amount
resident
non_resident
tax_exempt
effective_from
effective_to
band_order
band_label
lower_bound
upper_bound
rate_percent
cumulative_tax
excess_band
tax_breakdown
employee_tax_profile
tax_identification_number
```

Maintain EN/FR parity.

Run:

```bash id="8xudsn"
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
```

Active runtime candidates must remain:

```text id="pdbk3x"
0
```

---

# 23. Tests

Add tests for attendance:

```text id="gcwwrh"
attendance processing marks present
attendance processing marks absent
late minutes are calculated
early exit minutes are calculated
overtime minutes are calculated
approved leave prevents absence
attendance must be reviewed before payroll use
unauthorized user cannot approve attendance
```

Add tests for payroll:

```text id="ylhurq"
payroll draft generation uses approved attendance
unapproved attendance is not used for final payroll
payroll calculates gross pay
payroll calculates deductions
payslip cannot be released before payroll approval
approved payroll can generate payslips
unauthorised users cannot approve payroll
```

Add tests for Ghana PAYE:

```text id="9j8emp"
Ghana resident monthly PAYE table is seeded
PAYE bands are ordered correctly
PAYE calculation returns 0 for income within tax-free band
PAYE calculation applies multiple bands progressively
PAYE calculation applies 35% excess band
non-resident flat tax can be calculated
tax-exempt employee returns PAYE 0
PAYE calculation snapshot is stored on payroll draft
approved payroll keeps original tax table snapshot
new effective-dated PAYE table does not alter old approved payroll
employee without permission cannot manage tax tables
payslip shows PAYE deduction
localisation remains locked
```

Example PAYE tests:

```text id="z4q6i2"
Chargeable income: 490 → PAYE 0
Chargeable income: 600 → PAYE 5.50
Chargeable income: 730 → PAYE 18.50
```

Add at least one larger salary test crossing several bands.

---

# 24. Safety Rules

Do not weaken:

```text id="hgwnzv"
permissions
policies
gates
middleware
clinical confidentiality
financial visibility
audit logging
payroll approval checks
```

Do not bypass:

```text id="z1aywn"
ActivityLogService
Payroll services
Attendance services
Accounting services
```

Do not move business logic into Blade.

Do not hardcode Ghana PAYE inside controllers.

Do not hardcode hospital-specific HR policies.

Do not expose salary, tax, or payslip information to unauthorized users.

Do not release payslips before payroll approval.

---

# 25. Verification Commands

Run:

```bash id="xqd34r"
php artisan route:list
php artisan view:cache
php artisan view:clear
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
php artisan test
git diff --check
```

If assets are touched:

```bash id="4le25c"
npm run build
```

---

# 26. Documentation

Create:

```text id="ta01zc"
docs/HR_ATTENDANCE_GHANA_PAYE_PAYROLL_FOUNDATION_REPORT.md
```

Include:

```text id="luu073"
summary
database changes
models added/changed
services added
commands added
permissions added
UI screens added
attendance workflow
shift/roster workflow
leave impact workflow
payroll draft workflow
Ghana PAYE table seeded
PAYE calculation formula
sample PAYE calculations
employee tax profile
payslip workflow
approval workflow
accounting integration preparation
audit logging
tests added
commands run
localisation audit result
remaining risks
next recommended phase
```

---

# 27. Acceptance Criteria

This phase is complete only when:

```text id="qd7z9q"
HR policies can be configured
shifts can be configured
attendance can be processed safely
attendance can be reviewed/approved
approved leave affects attendance
payroll drafts can be generated from approved attendance
Ghana PAYE table is configurable and effective-dated
resident PAYE uses progressive bands
non-resident flat tax is configurable
tax-exempt employees calculate PAYE as 0
PAYE calculation stores detailed snapshot
payroll draft includes PAYE deduction
payslips can be generated from approved payroll
payslips are not released before approval
payslip displays PAYE deduction
permissions are enforced
ActivityLogService is used
EN/FR localisation parity passes
active runtime candidates remain 0
route list works
view cache compiles
tests pass or failures are documented
documentation report is created
```

Proceed with UHMS HR Attendance + Ghana PAYE Payroll Foundation now.
