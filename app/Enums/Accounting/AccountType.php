<?php

namespace App\Enums\Accounting;

enum AccountType: string
{
    case ASSET = 'ASSET';
    case LIABILITY = 'LIABILITY';
    case EQUITY = 'EQUITY';
    case INCOME = 'INCOME';
    case EXPENSE = 'EXPENSE';

    public function label(): string
    {
        return ucwords(strtolower(str_replace('_', ' ', $this->value)));
    }

    public function translatedLabel(): string
    {
        return __('statuses.default.' . strtolower($this->value));
    }

    public function normalBalance(): NormalBalance
    {
        return match ($this) {
            self::ASSET, self::EXPENSE => NormalBalance::DEBIT,
            self::LIABILITY, self::EQUITY, self::INCOME => NormalBalance::CREDIT,
        };
    }
}
