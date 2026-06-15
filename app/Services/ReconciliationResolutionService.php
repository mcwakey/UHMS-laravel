<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Models\AccountingReconciliationItem;
use App\Models\AccountingReconciliationResolution;
use App\Models\AccountingReconciliationRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReconciliationResolutionService
{
    public function __construct(protected ActivityLogService $activityLog) {}

    public function add(AccountingReconciliationRun $run, ?AccountingReconciliationItem $item, array $data, User $actor): AccountingReconciliationResolution
    {
        if (! in_array($data['resolution_type'], AccountingReconciliationResolution::TYPES, true)) {
            throw ValidationException::withMessages(['resolution_type' => 'Unsupported reconciliation resolution type.']);
        }
        if ($item && $item->accounting_reconciliation_run_id !== $run->id) {
            throw ValidationException::withMessages(['item' => 'The reconciliation item does not belong to this run.']);
        }
        if (in_array($run->status, [AccountingReconciliationRun::STATUS_CANCELLED, AccountingReconciliationRun::STATUS_SUPERSEDED], true)) {
            throw ValidationException::withMessages(['status' => 'This reconciliation run is not open for resolution.']);
        }

        return DB::transaction(function () use ($run, $item, $data, $actor) {
            $resolution = $run->resolutions()->create([
                'accounting_reconciliation_item_id' => $item?->id,
                'resolution_type' => $data['resolution_type'],
                'resolution_note' => $data['resolution_note'],
                'linked_journal_entry_id' => $data['linked_journal_entry_id'] ?? null,
                'linked_posting_attempt_id' => $data['linked_posting_attempt_id'] ?? null,
                'linked_source_type' => $data['linked_source_type'] ?? null,
                'linked_source_id' => $data['linked_source_id'] ?? null,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
                'metadata_snapshot' => $data['metadata_snapshot'] ?? null,
            ]);

            if ($item) {
                $item->update(['resolution_status' => $this->statusFor($data['resolution_type'])]);
            }
            $action = $data['resolution_type'] === 'waived_after_review'
                ? 'SUBLEDGER_RECONCILIATION_ITEM_WAIVED'
                : 'SUBLEDGER_RECONCILIATION_RESOLUTION_ADDED';
            $this->activityLog->log(LogModule::ACCOUNTING, $action, [
                'causer' => $actor,
                'metadata' => [
                    'run_id' => $run->id,
                    'item_id' => $item?->id,
                    'resolution_id' => $resolution->id,
                    'resolution_type' => $data['resolution_type'],
                ],
            ], $run, 'Subledger reconciliation resolution added');

            return $resolution->load(['item', 'linkedJournalEntry', 'linkedPostingAttempt', 'resolvedBy']);
        });
    }

    protected function statusFor(string $type): string
    {
        return match ($type) {
            'accepted_timing_difference' => 'accepted_timing',
            'waived_after_review' => 'waived',
            'other' => 'explained',
            default => 'resolved',
        };
    }
}
