# UHMS Finance Department Workspace — Billing, Cashiering, Receivables, Accounting Control, and `/finance/*` Route Architecture

Implement a dedicated **Finance Department Workspace** for UHMS.

This workspace is for Finance, Accounts, Billing, Cashier, Insurance, Claims, and authorized financial-control staff managing the hospital’s financial operations from billing and payment collection through accounts receivable, insurance claims, sponsor accounts, refunds, credit notes, cashier reconciliation, journal review, financial reporting, and audit oversight.

This implementation should follow the same department-aware workspace architecture already established for:

* Doctors and consultation departments
* Records
* Nursing OPD
* Emergency
* Inpatient
* Investigations
* Pharmacy
* Stores
* Maternity

The configured department type is:

```php
DepartmentType::FINANCE
```

The browser workspace must use:

```text
/finance/*
```

The objective is that when a logged-in user’s active department has:

```php
DepartmentType::FINANCE
```

the user should experience UHMS as a dedicated Finance application with:

* A Finance-specific sidebar menu
* A Finance operations dashboard
* Finance-specific breadcrumbs
* Finance-specific route names
* Consistent `/finance/*` URLs
* Billing and invoice worklists
* Cashier and payment-collection workflows
* Accounts-receivable management
* Previous-balance and cross-visit allocation visibility
* Insurance and sponsor billing
* Claims preparation and submission
* Refund, reversal, discount, and credit-note controls
* Cashier-session and shift reconciliation
* Deposit and advance-payment management
* General-ledger and journal visibility
* Bank and payment-provider reconciliation
* Permission-controlled menu visibility
* Active-department and cashier-context scoping
* Separation of duties
* Financial audit integrity
* Reuse of existing billing, payment, receivable, insurance, claims, accounting, stock, payroll, reporting, and audit logic

Do not duplicate core billing, payment, invoice, receivable, insurance, claims, general-ledger, journal, refund, credit-note, or reconciliation logic merely to create the Finance workspace.

---

# 1. Core Functional Requirement

When the active department type is `finance`, all supported browser pages used by Finance staff must appear under the `/finance` URL prefix.

Examples:

```text
/finance
/finance/dashboard

/finance/billing
/finance/billing/pending
/finance/billing/incomplete
/finance/billing/exceptions
/finance/billing/{visit}

/finance/invoices
/finance/invoices/draft
/finance/invoices/unpaid
/finance/invoices/partially-paid
/finance/invoices/paid
/finance/invoices/overdue
/finance/invoices/cancelled
/finance/invoices/{invoice}

/finance/payments
/finance/payments/collect
/finance/payments/today
/finance/payments/unallocated
/finance/payments/reversed
/finance/payments/{payment}

/finance/cashier
/finance/cashier/session
/finance/cashier/open
/finance/cashier/close
/finance/cashier/reconciliation

/finance/receivables
/finance/receivables/patient
/finance/receivables/insurance
/finance/receivables/sponsors
/finance/receivables/aging
/finance/receivables/{receivable}

/finance/patient-balances
/finance/patient-balances/{patient}
/finance/payment-allocation

/finance/insurance
/finance/insurance/authorizations
/finance/insurance/invoices
/finance/insurance/claims

/finance/claims
/finance/claims/draft
/finance/claims/validation
/finance/claims/ready
/finance/claims/submitted
/finance/claims/rejected
/finance/claims/paid
/finance/claims/{claim}

/finance/sponsors
/finance/sponsors/accounts
/finance/sponsors/statements

/finance/discounts
/finance/credit-notes
/finance/refunds
/finance/reversals
/finance/write-offs

/finance/deposits
/finance/advances

/finance/journals
/finance/general-ledger
/finance/trial-balance
/finance/chart-of-accounts

/finance/reconciliation
/finance/reconciliation/cash
/finance/reconciliation/bank
/finance/reconciliation/digital-payments
/finance/reconciliation/insurance

/finance/reports
```

A Finance user should not enter through:

```text
/finance/invoices/{invoice}
```

and later be redirected to generic URLs such as:

```text
/invoices/{invoice}
/payments/{payment}
/billing/{visit}
/claims/{claim}
/receivables/{receivable}
/accounting/journals/{journal}
```

All browser navigation, forms, payment actions, invoice actions, worklists, breadcrumbs, notifications, dashboard cards, report drilldowns, and redirects must preserve the Finance workspace context.

---

# 2. Finance Workspace Scope

The Finance workspace is responsible for configured financial workflows, including:

1. Patient billing review
2. Visit billing review
3. Invoice generation
4. Draft invoice review
5. Invoice finalization
6. Payment collection
7. Cash payments
8. Digital payments
9. Bank payments
10. Card or TPE payments where supported
11. Mobile Money payments where supported
12. Receipt generation
13. Partial payments
14. Advance payments
15. Patient deposits
16. Payment allocation
17. Cross-visit payment allocation
18. Previous outstanding balance management
19. Accounts receivable
20. Patient receivables
21. Insurance receivables
22. Sponsor receivables
23. Receivable aging
24. Discounts
25. Credit notes
26. Refunds
27. Payment reversals
28. Invoice cancellations
29. Write-offs
30. Insurance authorization awareness
31. Insurance invoice preparation
32. Claims validation
33. Claims submission
34. Claims rejection management
35. Claims payment posting
36. Sponsor account management
37. Sponsor statements
38. Cashier opening and closing
39. Cashier-session reconciliation
40. Cash variance management
41. Bank reconciliation
42. Digital payment-provider reconciliation
43. Journal and general-ledger review
44. Trial balance
45. Chart-of-accounts visibility
46. Revenue and collection reporting
47. Financial audit review
48. Payment-gate policy administration where authorized
49. Billing override review
50. Financial reports and exports

The Finance workspace must remain distinct from:

* Clinical service ordering
* Pharmacy dispensing
* Stores inventory operations
* Insurance clinical coding
* Human-resources payroll processing unless Finance has specific payroll permissions
* Procurement approval unless explicitly assigned
* Patient registration
* Clinical discharge decisions

Finance may review the financial effect of those workflows, but their specialist departments remain authoritative for clinical and operational decisions.

---

# Phase 1 — Inspect the Existing Finance and Accounting Architecture

Before implementing, inspect the current codebase and identify:

1. How department-specific menus are selected.
2. How existing department workspaces are registered.
3. How active department context is resolved.
4. How multi-department users switch active departments.
5. How `DepartmentType::FINANCE` is currently mapped by:

   * dashboard resolver
   * menu profile service
   * capability resolver
   * department metrics registry
6. Whether Finance currently resolves to an internal dashboard key such as:

```text
accounting
finance
billing
cashier
```

Preserve the established internal dashboard key where required, while exposing the browser workspace under `/finance/*`.

7. Existing routes, controllers, models, services, policies, commands, jobs, and views for:

   * billing
   * invoice generation
   * invoices
   * invoice items
   * invoice receivables
   * payments
   * payment allocations
   * payment methods
   * cashier sessions
   * receipts
   * patient balances
   * previous balances
   * credit notes
   * discounts
   * refunds
   * payment reversals
   * write-offs
   * insurance
   * claims
   * sponsors
   * deposits
   * advance payments
   * general ledger
   * journals
   * journal entries
   * chart of accounts
   * trial balance
   * bank reconciliation
   * digital payment reconciliation
   * payment gateways
   * payment-gate policy
   * visit billing overrides
   * financial reports
8. Existing invoice statuses.
9. Existing payment statuses.
10. Existing cashier-session statuses.
11. Existing receivable states.
12. Existing claim statuses.
13. Existing credit-note and refund rules.
14. Existing journal-posting behaviour.
15. Existing accounting-period controls.
16. Existing previous-balance policy.
17. Existing cross-visit payment-allocation services.
18. Existing patient privacy and audit protections.
19. Existing views that hardcode routes such as:

```php
route('invoices.show', $invoice)
route('payments.show', $payment)
route('claims.show', $claim)
route('patients.show', $patient)
route('visits.show', $visit)
```

Do not create a competing finance, billing, receivable, claims, accounting, menu, or route-resolution framework where one already exists.

Extend the existing:

