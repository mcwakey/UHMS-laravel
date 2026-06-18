<?php

namespace App\Services\Integrations\Payment;

use App\Enums\LogModule;
use App\Exceptions\Integrations\IntegrationException;
use App\Models\Invoice;
use App\Models\PaymentRequestLink;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Secure payment-request references (UUID). A link NEVER marks an invoice paid —
 * it only seeds provider payment initiation, which is then verified. Amount and
 * invoice reference are re-validated before initiation; expired/used links cannot
 * initiate. No internal numeric IDs are exposed (the UUID is the public handle).
 */
class PaymentRequestLinkService
{
    public function __construct(
        protected PaymentGatewayService $gateway,
        protected ActivityLogService $logger,
    ) {}

    public function create(array $data): PaymentRequestLink
    {
        $expiresDays = (int) config('integrations.payment_link_expiry_days', 7);

        $link = PaymentRequestLink::create([
            'link_uuid' => (string) Str::uuid(),
            'invoice_id' => $data['invoice_id'] ?? null,
            'visit_id' => $data['visit_id'] ?? null,
            'patient_id' => $data['patient_id'] ?? null,
            'payer_type' => $data['payer_type'] ?? null,
            'payer_id' => $data['payer_id'] ?? null,
            'amount' => round((float) ($data['amount'] ?? 0), 2),
            'currency' => $data['currency'] ?? config('integrations.default_currency', 'GHS'),
            'status' => PaymentRequestLink::STATUS_ACTIVE,
            'expires_at' => $data['expires_at'] ?? now()->addDays($expiresDays),
            'created_by' => Auth::id(),
            'metadata_snapshot' => $data['metadata'] ?? null,
        ]);

        $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_REQUEST_LINK_CREATED', [
            'source_type' => 'payment_request_link', 'source_id' => $link->id,
            'invoice_id' => $link->invoice_id,
            'metadata' => ['amount' => (float) $link->amount, 'uuid' => $link->link_uuid],
        ], $link, 'Payment request link created');

        return $link;
    }

    /**
     * Start provider payment initiation from a link. Re-validates amount/invoice
     * and link state before touching a provider.
     *
     * @throws IntegrationException
     */
    public function initiate(PaymentRequestLink $link, array $payer = []): PaymentRequestLink
    {
        if ($link->isExpired()) {
            $link->update(['status' => PaymentRequestLink::STATUS_EXPIRED]);
            throw new IntegrationException('Payment link expired.', 'integrations.errors.link_expired');
        }
        if (! $link->isUsable()) {
            throw new IntegrationException('Payment link is not usable.', 'integrations.errors.link_unusable');
        }

        $amount = (float) $link->amount;
        $invoice = $link->invoice_id ? Invoice::find($link->invoice_id) : null;
        if ($invoice) {
            // Re-validate against the live invoice balance — never trust the stored amount alone.
            $balance = round((float) $invoice->balance, 2);
            if ($balance <= 0) {
                throw new IntegrationException('Invoice already settled.', 'integrations.errors.invoice_settled');
            }
            $amount = min($amount, $balance) ?: $balance;
        }

        $txn = $this->gateway->initiate([
            'invoice_id' => $link->invoice_id,
            'visit_id' => $link->visit_id,
            'patient_id' => $link->patient_id,
            'amount' => $amount,
            'currency' => $link->currency,
            'payment_method' => $payer['payment_method'] ?? 'mtn_momo',
            'payer_name' => $payer['payer_name'] ?? null,
            'payer_phone' => $payer['payer_phone'] ?? null,
            'description' => $invoice ? ('Invoice ' . $invoice->invoice_number) : 'UHMS payment link',
            'metadata' => ['payment_request_link_id' => $link->id],
        ]);

        $link->update([
            'status' => PaymentRequestLink::STATUS_USED,
            'used_at' => now(),
            'payment_provider_transaction_id' => $txn->id,
        ]);

        $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_REQUEST_LINK_USED', [
            'source_type' => 'payment_request_link', 'source_id' => $link->id,
            'metadata' => ['transaction' => $txn->payment_reference],
        ], $link, 'Payment request link used');

        return $link->refresh();
    }

    public function expire(PaymentRequestLink $link): PaymentRequestLink
    {
        $link->update(['status' => PaymentRequestLink::STATUS_EXPIRED]);
        return $link;
    }

    public function cancel(PaymentRequestLink $link): PaymentRequestLink
    {
        $link->update(['status' => PaymentRequestLink::STATUS_CANCELLED]);
        return $link;
    }
}
