<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetCommitment;
use App\Models\FiscalYear;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommitmentService
{
    public function __construct(
        protected BudgetAvailabilityService $availability,
        protected ModuleService $modules,
    ) {}

    public function create(array $data, ?User $user = null): BudgetCommitment
    {
        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Commitment amount must be greater than zero.']);
        }

        $snapshot = $this->availability->available((int) $data['fiscal_year_id'], $data['department_id'] ?? null, (int) $data['account_id']);
        $overBudget = $snapshot['available'] < $amount;
        $mode = $data['enforcement_mode'] ?? 'warning';

        if ($overBudget && $mode === 'blocking' && empty($data['override'])) {
            throw ValidationException::withMessages(['budget' => 'This commitment exceeds available budget and blocking mode is enabled.']);
        }

        return DB::transaction(function () use ($data, $user, $amount, $overBudget) {
            $commitment = BudgetCommitment::create([
                'budget_id' => $data['budget_id'] ?? null,
                'fiscal_year_id' => $data['fiscal_year_id'],
                'department_id' => $data['department_id'] ?? null,
                'account_id' => $data['account_id'],
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'source_reference' => $data['source_reference'] ?? null,
                'status' => BudgetCommitment::STATUS_ACTIVE,
                'original_amount' => $amount,
                'remaining_amount' => $amount,
                'is_over_budget' => $overBudget,
                'over_budget_acknowledged' => (bool) ($data['over_budget_acknowledged'] ?? $overBudget),
                'created_by' => $user?->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->movement($commitment, 'create', $amount, $user, $data['source_type'] ?? null, $data['source_id'] ?? null, 'Commitment created.');

            return $commitment->refresh();
        });
    }

    public function release(BudgetCommitment $commitment, float $amount, ?User $user = null, ?string $notes = null, ?string $sourceType = null, ?int $sourceId = null): BudgetCommitment
    {
        if (! in_array($commitment->status, [BudgetCommitment::STATUS_ACTIVE, BudgetCommitment::STATUS_PARTIALLY_RELEASED], true)) {
            return $commitment;
        }

        $amount = min(round($amount, 2), (float) $commitment->remaining_amount);
        if ($amount <= 0) {
            return $commitment;
        }

        return DB::transaction(function () use ($commitment, $amount, $user, $notes, $sourceType, $sourceId) {
            $remaining = round((float) $commitment->remaining_amount - $amount, 2);
            $commitment->update([
                'remaining_amount' => $remaining,
                'status' => $remaining > 0 ? BudgetCommitment::STATUS_PARTIALLY_RELEASED : BudgetCommitment::STATUS_RELEASED,
                'released_at' => $remaining > 0 ? $commitment->released_at : now(),
                'released_by' => $remaining > 0 ? $commitment->released_by : $user?->id,
            ]);

            $this->movement($commitment->refresh(), 'release', $amount, $user, $sourceType, $sourceId, $notes ?: 'Commitment released.');

            return $commitment->refresh();
        });
    }

    public function cancel(BudgetCommitment $commitment, ?User $user = null, ?string $notes = null): BudgetCommitment
    {
        if (! in_array($commitment->status, [BudgetCommitment::STATUS_ACTIVE, BudgetCommitment::STATUS_PARTIALLY_RELEASED], true)) {
            return $commitment;
        }

        return DB::transaction(function () use ($commitment, $user, $notes) {
            $amount = (float) $commitment->remaining_amount;
            $commitment->update([
                'remaining_amount' => 0,
                'status' => BudgetCommitment::STATUS_CANCELLED,
                'released_at' => now(),
                'released_by' => $user?->id,
            ]);

            if ($amount > 0) {
                $this->movement($commitment->refresh(), 'cancel', $amount, $user, null, null, $notes ?: 'Commitment cancelled.');
            }

            return $commitment->refresh();
        });
    }

    public function commitPurchaseOrder(PurchaseOrder $purchaseOrder, ?User $user = null): ?BudgetCommitment
    {
        if ($this->modules->disabled('budgets') || ! $purchaseOrder->budget_account_id) {
            return null;
        }

        if ($purchaseOrder->budget_commitment_id) {
            return $purchaseOrder->budgetCommitment;
        }

        $fiscalYear = FiscalYear::query()
            ->whereDate('start_date', '<=', $purchaseOrder->order_date)
            ->whereDate('end_date', '>=', $purchaseOrder->order_date)
            ->first();

        if (! $fiscalYear) {
            return null;
        }

        $budget = Budget::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->whereIn('status', [Budget::STATUS_APPROVED, Budget::STATUS_ACTIVE])
            ->first();

        $commitment = $this->create([
            'budget_id' => $budget?->id,
            'fiscal_year_id' => $fiscalYear->id,
            'department_id' => $purchaseOrder->budget_department_id,
            'account_id' => $purchaseOrder->budget_account_id,
            'amount' => (float) $purchaseOrder->total_amount,
            'source_type' => PurchaseOrder::class,
            'source_id' => $purchaseOrder->id,
            'source_reference' => $purchaseOrder->po_number,
            'over_budget_acknowledged' => $purchaseOrder->budget_overrun_acknowledged,
            'enforcement_mode' => $budget?->enforcement_mode ?? 'warning',
        ], $user);

        $purchaseOrder->update(['budget_commitment_id' => $commitment->id]);

        return $commitment;
    }

    private function movement(BudgetCommitment $commitment, string $type, float $amount, ?User $user, ?string $sourceType, ?int $sourceId, ?string $notes): void
    {
        $commitment->movements()->create([
            'movement_type' => $type,
            'amount' => round($amount, 2),
            'remaining_after' => round((float) $commitment->remaining_amount, 2),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'notes' => $notes,
            'created_by' => $user?->id,
        ]);
    }
}
