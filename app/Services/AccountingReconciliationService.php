<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntryLine;
use App\Models\StockBalance;
use App\Models\SupplierPayable;
use Throwable;

/**
 * Reconciliation warnings (Phase 7): compares operational subledger balances
 * with their GL control-account balances and flags mismatches. Never blocks —
 * surfaces warnings for management.
 */
class AccountingReconciliationService
{
    public function checks(): array
    {
        return array_values(array_filter([
            $this->check('Receivables (AR)', ['1210', '1220', '1230', '1240'], false, fn () => $this->openReceivables()),
            $this->check('Supplier Payables (AP)', ['2110'], true, fn () => $this->openPayables()),
            $this->check('Inventory', ['1300', '1310', '1320', '1330', '1340'], false, fn () => $this->inventoryValue()),
        ]));
    }

    private function check(string $label, array $codes, bool $creditNormal, callable $operational): ?array
    {
        try {
            $gl = $this->glBalance($codes, $creditNormal);
            $op = round((float) $operational(), 2);
            $diff = round($gl - $op, 2);

            return [
                'label' => $label,
                'gl_balance' => $gl,
                'operational_balance' => $op,
                'difference' => $diff,
                'matched' => abs($diff) < 0.01,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function glBalance(array $codes, bool $creditNormal): float
    {
        $accountIds = Account::whereIn('code', $codes)->pluck('id');
        if ($accountIds->isEmpty()) {
            return 0.0;
        }
        $row = JournalEntryLine::query()
            ->whereIn('account_id', $accountIds)
            ->whereHas('journalEntry', fn ($q) => $q->ledgerAffecting())
            ->selectRaw('COALESCE(SUM(debit),0) d, COALESCE(SUM(credit),0) c')
            ->first();

        $debit = (float) ($row->d ?? 0);
        $credit = (float) ($row->c ?? 0);

        return round($creditNormal ? $credit - $debit : $debit - $credit, 2);
    }

    private function openReceivables(): float
    {
        if (! class_exists(\App\Models\InvoiceReceivable::class)) {
            return 0.0;
        }

        return (float) \App\Models\InvoiceReceivable::query()->open()->sum('balance');
    }

    private function openPayables(): float
    {
        return (float) SupplierPayable::query()->open()->sum('balance');
    }

    private function inventoryValue(): float
    {
        return (float) StockBalance::query()->sum('total_value');
    }
}
