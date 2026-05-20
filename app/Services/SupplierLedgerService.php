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
        if ($sourceType === null && ! in_array($entryType, SupplierLedgerEntry::manualTypes(), true)) {
            throw new \InvalidArgumentException('This supplier ledger entry must be created by its source workflow.');
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
            ->with('creator')
            ->where('supplier_id', $supplier->id)
            ->orderBy('entry_date')
            ->orderBy('id');
    }

    public function recordManualEntry(Supplier $supplier, array $data): SupplierLedgerEntry
    {
        $entryType = $data['entry_type'] ?? '';
        if (! in_array($entryType, SupplierLedgerEntry::manualTypes(), true)) {
            throw new \InvalidArgumentException('Manual supplier ledger entries can only be Payment, Credit Note, or Debit Note.');
        }

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            $amount = max((float) ($data['debit'] ?? 0), (float) ($data['credit'] ?? 0));
        }
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        $debit = in_array($entryType, [SupplierLedgerEntry::TYPE_PAYMENT, SupplierLedgerEntry::TYPE_CREDIT_NOTE], true) ? $amount : 0.0;
        $credit = $entryType === SupplierLedgerEntry::TYPE_DEBIT_NOTE ? $amount : 0.0;

        return $this->recordEntry(
            supplier: $supplier,
            entryType: $entryType,
            debit: $debit,
            credit: $credit,
            description: $data['description'],
            entryDate: isset($data['entry_date']) ? Carbon::parse($data['entry_date']) : null,
        );
    }
}
