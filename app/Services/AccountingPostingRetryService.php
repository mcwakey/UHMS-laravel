<?php

namespace App\Services;

use App\Services\LegacyMigration\Foundation\Runtime\OperationalEffectGate;
use App\Services\LegacyMigration\Foundation\Runtime\ProhibitedSubsystem;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceDiscount;
use App\Models\Payment;
use App\Models\JournalEntry;
use InvalidArgumentException;

class AccountingPostingRetryService
{
    public function __construct(
        protected BillingAccountingPostingService $billingPosting,
        protected PaymentAccountingPostingService $paymentPosting,
    ) {}

    public function retry(string $sourceType, int $sourceId): ?JournalEntry
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::AccountingPosting);
        return match ($sourceType) {
            'invoice' => $this->billingPosting->postInvoice(Invoice::query()->findOrFail($sourceId)),
            'payment' => $this->paymentPosting->postPayment(Payment::query()->findOrFail($sourceId)),
            'discount' => $this->billingPosting->postDiscount(InvoiceDiscount::query()->findOrFail($sourceId)),
            'credit_note' => $this->billingPosting->postCreditNote(CreditNote::query()->findOrFail($sourceId)),
            default => throw new InvalidArgumentException('Unsupported accounting posting source.'),
        };
    }
}
