# UHMS — The Three Payment Paths

How money is taken in UHMS. There are **three** ways to record a payment. All three
end at the same place — the existing `PaymentService::recordPayment()` — so receipts,
invoice balance deduction, receivable allocation and accounting posting behave
identically no matter which path is used.

```
Path 1  Manual / cash            cashier records it directly
Path 2  Provider-led / online    Integrations area, public link, or SMS link
Path 3  Inline on the invoice     "Pay by Mobile Money" card on the invoice screen
```

The golden rule for the online paths (2 & 3): **a provider transaction is NOT a UHMS
payment.** The UHMS payment (and therefore the invoice deduction + accounting) is only
created after the provider **verifies** the money was collected and the amount/currency
match. Duplicate callbacks or repeated rechecks never create a second payment.

---

## Path 1 — Manual / cash (unchanged core)

The original billing flow, untouched by the integration work.

- **Where:** Billing & Collections → **Payments / Receive Payments**, or the
  "Receive payment" card on the invoice screen.
- **Who:** `payments.create`.
- **What happens:** the cashier enters amount + method (cash, bank transfer, cheque,
  card, …) → `PaymentService::recordPayment()` posts the payment immediately, prints a
  receipt, deducts the invoice and posts to accounting.
- **Use for:** cash, cheque, POS/terminal, bank transfer — anything settled outside the
  integrated mobile-money gateway.

This path has no dependency on the payment_gateway module or any provider.

---

## Path 2 — Provider-led / online (Payment Gateway)

Mobile-money / online collection through the active payment provider (MTN MoMo,
NALOPAY, …). Three entry points, one engine (`PaymentGatewayService`).

**Prerequisites:** `payment_gateway` module enabled, exactly **one active provider**
configured (use the **Fake Payment** provider to rehearse end-to-end without a network),
and the provider must pass its go-live checklist before live activation.

### 2a. Staff-initiated transaction
- **Where:** Billing & Collections → **Payment API Transactions → Initiate payment**
  (`/admin/integrations/payments/transactions/*`).
- **Who:** `integrations.payments.transactions.initiate` / `.verify`.
- **Flow:** pick invoice + amount + method + payer phone → provider transaction created
  (`pending`) → customer approves on phone → **callback** or **manual verify** →
  verified ⇒ UHMS payment created.

### 2b. Public payment link / portal (patient self-service)
- **Where:** Billing & Collections → **Payment Request Links** (create a link) or the
  patient opens **`/pay/{token}`** (public, no login, rate-limited, module-gated).
- **Who (admin side):** `integrations.payments.request_links.manage`.
- **Flow:** patient opens the link → safe invoice summary (no internal ids, no clinical
  data) → enters phone → approves → pending → success → receipt (only after verified).

### 2c. Payment-request SMS
- **Where:** "Send payment request SMS" on the request-links screen / invoice — sends an
  SMS containing the public `{{payment_link}}` (opt-in, deduplicated, resend supported).
- Then the patient follows path 2b.

Operational tooling for path 2: **Payment Reconciliation** dashboard (+ CSV export),
**Provider Health**, the `integrations:payments-recheck-pending` scheduler, and
idempotent webhook handling at `…/api/integrations/payments/{code}/callback`.

---

## Path 3 — Inline in the Record Payment form (NEW)

Mobile money is **built into the existing Record Payment form** — there is no separate
card. The cashier picks a method and pays in one place; the bill is deducted through the
normal billing → accounting flow.

- **Where:** the **Record Payment** form on the invoice screen
  (`/admin/billing/invoices/{invoice}`) **and** every row of the **Receive Payments**
  page (`/admin/billing/payments/receive`).
- **Payment Method options:** Cash, **Mobile Money**, Bank Transfer, Card, Cheque.
  Cash / Bank Transfer / Card / Cheque record a **manual** payment exactly as before.
  (Mobile Money only appears when the `payment_gateway` module is on with an active
  provider and the user has `integrations.payments.transactions.initiate`.)
- **When "Mobile Money" is selected:** a **payer phone** field (pre-filled with the
  patient's phone, editable) and a **network** select (MTN / Telecel / AirtelTigo)
  appear. The **Amount (GH₵)** entered in the same form is used (capped at the balance).
- **Flow:**
  1. Cashier selects Mobile Money, confirms phone + network + amount, clicks the normal
     **Record Payment / Receive** button → the form posts to the payment gateway
     (`charge`) and a provider transaction is created; the prompt goes to the payer's
     phone. **The invoice is not changed yet.**
  2. The charge is resolved by the provider **callback**, the
     `integrations:payments-recheck-pending` scheduler, or a manual recheck on the
     **Payment API Transactions** / **Reconciliation** screen.
  3. On verification the existing `PaymentService` creates the UHMS payment, **deducts
     the invoice balance and posts to accounting** — exactly like a manual payment.

### Why it's a clean seam (no architecture break)
- The method dropdown drives everything: choosing **Mobile Money** reveals the phone +
  network and, on submit, JavaScript repoints that form's `action` to the gateway charge
  route (`data-gateway-charge`). The other four methods submit to the unchanged manual
  endpoint. If JS is unavailable the manual methods still work normally.
- The charge/verify actions live in
  `App\Http\Controllers\Admin\Integrations\InvoiceGatewayPaymentController`, which only
  calls `PaymentGatewayService` and redirects back to the invoice. The billing
  `InvoiceController` / `PaymentController` are **never touched** — no provider logic in
  billing controllers.
- Invoice totals, manual payment entry, and accounting posting rules are unchanged.

---

## Shared guarantees (all online paths)

- Invoice is **never** marked paid before provider verification.
- Amount **and** currency must match the request before a UHMS payment is created.
- Duplicate callbacks / repeated rechecks / page refreshes never duplicate a payment
  (idempotent — guarded by the transaction's `uhms_payment_id`).
- Verified provider payment → normal `PaymentService` → receipt + invoice status +
  receivable allocation + accounting posting (no separate/parallel accounting).
- SMS/notification failures never block billing, and provider credentials are encrypted,
  masked and never logged.

## Quick reference

| | Path 1 Manual | Path 2 Provider-led | Path 3 Inline |
|---|---|---|---|
| Entry point | Record Payment form | Integrations area / `/pay/{token}` / SMS | Record Payment form (method = Mobile Money) |
| Needs payment_gateway module | No | Yes | Yes |
| Creates UHMS payment | Immediately | After verification | After verification |
| Deducts invoice + posts accounting | Yes (PaymentService) | Yes (PaymentService) | Yes (PaymentService) |
| Billing controllers changed | — | No | No (Blade form + JS only) |

## Tests

- `IntegrationsInlineInvoicePaymentTest` — inline charge creates a transaction (no UHMS
  payment), inline verify creates the payment + deducts the invoice, paid-invoice guard,
  permission gate, and the typed amount is honoured (capped at balance). (5/5)
- Path 2 is covered by `IntegrationsGatewayTest`, `IntegrationsPhase2Test`,
  `IntegrationsPhase3Test`, `NaloPaymentProviderTest`.
