# Logging Burn-Down — Billing / Claims

Module-focused burn-down. The guiding principle here was **reuse what already
exists, fill only the gaps, duplicate nothing**. Billing/Payment were already
well-covered; **Claims** was the real gap.

## 1. Write paths inspected

- **Billing:** `InvoiceObserver`, `BillingService` (invoice items, discount),
  `VisitBillingOverrideService` (deferred settlement / overrides),
  `BillingPolicyService` / `PaymentGateService`.
- **Payment:** `PaymentObserver`, `PaymentService`.
- **Claims:** `ClaimService`, `ClaimStatusService` (status funnel),
  `ClaimPreparationMirrorService`, models `Claim`/`ClaimItem`/`ClaimPayment`/`ClaimStatusLog`.

## 2. Existing billing/payment/claims logs found (REUSED, not duplicated)

| Already logged | By |
|----------------|----|
| `BILLING / INVOICE_CREATED`, `INVOICE_STATUS_CHANGED` (incl. cancelled/refunded), invoice deleted | `InvoiceObserver` |
| `PAYMENTS / PAYMENT_RECORDED`, `PAYMENT_REFUNDED` | `PaymentObserver` |
| Discount applied / override / removed | `BillingService` (discount work) |
| `DEFERRED_SETTLEMENT_APPROVED/REVOKED/COMPLETED`, `BILLING_OVERRIDE_REVOKED` | `VisitBillingOverrideService` (billing-policy work) |

**None of these were touched or duplicated.**

## 3. Actions now logged (the gap: CLAIMS)

Claims had **zero** activity logging. Filled via:

- **`ClaimStatusService::transition`** dual-write (the central status funnel) →
  `CLAIM_MARKED_READY`, `CLAIM_SUBMITTED`, `CLAIM_ACKNOWLEDGED`, `CLAIM_REVIEWED`,
  `CLAIM_APPROVED`, `CLAIM_PARTIALLY_APPROVED`, `CLAIM_REJECTED`, `CLAIM_RESUBMITTED`,
  `CLAIM_APPEALED`, `CLAIM_PAYMENT_RECORDED` (paid/partially-paid), `CLAIM_CANCELLED`.
  Covers submit / review / approve / reject / pay / appeal in one place.
- **`ClaimService`** non-transition logs → `CLAIM_PREPARED` (create / createFromInvoice),
  `CLAIM_ITEM_ADDED`, `CLAIM_ITEM_REMOVED`, `CCC_CODE_UPDATED`.

Module `CLAIMS`. The initial `DRAFT` status is set at creation (not a transition),
so `CLAIM_PREPARED` is logged once by `ClaimService` and not duplicated by the funnel.

## 4. Existing discount/policy logs reused

Discount and billing-policy (deferred settlement, override, waiver/credit via
override) events remain owned by their existing services — this burn-down added
**no** discount/policy logs, so there is no duplication.

## 5. Duplication risks avoided

- **Invoice / payment:** owned by `InvoiceObserver` / `PaymentObserver` — not re-logged.
- **Generic invoice-item add:** intentionally **not** logged here, because invoice
  items are already logged by their source modules (`PHARMACY / PRESCRIPTION_ITEMS_BILLED`,
  `PROCEDURE_BILLED`, lab billing) — a generic `INVOICE_ITEM_ADDED` would duplicate them.
- **Pharmacy billing-selection / MAR / stock:** untouched.

## 6. Payment / invoice balance context

Payment amount/method/invoice are already in the `PaymentObserver` log metadata;
claim logs carry `total_amount` / `approved_amount` / `paid_amount` in metadata.
No payment calculations changed.

## 7. Claims mirror behaviour

Claim preparation and item actions log under **CLAIMS** with the claim as subject
(`CLAIM_PREPARED`, `CLAIM_ITEM_ADDED/REMOVED`, `CCC_CODE_UPDATED`) — clearly claim
activity, **not** clinical-record edits. The original clinical entries are
untouched.

## 8. Context fields included

Via `Claim::toActivityContext()`: `patient_id`, `visit_id`, `invoice_id`,
`claim_id`, `insurance_provider_id`, `insurance_type_id`, plus `claim_item_id` /
`service_id` on item events, `old/new status`, reason, and amount metadata. New
persisted context keys: `claim_item_id`, `insurance_provider_id`,
`insurance_type_id`, `refund_id`.

## 9. Patient timeline verification

Tests assert the full claim lifecycle (submitted → reviewed → approved → paid),
rejection-with-reason, and CCC/item events appear via `getPatientTimeline()` with
module `CLAIMS`, `claim_id`/`claim_item_id`, old/new status, and readable
descriptions (*“Claim submitted: CLM-…”*, *“Claim rejected: … — Missing CCC code”*).

## 10. Tests added/passing

`tests/Feature/ClaimsLogTest.php` (**3**): status lifecycle on the timeline
(context + old/new); rejection-with-reason; CCC update + item-added. Regression:
35 tests (existing claims + payments + billing-policy) pass — no break, no duplicate.

## 11. logs:audit before/after

`73 → 73`. Billing/payment/claims logging lives in observers + service funnels;
controllers delegate, so any controller still flagged is a false positive.

## 12. Remaining Billing/Claims logging TODOs

- **Payment reversal** as a distinct event — `PaymentObserver::deleted` logs
  `PAYMENT_REFUNDED`; if a true reversal path exists separate from delete, add
  `PAYMENT_REVERSED` with reason.
- **`ClaimPayment` record** — if claim payments are recorded as `ClaimPayment` rows
  separately from `markPaid` (PAID transition), log `CLAIM_PAYMENT_RECORDED` there too.
- **Claim mirror item-level edits** (`ClaimPreparationMirrorService`) — add
  `CLAIM_MIRROR_UPDATED` for officer mirror selections/edits.
- **Invoice / receipt / claim print/export** — add `INVOICE_PRINTED`,
  `RECEIPT_PRINTED`, `CLAIM_EXPORTED` on official print/download.
- **Item-level claim review** (`reviewItem`) — currently only the aggregate
  `completeReview` transition is logged; add per-item review if wanted.

## 13. Files modified

`app/Services/Claims/ClaimStatusService.php` (dual-write funnel + event/description
helpers), `app/Services/ClaimService.php` (CLAIM_PREPARED / item / CCC logs +
`logClaimEvent` helper), `app/Models/Claim.php` (`toActivityContext()`),
`app/Services/ActivityLogService.php` (+claim/insurance context keys),
`tests/Feature/ClaimsLogTest.php` (new).
