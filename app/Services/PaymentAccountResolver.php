<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Account;
use App\Models\Payment;
use RuntimeException;

class PaymentAccountResolver
{
    public function __construct(protected AccountingSettingsService $settings) {}

    public function accountFor(Payment $payment): Account
    {
        $method = $payment->payment_method instanceof PaymentMethod
            ? $payment->payment_method
            : PaymentMethod::tryFrom((string) $payment->payment_method);

        $key = match ($method) {
            PaymentMethod::CASH => 'default_cash_account_id',
            PaymentMethod::MTN_MOMO,
            PaymentMethod::VODAFONE_CASH,
            PaymentMethod::AIRTELTIGO_MONEY => 'default_mobile_money_account_id',
            PaymentMethod::BANK_TRANSFER,
            PaymentMethod::CARD,
            PaymentMethod::CHEQUE,
            PaymentMethod::INSURANCE,
            null => 'default_bank_account_id',
        };

        $account = $this->settings->account($key);

        if (! $account) {
            throw new RuntimeException("Accounting setting {$key} is not configured.");
        }

        if (! $account->is_active) {
            throw new RuntimeException("Accounting account {$account->display_name} is inactive.");
        }

        return $account;
    }
}
