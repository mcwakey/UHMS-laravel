<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Models\AccountingReconciliationItem;
use App\Models\AccountingReconciliationRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubledgerReconciliationService
{
    public function __construct(
        protected ReceivablesReconciliationService $receivables,
        protected PayablesReconciliationService $payables,
        protected InventoryReconciliationService $inventory,
        protected PayrollReconciliationService $payroll,
        protected CashBankReconciliationService $cashBank,
        protected TaxLiabilityReconciliationService $tax,
        protected ActivityLogService $activityLog,
    ) {}

    public function start(array $data, User $actor): AccountingReconciliationRun
    {
        $type = (string) $data['reconciliation_type'];
        if (! in_array($type, AccountingReconciliationRun::TYPES, true)) {
            throw ValidationException::withMessages(['reconciliation_type' => 'Unsupported reconciliation type.']);
        }

        $periodStart = Carbon::parse($data['period_start'])->startOfDay();
        $periodEnd = Carbon::parse($data['period_end'])->endOfDay();
        $asOf = Carbon::parse($data['as_of_date'] ?? $periodEnd)->endOfDay();
        $tolerance = round((float) ($data['tolerance_amount'] ?? 0.01), 2);

        return DB::transaction(function () use ($data, $actor, $type, $periodStart, $periodEnd, $asOf, $tolerance) {
            AccountingReconciliationRun::query()
                ->where('reconciliation_type', $type)
                ->where('status', AccountingReconciliationRun::STATUS_DRAFT)
                ->update(['status' => AccountingReconciliationRun::STATUS_SUPERSEDED]);

            $run = AccountingReconciliationRun::create([
                'reconciliation_type' => $type,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'as_of_date' => $asOf,
                'status' => AccountingReconciliationRun::STATUS_RUNNING,
                'tolerance_amount' => $tolerance,
                'started_by' => $actor->id,
                'started_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->activityLog->log(LogModule::ACCOUNTING, 'SUBLEDGER_RECONCILIATION_STARTED', [
                'causer' => $actor,
                'metadata' => ['run_id' => $run->id, 'type' => $type, 'as_of_date' => $asOf->toDateString()],
            ], $run, 'Subledger reconciliation started');

            $result = $this->calculate($type, $periodStart, $periodEnd, $asOf);
            foreach ($result['items'] as $item) {
                $run->items()->create($item);
            }

            $classification = $this->runClassification($result, $tolerance);
            $summary = [
                'availability' => $result['availability'],
                'availability_reason' => $result['availability_reason'],
                'item_count' => count($result['items']),
                'classification_counts' => collect($result['items'])->countBy('classification')->all(),
                'resolution_counts' => collect($result['items'])->countBy('resolution_status')->all(),
            ];
            $run->update([
                'status' => AccountingReconciliationRun::STATUS_COMPLETED,
                'subledger_total' => $result['subledger_total'],
                'gl_total' => $result['gl_total'],
                'difference_amount' => $result['difference_amount'],
                'difference_classification' => $classification,
                'source_snapshot' => $result['source_snapshot'],
                'gl_snapshot' => $result['gl_snapshot'],
                'summary_snapshot' => $summary,
                'completed_by' => $actor->id,
                'completed_at' => now(),
            ]);

            $this->activityLog->log(LogModule::ACCOUNTING, 'SUBLEDGER_RECONCILIATION_COMPLETED', [
                'causer' => $actor,
                'metadata' => [
                    'run_id' => $run->id,
                    'type' => $type,
                    'availability' => $result['availability'],
                    'difference_amount' => $result['difference_amount'],
                    'classification' => $classification,
                ],
            ], $run, 'Subledger reconciliation completed');

            return $run->refresh()->load(['items.glAccount', 'startedBy', 'completedBy']);
        });
    }

    public function approve(AccountingReconciliationRun $run, User $actor, bool $allowUnresolved = false): AccountingReconciliationRun
    {
        if ($run->status !== AccountingReconciliationRun::STATUS_COMPLETED) {
            throw ValidationException::withMessages(['status' => 'Only completed reconciliation runs can be approved.']);
        }
        if ($run->hasUnresolvedDifferences() && ! $allowUnresolved) {
            throw ValidationException::withMessages([
                'approval' => 'Unresolved differences remain. Use the elevated unresolved-difference approval confirmation.',
            ]);
        }

        $run->update(['status' => AccountingReconciliationRun::STATUS_APPROVED, 'approved_by' => $actor->id, 'approved_at' => now()]);
        $this->activityLog->log(LogModule::ACCOUNTING, 'SUBLEDGER_RECONCILIATION_APPROVED', [
            'causer' => $actor,
            'metadata' => ['run_id' => $run->id, 'approved_with_unresolved' => $run->hasUnresolvedDifferences()],
        ], $run, 'Subledger reconciliation approved');

        return $run->refresh();
    }

    public function cancel(AccountingReconciliationRun $run, string $reason, User $actor): AccountingReconciliationRun
    {
        if (in_array($run->status, [AccountingReconciliationRun::STATUS_APPROVED, AccountingReconciliationRun::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages(['status' => 'This reconciliation run cannot be cancelled.']);
        }
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['cancellation_reason' => 'A cancellation reason is required.']);
        }

        $run->update([
            'status' => AccountingReconciliationRun::STATUS_CANCELLED,
            'cancelled_by' => $actor->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
        $this->activityLog->log(LogModule::ACCOUNTING, 'SUBLEDGER_RECONCILIATION_CANCELLED', [
            'causer' => $actor,
            'reason' => $reason,
            'metadata' => ['run_id' => $run->id],
        ], $run, 'Subledger reconciliation cancelled');

        return $run->refresh();
    }

    public function dashboard(): array
    {
        $latest = collect(AccountingReconciliationRun::TYPES)->mapWithKeys(fn ($type) => [
            $type => AccountingReconciliationRun::query()
                ->where('reconciliation_type', $type)
                ->whereNotIn('status', [AccountingReconciliationRun::STATUS_CANCELLED, AccountingReconciliationRun::STATUS_SUPERSEDED])
                ->latest('completed_at')
                ->first(),
        ]);
        $activeRuns = AccountingReconciliationRun::query()
            ->whereNotIn('status', [AccountingReconciliationRun::STATUS_CANCELLED, AccountingReconciliationRun::STATUS_SUPERSEDED])
            ->get();
        $openItems = AccountingReconciliationItem::query()->where('resolution_status', 'open');

        return [
            'latest' => $latest,
            'balanced_domains' => $latest->filter(fn ($run) => $run && abs((float) $run->difference_amount) <= (float) $run->tolerance_amount)->count(),
            'difference_detected' => $latest->filter(fn ($run) => $run && abs((float) $run->difference_amount) > (float) $run->tolerance_amount)->count(),
            'failed_posting_linked' => (clone $openItems)->where('classification', 'failed_posting')->count(),
            'manual_journals_detected' => (clone $openItems)->where('classification', 'manual_journal')->count(),
            'unposted_source_records' => (clone $openItems)->where('classification', 'unposted_source')->count(),
            'oldest_unresolved_difference' => (clone $openItems)->oldest('created_at')->value('created_at'),
            'last_reconciliation_date' => $activeRuns->max('completed_at'),
        ];
    }

    protected function calculate(string $type, Carbon $periodStart, Carbon $periodEnd, Carbon $asOf): array
    {
        return match ($type) {
            'accounts_receivable' => $this->receivables->calculate($periodStart, $periodEnd, $asOf),
            'accounts_payable' => $this->payables->calculate($periodStart, $periodEnd, $asOf),
            'inventory' => $this->inventory->calculate($periodStart, $periodEnd, $asOf),
            'payroll' => $this->payroll->calculate($periodStart, $periodEnd, $asOf),
            'cash_bank' => $this->cashBank->calculate($periodStart, $periodEnd, $asOf),
            'paye' => $this->tax->calculatePaye($periodStart, $periodEnd, $asOf),
            'pension' => $this->tax->calculatePension($periodStart, $periodEnd, $asOf),
        };
    }

    protected function runClassification(array $result, float $tolerance): string
    {
        if ($result['availability'] === 'not_available') {
            return 'not_available';
        }
        if (abs((float) $result['difference_amount']) <= $tolerance) {
            return 'balanced';
        }

        return collect($result['items'])
            ->pluck('classification')
            ->first(fn ($classification) => ! in_array($classification, ['balanced', 'not_available'], true))
            ?? 'unknown_difference';
    }
}
