<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use Illuminate\Support\Carbon;

/**
 * Supplier statement built from the supplier ledger: opening balance, the
 * period's movements (goods received / invoices, payments, returns, credit/debit
 * notes) and closing balance.
 */
class SupplierStatementService
{
    public function statement(Supplier $supplier, ?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::today()->startOfMonth();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::today()->endOfDay();

        // Opening balance = net of all entries strictly before the period start.
        $opening = (float) SupplierLedgerEntry::query()
            ->where('supplier_id', $supplier->id)
            ->whereDate('entry_date', '<', $fromDate->toDateString())
            ->selectRaw('COALESCE(SUM(credit),0) - COALESCE(SUM(debit),0) AS bal')
            ->value('bal');

        $entries = SupplierLedgerEntry::query()
            ->where('supplier_id', $supplier->id)
            ->whereDate('entry_date', '>=', $fromDate->toDateString())
            ->whereDate('entry_date', '<=', $toDate->toDateString())
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $running = round($opening, 2);
        $rows = [];
        $totals = ['goods' => 0.0, 'payments' => 0.0, 'returns' => 0.0, 'credit_notes' => 0.0, 'debit_notes' => 0.0];

        foreach ($entries as $entry) {
            $running = round($running + (float) $entry->credit - (float) $entry->debit, 2);
            $rows[] = [
                'date' => optional($entry->entry_date)->format('d M Y'),
                'type' => $entry->entry_type,
                'description' => $entry->description,
                'debit' => (float) $entry->debit,
                'credit' => (float) $entry->credit,
                'balance' => $running,
                'journal_entry_id' => null,
            ];

            match ($entry->entry_type) {
                SupplierLedgerEntry::TYPE_GOODS_RECEIVED, SupplierLedgerEntry::TYPE_SUPPLIER_INVOICE => $totals['goods'] += (float) $entry->credit,
                SupplierLedgerEntry::TYPE_PAYMENT => $totals['payments'] += (float) $entry->debit,
                SupplierLedgerEntry::TYPE_RETURN_TO_SUPPLIER => $totals['returns'] += (float) $entry->debit,
                SupplierLedgerEntry::TYPE_CREDIT_NOTE => $totals['credit_notes'] += (float) $entry->debit,
                SupplierLedgerEntry::TYPE_DEBIT_NOTE => $totals['debit_notes'] += (float) $entry->credit,
                default => null,
            };
        }

        return [
            'supplier' => $supplier,
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'opening_balance' => round($opening, 2),
            'closing_balance' => $running,
            'rows' => $rows,
            'totals' => array_map(fn ($v) => round($v, 2), $totals),
        ];
    }
}
