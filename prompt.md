Next is **Accounting Phase 4: Sponsors, Insurance, Corporate Receivables & AR Aging**.

This phase answers the big question:

```text
Who owes the hospital money — patient, insurance, sponsor, or corporate client — and for how long?
```

You are working on UHMS — Ultimate Hospital Management System.

Accounting Phase 1 Foundation is complete.
Accounting Phase 2 Billing → Accounting Posting is complete.
Accounting Phase 3 Payments, Discounts, Credit Notes, Write-offs, Refunds & Reversals is complete.

Now proceed with Accounting Phase 4:

Sponsors, Insurance, Corporate Receivables & AR Aging

Goal:
Implement payer-based receivables management so UHMS can clearly track what is owed by:

1. Patients
2. Insurance providers
3. Sponsors
4. Corporate clients

This phase must also introduce AR Aging so the hospital can know who owes money, how much, and for how long.

Do not replace existing invoices.
Do not replace existing insurance workflows.
Do not hardcode NHIS.
Do not treat sponsors as discounts.
Do not treat insurance as sponsors.
Do not treat write-offs as payments.
Do not write the full automated test suite yet. Full tests will be written after the full accounting implementation is complete.

Important rule:

Invoice = operational billing document.
Receivable = who owes the hospital.
Journal entry = accounting impact.
AR aging = reporting/control over unpaid receivables.

---

# 1. Main Objective

Implement payer-based receivables.

The system must support:

- patient receivables
- insurance receivables
- sponsor receivables
- corporate receivables
- split invoice responsibility
- sponsor allocation
- insurance allocation
- corporate allocation
- payer-specific balances
- payer-specific payments
- AR aging by payer
- AR aging by invoice
- AR aging summary reports
- activity logs
- accounting posting integration

---

# 2. Business Meaning

Use these meanings consistently:

```text
Patient receivable = amount patient personally owes
Insurance receivable = amount insurance provider owes
Sponsor receivable = amount sponsor owes
Corporate receivable = amount company/employer/client owes
````

Sponsor is not a discount.

Insurance is not a sponsor.

Corporate billing is not a write-off.

---

# 3. Payer Responsibility Rule

An invoice can have one or more responsible payers.

Example:

Invoice total = GHS 1,000

```text
Patient responsible:   GHS 200
Insurance responsible: GHS 500
Sponsor responsible:   GHS 300
```

The invoice total remains GHS 1,000.

The system should track balances separately:

```text
Patient balance:   GHS 200
Insurance balance: GHS 500
Sponsor balance:   GHS 300
```

Payments should reduce the correct payer balance.

---

# 4. Required Data Model

Create or update a payer receivables structure.

Recommended table:

```text
invoice_receivables
```

Fields:

```text
id
invoice_id
patient_id nullable
visit_id nullable

payer_type enum: patient, insurance, sponsor, corporate
payer_id nullable

original_amount decimal
allocated_amount decimal
paid_amount decimal default 0
discount_amount decimal default 0
credit_note_amount decimal default 0
write_off_amount decimal default 0
refund_amount decimal default 0
balance decimal

aging_start_date date
due_date nullable
status enum: pending, partially_paid, paid, overdue, written_off, cancelled

insurance_provider_id nullable
sponsor_id nullable
corporate_client_id nullable

claim_id nullable
sponsor_authorization_id nullable
corporate_account_id nullable

journal_entry_id nullable
accounting_status nullable
accounting_posted_at nullable
accounting_error nullable

