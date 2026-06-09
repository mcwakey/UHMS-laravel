<?php

namespace App\Services;

use App\Enums\BillingType;
use App\Models\Account;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceDiscount;
use App\Models\InvoiceItem;
use App\Models\Payment;
use RuntimeException;

class ReceivableAccountingService
{
    public function __construct(protected AccountingSettingsService $settings) {}

    public function accountForInvoice(Invoice $invoice): Account
    {
        $billingType = $invoice->billing_type instanceof BillingType
            ? $invoice->billing_type->value
            : (string) $invoice->billing_type;

        return match ($billingType) {
            BillingType::INSURANCE->value => $this->requiredAccount('insurance_receivable_account_id'),
            BillingType::CORPORATE->value => $invoice->sponsor_id
                ? $this->requiredAccount('sponsor_receivable_account_id')
                : $this->requiredAccount('corporate_receivable_account_id'),
            default => $this->requiredAccount('patient_receivable_account_id'),
        };
    }

    public function accountForInvoiceItem(InvoiceItem $item): Account
    {
        $payerType = (string) ($item->payer_type ?: '');

        if ($payerType === '') {
            return $this->accountForInvoice($item->invoice);
        }

        return match ($payerType) {
            BillingType::INSURANCE->value => $this->requiredAccount('insurance_receivable_account_id'),
            BillingType::CORPORATE->value => $item->invoice?->sponsor_id
                ? $this->requiredAccount('sponsor_receivable_account_id')
                : $this->requiredAccount('corporate_receivable_account_id'),
            default => $this->requiredAccount('patient_receivable_account_id'),
        };
    }

    public function accountForPayment(Payment $payment): Account
    {
        return $this->accountForInvoice($payment->invoice);
    }

    public function accountForDiscount(InvoiceDiscount $discount): Account
    {
        return $discount->invoiceItem
            ? $this->accountForInvoiceItem($discount->invoiceItem)
            : $this->accountForInvoice($discount->invoice);
    }

    public function accountForCreditNote(CreditNote $creditNote): Account
    {
        return $this->accountForInvoice($creditNote->invoice);
    }

    protected function requiredAccount(string $key): Account
    {
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