* Department dashboard registry
* Department menu profile service
* Department capability resolver
* Active department context
* Workspace URL resolver
* Workspace redirect resolver
* Billing services
* Invoice services
* Payment services
* Payment allocation services
* Receivable services
* Previous-balance services
* Insurance services
* Claims services
* Sponsor services
* Refund and credit-note services
* General-ledger services
* Journal services
* Reconciliation services
* Payment-gate policy
* Authorization policies
* Patient privacy services
* Activity logging

---

# Phase 2 — Finance Workspace Route Group

Create a dedicated Finance route group.

Use a structure equivalent to:

```php
Route::prefix('finance')
    ->name('finance.')
    ->middleware([
        'auth',
        'verified',
        'department.context',
        'department.type:finance',
    ])
    ->group(function () {
        // Finance workspace routes
    });
```

Use the project’s actual middleware names and active-department authorization architecture.

Required route names should include, where the corresponding functionality already exists:

```text
finance.dashboard

finance.billing.index
finance.billing.pending
finance.billing.incomplete
finance.billing.exceptions
finance.billing.show
finance.billing.review
finance.billing.finalize

finance.invoices.index
finance.invoices.draft
finance.invoices.unpaid
finance.invoices.partially_paid
finance.invoices.paid
finance.invoices.overdue
finance.invoices.cancelled
finance.invoices.show
finance.invoices.create
finance.invoices.store
finance.invoices.finalize
finance.invoices.cancel
finance.invoices.print

finance.payments.index
finance.payments.collect
finance.payments.store
finance.payments.today
finance.payments.unallocated
finance.payments.reversed
finance.payments.show
finance.payments.allocate
finance.payments.reverse
finance.payments.receipt

finance.cashier.index
finance.cashier.session
finance.cashier.open
finance.cashier.close
finance.cashier.reconciliation

finance.receivables.index
finance.receivables.patient
finance.receivables.insurance
finance.receivables.sponsors
finance.receivables.aging
finance.receivables.show

finance.patient_balances.index
finance.patient_balances.show
finance.payment_allocation.index
finance.payment_allocation.store

finance.insurance.index
finance.insurance.authorizations
finance.insurance.invoices
finance.insurance.claims

finance.claims.index
finance.claims.draft
finance.claims.validation
finance.claims.ready
finance.claims.submitted
finance.claims.rejected
finance.claims.paid
finance.claims.show
finance.claims.validate
finance.claims.submit
finance.claims.resubmit
finance.claims.post_payment

finance.sponsors.index
finance.sponsors.accounts
finance.sponsors.statements
finance.sponsors.show

finance.discounts.index
finance.discounts.create
finance.discounts.store
finance.discounts.show
finance.discounts.approve
finance.discounts.reject

finance.credit_notes.index
finance.credit_notes.create
finance.credit_notes.store
finance.credit_notes.show
finance.credit_notes.approve
finance.credit_notes.issue

finance.refunds.index
finance.refunds.create
finance.refunds.store
finance.refunds.show
finance.refunds.approve
finance.refunds.complete

finance.reversals.index
finance.reversals.show
finance.write_offs.index
finance.write_offs.create
finance.write_offs.store
finance.write_offs.approve

finance.deposits.index
finance.deposits.create
finance.deposits.store
finance.deposits.show

finance.advances.index
finance.advances.show

finance.journals.index
finance.journals.show
finance.general_ledger.index
finance.general_ledger.show
finance.trial_balance.index
finance.chart_of_accounts.index
finance.chart_of_accounts.show

finance.reconciliation.index
finance.reconciliation.cash
finance.reconciliation.bank
finance.reconciliation.digital_payments
finance.reconciliation.insurance

finance.payment_gate.index
finance.payment_gate.overrides
finance.payment_gate.audit

finance.reports.index
```

Only register routes for functionality that genuinely exists or is implemented in this phase.

Do not create empty placeholder pages merely to populate the Finance menu.

---

# Phase 3 — Finance Operations Dashboard

Create or complete a dedicated Finance dashboard.

The canonical route should be:

```text
/finance
```

or:

```text
/finance/dashboard
```

Choose one canonical route and redirect the other to it.

The department dashboard resolver should map:

```php
DepartmentType::FINANCE => 'finance.dashboard'
```

If the existing dashboard registry uses an internal key such as `accounting`, update it safely so that the public Finance destination remains:

```text
/finance
```

The dashboard should function as a Finance command board.

Recommended metrics and widgets include, where reliable data exists:

* Gross billing today
* Net billing today
* Payments collected today
* Cash collected today
* Digital payments today
* Insurance billing today
* Sponsor billing today
* Outstanding patient receivables
* Outstanding insurance receivables
* Outstanding sponsor receivables
* Total accounts receivable
* Receivables overdue
* Unpaid invoices
* Partially paid invoices
* Payments awaiting allocation
* Unreconciled payments
* Open cashier sessions
* Cashier sessions awaiting closure
* Cashier variances
* Refunds awaiting approval
* Credit notes awaiting approval
* Discounts awaiting approval
* Write-offs awaiting approval
* Claims awaiting validation
* Claims ready for submission
* Rejected claims
* Claims awaiting payment
* Claims paid today
* Previous patient balances
* Billing-context exceptions
* Payment-gate overrides today
* Journal-posting exceptions
* Unbalanced journal alerts
* Revenue by department
* Collection rate
* Average days receivable
* AR aging distribution
* Daily cash position where authorized

Each dashboard metric must:

* Respect permissions
* Respect the active Finance department
* Respect cashier or branch scoping where configured
* Avoid exposing protected patient information unnecessarily
* Avoid exposing financial valuation to unauthorized users
* Link to valid `/finance/*` routes
* Use safe empty states
* Avoid expensive unbounded queries
* Reuse the existing department metrics and accounting services where applicable

Do not introduce metrics that cannot be calculated reliably.

---

# Phase 4 — Finance-Specific Menu Profile

Extend the existing department menu profile or menu registry so that:

```php
DepartmentType::FINANCE
```

receives a dedicated Finance menu.

Recommended menu structure:

## Finance Command

* Dashboard
* Billing Exceptions
* Unpaid Invoices
* Open Cashier Sessions
* Claims Requiring Attention
* Approval Worklist

## Billing

* Billing Worklist
* Pending Billing
* Incomplete Billing
* Billing Exceptions
* Finalized Billing
* Visit Billing Review

## Invoices

* All Invoices
* Draft Invoices
* Unpaid Invoices
* Partially Paid
* Paid Invoices
* Overdue Invoices
* Cancelled Invoices

## Payments and Cashier

* Collect Payment
* Today’s Payments
* All Payments
* Unallocated Payments
* Reversed Payments
* Cashier Session
* Open Cashier Session
* Close Cashier Session
* Cashier Reconciliation
* Receipt History

## Accounts Receivable

* Patient Receivables
* Insurance Receivables
* Sponsor Receivables
* Receivable Aging
* Patient Balances
* Previous Balances
* Statements

## Insurance and Claims

* Insurance Authorizations
* Insurance Invoices
* Claims Drafts
* Claims Validation
* Claims Ready for Submission
* Submitted Claims
* Rejected Claims
* Paid Claims
* Claims Reconciliation

## Sponsor Accounts

* Sponsor Accounts
* Sponsor Invoices
* Sponsor Receivables
* Sponsor Statements
* Sponsor Payments

## Adjustments and Approvals

* Discounts
* Credit Notes
* Refunds
* Payment Reversals
* Invoice Cancellations
* Write-Offs
* Billing Overrides
* Payment-Gate Overrides

## Deposits and Advances

* Patient Deposits
* Advance Payments
* Deposit Allocation
* Unused Deposits
* Refundable Deposits

## Accounting

* Journal Entries
* General Ledger
* Trial Balance
* Chart of Accounts
* Posting Exceptions
* Accounting Periods where supported

## Reconciliation

* Cash Reconciliation
* Bank Reconciliation
* Digital Payment Reconciliation
* Insurance Reconciliation
* Sponsor Reconciliation
* Unmatched Transactions

## Finance Reports

* Revenue Report
* Collection Report
* Payment Method Report
* Cashier Report
* Invoice Report
* Accounts Receivable Report
* AR Aging Report
* Patient Balance Report
* Insurance Receivable Report
* Claims Report
* Sponsor Report
* Discount Report
* Credit-Note Report
* Refund Report
* Write-Off Report
* General Ledger Report
* Trial Balance Report
* Reconciliation Report
* Department Revenue Report
* Daily Financial Summary
* Audit Report

