<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\GoodsReceivedNote;
use App\Models\Supplier;
use App\Models\SupplierPayable;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Lifecycle of supplier payables: recognise from goods receipt, then reduce as
 * payments / returns / credit notes are applied (FIFO, oldest first). The
 * payable balance reconciles with the supplier ledger.
 */
class SupplierPayableService
{
    public function __construct(
        protected SupplierAccountingService $accounts,
        protected SupplierAccountingPostingService $posting,
    ) {}

    /**
     * Recognise a payable from a goods receipt and post Dr Inventory / Cr AP.
     * Idempotent: returns the existing payable if this GRN already has one.
     */
    public function recognizeFromGoodsReceipt(GoodsReceivedNote $grn, ?int $ledgerEntryId = null): ?SupplierPayable
    {
        if (! $grn->supplier_id) {
            return null;
        }

        $existing = SupplierPayable::where('goods_received_note_id', $grn->id)->first();
        if ($existing) {
            return $existing;
        }

        $grn->loadMissing('items.product', 'supplier');

        // Group received value by inventory account (drug/reagent/consumable/…).
        $byAccount = [];
        $total = 0.0;
        foreach ($grn->items as $item) {
            $amount = round((float) $item->quantity_received * (float) $item->unit_cost, 2);
            if ($amount <= 0) {
                continue;
            }
            $account = $this->accounts->inventoryAccountForProduct($item->product);
            $byAccount[$account->id] ??= ['account' => $account, 'amount' => 0.0];
            $byAccount[$account->id]['amount'] += $amount;
            $total += $amount;
        }
        $total = round($total, 2);
        if ($total <= 0) {
            return null;
        }

        $receivedDate = $grn->received_date ? Carbon::parse($grn->received_date) : now();
        $termDays = (int) ($grn->supplier?->payment_terms_days ?? $grn->supplier?->credit_days ?? 30);
        $termDays = $termDays > 0 ? $termDays : 30;

        $payable = SupplierPayable::create([
            'supplier_id' => $grn->supplier_id,
            'purchase_order_id' => $grn->purchase_order_id,
            'goods_received_note_id' => $grn->id,
            'supplier_ledger_entry_id' => $ledgerEntryId,
            'original_amount' => $total,
            'balance' => $total,
            'invoice_date' => $receivedDate->toDateString(),
            'aging_start_date' => $receivedDate->toDateString(),
            'due_date' => $receivedDate->copy()->addDays($termDays)->toDateString(),
            'status' => SupplierPayable::STATUS_PENDING,
            'created_by' => auth()->id(),
        ]);

        $grn->forceFill(['supplier_payable_id' => $payable->id])->save();

        $this->log('SUPPLIER_PAYABLE_CREATED', $payable, [
            'supplier_id' => $payable->supplier_id,
            'goods_receipt_id' => $grn->id,
            'purchase_order_id' => $grn->purchase_order_id,
            'amount' => $total,
        ]);

        // Post Dr Inventory (by category) / Cr Supplier Payables.
        $this->posting->postGoodsReceipt($grn, $payable, array_values($byAccount));

        return $payable;
    }

    /**
     * Apply a supplier payment across the supplier's open payables (FIFO).
     */
    public function applyPaymentFifo(Supplier $supplier, float $amount): void
    {
        $this->applyFifo($supplier, $amount, 'paid_amount');
    }

    /**
     * Apply a supplier return across the supplier's open payables (FIFO).
     */
    public function applyReturnFifo(Supplier $supplier, float $amount): void
    {
        $this->applyFifo($supplier, $amount, 'return_amount');
    }

    /**
     * Restore payable balances when a supplier payment is reversed — reduce
     * paid_amount across the supplier's payables (most-recently-paid first) so
     * AP stays reconciled with the supplier ledger.
     */
    public function restoreFromPaymentReversal(Supplier $supplier, float $amount): void
    {
        $remaining = round($amount, 2);
        if ($remaining <= 0) {
            return;
        }

        $payables = SupplierPayable::query()
            ->where('supplier_id', $supplier->id)
            ->where('paid_amount', '>', 0)
            ->whereNotIn('status', [SupplierPayable::STATUS_CANCELLED, SupplierPayable::STATUS_WRITTEN_OFF])
            ->orderByDesc('id')
            ->get();

        foreach ($payables as $payable) {
            if ($remaining <= 0) {
                break;
            }
            $reduce = min($remaining, (float) $payable->paid_amount);
            if ($reduce <= 0) {
                continue;
            }
            $payable->forceFill(['paid_amount' => round((float) $payable->paid_amount - $reduce, 2)])->save();
            $payable->recompute();
            $remaining = round($remaining - $reduce, 2);
        }
    }

    private function applyFifo(Supplier $supplier, float $amount, string $column): void
    {
        $remaining = round($amount, 2);
        if ($remaining <= 0) {
            return;
        }

        $payables = SupplierPayable::query()
            ->where('supplier_id', $supplier->id)
            ->open()
            ->orderBy('aging_start_date')
            ->orderBy('id')
            ->get();

        foreach ($payables as $payable) {
            if ($remaining <= 0) {
                break;
            }
            $applied = min($remaining, (float) $payable->balance);
            if ($applied <= 0) {
                continue;
            }
            $payable->forceFill([$column => round((float) $payable->{$column} + $applied, 2)])->save();
            $payable->recompute();
            $remaining = round($remaining - $applied, 2);
        }
        // Any unallocated remainder (e.g. advance/overpayment) is intentionally
        // left on the supplier ledger only — supplier advances are a documented TODO.
    }

    private function log(string $action, SupplierPayable $payable, array $context): void
    {
        try {
            app(ActivityLogService::class)->log(
                LogModule::PURCHASE_ORDERS,
                $action,
                array_merge(['severity' => LogSeverity::INFO], $context),
                $payable,
                str_replace('_', ' ', ucfirst(strtolower($action))),
            );
        } catch (Throwable) {
            // Logging must never break payable recognition.
        }
    }
}
