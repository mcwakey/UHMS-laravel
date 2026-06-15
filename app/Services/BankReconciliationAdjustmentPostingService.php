<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Proposes, approves, posts and rejects bank reconciliation adjustments
 * (bank charges, interest, fees, corrections). Posting always goes through
 * JournalEntryService — never directly from a controller — producing a
 * balanced journal. Account ids are selected, never hardcoded.
 */
class BankReconciliationAdjustmentPostingService
{
    public function __construct(
        protected JournalEntryService $journalEntries,
        protected BankReconciliationService $reconciliations,
    ) {}

    public function propose(BankReconciliation $reconciliation, array $data, User $actor): BankReconciliationAdjustment
    {
        if ($reconciliation->isLocked()) {
            throw ValidationException::withMessages(['status' => __('accounting.reconciliation_locked')]);
        }

        $type = $data['type'] ?? BankReconciliationAdjustment::TYPE_OTHER;
        if (! in_array($type, BankReconciliationAdjustment::TYPES, true)) {
            throw ValidationException::withMessages(['type' => __('accounting.invalid_adjustment_type')]);
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => __('accounting.match_amount_positive')]);
        }

        $adjustment = BankReconciliationAdjustment::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'bank_account_id' => $reconciliation->bank_account_id,
            'type' => $type,
            'description' => $data['description'] ?? null,
            'amount' => $amount,
            'side' => $this->resolveSide($type, $data['side'] ?? null),
            'account_id' => $data['account_id'] ?? null,
            'status' => BankReconciliationAdjustment::STATUS_PROPOSED,
            'proposed_by' => $actor->id,
            'proposed_at' => now(),
        ]);

        $this->audit($adjustment, 'BANK_ADJUSTMENT_PROPOSED', $actor, LogSeverity::NOTICE);

        return $adjustment;
    }

    public function approve(BankReconciliationAdjustment $adjustment, User $actor): BankReconciliationAdjustment
    {
        if ($adjustment->status !== BankReconciliationAdjustment::STATUS_PROPOSED) {
            throw ValidationException::withMessages(['status' => __('accounting.only_proposed_can_approve')]);
        }

        $adjustment->update([
            'status' => BankReconciliationAdjustment::STATUS_APPROVED,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);

        $this->reconciliations->recompute($adjustment->reconciliation);
        $this->audit($adjustment, 'BANK_ADJUSTMENT_APPROVED', $actor, LogSeverity::WARNING);

        return $adjustment->refresh();
    }

    public function reject(BankReconciliationAdjustment $adjustment, string $reason, User $actor): BankReconciliationAdjustment
    {
        if (! in_array($adjustment->status, [BankReconciliationAdjustment::STATUS_PROPOSED, BankReconciliationAdjustment::STATUS_APPROVED], true)) {
            throw ValidationException::withMessages(['status' => __('accounting.cannot_reject_adjustment')]);
        }

        $adjustment->update([
            'status' => BankReconciliationAdjustment::STATUS_REJECTED,
            'rejected_by' => $actor->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->reconciliations->recompute($adjustment->reconciliation);
        $this->audit($adjustment, 'BANK_ADJUSTMENT_REJECTED', $actor, LogSeverity::WARNING, $reason);

        return $adjustment->refresh();
    }

    /**
     * Post an approved adjustment through JournalEntryService as a balanced journal.
     */
    public function post(BankReconciliationAdjustment $adjustment, User $actor): BankReconciliationAdjustment
    {
        return DB::transaction(function () use ($adjustment, $actor) {
            $adjustment = BankReconciliationAdjustment::query()
                ->with(['journalEntry', 'reconciliation.bankAccount'])
                ->lockForUpdate()
                ->findOrFail($adjustment->id);
            if ($adjustment->status === BankReconciliationAdjustment::STATUS_POSTED && $adjustment->journalEntry) {
                return $adjustment;
            }
            if ($adjustment->status !== BankReconciliationAdjustment::STATUS_APPROVED) {
                throw ValidationException::withMessages(['status' => __('accounting.only_approved_can_post')]);
            }
            if (! $adjustment->account_id) {
                throw ValidationException::withMessages(['account_id' => __('accounting.adjustment_account_required')]);
            }

            $reconciliation = $adjustment->reconciliation;
            $bankGlAccountId = $reconciliation->bankAccount->gl_account_id;
            $lines = $this->journalLines($adjustment, $bankGlAccountId);

            $entry = $this->journalEntries->createDraft([
                'entry_date' => now()->toDateString(),
                'description' => $this->journalDescription($adjustment),
                'source_module' => 'BANK_RECONCILIATION',
                'reference_type' => BankReconciliationAdjustment::class,
                'reference_id' => $adjustment->id,
                'reference_number' => 'BANK-ADJ-' . $adjustment->id,
                'allow_control_accounts' => true,
                'lines' => $lines,
            ]);

            $this->journalEntries->post($entry, $actor);

            $adjustment->update([
                'status' => BankReconciliationAdjustment::STATUS_POSTED,
                'journal_entry_id' => $entry->id,
                'posted_by' => $actor->id,
                'posted_at' => now(),
            ]);

            $this->reconciliations->recompute($reconciliation);

            app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'BANK_ADJUSTMENT_POSTED', [
                'severity' => LogSeverity::WARNING,
                'causer' => $actor,
                'journal_entry_id' => $entry->id,
                'metadata' => [
                    'bank_reconciliation_id' => $reconciliation->id,
                    'bank_reconciliation_adjustment_id' => $adjustment->id,
                    'type' => $adjustment->type,
                    'amount' => (float) $adjustment->amount,
                ],
            ], $adjustment, 'Bank adjustment posted: ' . $entry->journal_number);

            return $adjustment->refresh();
        });
    }

    /**
     * Build the two balanced journal lines for the adjustment.
     *
     *   bank_charge / transfer_fee : Dr contra (expense)  Cr bank
     *   interest_income            : Dr bank              Cr contra (income)
     *   correction / other         : driven by `side` (debit = Dr contra/Cr bank)
     */
    protected function journalLines(BankReconciliationAdjustment $adjustment, int $bankGlAccountId): array
    {
        $amount = round((float) $adjustment->amount, 2);
        $contra = (int) $adjustment->account_id;
        $description = $this->journalDescription($adjustment);

        $bankDebited = match ($adjustment->type) {
            BankReconciliationAdjustment::TYPE_INTEREST_INCOME => true,
            BankReconciliationAdjustment::TYPE_BANK_CHARGE,
            BankReconciliationAdjustment::TYPE_TRANSFER_FEE => false,
            default => $adjustment->side === BankReconciliationAdjustment::SIDE_CREDIT,
        };

        if ($bankDebited) {
            return [
                ['account_id' => $bankGlAccountId, 'debit' => $amount, 'credit' => 0, 'description' => $description],
                ['account_id' => $contra, 'debit' => 0, 'credit' => $amount, 'description' => $description],
            ];
        }

        return [
            ['account_id' => $contra, 'debit' => $amount, 'credit' => 0, 'description' => $description],
            ['account_id' => $bankGlAccountId, 'debit' => 0, 'credit' => $amount, 'description' => $description],
        ];
    }

    protected function journalDescription(BankReconciliationAdjustment $adjustment): string
    {
        $label = __('accounting.adjustment_type_' . $adjustment->type);

        return trim($label . ' — ' . ($adjustment->description ?: __('accounting.bank_reconciliation')));
    }

    protected function resolveSide(string $type, ?string $side): string
    {
        return match ($type) {
            BankReconciliationAdjustment::TYPE_INTEREST_INCOME => BankReconciliationAdjustment::SIDE_CREDIT,
            BankReconciliationAdjustment::TYPE_BANK_CHARGE,
            BankReconciliationAdjustment::TYPE_TRANSFER_FEE => BankReconciliationAdjustment::SIDE_DEBIT,
            default => in_array($side, BankReconciliationAdjustment::SIDES, true) ? $side : BankReconciliationAdjustment::SIDE_DEBIT,
        };
    }

    protected function audit(
        BankReconciliationAdjustment $adjustment,
        string $event,
        User $actor,
        LogSeverity $severity,
        ?string $reason = null,
    ): void {
        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, $event, [
            'severity' => $severity,
            'causer' => $actor,
            'reason' => $reason,
            'metadata' => [
                'bank_reconciliation_id' => $adjustment->bank_reconciliation_id,
                'bank_reconciliation_adjustment_id' => $adjustment->id,
                'type' => $adjustment->type,
                'amount' => (float) $adjustment->amount,
            ],
        ], $adjustment, str_replace('_', ' ', ucfirst(strtolower($event))));
    }
}