## General

* Notifications
* My Profile
* Switch Department

Only show a menu item when:

1. The feature exists.
2. The required module is enabled.
3. The user possesses the required permission.
4. The active department permits access.
5. The user’s cashier, branch, facility, or accounting scope permits access.

Permissions remain authoritative.

Do not expose menu items solely because the active department type is `finance`.

---

# Phase 5 — Billing Worklist

Create or adapt billing worklists under:

```text
/finance/billing
```

Recommended billing states include:

```text
not_started
in_progress
incomplete
billing_context_missing
price_missing
insurance_mapping_missing
authorization_pending
payment_gate_exception
ready_for_invoice
invoiced
cancelled
```

Use existing billing, service, price, insurance, visit, and payment-gate data.

Do not introduce duplicate billing states where the worklist status can be derived through a centralized resolver.

Each billing row should display only authorized information, such as:

* Patient identifier
* Patient name according to privacy rules
* Visit number
* Department
* Patient category
* Payer type
* Insurance or sponsor
* Number of billable items
* Gross amount
* Discount
* Insurance coverage
* Patient responsibility
* Billing state
* Invoice state
* Payment state
* Billing exceptions
* Responsible billing user
* Next required action

Support filters such as:

* Date
* Department
* Patient category
* Payer
* Insurance
* Sponsor
* Billing state
* Invoice state
* Payment state
* Billing exception
* Responsible user
* Visit type
* Admission or outpatient
* Emergency or routine

Use pagination and efficient queries.

---

# Phase 6 — Visit Billing Workspace

Create or adapt a Finance visit-billing workspace.

Recommended route:

```text
/finance/billing/{visit}
```

The page should coordinate all financial information relevant to the visit.

Recommended sections:

1. Patient identity strip
2. Visit information
3. Payer and insurance information
4. Sponsor information
5. Billable services
6. Service status
7. Department
8. Cash price
9. Insurance price
10. Insurance coverage
11. Patient responsibility
12. Discounts
13. Gross total
14. Net total
15. Existing invoices
16. Payments
17. Previous balance
18. Deposit or advance balance
19. Billing overrides
20. Payment-gate state
21. Billing exceptions
22. Receivable summary
23. Financial timeline
24. Authorized actions

Reuse the existing billing-calculation services.

Do not recalculate invoice totals independently inside the Finance controller or Blade view.

The selected insurance price must remain authoritative for insurance coverage calculations.

Do not calculate insurance coverage against the cash price where the project’s established rule is to calculate against the selected insurer’s price.

---

# Phase 7 — Invoice Lifecycle

Expose invoices under:

```text
/finance/invoices
```

Potential invoice states may include:

```text
draft
finalized
unpaid
partially_paid
paid
overpaid
overdue
cancelled
credited
written_off
```

Use existing configured statuses where available.

The invoice workflow should support:

* Draft creation
* Billable-item review
* Payer allocation
* Insurance and patient-responsibility split
* Sponsor allocation
* Discount application
* Finalization
* Receipt and payment linking
* Credit-note linking
* Cancellation where permitted
* Write-off where approved
* Statement generation
* Print or PDF where supported

Finalized invoices must not be silently edited.

Changes after finalization should use:

* Credit notes
* Debit adjustments where supported
* Invoice cancellation and replacement
* Approved correction workflow

according to the established accounting architecture.

---

# Phase 8 — Payment Collection

Expose payment collection under:

```text
/finance/payments/collect
```

Reuse the existing `PaymentService` or equivalent authoritative payment pipeline.

Payment collection may support:

* Cash
* Mobile Money
* Card or TPE
* Bank transfer
* Cheque where supported
* Digital payment provider
* Insurance settlement
* Sponsor settlement
* Deposit allocation
* Advance-payment allocation

A payment should record:

* Patient or payer
* Invoice
* Visit
* Amount
* Currency
* Payment method
* Provider
* External transaction reference
* Cashier
* Cashier session
* Payment date and time
* Allocation state
* Receipt number
* Notes where permitted

Do not create a second payment-recording implementation for the Finance workspace.

All financial postings must continue through the existing payment and journal services.

---

# Phase 9 — Partial Payments and Overpayments

Support partial payment where a payer does not settle the full invoice.

The system must preserve:

* Invoice amount
* Previously paid amount
* Amount paid now
* Remaining balance
* Receivable state
* Allocation history
* Responsible cashier
* Payment method
* Date and time

Overpayment should follow the existing policy.

Potential outcomes may include:

```text
patient_credit
unallocated_payment
deposit_balance
refund_required
allocation_required
```

Do not silently apply an overpayment to unrelated invoices without an authorized allocation policy.

All allocations must remain traceable.

---

# Phase 10 — Patient Deposits and Advance Payments

Expose deposits and advances under:

```text
/finance/deposits
/finance/advances
```

A deposit or advance may include:

* Patient
* Visit or admission where applicable
* Amount
* Payment method
* Cashier
* Receipt
* Available balance
* Allocated amount
* Refunded amount
* Expiry or closure state where configured

The system should support:

* Deposit collection
* Deposit allocation
* Partial allocation
* Cross-invoice allocation where permitted
* Deposit refund
* Transfer to patient credit where configured

Do not treat an unallocated deposit as earned revenue until the existing accounting policy recognizes it.

Use the correct liability or control account where configured.

---

# Phase 11 — Previous Patient Balance

Expose previous patient balances under:

```text
/finance/patient-balances
```

Reuse the existing:

```php
PatientOutstandingBalanceService
```

or its current equivalent.

The patient balance summary should distinguish:

* Previous-visit balance
* Current-visit balance
* Total balance
* Oldest unpaid invoice
* Age in days
* Receivable bucket
* Patient responsibility
* Insurance responsibility
* Sponsor responsibility

Do not combine invoices into a single new invoice merely for display.

Each visit must retain its own invoice and receivable history.

Patient-balance views must preserve privacy and permission controls.

---

# Phase 12 — Cross-Visit Payment Allocation

Expose payment allocation under:

```text
/finance/payment-allocation
```

Reuse the existing:

```php
PatientPaymentAllocationService
```

or its current equivalent.

Support configured allocation strategies such as:

```text
oldest_first
current_visit
manual
```

Because the existing payment and accounting pipeline may be invoice-scoped, a cross-visit tender should continue to be recorded through the established design:

* One tender may produce multiple invoice-scoped payments.
* Each invoice keeps its own payment history.
* Each visit keeps its own invoice.
* Each payment posts through the unchanged payment pipeline.
* Each journal entry remains balanced and invoice-specific.

Do not create one synthetic cross-visit payment record that bypasses the accounting architecture.

Manual allocation must:

* Prevent allocation beyond the payment amount
* Prevent allocation beyond eligible receivable amounts
* Preserve outstanding balances
* Record the user
* Record the strategy
* Record the allocation sequence
* Be audited

---

# Phase 13 — Accounts Receivable

Expose Accounts Receivable under:

```text
/finance/receivables
```

Reuse the existing `InvoiceReceivable` architecture.

Receivables should be categorized by responsibility:

```text
patient
insurance
sponsor
other_configured_payer
```

Potential states may include:

```text
current
partially_paid
overdue
disputed
under_claim
settled
written_off
cancelled
```

Each receivable should display:

* Invoice
* Patient or payer
* Original amount
* Paid amount
* Outstanding amount
* Due date
* Age
* Aging bucket
* Responsibility type
* Claim state where relevant
* Last payment
* Next action

Do not infer responsibility from invoice totals alone.

Use the existing patient-responsibility and payer-allocation data.

---

# Phase 14 — Receivable Aging

Expose receivable aging under:

```text
/finance/receivables/aging
```

Use configured aging buckets such as:

```text
current
1_30_days
31_60_days
61_90_days
91_120_days
over_120_days
```

Use existing configured buckets where available.

The aging report should support:

* Patient receivables
* Insurance receivables
* Sponsor receivables
* Department
* Facility
* Payer
* Date range
* Aging bucket
* Invoice status

Do not calculate aging from payment date when the established policy uses invoice due date or invoice finalization date.

Use the authoritative receivable-aging service.

---

# Phase 15 — Cashier Sessions

Expose cashier sessions under:

