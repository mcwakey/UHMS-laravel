<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankReconciliation;
use Carbon\Carbon;

class CashBankReconciliationService extends AbstractReconciliationDomainService
{
    public function calculate(Carbon $periodStart, Carbon $periodEnd, Carbon $asOf): array
    {
        $bankAccounts = BankAccount::query()->with('glAccount')->active()->get();
        $items = [];
        $sourceSnapshot = [];
        $glSnapshot = [];
        $subledger = 0.0;
        $glTotal = 0.0;

        foreach ($bankAccounts as $bankAccount) {
            $reconciliation = BankReconciliation::query()
                ->where('bank_account_id', $bankAccount->id)
                ->whereIn('status', [BankReconciliation::STATUS_APPROVED, BankReconciliation::STATUS_PREPARED])
                ->whereDate('period_end', '<=', $asOf)
                ->latest('period_end')
                ->first();
            $position = $reconciliation
                ? round(
                    (float) $reconciliation->statement_closing_balance
                    + (float) $reconciliation->outstanding_deposits_total
                    - (float) $reconciliation->outstanding_withdrawals_total
                    + (float) $reconciliation->adjustments_total,
                    2,
                )
                : (float) $bankAccount->opening_balance;
            $gl = $this->glBalance($bankAccount->glAccount, $asOf);
            $classification = ! $reconciliation
                ? 'source_data_issue'
                : (abs($position - $gl) < 0.01 ? 'balanced' : 'timing_difference');
            $items[] = $this->item(
                BankAccount::class,
                $bankAccount->id,
                $bankAccount->account_number_masked,
                $bankAccount->display_name,
                $bankAccount->glAccount,
                $position,
                $gl,
                $classification,
                [
                    'bank_reconciliation_id' => $reconciliation?->id,
                    'bank_reconciliation_status' => $reconciliation?->status,
                    'period_end' => $reconciliation?->period_end?->toDateString(),
                ],
            );
            $sourceSnapshot[] = ['bank_account_id' => $bankAccount->id, 'position' => $position, 'reconciliation_id' => $reconciliation?->id];
            $glSnapshot[] = ['account_id' => $bankAccount->gl_account_id, 'balance' => $gl];
            $subledger += $position;
            $glTotal += $gl;
        }
        $accounts = $bankAccounts->pluck('glAccount')->filter()->unique('id')->values();
        $items = array_merge($items, $this->manualJournalItems($accounts, $asOf));
        $availability = $bankAccounts->isEmpty() ? 'not_available' : 'available';
        if ($bankAccounts->isEmpty()) {
            $items[] = $this->item('bank_position', null, null, 'No active Phase B bank accounts are configured.', null, 0, 0, 'not_available');
        }

        return $this->result(
            $availability,
            $subledger,
            $glTotal,
            $items,
            ['bank_accounts' => $sourceSnapshot],
            ['accounts' => $glSnapshot],
            $bankAccounts->isEmpty() ? 'No active bank account is mapped to a GL account.' : null,
        );
    }
}
