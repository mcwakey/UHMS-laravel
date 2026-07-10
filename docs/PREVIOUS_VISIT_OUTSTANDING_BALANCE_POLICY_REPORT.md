# Previous Visit Outstanding Balance Policy & Cross-Visit Payment Allocation

**Status:** Implemented (backend + core UI + tests). Some secondary UI touchpoints
listed as follow-ups in *Known TODOs*.

**Module:** Billing / Payments · **Branch:** `beta-x`

---

## 1. Business policy implemented

When a patient carries unpaid balances from **previous** visits, UHMS now surfaces
that debt clearly, allows proper cross-visit payment allocation, and keeps each
visit's invoice and accounting clean.

The implementation obeys the golden rules from the spec:

- **Each visit keeps its own invoice and balance.** Old invoice lines are never
  merged into the current visit invoice, and no previous-balance line item is
  added to the current invoice.
- **The patient account balance is a *sum*** of unpaid invoice balances — it is a
  computed view, not a stored figure and not a new invoice.
- **No old revenue is recognised again.** Collecting old debt only reduces the
  existing receivable (Dr Cash / Cr Patient Receivables). No new revenue journal.
- **Emergency care is never blocked** by previous debt.
- Everything runs **through the existing** billing / payment / receivable /
  accounting / activity-log services — nothing is bypassed.

## 2. Visit-level vs patient-level balance

| Concept | Definition | Source of truth |
|---|---|---|
| **Visit-level balance** | What is owed for one specific visit | `InvoiceReceivable` rows for that `visit_id`, `payer_type = patient` |
| **Patient-level balance** | Sum of unpaid balances across **all** visits | `SUM(balance)` of all open patient `InvoiceReceivable` |
| **Previous outstanding** | Patient-level balance **excluding** the current visit | open patient receivables where `visit_id != current` |

Only **patient-responsibility** receivables are counted as "what the patient owes".
Insurance / sponsor / corporate receivables are owed by third parties and are
settled through their own workflows (claims, sponsor/corporate payments).

Example (from the spec):

```
Previous visit balance:  GHS 250   (its own invoice, untouched)
Current visit invoice:   GHS 100   (its own invoice, stays GHS 100)
Total patient balance:   GHS 350   (250 + 100, computed)
```

## 3. Key components

| Layer | File |
|---|---|
| Config | `config/billing.php` → `previous_balance_policy` |
| Detection / summary | `app/Services/Billing/PatientOutstandingBalanceService.php` |
| Cross-visit allocation | `app/Services/Billing/PatientPaymentAllocationService.php` |
| OPD override gate | `app/Services/Billing/PreviousBalanceOverrideService.php` |
| HTTP surface | `app/Http/Controllers/Billing/PreviousBalanceController.php` |
| Routes | `routes/web.php` → `admin.billing.previous-balance.{override,allocate}` |
| UI component | `resources/views/components/billing/previous-balance-alert.blade.php` |
| Override modal | `resources/views/billing/partials/previous-balance-override-modal.blade.php` |
| Migrations | `..._add_payment_batch_reference_to_payments_table.php`, `..._seed_previous_balance_permissions.php` |
| Tests | `tests/Feature/PreviousBalancePolicyTest.php` (10 tests) |

`PatientOutstandingBalanceService::buildPatientBalanceSummary()` returns:

```php
[
    'previous_outstanding'      => 250.00,
    'current_visit_outstanding' => 100.00,
    'total_outstanding'         => 350.00,
    'oldest_unpaid_invoice'     => Invoice,
    'oldest_age_days'           => 45,
    'ar_bucket'                 => '31-60',
    'previous_invoice_count'    => 1,
    'has_previous_outstanding'  => true,
]
```

## 4. OPD rule (previous-balance override gate)

Runs **separately** from the existing current-visit payment gate
(`BillingPolicyService`). For **OPD only**, if the patient's previous balance
exceeds `opd_requires_override_above_amount` (default GHS 100), non-emergency
service is **blocked** until an authorised override is approved. Config
`opd_requires_override_if_any_previous_balance` makes *any* previous balance
require an override.

The override reuses the existing `VisitBillingOverride` infrastructure with a new
type `PREVIOUS_BALANCE_OVERRIDE` (scope `VISIT`). Approval requires a reason and
`billing.previous_balance.override` permission, and records the reason, authorising
user, timestamp, patient/visit and `previous_balance_amount` to the activity log.