```text
/finance/cashier
```

A cashier session may include:

* Cashier
* Cash point
* Department
* Opening date and time
* Opening float
* Payments received
* Refunds
* Reversals
* Cash expected
* Cash counted
* Variance
* Closing date and time
* Closing user
* Supervisor approval
* Session state

Potential session states may include:

```text
not_open
open
closing
closed
variance_review
approved
```

The system should prevent payment collection through a cashier account where an open session is required but absent.

Do not allow one cashier to have conflicting active sessions where policy prohibits it.

Cashier opening and closing must be audited.

---

# Phase 16 — Cashier Closing and Reconciliation

Cashier closing should calculate:

* Opening float
* Cash payments
* Non-cash payments
* Refunds
* Reversals
* Cash expected
* Cash declared
* Variance
* Payment count
* Receipt range
* Digital payment totals
* Bank or cheque totals where supported

A variance should record:

* Expected amount
* Counted amount
* Variance amount
* Variance type
* Reason
* Cashier
* Supervisor
* Resolution
* Approval state

Potential variance states may include:

```text
balanced
shortage
overage
under_review
approved
resolved
```

Do not automatically write off cashier shortages or overages.

Use the configured variance-review and journal-posting workflow.

---

# Phase 17 — Payment Reversal

Completed payments must not be deleted directly.

Provide a formal payment-reversal workflow.

A reversal should record:

* Original payment
* Invoice
* Patient or payer
* Amount
* Payment method
* Reversal reason
* Requesting user
* Approving user where required
* Reversal date and time
* Cashier session
* Journal reversal
* Receipt effect
* Receivable effect

The system must:

1. Preserve the original payment.
2. Create a traceable reversal.
3. Restore the receivable appropriately.
4. Reverse accounting entries.
5. Update the cashier session.
6. Preserve external transaction references.
7. Prevent duplicate reversal.
8. Audit the action.

Digital-payment reversals should also respect provider capabilities and settlement state.

---

# Phase 18 — Refund Workflow

Expose refunds under:

```text
/finance/refunds
```

A refund may relate to:

* Overpayment
* Cancelled service
* Cancelled invoice
* Returned medication
* Reversed dispensing
* Unused deposit
* Insurance correction
* Sponsor correction
* Duplicate payment

A refund should record:

* Patient or payer
* Original payment
* Invoice
* Refund amount
* Refund reason
* Refund method
* Requesting user
* Approving user
* Paying cashier
* Date and time
* Journal effect
* Provider reference where applicable
* Receipt or refund voucher

Refunds must not exceed the refundable balance.

Do not treat payment reversal and refund as identical unless the existing domain model explicitly does so.

A reversal voids or negates a payment.

A refund pays money back after a valid payment or credit balance.

Preserve that distinction.

---

# Phase 19 — Discounts

Expose discounts under:

```text
/finance/discounts
```

Discounts may apply to:

* Invoice
* Invoice item
* Service category
* Patient responsibility
* Sponsor arrangement
* Authorized welfare support

A discount should record:

* Invoice
* Item where applicable
* Original amount
* Discount type
* Discount value
* Final amount
* Reason
* Requesting user
* Approving user
* Approval level
* Date and time

Potential discount types may include:

```text
fixed_amount
percentage
full_waiver
configured_scheme
```

Do not apply discounts by directly changing historical service prices after invoice finalization.

Use the existing discount and invoice-adjustment architecture.

Discounts above configured thresholds should require higher-level approval.

---

# Phase 20 — Credit Notes

Expose credit notes under:

```text
/finance/credit-notes
```

A credit note should record:

* Original invoice
* Patient or payer
* Credited invoice items
* Amount
* Tax or charge adjustments where supported
* Reason
* Requesting user
* Approving user
* Issue date
* Receivable effect
* Journal effect
* Remaining invoice balance

Potential reasons include:

```text
service_cancelled
service_not_rendered
billing_error
price_correction
insurance_adjustment
returned_item
duplicate_charge
other
```

Do not directly overwrite or delete finalized invoice items.

Credit notes must preserve the original invoice and create traceable accounting adjustments.

---

# Phase 21 — Invoice Cancellation

A finalized invoice should only be cancelled through an authorized workflow.

Cancellation should record:

* Invoice
* Cancellation reason
* Cancelling user
* Approval
* Payment state
* Credit-note requirement
* Replacement invoice where applicable
* Receivable effect
* Journal effect
* Date and time

Do not cancel an invoice with payments, claims, or dependent transactions without resolving those dependencies.

Where replacement is required, link the original and replacement invoices.

---

# Phase 22 — Write-Offs

Expose write-offs under:

```text
/finance/write-offs
```

A write-off may apply to approved unrecoverable receivables.

A write-off should record:

* Receivable
* Invoice
* Payer
* Outstanding amount
* Write-off amount
* Reason
* Collection history
* Requesting user
* Approving user
* Approval level
* Journal effect
* Date and time

Potential reasons may include:

```text
uncollectible
deceased_estate
charity_approval
insurance_denial_final
sponsor_default
administrative_decision
other
```

Write-off does not mean deletion.

The receivable, invoice, payment history, and write-off record must remain visible.

---

# Phase 23 — Insurance Billing

Expose insurance financial workflows under:

```text
/finance/insurance
```

Finance should be able to view:

* Patient insurer
* Insurance plan
* Authorization state
* Covered services
* Excluded services
* Insurance price
* Insurance coverage
* Patient responsibility
* Claimable amount
* Non-claimable amount
* Insurance invoice state
* Claim state
* Receivable state

The selected insurance price must remain the basis for insurance coverage calculation.

Do not calculate insurance coverage from the cash price when an insurer-specific price exists.

Reuse the existing insurance-pricing and coverage services.

Finance must not override clinical service delivery merely because insurance authorization is pending unless the configured payment policy requires it.

---

# Phase 24 — Claims Workflow

Expose claims under:

```text
/finance/claims
```

Potential claim states may include:

```text
draft
incomplete
validation_failed
ready
submitted
acknowledged
under_review
partially_approved
approved
rejected
paid
partially_paid
cancelled
resubmission_required
```

Use existing configured states where available.

Claims workflow may include:

1. Claim generation
2. Patient and insurer validation
3. Service validation
4. Diagnosis and code validation
5. Price validation
6. Coverage validation
7. Supporting-document validation
8. Claim readiness
9. Submission
10. Submission reference
11. Insurer acknowledgement
12. Rejection or query
13. Correction
14. Resubmission
15. Approval
16. Settlement
17. Receivable allocation
18. Variance management

Do not duplicate clinical coding or diagnosis data.

Reuse the existing consultation, insurance, claims, and billing records.

---

# Phase 25 — Claims Validation

Claims validation should identify issues such as:

* Missing insurer
* Missing policy number
* Missing authorization
* Missing diagnosis
* Missing procedure code
* Missing service code
* Price mismatch
* Coverage mismatch
* Duplicate claim item
* Missing supporting document
* Invalid service date
* Invalid patient category
* Claim amount mismatch
* Billing item not finalized

Validation findings should distinguish:

```text
blocking
warning
informational
```

Do not silently remove claim items to make a claim pass validation.

Every excluded or corrected item must remain traceable.

---

# Phase 26 — Claims Settlement

When an insurer payment is received, Finance should be able to:

* Record settlement
* Link insurer payment reference
* Allocate payment to claims
* Allocate payment to invoices
* Record approved amount
* Record rejected amount
* Record deductions
* Record withholding where applicable
* Record unexplained variance
* Update insurance receivables
* Update claim status
* Post accounting entries

Do not mark the full claim paid when only part of the amount was received.

Claims settlement should support partial payment and deductions.

---

# Phase 27 — Sponsor Accounts

Expose sponsor accounts under:

```text
/finance/sponsors
```

A sponsor account may include:

* Sponsor
* Agreement
* Credit limit
* Covered services
* Covered beneficiaries
* Billing cycle
* Invoice state
* Receivable balance
* Payment history
* Statement history
* Account status

Finance may generate:

* Sponsor invoices
* Sponsor statements
* Receivable-aging reports
* Payment allocations
* Account reconciliations

Do not expose sponsor contract details without the required permission.

---

# Phase 28 — Payment-Gate Policy Visibility

Expose authorized payment-gate policy visibility under:

