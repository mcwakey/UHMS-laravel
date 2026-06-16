<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetRevision;
use App\Models\BudgetTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BudgetRevisionService
{
    public function createApprovedRevision(Budget $budget, array $lines, string $reason, User $user): BudgetRevision
    {
        if (! $budget->isApprovedLike()) {
            throw ValidationException::withMessages(['budget' => 'Only approved budgets can be revised.']);
        }

        return DB::transaction(function () use ($budget, $lines, $reason, $user) {
            $revision = BudgetRevision::create([
                'budget_id' => $budget->id,
                'revision_number' => $this->nextNumber('BR', 'budget_revisions', 'revision_number'),
                'status' => BudgetRevision::STATUS_APPROVED,
                'reason' => $reason,
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            foreach ($lines as $line) {
                $revision->lines()->create([
                    'department_id' => $line['department_id'] ?? null,
                    'account_id' => $line['account_id'],
                    'amount_delta' => round((float) $line['amount_delta'], 2),
                    'notes' => $line['notes'] ?? null,
                ]);
            }

            return $revision->load('lines');
        });
    }

    public function createApprovedTransfer(Budget $budget, array $data, User $user): BudgetTransfer
    {
        if (! $budget->isApprovedLike()) {
            throw ValidationException::withMessages(['budget' => 'Only approved budgets can receive transfers.']);
        }

        return BudgetTransfer::create([
            'budget_id' => $budget->id,
            'transfer_number' => $this->nextNumber('BT', 'budget_transfers', 'transfer_number'),
            'status' => BudgetTransfer::STATUS_APPROVED,
            'from_department_id' => $data['from_department_id'] ?? null,
            'from_account_id' => $data['from_account_id'],
            'to_department_id' => $data['to_department_id'] ?? null,
            'to_account_id' => $data['to_account_id'],
            'amount' => round((float) $data['amount'], 2),
            'reason' => $data['reason'],
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
    }

    private function nextNumber(string $prefix, string $table, string $column): string
    {
        $last = DB::table($table)->where($column, 'like', $prefix.'-%')->orderByDesc($column)->value($column);
        $next = $last ? ((int) substr($last, -6)) + 1 : 1;

        return $prefix . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
