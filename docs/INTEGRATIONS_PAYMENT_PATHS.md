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

## Path 3 — Inline on the invoice screen (NEW)

Take a mobile-money payment **without leaving the invoice**, so it slots straight into
the normal billing → accounting flow and deducts the bill on the same page.

- **Where:** the **"Pay by Mobile Money"** card in the right column of the invoice
  screen (`/admin/billing/invoices/{invoice}`), right beneath the manual "Receive
  payment" card. It appears only when the `payment_gateway` module is on **and** an
  active provider exists (otherwise it is invisible — zero clutter).
- **Who:** `integrations.payments.transactions.initiate` to charge,
  `integrations.payments.transactions.verify` to recheck (the invoice page itself needs
  the usual `invoices.view`).
- **Flow:**
  1. Cashier enters the payer phone + network and clicks **Charge now** — a provider
     transaction is created for the invoice's outstanding balance and the prompt goes to
     the payer's phone. **The invoice is not changed yet.**
  2. The pending charge shows on the same card with a **Recheck** button (and the
     provider callback / scheduler will also resolve it).
  3. On verification the existing `PaymentService` creates the UHMS payment, **deducts
     the invoice balance and posts to accounting** — exactly like a manual payment — and
     the cashier lands back on the invoice with the updated balance.

### Why it's a clean seam (no architecture break)
- The invoice screen only gains **one line**:
  `<x-integrations.invoice-payment :invoice="$invoice" />`.
- All gating + data lives in the integration-owned component
  (`App\View\Components\Integrations\InvoicePayment`); the billing `InvoiceController`
  is **never touched** — no provider logic in billing controllers.
- The charge/verify actions live in
  `App\Http\Controllers\Admin\Integrations\InvoiceGatewayPaymentController`, which only
  calls `PaymentGatewayService` and redirects back to the invoice.
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
| Entry point | Payments screen | Integrations area / `/pay/{token}` / SMS | Invoice screen card |
| Needs payment_gateway module | No | Yes | Yes |
| Creates UHMS payment | Immediately | After verification | After verification |
| Deducts invoice + posts accounting | Yes (PaymentService) | Yes (PaymentService) | Yes (PaymentService) |
| Billing controllers changed | — | No | No (component only) |

## Tests

- `IntegrationsInlineInvoicePaymentTest` — inline charge creates a transaction (no UHMS
  payment), inline verify creates the payment + deducts the invoice, paid-invoice guard,
  permission gate, and the component shows/hides with the gateway. (5/5)
- Path 2 is covered by `IntegrationsGatewayTest`, `IntegrationsPhase2Test`,
  `IntegrationsPhase3Test`, `NaloPaymentProviderTest`.
