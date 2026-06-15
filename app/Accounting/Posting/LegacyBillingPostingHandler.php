<?php

namespace App\Accounting\Posting;

use App\Models\AccountingPostingAttempt;
use App\Models\User;
use App\Services\AccountingPostingRetryService;

class LegacyBillingPostingHandler implements AccountingPostingHandler
{
    public function __construct(protected AccountingPostingRetryService $retryService) {}

    public function supports(AccountingPostingAttempt $attempt): bool
    {
        return $this->alias($attempt) !== null;
    }

    public function retry(AccountingPostingAttempt $attempt, User $actor): RetryResult
    {
        $alias = $this->alias($attempt);
        if (! $alias) {
            return RetryResult::unsupported('No billing retry handler supports this source type.');
        }

        $journal = $this->retryService->retry($alias, $attempt->source_id);

        return $journal
            ? RetryResult::posted($journal)
            : RetryResult::failed('The source posting service did not produce a journal entry.');
    }

    protected function alias(AccountingPostingAttempt $attempt): ?string
    {
        $type = strtolower($attempt->source_type);

        return match (true) {
            $type === 'invoice', str_ends_with($type, '.invoice') => 'invoice',
            $type === 'payment', str_ends_with($type, '.payment') => 'payment',
            in_array($type, ['discount', 'invoice_discount'], true), str_ends_with($type, '.invoicediscount') => 'discount',
            $type === 'credit_note', str_ends_with($type, '.creditnote') => 'credit_note',
            default => null,
        };
    }
}
