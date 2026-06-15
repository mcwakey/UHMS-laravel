<?php

namespace App\Services;

use App\Models\SupplierPayable;
use Carbon\Carbon;

class PayablesReconciliationService extends AbstractReconciliationDomainService
{
    public function calculate(Carbon $periodStart, Carbon $periodEnd, Carbon $asOf): array
    {
        $account = $this->account('supplier_payable_account_id');
        $rows = SupplierPayable::query()
            ->with('supplier')
            ->open()
            ->whereDate('aging_start_date', '<=', $asOf)
            ->get();
        $subledger = round((float) $rows->sum('balance'), 2);
        $gl = $this->glBalance($account, $asOf);
        $items = [
            $this->item(
                'supplier_payable_control',
                null,
                'AP',
                'Supplier payables control',
                $account,
                $subledger,
                $gl,
                ! $account ? 'mapping_issue' : (abs($subledger - $gl) < 0.01 ? 'balanced' : 'unknown_difference'),
            ),
        ];

        foreach ($rows as $row) {
            $classification = $this->sourceClassification(SupplierPayable::class, $row->id, $row->accounting_status, $row->journal_entry_id);
            $attempt = $this->failedAttempt(SupplierPayable::class, $row->id);
            $attributedGl = $classification === 'balanced' ? (float) $row->balance : 0.0;
            $items[] = $this->item(
                SupplierPayable::class,
                $row->id,
                'AP-'.$row->id,
                $row->supplier?->name ?? 'Supplier payable',
                $account,
                (float) $row->balance,
                $attributedGl,
                $classification,
                [
                    'supplier_id' => $row->supplier_id,
                    'due_date' => $row->due_date?->toDateString(),
                    'posting_attempt_id' => $attempt?->id,
                    'comparison_basis' => $classification === 'balanced'
                        ? 'Posted payable balance attributed to the supplier control account; the control summary row holds the authoritative GL comparison.'
                        : 'Source exception awaiting posting or resolution.',
                ],
            );
        }
        $items = array_merge($items, $this->manualJournalItems(collect([$account])->filter(), $asOf));

        return $this->result(
            'available',
            $subledger,
            $gl,
            $items,
            ['record_count' => $rows->count(), 'supplier_count' => $rows->pluck('supplier_id')->unique()->count()],
            ['account_id' => $account?->id, 'balance' => $gl],
        );
    }
}
