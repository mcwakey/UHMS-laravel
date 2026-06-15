<?php

namespace App\Accounting\Posting;

use App\Models\AccountingPostingAttempt;
use App\Models\FinancialEntry;
use App\Models\User;
use App\Services\BasicAccountingPostingService;

class BasicAccountingPostingHandler implements AccountingPostingHandler
{
    public function __construct(protected BasicAccountingPostingService $posting) {}

    public function supports(AccountingPostingAttempt $attempt): bool
    {
        return str_ends_with($attempt->source_type, 'financialentry')
            || in_array($attempt->source_type, ['financial_entry', 'financialentry'], true);
    }

    public function retry(AccountingPostingAttempt $attempt, User $actor): RetryResult
    {
        $entry = FinancialEntry::query()->findOrFail($attempt->source_id);
        $result = $this->posting->post($entry, $actor);

        return $result['success']
            ? RetryResult::posted($result['journal'], true)
            : RetryResult::failed($result['error'], true);
    }
}
