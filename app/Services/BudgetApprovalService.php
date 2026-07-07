<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Budget;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
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

        $budget = $budget->refresh();
        $this->logBudgetEvent('BUDGET_SUBMITTED', $budget, $user, LogSeverity::WARNING, [
            'submitted_by' => $user->id,
        ]);

        return $budget;
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

            $budget = $budget->refresh();
            $this->logBudgetEvent('BUDGET_APPROVED', $budget, $user, LogSeverity::WARNING, [
                'approved_by' => $user->id,
            ]);

            return $budget;
        });
    }

    private function logBudgetEvent(string $event, Budget $budget, User $user, LogSeverity $severity, array $metadata = []): void
    {
        $this->logAccounting(
            $event,
            $budget,
            'budget',
            str_replace('_', ' ', ucfirst(strtolower($event))).': '.$budget->name,
            array_merge([
                'budget_id' => $budget->id,
                'fiscal_year_id' => $budget->fiscal_year_id,
                'status' => $budget->status,
                'name' => $budget->name,
            ], $metadata),
            $severity,
            $user
        );
    }

    private function logAccounting(string $event, Model $subject, string $sourceType, string $description, array $metadata, LogSeverity $severity, User $user): void
    {
        try {
            app(ActivityLogService::class)->log(LogModule::ACCOUNTING, $event, [
                'causer' => $user,
                'severity' => $severity,
                'metadata' => $metadata,
                'source_type' => $sourceType,
                'source_id' => $subject->getKey(),
            ], $subject, $description);
        } catch (\Throwable) {
            // Audit logging must never block the accounting workflow.
        }
    }
}