```text
/finance/payment-gate
```

Reuse the existing:

* `PaymentGateOperationPolicy`
* `PaymentGateEnforcementEligibility`
* Configuration resolver
* Eligibility service
* Compatibility service
* Payment-gate audit command
* Visit billing overrides

The Finance workspace may show:

* Operation
* Current mode
* Department rule
* Visit context rule
* Missing billing-context policy
* Override scope
* Wired or unwired state
* Enforcement eligibility
* Recent overrides
* Policy-audit findings

Do not allow Finance users to modify wired hard-gate operations where the existing admin UI marks them read-only.

Policy editing must remain under the established permission and audit architecture.

---

# Phase 29 — Visit Billing Overrides

Expose billing overrides where authorized.

An override may include:

* Visit
* Patient
* Operation
* Scope
* Reason
* Requested user
* Approving user
* Start time
* Expiry
* Status
* Audit reference

Potential override reasons may include:

```text
emergency
payment_deferred
insurance_pending
sponsor_pending
authorized_exception
clinical_priority
billing_context_issue
other
```

Overrides must be:

* Explicit
* Permission-controlled
* Time-scoped where appropriate
* Visit-scoped
* Operation-scoped
* Reasoned
* Audited

Do not create a universal “allow all services” override unless the existing policy explicitly supports it.

---

# Phase 30 — General Ledger and Journal Visibility

Expose accounting visibility under:

```text
/finance/journals
/finance/general-ledger
```

Reuse the existing journal and ledger architecture.

The journal view may display:

* Journal reference
* Date
* Source transaction
* Source module
* Description
* Debit account
* Credit account
* Amount
* Currency
* Posting state
* Accounting period
* Responsible user
* Reversal link

The ledger view may display:

* Account
* Date
* Reference
* Description
* Debit
* Credit
* Running balance
* Source transaction
* Department or cost center where supported

Do not allow direct deletion or editing of posted journal entries.

Corrections must use:

* Reversal
* Correcting journal
* Approved adjustment

according to the existing accounting architecture.

---

# Phase 31 — Chart of Accounts

Expose Chart of Accounts visibility under:

```text
/finance/chart-of-accounts
```

The operational view may display:

* Account code
* Account name
* Account type
* Parent account
* Normal balance
* Active state
* Posting permission
* Financial statement classification

Editing the Chart of Accounts must remain restricted to users with the existing administrative accounting permission.

Do not grant account-editing permission merely because a user belongs to Finance.

---

# Phase 32 — Trial Balance

Expose Trial Balance under:

```text
/finance/trial-balance
```

The report should display:

* Account
* Opening debit
* Opening credit
* Period debit
* Period credit
* Closing debit
* Closing credit

The Trial Balance must use the authoritative journal-posting data.

Do not calculate it independently from invoices and payments where the system already posts to the General Ledger.

The report should support:

* Date range
* Accounting period
* Facility
* Department or cost center where supported
* Posted entries only
* Draft-entry exclusion

---

# Phase 33 — Accounting Period Controls

Where accounting-period functionality exists, Finance should respect:

* Open period
* Closed period
* Locked period
* Adjustment period

Do not allow normal transaction posting into a closed or locked accounting period.

Authorized late adjustments should use the existing adjustment-period or reopening workflow.

Period reopening must be permission-controlled, reasoned, and audited.

---

# Phase 34 — Cash and Bank Reconciliation

Expose reconciliation under:

```text
/finance/reconciliation
```

Cash reconciliation should compare:

* Cashier sessions
* Expected cash
* Counted cash
* Deposited cash
* Variances

Bank reconciliation should compare:

* Bank statement transactions
* Recorded bank payments
* Deposits
* Refunds
* Charges
* Transfers
* Unmatched transactions

Potential reconciliation states may include:

```text
unmatched
partially_matched
matched
exception
resolved
```

Do not delete unmatched transactions merely to complete reconciliation.

Preserve exceptions and resolution history.

---

# Phase 35 — Digital Payment Reconciliation

Expose digital-payment reconciliation under:

```text
/finance/reconciliation/digital-payments
```

Support configured providers such as:

* Mobile Money
* Card or TPE
* Bank gateway
* Payment aggregator
* Other active payment providers

The worklist may display:

* Internal payment reference
* Provider transaction reference
* Amount
* Provider amount
* Fees
* Settlement amount
* Payment date
* Settlement date
* Status
* Match state
* Variance
* Provider response

Do not expose payment-provider credentials or secrets.

Provider callbacks and APIs must remain outside browser-route redirects.

---

# Phase 36 — Finance Workspace URL Resolution

Extend the centralized workspace route resolver.

Do not scatter checks such as:

```php
if ($department->type === DepartmentType::FINANCE) {
    return route('finance.invoices.show', $invoice);
}
```

across controllers and Blade views.

Extend a service such as:

```php
DepartmentWorkspaceRouteResolver
WorkspaceUrlResolver
DepartmentRouteResolver
```

The resolver should support methods equivalent to:

```php
dashboard()

billingIndex()
billingShow(Visit $visit)

invoiceIndex()
invoiceShow(Invoice $invoice)

paymentIndex()
paymentShow(Payment $payment)
paymentCollect()

cashierSession()

receivableIndex()
receivableShow(InvoiceReceivable $receivable)

patientBalanceIndex()
patientBalanceShow(Patient $patient)

paymentAllocation()

insuranceIndex()
claimIndex()
claimShow(Claim $claim)

sponsorIndex()
sponsorShow(Sponsor $sponsor)

discountIndex()
creditNoteIndex()
refundIndex()
reversalIndex()
writeOffIndex()

depositIndex()

journalIndex()
journalShow(Journal $journal)
generalLedger()
trialBalance()
chartOfAccounts()

reconciliationIndex()
paymentGateIndex()
reportIndex()
```

For a Finance user, the resolver must return `finance.*` routes.

For other users, preserve the appropriate existing workspace or generic route.

Always use the active department context rather than only the user’s primary department.

---

# Phase 37 — Replace Hardcoded Shared Links

Audit all shared pages accessed by Finance users.

Replace hardcoded generic links that break workspace continuity.

Review at minimum:

* Billing worklists
* Invoice lists
* Invoice details
* Payment collection
* Payment history
* Receipt pages
* Cashier sessions
* Receivable pages
* Patient balances
* Insurance pages
* Claims pages
* Sponsor pages
* Discounts
* Credit notes
* Refunds
* Reversals
* Write-offs
* Deposits
* Journals
* General Ledger
* Reconciliation pages
* Patient search
* Patient profile
* Visit history
* Dashboard cards
* Breadcrumbs
* Notifications
* Approval links
* Action dropdowns
* Empty-state actions
* Report drilldowns
* Flash-message action links

Avoid shared-view code such as:

```php
route('invoices.show', $invoice)
```

Use the centralized workspace route resolver.

Do not alter API, payment callback, insurance callback, provider webhook, signed, print, export, integration, or background-job URLs unless explicitly part of the Finance browser workspace.

---

# Phase 38 — Workspace-Aware Redirects

All successful Finance actions must redirect back into `/finance/*`.

Examples:

After finalizing billing:

```text
/finance/invoices/{invoice}
```

After collecting payment:

```text
/finance/payments/{payment}
```

After allocating a payment:

```text
/finance/patient-balances/{patient}
```

After reversing a payment:

```text
/finance/payments/{payment}
```

After issuing a credit note:

```text
/finance/credit-notes/{creditNote}
```

After approving a refund:

```text
/finance/refunds/{refund}
```

After submitting a claim:

```text
/finance/claims/{claim}
```

After closing a cashier session:

```text
/finance/cashier/reconciliation
```

Avoid hardcoding Finance redirects inside domain services.

Use a workspace redirect resolver such as:

```php
$workspaceRedirects->toInvoice($invoice);
$workspaceRedirects->toPayment($payment);
$workspaceRedirects->toPatientBalance($patient);
$workspaceRedirects->toClaim($claim);
$workspaceRedirects->toRefund($refund);
$workspaceRedirects->toFinanceDashboard();
```

Validation failures must return users to the same `/finance/*` route with input preserved.

---

# Phase 39 — Login and Department Switching

When a user logs in and their active department type is `finance`, redirect them to:

```text
/finance
```

When a multi-department user switches to a Finance department, redirect them to:

