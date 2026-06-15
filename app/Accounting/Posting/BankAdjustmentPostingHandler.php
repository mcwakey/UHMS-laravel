<?php

namespace App\Accounting\Posting;

use App\Models\AccountingPostingAttempt;
use App\Models\BankReconciliationAdjustment;
use App\Models\User;
use App\Services\BankReconciliationAdjustmentPostingService;

class BankAdjustmentPostingHandler implements AccountingPostingHandler
{
    public function __construct(protected BankReconciliationAdjustmentPostingService $posting) {}

    public function supports(AccountingPostingAttempt $attempt): bool
    {
        return str_ends_with($attempt->source_type, 'bankreconciliationadjustment')
            || $attempt->source_type === 'bank_reconciliation_adjustment';
    }

    public function retry(AccountingPostingAttempt $attempt, User $actor): RetryResult
    {
        $adjustment = BankReconciliationAdjustment::query()->findOrFail($attempt->source_id);
        $adjustment = $this->posting->post($adjustment, $actor);

        return $adjustment->journalEntry
            ? RetryResult::posted($adjustment->journalEntry)
            : RetryResult::failed('The bank adjustment did not produce a journal entry.');
    }
}