created_by nullable
updated_by nullable
timestamps
```

Rules:

* total allocated receivables must not exceed invoice net amount
* payer balances must reconcile with invoice balance
* write-off reduces collectible balance
* refund increases balance or reduces deposit liability depending source
* cancelled receivables must not appear as collectible AR
* paid receivables should not appear as outstanding AR

Use existing tables if similar structure already exists.

Do not create duplicate payer tracking if one already exists.

---

# 5. Sponsor Module

If sponsor module does not exist, create a simple one.

Recommended tables:

```text
sponsors
sponsor_authorizations
```

## sponsors

Fields:

```text
id
name
type enum: individual, company, ngo, church, government, charity, hospital_welfare, other
contact_person nullable
phone nullable
email nullable
address nullable
status active/inactive
created_by nullable
timestamps
```

## sponsor_authorizations

Fields:

```text
id
sponsor_id
patient_id
visit_id nullable
invoice_id nullable
authorization_number nullable
approved_amount decimal nullable
coverage_percentage decimal nullable
valid_from nullable
valid_to nullable
notes nullable
status pending/approved/rejected/expired/cancelled
approved_by nullable
approved_at nullable
created_by nullable
timestamps
```

Sponsor behavior:

* sponsor can cover full or partial invoice
* sponsor allocation creates sponsor receivable
* sponsor payment reduces sponsor receivable
* sponsor does not reduce invoice total
* sponsor payment is not discount
* sponsor unpaid balance appears in sponsor AR aging

---

# 6. Insurance Receivables

Use existing insurance provider and claims workflow.

Do not hardcode NHIS.

When insurance is responsible for invoice amount:

* create insurance receivable
* link to insurance provider
* link to claim if claim exists
* track submitted/approved/paid/rejected statuses
* insurance payment reduces insurance receivable
* rejected claim amount should be reassigned to patient or written off depending workflow

Insurance receivable statuses:

```text
pending_claim
claim_submitted
approved
partially_paid
paid
rejected
appealed
written_off
cancelled
```

If claims module already handles statuses, do not duplicate statuses unnecessarily; map them cleanly.

---

# 7. Corporate Receivables

Add support for corporate clients if not already present.

Possible corporate payers:

* employer
* company
* school
* organization
* contracted client

Recommended table if missing:

```text
corporate_clients
```

Fields:

```text
id
name
contact_person nullable
phone nullable
email nullable
address nullable
billing_terms nullable
credit_limit nullable
status active/inactive
created_by nullable
timestamps
```

Corporate receivable behavior:

* corporate client can be responsible for patient invoice
* corporate payment reduces corporate receivable
* corporate balance appears in corporate AR aging
* corporate statement can be generated later

Do not confuse corporate receivable with sponsor unless project intentionally treats them together.

---

# 8. Allocation Workflow

Create service:

```text
ReceivableAllocationService
```

Methods:

```php
allocatePatientResponsibility(Invoice $invoice, float $amount, array $meta = []): InvoiceReceivable
allocateInsuranceResponsibility(Invoice $invoice, InsuranceProvider $provider, float $amount, array $meta = []): InvoiceReceivable
allocateSponsorResponsibility(Invoice $invoice, Sponsor $sponsor, float $amount, array $meta = []): InvoiceReceivable
allocateCorporateResponsibility(Invoice $invoice, CorporateClient $client, float $amount, array $meta = []): InvoiceReceivable
reallocateResponsibility(InvoiceReceivable $from, string $toPayerType, ?Model $payer, float $amount, string $reason): void
```

Rules:

* allocations must not exceed invoice net total
* reallocation must be auditable
* reallocation may create accounting journal entries
* reallocation must not duplicate receivable rows
* allocation should update invoice receivable summary

---

# 9. Accounting Posting for Receivable Allocation

When invoice is first posted:

Option A — if payer split is known at invoice finalization:

```text
Dr Patient Receivables
Dr Insurance Receivables
Dr Sponsor Receivables
Dr Corporate Receivables
Cr Revenue
```

Option B — if invoice was first posted to patient receivable, then later reallocated:

```text
Dr Insurance Receivables
Cr Patient Receivables
```

or:

```text
Dr Sponsor Receivables
Cr Patient Receivables
```

or:

```text
Dr Corporate Receivables
Cr Patient Receivables
```

Use the option that matches existing Phase 2 implementation.

Do not double-recognize revenue.

Revenue should not be credited again during reallocation.

---

# 10. Payments by Payer

Payment must identify payer type.

Payment payer types:

```text
patient
insurance
sponsor
corporate
```

When payer pays:

## Patient payment

```text
Dr Cash / Bank / Mobile Money
Cr Patient Receivables
```

## Insurance payment

```text
Dr Bank
Cr Insurance Receivables
```

## Sponsor payment

```text
Dr Bank / Cash
Cr Sponsor Receivables
```

## Corporate payment

```text
Dr Bank
Cr Corporate Receivables
```

Do not let sponsor payment reduce patient receivable unless there is an allocation/reallocation record.

Do not let insurance payment reduce sponsor receivable.

---

# 11. AR Aging

Implement AR Aging report.

AR aging should be payer-based.

Required reports:

```text
Overall AR Aging
Patient AR Aging
Insurance AR Aging
Sponsor AR Aging
Corporate AR Aging
```

Aging buckets:

```text
Current / Not Due
0–30 days
31–60 days
61–90 days
91–120 days
120+ days
```

or if project uses simpler buckets:

```text
0–30
31–60
61–90
90+
```

Each report should show:

```text
Payer
Invoice Number
Patient
Visit
Original Amount
Paid
Adjustments
Balance
Aging Start Date
Due Date
Age Days
Bucket
Status
```

Summary should show totals by bucket.

Example:

```text
0–30:   GHS 4,000
31–60:  GHS 2,500
61–90:  GHS 900
90+:    GHS 7,200
Total:  GHS 14,600
```

---

# 12. Aging Start Date Rules

Use consistent aging start dates.

Recommended:

```text
Patient receivable = invoice finalized date or discharge date
Insurance receivable = claim submission date or insurance allocation date
Sponsor receivable = sponsor approval/allocation date
Corporate receivable = invoice issue/allocation date
```

If discharge/finalization matters for emergency/admission:

* emergency/admission patient balance should age from invoice finalization or discharge
* not necessarily from the emergency case start date

Document the rule clearly.

---

# 13. Due Dates

Support due dates.

Default due dates may come from:

* invoice due date
* insurance provider payment terms
* sponsor authorization terms
* corporate client billing terms
* system default receivable terms

If no due date exists, aging can still use aging_start_date.

Status overdue should be based on due_date when available.

---

# 14. Invoice Detail UI

Update invoice detail page.

Add section:

```text
Payer Responsibility / Receivables
```

Show:

```text
Payer Type
Payer Name
Allocated Amount
Paid Amount
Adjustments
Balance
Status
Aging
Due Date
```

Example:

```text
Patient: Emmanuel Wakey     GHS 200 allocated · GHS 100 paid · GHS 100 balance
Insurance: NHIS             GHS 500 allocated · GHS 0 paid · GHS 500 balance
Sponsor: Church Welfare     GHS 300 allocated · GHS 300 paid · GHS 0 balance
```

Also show journal entry links if posted.

---

# 15. Sponsor UI

Add UI under Billing or Accounts & Finance:

```text
Sponsors
├── Sponsor List
├── Create Sponsor
├── Sponsor Details
├── Sponsor Authorizations
├── Sponsor Receivables
├── Sponsor Payments
└── Sponsor AR Aging
```

Sponsor detail should show:

* sponsor profile
* active authorizations
* patients covered
* invoices allocated
* payments received
* outstanding balance
* AR aging

---

# 16. Insurance AR UI

Insurance provider detail should show:

* invoices/claims pending
* approved claims
* rejected claims
* payments received
* outstanding receivable
* AR aging

If claims already has this, integrate rather than duplicate.

---

# 17. Corporate AR UI

Corporate client detail should show:

* patients/invoices covered
* payment terms
* payments
* outstanding balance
* AR aging
* statement later if supported

Corporate statements can be documented as future TODO if not implemented now.

---

# 18. Services to Create / Update

Create or update:

```text
InvoiceReceivableService
ReceivableAllocationService
SponsorService
SponsorAuthorizationService
SponsorPaymentService
InsuranceReceivableService
CorporateClientService
CorporateReceivableService
ARAgingService
ReceivablePaymentService
ReceivableAccountingPostingService
InvoiceBalanceService
```

Use existing services if already present.

Controllers must remain thin.

Accounting logic must remain inside services.

---

# 19. Activity Logs

Use ActivityLogService.

Log:

```text
RECEIVABLE_ALLOCATED
RECEIVABLE_REALLOCATED
PATIENT_RECEIVABLE_CREATED
INSURANCE_RECEIVABLE_CREATED
SPONSOR_RECEIVABLE_CREATED
CORPORATE_RECEIVABLE_CREATED