```text
/finance
```

When switching away from Finance, redirect to the selected department’s appropriate workspace.

The menu, dashboard, route context, cashier context, and data scoping must always use the active department.

Do not rely only on:

```php
$user->department_id
```

where the application supports active department selection.

---

# Phase 40 — Finance Workspace Authorization

The `/finance` prefix is not authorization.

Protect the workspace so access requires:

1. An authenticated user.
2. A valid active department.
3. Active department type equal to `finance`, unless authorized admin preview applies.
4. The required permission.
5. The relevant module being enabled.
6. Access to the requested invoice, payment, receivable, claim, sponsor, journal, cashier session, or accounting scope.
7. Facility, branch, cashier, payer, or accounting-scope authorization where required.

A user from another department who manually enters:

```text
/finance/invoices
```

must not receive access merely because they possess a broad billing-view permission.

Use the project’s existing unauthorized workspace behaviour:

* `403`
* Workspace unavailable page
* Safe redirect

Do not create inconsistent authorization behaviour.

---

# Phase 41 — Permission Model

Reuse existing permissions wherever possible.

Only add permissions where the current model does not represent the required action.

Potential permissions may include:

```text
finance.workspace.view

finance.billing.view
finance.billing.review
finance.billing.finalize

finance.invoices.view
finance.invoices.create
finance.invoices.finalize
finance.invoices.cancel
finance.invoices.print

finance.payments.view
finance.payments.collect
finance.payments.allocate
finance.payments.reverse
finance.receipts.view

finance.cashier.view
finance.cashier.open
finance.cashier.close
finance.cashier.reconcile
finance.cashier.variance_approve

finance.receivables.view
finance.patient_balances.view
finance.payment_allocation.manage

finance.insurance.view
finance.claims.view
finance.claims.validate
finance.claims.submit
finance.claims.resubmit
finance.claims.post_payment

finance.sponsors.view
finance.sponsors.manage

finance.discounts.view
finance.discounts.request
finance.discounts.approve

finance.credit_notes.view
finance.credit_notes.create
finance.credit_notes.approve
finance.credit_notes.issue

finance.refunds.view
finance.refunds.request
finance.refunds.approve
finance.refunds.complete

finance.reversals.view
finance.reversals.manage

finance.write_offs.view
finance.write_offs.request
finance.write_offs.approve

finance.deposits.view
finance.deposits.manage

finance.journals.view
finance.journals.post
finance.journals.reverse
finance.general_ledger.view
finance.trial_balance.view
finance.chart_of_accounts.view
finance.chart_of_accounts.manage

finance.reconciliation.view
finance.reconciliation.manage

finance.payment_gate.view
finance.payment_gate.manage
finance.billing_overrides.view
finance.billing_overrides.manage

finance.reports.view
finance.reports.export
```

Inspect current permission names before adding new permissions.

Avoid duplicating equivalent permissions.

Menu visibility must follow permissions, but controllers, policies, form requests, approval services, and financial domain services must independently enforce authorization.

---

# Phase 42 — Separation of Duties

Enforce separation of duties for high-risk financial actions where configured.

Examples:

* A cashier should not approve their own cash variance.
* A refund requester should not approve and complete the same refund.
* A credit-note creator should not approve the credit note.
* A discount requester should not approve the same discount above configured thresholds.
* A payment collector should not reverse the payment without additional authority where configured.
* A write-off requester should not approve the write-off.
* A claim preparer should not be the final claim approver where separation is required.
* A journal preparer should not post the same journal where maker-checker control is enabled.
* A bank-reconciliation preparer should not approve the reconciliation where configured.

Use configurable rules rather than hardcoding one universal workflow.

Exceptions must be:

* Permission-controlled
* Reasoned
* Time-stamped
* Audited

---

# Phase 43 — Patient Privacy and Financial Data Security

The Finance workspace handles highly sensitive patient and financial information.

Ensure existing privacy controls remain active, including:

* Patient-name masking where applicable
* Protected phone and email fields
* Sensitive-field permission checks
* Patient search masking
* Export restrictions
* Secure patient, visit, invoice, payment, and claim lookups
* Privacy-aware notifications
* Activity-log sanitization

Finance users should only see clinical information necessary to understand billing, insurance, claims, and authorization.

Do not expose full consultation notes, unrelated diagnoses, or clinical records without permission.

Financial information must also remain permission-controlled, including:

* Patient balances
* Insurance balances
* Sponsor balances
* Revenue
* Cash positions
* Bank details
* Payment-provider references
* General Ledger
* Trial Balance
* Stock valuation where linked
* Payroll information where integrated

---

# Phase 44 — Financial Integrity and Accounting Safety

Preserve existing financial safeguards, including:

* Balanced journal entries
* Invoice immutability after finalization
* Payment traceability
* Receipt uniqueness
* Cashier-session integrity
* Receivable integrity
* Payment-allocation integrity
* Insurance-coverage accuracy
* Refund limits
* Credit-note limits
* Write-off approval
* Accounting-period controls
* Bank-reconciliation traceability
* Digital-payment reconciliation
* Separation of duties
* Audit trails

Do not allow:

* Direct editing of posted payments
* Direct deletion of finalized invoices
* Direct deletion of posted journals
* Refunds beyond refundable balances
* Credit notes beyond eligible invoice balances
* Duplicate payment reversal
* Duplicate provider transaction posting
* Cross-visit payment allocation beyond the tender amount
* Insurance coverage to be calculated against the wrong price basis
* Receivables to disappear without settlement, credit, write-off, or cancellation
* Cashier sessions to close without required reconciliation
* Unbalanced journal posting
* Posting into closed accounting periods
* Financial history to be silently overwritten

Overrides must be explicit, permission-controlled, reasoned, scoped, and audited.

---

# Phase 45 — Activity Logging and Audit

Record relevant Finance actions through the existing `ActivityLog` infrastructure.

Audit events should cover actions such as:

* Billing reviewed
* Billing finalized
* Invoice created
* Invoice finalized
* Invoice cancelled
* Payment collected
* Receipt issued
* Payment allocated
* Cross-visit allocation completed
* Payment reversed
* Deposit collected
* Deposit allocated
* Cashier session opened
* Cashier session closed
* Cash variance recorded
* Cash variance approved
* Discount requested
* Discount approved
* Credit note created
* Credit note approved
* Credit note issued
* Refund requested
* Refund approved
* Refund completed
* Write-off requested
* Write-off approved
* Claim generated
* Claim validated
* Claim submitted
* Claim rejected
* Claim resubmitted
* Claim payment posted
* Sponsor statement generated
* Billing override created
* Payment-gate override created
* Journal posted
* Journal reversed
* Reconciliation completed
* Accounting period reopened where supported

Do not log full payment credentials, bank details, card data, provider secrets, or unnecessary patient clinical information.

Audit records should include sufficient context such as:

* Actor
* Patient identifier where relevant
* Visit identifier
* Invoice identifier
* Payment identifier
* Receivable identifier
* Claim identifier
* Cashier session identifier
* Journal identifier
* Department
* Facility or branch where applicable
* Amount where audit policy permits
* Action
* Timestamp
* Reason where required
* Previous and new state where appropriate

---

# Phase 46 — Localization

Add complete English and French localization for the Finance workspace.

Prefer existing Finance, Billing, Accounting, and Claims localization files where available.

Otherwise, use or extend:

```text
lang/en/finance.php
lang/fr/finance.php
```

Include keys for:

* Workspace title
* Dashboard
* Menu sections
* Billing states
* Invoice states
* Payment states
* Payment methods
* Allocation states
* Cashier-session states
* Cash-variance states
* Receivable states
* Aging buckets
* Insurance states
* Claim states
* Sponsor states
* Discount types and states
* Credit-note states
* Refund states
* Reversal states
* Write-off states
* Deposit states
* Journal states
* Reconciliation states
* Payment-gate states
* Override reasons
* Empty states
* Quick actions
* Reports
* Breadcrumbs
* Unauthorized workspace message

Maintain complete English and French parity.

Do not hardcode visible Finance labels in controllers, services, Blade templates, or JavaScript.

---

# Phase 47 — Finance Reports and Statistics

Create or adapt Finance reports under:

```text
/finance/reports
```

Recommended reports include:

