<?php

namespace App\Services\Integrations\Payment;

use App\Enums\InvoiceStatus;
use App\Enums\LogModule;
use App\Exceptions\Integrations\IntegrationException;
use App\Models\Invoice;
use App\Models\PaymentProviderTransaction;
use App\Models\PaymentRequestLink;
use App\Services\ActivityLogService;

/**
 * Drives the PUBLIC (unauthenticated) payment-link portal. Exposes only safe
 * invoice summary data — never internal numeric ids, clinical details, provider
 * secrets, callback payloads or accounting internals. Reuses PaymentGatewayService
 * for all provider work and never creates a UHMS payment directly.
 */
class PublicPaymentLinkService
{
    public function __construct(
        protected PaymentGatewayService $gateway,
        protected ActivityLogService $logger,
    ) {}

    public function resolve(string $token): ?PaymentRequestLink
    {
        if ($token === '' || ! preg_match('/^[A-Za-z0-9]{20,80}$/', $token)) {
            return null;
        }
        return PaymentRequestLink::with(['invoice', 'patient', 'transaction'])
            ->where('public_token', $token)
            ->first();
    }

    public function recordView(PaymentRequestLink $link, string $event = 'PAYMENT_LINK_PUBLIC_VIEWED'): void
    {
        $link->forceFill(['last_viewed_at' => now()])->save();

        $this->logger->log(LogModule::INTEGRATIONS, $event, [
            'source_type' => 'payment_request_link', 'source_id' => $link->id,
            'metadata' => ['token' => substr((string) $link->public_token, 0, 8) . '…'],
        ], $link, 'Public payment link viewed');
    }

    /** Coarse state used by the public views — derived, never trusts the link alone. */
    public function state(PaymentRequestLink $link): string
    {
        if ($this->invoiceIsPaid($link)) {
            return 'paid';
        }
        if ($link->status === PaymentRequestLink::STATUS_CANCELLED) {
            return 'cancelled';
        }
        if ($link->isExpired()) {
            return 'expired';
        }
        if ($link->transaction) {
            return match (true) {
                $link->transaction->isSuccessful() => 'paid',
                in_array($link->transaction->status, [
                    PaymentProviderTransaction::STATUS_FAILED,
                    PaymentProviderTransaction::STATUS_CANCELLED,
                    PaymentProviderTransaction::STATUS_EXPIRED,
                ], true) => 'failed',
                default => 'pending',
            };
        }
        return $link->status === PaymentRequestLink::STATUS_ACTIVE ? 'active' : 'pending';
    }

    /**
     * Initiate provider payment from a public link. Revalidates state + amount.
     *
     * @throws IntegrationException
     */
    public function initiate(PaymentRequestLink $link, array $payer = []): PaymentProviderTransaction
    {
        if ($this->invoiceIsPaid($link)) {
            throw new IntegrationException('Invoice already settled.', 'integrations.errors.invoice_settled');
        }
        if ($link->status === PaymentRequestLink::STATUS_CANCELLED) {
            throw new IntegrationException('Payment link cancelled.', 'integrations.errors.link_cancelled');
        }
        if ($link->isExpired()) {
            $link->update(['status' => PaymentRequestLink::STATUS_EXPIRED]);
            throw new IntegrationException('Payment link expired.', 'integrations.errors.link_expired');
        }
        if (! in_array($link->status, [PaymentRequestLink::STATUS_ACTIVE, PaymentRequestLink::STATUS_INITIATED], true)) {
            throw new IntegrationException('Payment link is not usable.', 'integrations.errors.link_unusable');
        }
        // A used link that already produced a transaction should not start another.
        if ($link->transaction && ! in_array($link->transaction->status, [
            PaymentProviderTransaction::STATUS_FAILED,
            PaymentProviderTransaction::STATUS_CANCELLED,
            PaymentProviderTransaction::STATUS_EXPIRED,
        ], true)) {
            return $link->transaction; // already initiated / pending / paid
        }

        $amount = (float) $link->amount;
        $invoice = $link->invoice_id ? Invoice::find($link->invoice_id) : null;
        if ($invoice) {
            $balance = round((float) $invoice->balance, 2);
            if ($balance <= 0) {
                throw new IntegrationException('Invoice already settled.', 'integrations.errors.invoice_settled');
            }
            $amount = min($amount ?: $balance, $balance) ?: $balance;
        }

        $txn = $this->gateway->initiate([
            'invoice_id' => $link->invoice_id,
            'visit_id' => $link->visit_id,
            'patient_id' => $link->patient_id,
            'amount' => $amount,
            'currency' => $link->currency,
            'payment_method' => $payer['payment_method'] ?? 'mtn_momo',
            'payer_name' => $payer['payer_name'] ?? $link->patient?->first_name,
            'payer_phone' => $payer['payer_phone'] ?? null,
            'description' => $invoice ? ('Invoice ' . $invoice->invoice_number) : 'UHMS payment',
            'metadata' => ['payment_request_link_id' => $link->id, 'public' => true],
        ]);

        $link->update([
            'status' => PaymentRequestLink::STATUS_INITIATED,
            'payment_provider_transaction_id' => $txn->id,
            'initiated_count' => (int) $link->initiated_count + 1,
            'last_initiated_at' => now(),
        ]);

        $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_LINK_PUBLIC_INITIATED', [
            'source_type' => 'payment_request_link', 'source_id' => $link->id,
            'metadata' => ['transaction' => $txn->payment_reference, 'amount' => $amount],
        ], $link, 'Public payment link initiated');

        return $txn;
    }

    /** Safe manual recheck of the linked transaction via the verification service. */
    public function recheck(PaymentRequestLink $link): void
    {
        if ($link->transaction && ! $link->transaction->isSuccessful()) {
            $this->gateway->verify($link->transaction);
            if ($link->fresh()->transaction?->isSuccessful()) {
                $link->update(['status' => PaymentRequestLink::STATUS_USED, 'used_at' => now()]);
            }
        }
    }

    /** Safe display summary — NO internal ids, NO clinical data. */
    public function safeSummary(PaymentRequestLink $link): array
    {
        return [
            'facility' => config('app.name', 'UHMS'),
            'invoice_number' => $link->invoice?->invoice_number,
            'amount_due' => number_format((float) $link->amount, 2),
            'currency' => $link->currency,
            'payer_name' => $link->patient?->first_name,
            'expires_at' => optional($link->expires_at)->format('Y-m-d'),
            'state' => $this->state($link),
        ];
    }

    private function invoiceIsPaid(PaymentRequestLink $link): bool
    {
        if (! $link->invoice) {
            return false;
        }
        $status = $link->invoice->status?->value ?? $link->invoice->status;
        return $status === InvoiceStatus::PAID->value || round((float) $link->invoice->balance, 2) <= 0;
    }
}
