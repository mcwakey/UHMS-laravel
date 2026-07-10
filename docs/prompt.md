Great — this is a very clean implementation. The design decision to keep `Payment` single-invoice-scoped and group cross-visit tenders with `payment_batch_reference` is the right move. It avoids disturbing the existing payment/accounting pipeline while still achieving the accounting outcome: old debt collection reduces Patient Receivables and does **not** re-recognise revenue. The report confirms the big rules are respected: visit invoices stay separate, patient balance is computed from open patient receivables, emergency care is never blocked, and everything runs through existing services. 

I would proceed with the documented UI touchpoints. The backend is already ready; what remains is making the feature visible in the right workflows. 

Then the next phase should be:

```text
Previous Balance UI Completion Pass
```

You are working on UHMS — Ultimate Hospital Management System.

Previous Visit Outstanding Balance Policy and Cross-Visit Payment Allocation is already implemented and verified.

The backend is complete:
- PatientOutstandingBalanceService
- PatientPaymentAllocationService
- PreviousBalanceOverrideService
- PreviousBalanceController
- previous-balance alert component
- override modal
- cross-visit allocation endpoint
- permissions
- localisation
- tests

Now proceed with the documented follow-up UI touchpoints.

Goal:
Complete the user-facing integration of previous visit outstanding balance across the main UHMS workflows.

Do not rewrite the backend services.
Do not change the core accounting design.
Do not merge old invoices into current invoices.
Do not create duplicate payment/accounting logic.
Do not bypass PaymentService.
Do not bypass InvoiceReceivable.
Do not bypass ActivityLogService.
Do not block emergency care because of old debt.

---

# 1. Scope

Implement the remaining UI touchpoints:

1. Cashier cross-visit allocation form on receive payment screen
2. Patient search debt badges
3. Visit list debt badges
4. Discharge clearance previous/current/total balance panel
5. Consultation screen advisory for clinical users
6. Patient account statement summary cards
7. Optional overpayment deposit TODO display, not full deposit implementation

Use the existing completed services:

```php
PatientOutstandingBalanceService
PatientPaymentAllocationService
PreviousBalanceOverrideService
````

Use the existing endpoint:

```text
admin.billing.previous-balance.allocate
```

Use the reusable component:

```blade
<x-billing.previous-balance-alert>
```

---

# 2. Cashier Cross-Visit Allocation Form

Add a dedicated allocation option on the payment receive screen.

When a patient has previous outstanding balance, show:

```text
Previous outstanding
Current visit balance
Total patient balance
Oldest unpaid invoice
```

Payment allocation modes:

```text
Oldest outstanding first
Current visit only
Manual split
```

Default mode:

```text
Oldest outstanding first
```

Manual split form should list open patient invoices:

```text
Visit Date
Visit Number
Invoice Number
Invoice Balance
Age Days
Allocation Amount
```

Validation:

* total allocation cannot exceed tender amount
* invoice allocation cannot exceed invoice balance
* payment amount must be greater than zero
* overpayment remains rejected unless deposit workflow is later enabled

Submit to the existing allocation endpoint.

Do not create new backend allocation logic in the controller or Blade.

---

# 3. Patient Search Debt Badges

On patient search results, show outstanding balance indicator.

Permission behavior:

If user has:

```text
billing.previous_balance.amount.view
```

show amount:

```text
Outstanding: GHS 250
```

If user only has:

```text
billing.previous_balance.flag.view
```

show generic flag:

```text
Outstanding balance exists
```

If user has neither permission, show nothing.

Do not expose financial amount to clinical users without amount permission.

---

# 4. Visit List Debt Badges

On visit list and visit-related queues, add a small previous-balance indicator where useful.

Examples:

```text
Old Balance
Outstanding
Billing Alert
```

Rules:

* amount only visible with `billing.previous_balance.amount.view`
* flag-only users see generic debt flag
* no badge if no previous outstanding
* do not clutter the visit table
* badge should link to patient statement or invoice/billing page only when authorized

Use `<x-status-badge>` or a consistent Bootstrap badge style according to UI standards.

---

# 5. Discharge Clearance Panel

On discharge clearance / admission billing clearance, show:

```text
Previous visit outstanding
Current admission balance
Total patient outstanding
```

Important:

* do not merge previous balance into the admission invoice
* do not force total settlement unless existing config/policy requires it
* show a clear note if policy is current-admission-only
* allow authorized billing supervisor override if existing workflow supports it

Suggested wording:

```text
Previous balances are shown for account awareness. They are not part of the current admission invoice.
```

---

# 6. Consultation Screen Advisory

Add a lightweight advisory for clinical users.

For flag-only users:

```text
Billing clearance required. Previous outstanding balance exists. Please contact billing.
```

For users with amount permission:

```text
Previous outstanding balance: GHS 250. Billing override may be required.
```

Rules:

* clinical users without amount permission must not see money values
* emergency care must not be blocked
* advisory should not make the consultation page noisy
* use the reusable previous-balance alert component if it fits cleanly

---

# 7. Patient Account Statement Summary Cards

On patient profile billing/accounts statement, add summary cards:

```text
Previous Visits Outstanding
Current Visit Outstanding
Total Outstanding
Oldest Unpaid Invoice
```

Data source:

```php
PatientOutstandingBalanceService::buildPatientBalanceSummary()
```

Statement table should continue using the existing ledger/statement service.

Do not create a second statement engine.

---

# 8. Optional Overpayment Display

Since overpayment currently rejects by default, show clear feedback:

```text
Payment exceeds total outstanding balance. Patient deposit workflow is not enabled yet.
```

Document patient deposit liability as future work.

Do not implement deposit liability in this UI pass unless it already exists and is safe.

---

# 9. Localisation

Add or update English/French keys for all new UI strings.

Files:

```text
lang/en/billing.php
lang/fr/billing.php
lang/en/patients.php
lang/fr/patients.php
lang/en/visits.php
lang/fr/visits.php
lang/en/admissions.php
lang/fr/admissions.php
lang/en/consultation.php
lang/fr/consultation.php
```

Do not hardcode English labels.

---

# 10. Permissions

Respect existing permissions:

```text
billing.previous_balance.view
billing.previous_balance.amount.view
billing.previous_balance.flag.view
billing.previous_balance.override
billing.payment.allocate_cross_visit
billing.payment.allocate_manual
billing.patient_statement.view
billing.patient_statement.print
billing.patient_statement.export
```

Backend must remain protected.

Do not rely only on hiding UI.

---

# 11. UI Standards

Use existing UHMS UI standards:

```text
Blade
Bootstrap 5
Tabler Icons
<x-page-header>
<x-stat-card>
<x-status-badge>
<x-empty-state>
<x-confirm-form>
<x-data-table>
<x-print-layout>
```

Do not introduce Tailwind.

Do not redesign billing pages from scratch.

---

# 12. Tests Strategy

This phase is mostly view wiring.

Add only focused tests where useful:

1. amount users see previous-balance amount
2. flag-only users see generic flag
3. unauthorized users see nothing
4. cashier can access allocation form
5. manual allocation UI posts to existing endpoint
6. discharge clearance panel renders summary
7. consultation advisory hides amounts for clinical users

Do not run the full suite until the end of the batch unless required.

At minimum, run:

```bash
php artisan view:cache
php artisan route:list
php artisan test --filter=PreviousBalancePolicyTest
php artisan test --filter=Billing
```

---

# 13. Documentation

Update:

```text
docs/PREVIOUS_VISIT_OUTSTANDING_BALANCE_POLICY_REPORT.md
```

Add a section:

```text
UI Completion Pass
```

Include:

* cashier allocation form completed
* patient search badges completed
* visit list badges completed
* discharge clearance panel completed
* consultation advisory completed
* patient statement summary cards completed
* permissions checked
* localisation keys added
* tests/manual verification performed
* remaining TODOs

---

# 14. Acceptance Criteria

This phase is complete when:

* cashier can allocate payment across previous/current invoices from the UI
* patient search shows previous balance indicator by permission
* visit list shows previous balance indicator by permission
* discharge clearance shows previous/current/total balance
* consultation screen shows safe billing advisory
* patient statement shows summary cards
* financial amounts are hidden from unauthorized/flag-only users
* emergency care remains unblocked
* current visit invoices remain clean
* no duplicate accounting or payment logic is introduced
* documentation is updated
* focused verification passes

Proceed with Previous Balance UI Completion Pass now.