* Daily billing report
* Daily collection report
* Revenue report
* Revenue by department
* Revenue by service
* Revenue by payer
* Payment-method report
* Cashier collection report
* Cashier variance report
* Invoice-status report
* Unpaid-invoice report
* Patient-balance report
* Previous-balance report
* Accounts-receivable report
* AR aging report
* Insurance-receivable report
* Sponsor-receivable report
* Claims-status report
* Claims-rejection report
* Claims-settlement report
* Discount report
* Credit-note report
* Refund report
* Reversal report
* Write-off report
* Deposit and advance-payment report
* General Ledger report
* Trial Balance report
* Journal report
* Bank-reconciliation report
* Digital-payment reconciliation report
* Billing-override report
* Payment-gate override report
* Financial audit report

Reports must respect:

* Permissions
* Active department
* Facility or branch scope
* Cashier scope
* Payer scope
* Patient privacy
* Financial-data security
* Accounting period
* Export permissions

Reports should distinguish:

* Gross billing
* Discounts
* Credits
* Net billing
* Payments
* Refunds
* Reversals
* Net collections
* Patient receivables
* Insurance receivables
* Sponsor receivables
* Write-offs

Do not present billing as cash collected.

Do not present payments as revenue without following the configured accounting basis and journal architecture.

---

# Phase 48 — Menu Configuration and Future Extensibility

Implement the Finance menu through the existing menu registry or department menu profile service.

Do not define it directly inside the sidebar Blade template.

The menu configuration should support:

* Section ordering
* Route name
* Icon
* Permission
* Module requirement
* Active-route patterns
* Badge counts
* Department-type availability
* Facility or branch scoping
* Cashier scoping
* Feature flags
* Unpaid-invoice counts
* Unallocated-payment counts
* Open-cashier-session counts
* Refund-approval counts
* Claims-rejection counts
* Receivable-overdue counts
* Payment-gate override counts
* Reconciliation-exception counts

The architecture must remain extensible for future department menu personalization, including:

```text
radiology
theatre
blood_bank
mortuary
ambulance
support
administrative
```

Do not implement those other workspaces in this phase.

---

# Phase 49 — Focused Automated Verification

Add focused automated tests for the Finance workspace.

## Route tests

Verify:

* Finance routes exist.
* Route names use `finance.*`.
* URLs use `/finance/*`.
* Finance department middleware is attached.
* Generic routes remain available where required.

## Access tests

Verify:

* A Finance department user can access authorized Finance pages.
* A non-Finance department user cannot access the workspace.
* Users without the required permission cannot access protected actions.
* Admin preview continues working where supported.
* Multi-department active context is respected.
* Facility, branch, cashier, and accounting scopes are respected.

## Dashboard tests

Verify:

* The Finance dashboard loads.
* Billing, payment, receivable, claim, and cashier metrics are accurate.
* Sensitive financial values are hidden without permission.
* Links point to `/finance/*`.
* Empty states render safely.
* Metrics do not confuse billing with collections.

## Billing and invoice tests

Verify:

* Billing uses the existing calculation services.
* Insurance coverage uses the selected insurance price.
* Billing exceptions are surfaced.
* Finalized invoices cannot be silently edited.
* Credit notes or replacement workflows handle corrections.
* Invoice totals remain consistent.

## Payment tests

Verify:

* Payments use the existing payment service.
* Journal entries remain balanced.
* Partial payments update receivables correctly.
* Overpayments follow the configured policy.
* Duplicate provider references are rejected where required.
* Receipts remain unique.
* Payment actions are audited.

## Previous-balance tests

Verify:

* Previous and current balances are separated.
* Only patient-responsibility receivables are counted in patient balances.
* Oldest invoice and aging data are accurate.
* Previous visits retain their original invoices.

## Cross-visit allocation tests

Verify:

* `oldest_first` allocation works correctly.
* `current_visit` allocation works correctly.
* Manual allocation validates all amounts.
* A tender may create multiple invoice-scoped payments.
* Each invoice retains a clean payment and journal history.
* Allocation cannot exceed the tender or receivable.
* Allocation is audited.

## Cashier tests

Verify:

* Cashier sessions open and close correctly.
* Required open-session rules are enforced.
* Expected cash is calculated correctly.
* Refunds and reversals affect the session correctly.
* Variances require review where configured.
* A cashier cannot approve their own variance where separation is enabled.

## Refund and reversal tests

Verify:

* Posted payments cannot be deleted directly.
* Payment reversal preserves the original payment.
* Payment reversal restores the receivable.
* Refunds cannot exceed refundable balances.
* Refund and reversal remain distinct.
* Journal entries and cashier totals update correctly.
* Approval controls are enforced.

## Discount and credit-note tests

Verify:

* Discounts respect approval thresholds.
* Finalized invoice prices are not directly changed.
* Credit notes preserve the original invoice.
* Credit notes cannot exceed eligible balances.
* Credit-note journal and receivable effects are correct.

## Receivable tests

Verify:

* Patient, insurance, and sponsor receivables remain separate.
* Aging buckets are correct.
* Partial payment updates balances correctly.
* Receivables remain until settled, credited, cancelled, or written off.
* Write-offs preserve the original financial history.

## Claims tests

Verify:

* Claim validation identifies blocking and warning findings.
* Claims cannot be submitted with unresolved blocking findings.
* Claim submission preserves references.
* Rejected claims can be corrected and resubmitted.
* Partial settlement updates claim and receivable balances correctly.
* Claim payments post through the accounting pipeline.

## General Ledger tests

Verify:

* Posted transactions create balanced journal entries.
* General Ledger reports derive from posted journal data.
* Trial Balance remains balanced.
* Posted journals cannot be directly edited or deleted.
* Reversals create traceable correcting entries.
* Closed accounting periods block normal posting.

## Reconciliation tests

Verify:

* Cash reconciliation matches cashier sessions.
* Bank transactions can remain unmatched without being deleted.
* Digital-payment transactions reconcile by provider reference and amount.
* Variances remain visible until resolved.
* Reconciliation actions are audited.

## Redirect tests

Verify:

* Login redirects to `/finance`.
* Switching to Finance redirects to `/finance`.
* Billing, invoice, payment, cashier, claim, refund, credit-note, journal, and reconciliation actions remain under `/finance/*`.
* No redirect loops occur.
* JSON, API, payment callback, insurance callback, provider webhook, signed, print, export, and integration requests are not incorrectly redirected.

## Privacy and audit tests

Verify:

* Patient masking remains active.
* Protected fields require permission.
* Clinical context is limited to what Finance requires.
* Bank, provider, and financial details are permission-controlled.
* Finance actions generate required audit records.
* Sensitive values are not exposed through alternate Finance views.

Run focused Finance workspace tests and essential route, view, localization, billing, journal, reconciliation, privacy, and audit checks during implementation.

Do not run the full UHMS suite after each phase.

Run one broad relevant suite after all Finance workspace phases are complete.

---

# Phase 50 — Manual Acceptance Scenarios

## Scenario A — Finance login

1. Log in as a user whose active department type is `finance`.
2. Confirm the landing URL is `/finance`.
3. Confirm the Finance-specific menu is displayed.
4. Confirm unrelated department menus are absent.

## Scenario B — Visit billing review

1. Open the billing worklist.
2. Select a patient visit.
3. Confirm services, prices, payer allocation, discounts, and responsibilities.
4. Resolve any billing exceptions.
5. Finalize the invoice.
6. Confirm all routes remain under `/finance/*`.

## Scenario C — Insurance-price calculation

1. Open an insured visit.
2. Confirm the selected insurer’s price is used.
3. Confirm coverage is calculated from the insurance price.
4. Confirm patient responsibility is correct.
5. Confirm the cash price is not incorrectly used as the coverage basis.

## Scenario D — Partial payment

1. Open an unpaid invoice.
2. Collect part of the invoice amount.
3. Confirm the invoice becomes partially paid.
4. Confirm the remaining receivable is correct.
5. Confirm the payment and journal entries are recorded.

## Scenario E — Previous balance

1. Open a patient with an unpaid previous visit.
2. Confirm previous, current, and total balances are displayed separately.
3. Confirm the oldest unpaid invoice and age are correct.
4. Confirm each visit keeps its original invoice.

## Scenario F — Cross-visit allocation

