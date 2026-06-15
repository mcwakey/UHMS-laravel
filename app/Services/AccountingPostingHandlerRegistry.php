<?php

namespace App\Services;

use App\Accounting\Posting\AccountingPostingHandler;
use App\Accounting\Posting\BankAdjustmentPostingHandler;
use App\Accounting\Posting\BasicAccountingPostingHandler;
use App\Accounting\Posting\LegacyBillingPostingHandler;
use App\Models\AccountingPostingAttempt;

class AccountingPostingHandlerRegistry
{
    /** @var list<AccountingPostingHandler> */
    protected array $handlers;

    public function __construct(
        LegacyBillingPostingHandler $billing,
        BasicAccountingPostingHandler $basic,
        BankAdjustmentPostingHandler $bankAdjustment,
    ) {
        $this->handlers = [$billing, $basic, $bankAdjustment];
    }

    public function handlerFor(AccountingPostingAttempt $attempt): ?AccountingPostingHandler
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($attempt)) {
                return $handler;
            }
        }

        return null;
    }

    public function supports(AccountingPostingAttempt $attempt): bool
    {
        return $this->handlerFor($attempt) !== null;
    }
}
