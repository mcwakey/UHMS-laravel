<?php

namespace App\Accounting\Posting;

use App\Models\AccountingPostingAttempt;
use App\Models\User;

interface AccountingPostingHandler
{
    public function supports(AccountingPostingAttempt $attempt): bool;

    public function retry(AccountingPostingAttempt $attempt, User $actor): RetryResult;
}
