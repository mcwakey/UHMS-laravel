<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BudgetApprovalService
{
    public function submit(Budget $budget, User $user): Budget
    {
        $budget->refresh();

        if ($budget->status !== Budget::STATUS_DRAFT) {
            throw ValidationException::withMessages(['budget' => 'Only draft budgets can be submitted.']);
        }

        if (! $budget->lines()->exists()) {
            throw ValidationException::withMessages(['lines' => 'A budget must have at least one line before submission.']);
        }

        $budget->update([
            'status' => Budget::STATUS_SUBMITTED,
            'submitted_by' => $user->id,
            'submitted_at' => now(),
        ]);

        return $budget->refresh();
    }

    public function approve(Budget $budget, User $user): Budget
    {
        $budget->refresh();

        if (! in_array($budget->status, [Budget::STATUS_SUBMITTED, Budget::STATUS_APPROVED], true)) {
            throw ValidationException::withMessages(['budget' => 'Only submitted budgets can be approved.']);
        }

        return DB::transaction(function () use ($budget, $user) {
            $budget->update([
                'status' => Budget::STATUS_ACTIVE,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            return $budget->refresh();
        });
    }
}