SPONSOR_CREATED
SPONSOR_UPDATED
SPONSOR_AUTHORIZATION_APPROVED
SPONSOR_PAYMENT_RECORDED

INSURANCE_RECEIVABLE_UPDATED
INSURANCE_PAYMENT_RECORDED

CORPORATE_CLIENT_CREATED
CORPORATE_PAYMENT_RECORDED

AR_AGING_REPORT_VIEWED
```

Context:

```text
invoice_id
invoice_receivable_id
patient_id
visit_id
payer_type
payer_id
insurance_provider_id
sponsor_id
corporate_client_id
amount
old_values
new_values
journal_entry_id
```

Patient-related allocation should appear on patient timeline when appropriate.

Generic sponsor/corporate setup logs should remain global.

---

# 20. Permissions

Add or verify:

```text
receivables.view
receivables.allocate
receivables.reallocate
receivables.payment.record
receivables.write_off

sponsors.view
sponsors.create
sponsors.edit
sponsors.authorize
sponsors.payment.record

corporate_clients.view
corporate_clients.create
corporate_clients.edit
corporate_clients.payment.record

reports.ar_aging.view
reports.ar_aging.patient
reports.ar_aging.insurance
reports.ar_aging.sponsor
reports.ar_aging.corporate
```

Only authorized billing/finance/admin users should allocate receivables or record payer payments.

---

# 21. Manual Verification Strategy

Do not write the full automated test suite yet.

Full accounting tests will be written after all accounting phases are implemented.

For this phase, provide manual verification notes.

Manual verification required:

1. Create invoice with patient responsibility.
2. Confirm patient receivable is created.
3. Allocate part of invoice to insurance.
4. Confirm insurance receivable is created.
5. Allocate part of invoice to sponsor.
6. Confirm sponsor receivable is created.
7. Allocate part of invoice to corporate client if supported.
8. Confirm corporate receivable is created.
9. Confirm payer allocations do not exceed invoice net total.
10. Record patient payment and confirm patient receivable reduces.
11. Record insurance payment and confirm insurance receivable reduces.
12. Record sponsor payment and confirm sponsor receivable reduces.
13. Record corporate payment and confirm corporate receivable reduces.
14. Confirm payments post to correct accounting receivable account.
15. Confirm revenue is not double-posted during reallocation.
16. Confirm invoice detail shows payer responsibility section.
17. Open AR Aging report and confirm balances appear in correct buckets.
18. Confirm paid receivables do not appear as outstanding.
19. Confirm written-off receivables do not appear as collectible AR.
20. Confirm Trial Balance remains balanced.
21. Confirm General Ledger shows receivable movements.
22. Confirm logs:audit Stage-2 gate remains green.
23. Confirm emergency/admission and insurance claims workflows still work.

Do not skip validation, permissions, accounting posting, activity logs, or idempotency because tests are deferred.

---

# 22. Documentation

Create:

```text
docs/ACCOUNTING_PHASE_4_RECEIVABLES_AR_AGING_REPORT.md
```

Include:

* data model implemented
* sponsor behavior
* insurance receivable behavior
* corporate receivable behavior
* payer allocation rules
* accounting posting rules
* payment rules by payer type
* AR aging buckets
* aging start date rules
* UI changes
* permissions
* activity logs
* manual verification completed
* tests deferred list
* known risks/TODOs
* next phase recommendation

---

# 23. Acceptance Criteria

Phase 4 is complete when:

* invoice receivables exist by payer type
* sponsor allocation works
* insurance allocation works
* corporate allocation works if supported
* payer-specific payments reduce correct receivable
* payer reallocations do not duplicate revenue
* invoice detail shows payer responsibility
* AR Aging report works
* patient/insurance/sponsor/corporate AR can be separated
* Trial Balance remains balanced
* General Ledger shows receivable activity
* activity logs are written
* logs:audit Stage-2 gate remains green
* manual verification is documented
* full automated tests remain deferred until final accounting implementation pass

---

# 24. Important Rules

Do not treat sponsor as discount.
Do not treat insurance as sponsor.
Do not treat corporate receivable as write-off.
Do not treat write-off as payment.
Do not double-recognize revenue during receivable reallocation.
Do not allow payer allocations to exceed invoice net amount.
Do not let one payer’s payment reduce another payer’s balance.
Do not remove existing claims workflow.
Do not hardcode NHIS.
Do not bypass accounting services.
Do not bypass ActivityLogService.
Do not enable full automated tests yet.

Proceed with Accounting Phase 4: Sponsors, Insurance, Corporate Receivables & AR Aging now.