## 5. Emergency rule

Emergency and admission (running-bill) care contexts **always** return
"override not required" — `PreviousBalanceOverrideService::requiresOverride()`
short-circuits on care context via `BillingPolicyService::careContext()`. The UI
alert renders an **informational** ("emergency care cannot be blocked; billing
follow-up required") notice for staff with financial visibility, and never a block.

## 6. Admission / discharge rule

`admission_show_previous_balance_on_discharge` is honoured by rendering the same
`<x-billing.previous-balance-alert>` component wherever a visit/admission is in
scope. Discharge clearance policy itself (require-current-only vs require-total) is
left to the existing discharge clearance workflow — this feature *shows* the
previous + current + total figures but does not force total settlement. See
*Known TODOs* for the dedicated discharge-clearance panel.

## 7. Payment allocation rules

`PatientPaymentAllocationService` distributes **one physical tender** across the
patient's open invoices. Modes:

- `oldest_first` (default) — settle the oldest debt first; the current visit is
  intentionally settled **last**.
- `current_visit` — pay only the current visit invoice(s).
- `manual` — cashier supplies explicit `[{invoice_id, amount}]` allocations.

Guardrails (enforced in `validateAllocation()` + `planFromReceivables()`):

- payment amount must be `> 0`
- total allocation must not exceed the payment amount
- an allocation must not exceed the invoice's open balance
- old invoices stay old, current invoice stays current (never merged)
- overpayment beyond total patient balance is **rejected** by default
  (`overpayment_behaviour = reject`; `ignore` allocates what fits)

Worked example — patient pays GHS 300 (prev 250, current 100):

```
GHS 250 → old visit invoice        (invoice PAID)
GHS  50 → current visit invoice     (balance GHS 50 remaining)
```

### Architecture decision — why several Payment rows, not one

The platform ties a `Payment` to a **single** invoice + receivable, and the whole
accounting / AR pipeline assumes that. Rather than rework that (and risk existing
flows), a cross-visit tender is recorded as **several per-invoice `Payment` rows**
via the existing `PaymentService::recordPayment()`:

- each visit keeps its own clean invoice **and** its own
  `Dr Cash / Cr Patient Receivables` journal entry;
- the rows are grouped by a shared `payment_batch_reference` (new nullable column
  on `payments`) so cashier / statement / audit can show them as one tender;
- one `CROSS_VISIT_PAYMENT_ALLOCATED` activity log records the distribution.

This nets to the same accounting effect as the spec's "single journal entry"
option (the spec permits *either*), while reusing 100% of the existing payment,
receivable-sync and accounting-posting code.

## 8. Accounting treatment

- Showing a previous balance creates **no** journal entry.
- Starting a new visit for a patient with old debt creates **no** journal entry.
- Receiving payment posts, per target invoice, `Dr Cash/Bank/MoMo · Cr Patient
  Receivables` through the unchanged `PaymentAccountingPostingService`. **No
  revenue is credited** when collecting old debt.

## 9. AR aging treatment

Old balances remain in AR aging until settled — the feature reads directly from
`InvoiceReceivable` (the same ledger `ARAgingService` uses), so:

- each invoice's balance stays in its original aging bucket;
- payment reduces that invoice's receivable balance;
- the current visit gets its own `aging_start_date`;
- the balance summary exposes `oldest_age_days` + `ar_bucket` per patient using
  the same bucket thresholds as `ARAgingService`.

## 10. UI changes

Two reusable components drive all touchpoints:

- **`<x-billing.previous-balance-alert>`** — permission-aware advisory banner:
  previous / current / total outstanding, oldest-invoice age + aging bucket, and
  actions (view statement, collect old balance, request override). Flag-only users
  see a generic "outstanding balance exists" / "billing clearance required"
  message and **never** the amounts.
- **`<x-billing.outstanding-badge>`** — compact list badge (amount vs generic flag
  by permission).

Wired into:

| Location | View | What shows |
|---|---|---|
| Visit show | `visits/show.blade.php` | full alert + override modal |
| Invoice show | `billing/invoices/show.blade.php` | full alert |
| Consultation | `consultations/partials/session-context.blade.php` | advisory (flag-only for clinicians) |
| Discharge | `admissions/discharge.blade.php` | previous + current admission + total |
| Cashier "receive payment" | `billing/payments/receive.blade.php` | per-row previous-balance badge + **cross-visit allocation modal** (oldest-first / current-visit) |
| Patient statement | `billing/statements/show.blade.php` | summary stat-cards (previous / total / oldest / bucket) |
| Visit list | `visits/index.blade.php` | outstanding badge per row |
| Patient list | `patients/index.blade.php` | outstanding badge per row |

List badges use `PatientOutstandingBalanceService::totalOutstandingMap()` — **one
query per page**, no per-row work. Placement in consultation uses the structural
`session-context` partial to respect that page's line-count guard.

## 11. Permissions

Backend-enforced (not UI-only). Seeded both in `RoleSeeder` and a data migration
so existing databases pick them up.

| Permission | Purpose | Default roles |
|---|---|---|
| `billing.previous_balance.view` | See balance summary | Cashier, Accountant, Finance Mgr, Admin |
| `billing.previous_balance.amount.view` | See the amounts | Cashier, Accountant, Finance Mgr, Admin |
| `billing.previous_balance.flag.view` | See only a generic debt flag | + Receptionist, clinical roles |
| `billing.previous_balance.override` | Authorise OPD service despite debt | Accountant, Finance Mgr, Admin |
| `billing.payment.allocate_cross_visit` | Split a tender across visits | Cashier, Accountant, Finance Mgr, Admin |
| `billing.payment.allocate_manual` | Manual per-invoice allocation | Cashier, Accountant, Finance Mgr, Admin |
| `billing.patient_statement.{view,print,export}` | Patient statement | Cashier (view/print), Accountant, Finance Mgr, Admin |

## 12. Activity logs

Distinct from payment logs (payment log = money received; allocation log = how it
was distributed):

| Action | Module | When |
|---|---|---|
| `PREVIOUS_BALANCE_WARNING_SHOWN` | BILLING | warning surfaced (audit) |
| `PREVIOUS_BALANCE_OVERRIDE_APPROVED` | BILLING | OPD override approved |
| `PREVIOUS_BALANCE_OVERRIDE_REJECTED` | BILLING | override revoked |
| `CROSS_VISIT_PAYMENT_ALLOCATED` | PAYMENTS | tender distributed across ≥2 invoices |

`CROSS_VISIT_PAYMENT_ALLOCATED` carries `allocation_mode`, `payment_batch_reference`,
`total_amount`, `payment_ids` and the per-invoice `distribution`.

## 13. Localisation

English + French keys added to `lang/{en,fr}/billing.php` (previous/current/total
balance, allocation modes, override strings, emergency notice, statement labels).

## 14. Manual verification completed

Automated (10 tests, all green — `tests/Feature/PreviousBalancePolicyTest.php`):

1. Detects previous / current / total balances + aging bucket + oldest invoice.
2. Current visit invoice is never modified by previous debt (total & line count).
3. Oldest-first allocation clears old invoice then reduces current; batch grouped.
4. Manual allocation cannot exceed an invoice's balance.
5. Overpayment beyond total patient balance is rejected.
6. OPD override required above threshold, cleared after approval.
7. Emergency care never requires an override / is never blocked.
8. Below-threshold previous balance does not require an override.
9. Alert component shows amounts for authorised users.
10. Alert component hides amounts for flag-only (clinical) users.

Regression: existing `BillingTest`, `VisitBillingTest`, `BillingPaymentPolicyTest`
(27 tests) still pass.

## 15. UI completion pass (done)

The secondary UI touchpoints from the first phase are now wired (see §10 table):
cashier cross-visit allocation modal, patient/visit list debt badges, discharge
clearance panel, consultation advisory, and patient statement summary cards —
each permission-aware and covered by render smoke tests.

## 16. Remaining TODOs

- Overpayment → **patient deposit liability** workflow (currently `reject`; a
  credit/deposit ledger is out of scope for this feature).
- Discharge clearance currently *shows* previous + current + total; enforcing a
  configurable "settle total vs current-only" decision is left to the existing
  discharge clearance workflow.
- Pre-existing (unrelated): `ConsultationStructurePhase3Test` has one red
  assertion about inline `onsubmit=` handlers already present in
  `consultations/show.blade.php` — not introduced here.
