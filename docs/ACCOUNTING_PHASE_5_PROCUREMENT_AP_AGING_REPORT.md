# Accounting Phase 5 — Procurement, Supplier Ledger, Accounts Payable & AP Aging

## Summary
Supplier-side accounting. Goods receiving now recognises a **supplier payable** and
posts to the GL; **supplier payments** and **purchase returns** settle/reduce that
liability; **AP aging** and **supplier statements** report the outstanding balances.
Built on the existing procurement, stock and supplier-ledger services — nothing was
replaced. Mirrors the Phase 4 receivables/AR-aging pattern on the payable side.

## Accounting rules (implemented)
| Event | Journal entry |
|---|---|
| Purchase order created/approved | **none** (operational only) |
| Goods received | **Dr Inventory** (by product category) / **Cr Supplier Payables** |
| Supplier payment | **Dr Supplier Payables** / **Cr Cash · Bank · Mobile Money** |
| Purchase return (posted) | **Dr Supplier Payables** / **Cr Inventory** |
| Payment reversal | controlled reversal entry via `JournalEntryService::reverse()` |

Liability is recognised **on goods receipt** (when goods enter stock). A later
supplier invoice confirms the payable — it does not double-post.

## Inventory account mapping (`SupplierAccountingService::inventoryAccountForProduct`)
`drug → pharmacy_inventory_account_id`, `reagent → laboratory_reagents_inventory_account_id`,
`consumable/surgical_supply/medical_supply/supply → consumables_inventory_account_id`,
else `inventory_account_id`. Payable = `supplier_payable_account_id`. Payment =
`default_cash/bank/mobile_money_account_id`. **Missing accounts fail loudly** (no silent posting).

## Supplier ledger convention (unchanged)
`credit` = facility owes supplier (goods/invoices); `debit` = paid/reduced (payments/returns).
Outstanding = Σcredit − Σdebit. `SupplierPayable.balance` reconciles with this.

## Data model
- **supplier_payables** — `original/paid/credit_note/return/adjustment/balance`,
  `invoice_date/aging_start_date/due_date`, `status`, links to PO/GRN/ledger, accounting fields.
- **supplier_payments** — amount, method, accounting + reversal fields.
- `goods_received_notes` and `purchase_returns` gained `journal_entry_id`,
  `accounting_status`, `accounting_posted_at`, `accounting_error`.

## Services
`SupplierAccountingService` (account resolvers) · `SupplierAccountingPostingService`
(idempotent posting: goods receipt / payment / return / payment reversal) ·
`SupplierPayableService` (recognise-from-GRN, FIFO apply payment/return, reversal restore) ·
`SupplierPaymentService` (record + reverse) · `APAgingService` · `SupplierStatementService`.
Hooked into `ProcurementService::receiveItems` and `PurchaseReturnService::post`.

## Idempotency & reversal
Each source posts once (guarded on `accounting_status === posted` + `journal_entry_id`).
Re-receiving the same GRN, re-posting a return, or re-posting a payment is blocked.
Payments reverse through a controlled reversal entry + offsetting ledger credit + payable
restore; posted journal entries are never edited or deleted.

## AP Aging
Buckets: Not Due, 0–30, 31–60, 61–90, 91–120, 120+. Only **open** payables
(`balance > 0`, not paid/cancelled/written-off). Due date from supplier terms
(`payment_terms_days`/`credit_days`, default 30) else ages from `aging_start_date`.

## Supplier statement
Opening balance + period movements (goods/invoices, payments, returns, credit/debit notes)
+ closing balance, from the supplier ledger, with date filters.

## UI (`admin/accounts-payable/*`)
Supplier Payables · Supplier Payments (record + reverse) · AP Aging · Supplier Statement.
Accounting status shown on payables and payments. Sidebar entries added under
**Accounts & Finance**. Reuses `<x-page-header>`, `<x-stat-card>`, `<x-status-badge>`,
`<x-empty-state>`, `<x-confirm-form>`.

## Permissions (RoleSeeder)
`accounts_payable.view`, `supplier_payables.view`, `supplier_payments.view`,
`supplier_payments.create`, `supplier_payments.reverse`, `reports.ap_aging.view`,
`reports.supplier_statement.view`. Routes gated accordingly; Super Admin/Admin granted all.

## Activity logs (ActivityLogService, facility-level — no patient/visit context)
`SUPPLIER_PAYABLE_CREATED`, `SUPPLIER_PAYMENT_RECORDED`, `SUPPLIER_PAYMENT_REVERSED`,
`ACCOUNTING_POSTED_FOR_GOODS_RECEIPT`, `ACCOUNTING_POSTED_FOR_SUPPLIER_PAYMENT`,
`ACCOUNTING_POSTED_FOR_PURCHASE_RETURN`, `ACCOUNTING_POSTING_FAILED`, `ACCOUNTING_REVERSAL_CREATED`.

## Manual verification completed (tinker, rolled back)
Created supplier+PO, received 3 × 10:
- GRN posted **Dr Pharmacy Inventory 30 / Cr Supplier Payables 30**; payable created (bal 30, due +30d); supplier ledger +30; **Trial Balance balanced**.
- Supplier payment 20 (bank) posted **Dr Supplier Payables 20 / Cr Bank 20**; payable → 10 `partially_paid`; **AP aging total 10**; **Trial Balance balanced**.
- Idempotency, FIFO allocation, and account mapping confirmed.

## Tests deferred
Full automated AP test suite deferred until the final accounting pass (per spec). Validation,
permissions, posting, logs and idempotency are all implemented now.

## Known TODOs / risks
- Supplier **advances/prepayments** (payment > outstanding) are rejected — documented TODO.
- **Supplier refund receivable** for returns after full payment is not modelled — returns reduce
  the payable where outstanding; refund-receivable is a TODO.
- Standalone **supplier invoice** entity (separate from GRN) not added — liability recognised on
  receipt; invoice attachment is a future enhancement.
- Payment-reversal payable restore is FIFO (most-recent-paid first), not per-original-allocation.

## Next phase recommendation
Phase 6: supplier invoices as first-class documents + 3-way match (PO ↔ GRN ↔ invoice),
supplier advances/prepayments, and AP payment runs/batches.