1. Collect a payment intended for several unpaid invoices.
2. Select oldest-first allocation.
3. Confirm multiple invoice-scoped payments are created.
4. Confirm each invoice receives the correct allocation.
5. Confirm each payment posts through the normal payment service.

## Scenario G — Cashier session

1. Open a cashier session with a float.
2. Collect cash and digital payments.
3. Record a refund or reversal.
4. Close the session.
5. Count cash.
6. Confirm expected cash and variance are correct.
7. Confirm a supervisor reviews the variance where required.

## Scenario H — Payment reversal

1. Open a posted payment.
2. Request a reversal.
3. Record the reason.
4. Complete the required approval.
5. Confirm the original payment remains visible.
6. Confirm the receivable and journal entries are restored correctly.

## Scenario I — Refund

1. Open an eligible credit balance.
2. Request a refund.
3. Approve it using a separate authorized user.
4. Complete the refund.
5. Confirm the refundable balance, cashier session, and journal entries update correctly.

## Scenario J — Credit note

1. Open a finalized invoice containing an incorrect charge.
2. Create a credit note.
3. Approve and issue it.
4. Confirm the original invoice remains preserved.
5. Confirm the receivable and journal effects are correct.

## Scenario K — Claim submission

1. Open a draft insurance claim.
2. Run validation.
3. Resolve blocking findings.
4. Submit the claim.
5. Record the insurer reference.
6. Confirm the claim state and insurance receivable update.

## Scenario L — Claim rejection and resubmission

1. Open a rejected claim.
2. Record the rejection reasons.
3. Correct the eligible items.
4. Resubmit the claim.
5. Confirm the full claim history remains visible.

## Scenario M — Claim payment

1. Record an insurer settlement.
2. Allocate the payment to claims and invoices.
3. Record deductions and rejected balances.
4. Confirm partially paid claims remain open.
5. Confirm receivables and journals update correctly.

## Scenario N — General Ledger

1. Open a payment’s journal entry.
2. Confirm debit and credit entries balance.
3. Open the General Ledger.
4. Confirm the payment appears in the expected accounts.
5. Confirm the Trial Balance remains balanced.

## Scenario O — Digital payment reconciliation

1. Import or view provider transactions.
2. Match an internal payment to a provider reference.
3. Leave one transaction unmatched.
4. Confirm the unmatched item remains visible.
5. Resolve a variance with an audited reason.

## Scenario P — Active department scoping

1. Use a user assigned to multiple Finance departments or branches.
2. Switch the active department.
3. Confirm cashier sessions, invoices, reports, and approval worklists change to the selected Finance context.
4. Confirm unauthorized branches are not visible.

## Scenario Q — Permission control

1. Remove refund-approval permission.
2. Confirm the approval action disappears.
3. Enter the approval route directly.
4. Confirm access is denied.

## Scenario R — Legacy compatibility

1. Enter a generic invoice, payment, claim, or receivable route as a Finance user.
2. Confirm it safely resolves or redirects to the Finance equivalent where configured.
3. Confirm APIs, provider callbacks, insurance callbacks, signed URLs, print routes, and exports remain unaffected.

---

# Acceptance Criteria

The implementation is accepted only when all the following are true:

1. Users with an active department type of `finance` receive a dedicated Finance menu.
2. Their default dashboard uses `/finance`.
3. Supported Finance pages use `/finance/*` URLs.
4. Route names use the `finance.*` namespace.
5. Billing, invoices, payments, receivables, claims, journals, and reconciliation pages preserve Finance workspace context.
6. Forms submit through Finance routes.
7. Redirects remain inside the Finance workspace.
8. Breadcrumbs and active menu states are Finance-aware.
9. Permissions and enabled modules control menu visibility.
10. A non-Finance department user cannot access the workspace.
11. Multi-department users are evaluated using the active department.
12. Facility, branch, cashier, payer, and accounting scopes are respected.
13. Existing billing, invoice, payment, receivable, insurance, claims, journal, reconciliation, and audit logic is reused.
14. Core accounting and payment logic is not duplicated.
15. Insurance coverage uses the selected insurer’s price.
16. Finalized invoices cannot be silently edited.
17. Payments post through the existing payment and journal pipeline.
18. Partial payments preserve accurate receivable balances.
19. Previous and current patient balances remain separated.
20. Cross-visit tenders use multiple invoice-scoped payments where required by the accounting architecture.
21. Each visit keeps its own invoice and journal history.
22. Cashier sessions preserve opening, collection, refund, reversal, and closing integrity.
23. Cash variances require review where configured.
24. Completed payments cannot be silently deleted.
25. Reversals preserve original payments and restore receivables correctly.
26. Refunds cannot exceed refundable balances.
27. Refunds and reversals remain distinct workflows.
28. Discounts and credit notes preserve invoice history.
29. Patient, insurance, and sponsor receivables remain separately identifiable.
30. Claims validation, submission, rejection, resubmission, and settlement remain traceable.
31. General Ledger and Trial Balance derive from posted journal entries.
32. Posted journals cannot be silently edited or deleted.
33. Accounting-period controls remain active.
34. Reconciliation preserves unmatched transactions and exception history.
35. Separation of duties is enforced where configured.
36. Patient privacy and financial-data security remain fully active.
37. Generic routes remain functional for other departments and integrations.
38. APIs, payment callbacks, provider webhooks, insurance callbacks, signed URLs, print routes, and exports are not incorrectly redirected.
39. Relevant Finance actions are audited.
40. English and French localization are complete and in parity.
41. Focused Finance workspace tests pass.
42. One broad relevant suite passes after all phases are complete.
43. No broken links, route loops, duplicate route names, branch leakage, unbalanced journals, duplicate payments, silent receivable deletion, or accounting-history replacement remains.

---

# Deliverables

Provide:

1. Finance workspace route group.
2. Finance-specific controllers or thin adapters where required.
3. Finance operations dashboard.
4. Finance department menu profile.
5. Billing worklists.
6. Visit billing workspace.
7. Invoice lifecycle integration.
8. Payment-collection integration.
9. Partial-payment and overpayment handling.
10. Deposit and advance-payment workflows.
11. Previous patient balance integration.
12. Cross-visit payment allocation.
13. Accounts Receivable worklists.
14. Receivable-aging integration.
15. Cashier-session workflow.
16. Cashier closing and variance reconciliation.
17. Payment-reversal workflow.
18. Refund workflow.
19. Discount workflow.
20. Credit-note workflow.
21. Invoice-cancellation workflow.
22. Write-off workflow.
23. Insurance billing integration.
24. Claims validation, submission, rejection, resubmission, and settlement.
25. Sponsor-account integration.
26. Payment-gate policy visibility.
27. Visit billing override integration.
28. Journal and General Ledger visibility.
29. Chart of Accounts visibility.
30. Trial Balance.
31. Accounting-period controls.
32. Cash, bank, digital-payment, insurance, and sponsor reconciliation.
33. Workspace-aware URL resolver updates.
34. Workspace-aware redirect resolver updates.
35. Updated shared links and forms.
36. Login and department-switch integration.
37. Finance breadcrumbs and active-menu handling.
38. Permission and separation-of-duty integration.
39. Patient privacy and financial-data security integration.
40. English and French localization.
41. Focused feature tests.
42. A final implementation report containing:

* Files created
* Files modified
* Finance route map
* Finance menu map
* Dashboard metrics
* Billing workflow
* Invoice lifecycle
* Payment behaviour
* Previous-balance behaviour
* Cross-visit allocation behaviour
* Cashier-session behaviour
* Refund and reversal behaviour
* Discount and credit-note behaviour
* Receivable behaviour
* Insurance and claims behaviour
* Sponsor-account behaviour
* Payment-gate and billing-override behaviour
* Journal and General Ledger behaviour
* Reconciliation behaviour
* Active-department and branch scoping
* Reused services
* Redirect behaviour
* Permissions used
* Separation-of-duty rules
* Patient privacy checks
* Financial-integrity checks
* Audit events
* Tests executed
* Test results
* Remaining limitations, if any

Implement the work fully.

Do not stop at planning, route registration, menu configuration, dashboard layout, invoice listing, payment collection, or report creation alone. The final implementation must provide a functional, secure, auditable, department-specific Finance workspace throughout the complete hospital billing, payment, receivable, claims, accounting, and reconciliation lifecycle.
