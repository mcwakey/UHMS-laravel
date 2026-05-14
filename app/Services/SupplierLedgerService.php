<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierLedgerService
{
    /**
     * Record a supplier ledger entry and update the running balance.
     *
     * Convention:
     *   credit = amount the facility owes the supplier (e.g. goods received, invoices)
     *   debit  = amount paid / reduced (e.g. payments, returns, credit notes used)
     *
     * Outstanding supplier balance = SUM(credit) - SUM(debit).
     */
    public function recordEntry(
        Supplier $supplier,
        string $entryType,
        float $debit,
        float $credit,
        string $description,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?Carbon $entryDate = null,
        ?int $userId = null
    ): SupplierLedgerEntry {
        if ($debit < 0 || $credit < 0) {
            throw new \InvalidArgumentException('Debit/credit cannot be negative.');
        }
        if ($debit == 0 && $credit == 0) {
            throw new \InvalidArgumentException('At least one of debit/credit must be > 0.');
        }
        if (! in_array($entryType, SupplierLedgerEntry::types(), true)) {
            throw new \InvalidArgumentException("Unknown supplier ledger entry type: {$entryType}");
        }

        return DB::transaction(function () use ($supplier, $entryType, $debit, $credit, $description, $sourceType, $sourceId, $entryDate, $userId) {
            $current = $this->balance($supplier);
            $balanceAfter = $current + $credit - $debit;

            return SupplierLedgerEntry::create([
                'supplier_id'   => $supplier->id,
                'entry_date'    => ($entryDate ?? now())->toDateString(),
                'entry_type'    => $entryType,
                'source_type'   => $sourceType,
                'source_id'     => $sourceId,
                'description'   => mb_substr($description, 0, 500),
                'debit'         => $debit,
                'credit'        => $credit,
                'balance_after' => $balanceAfter,
                'created_by'    => $userId ?? Auth::id(),
            ]);
        });
    }

    /**
     * Outstanding amount the facility currently owes the supplier.
     * Positive = facility owes supplier.
     */
    public function balance(Supplier $supplier): float
    {
        $row = SupplierLedgerEntry::query()
            ->where('supplier_id', $supplier->id)
            ->selectRaw('COALESCE(SUM(credit),0) - COALESCE(SUM(debit),0) AS bal')
            ->first();

        return (float) ($row->bal ?? 0);
    }

    public function entriesQuery(Supplier $supplier)
    {
        return SupplierLedgerEntry::query()
            ->where('supplier_id', $supplier->id)
            ->orderBy('entry_date')
            ->orderBy('id');
    }
}
