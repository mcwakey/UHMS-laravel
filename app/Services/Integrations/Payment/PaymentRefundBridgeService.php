<?php

namespace App\Services\Integrations\Payment;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Exceptions\Integrations\IntegrationException;
use App\Models\PaymentProviderRefund;
use App\Models\PaymentProviderTransaction;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Bridges an approved UHMS refund / credit-note decision to a provider refund.
 *
 * Guarantees:
 *   • Only a verified provider payment with a linked UHMS payment can be refunded.
 *   • Total refunds can never exceed the verified provider amount (over-refund blocked).
 *   • If the provider does not support refunds, the request is retained as
 *     "unsupported" with a manual-refund instruction (never silently dropped).
 *   • This does not bypass the UHMS credit-note/refund approval workflow — callers
 *     pass an already-approved decision (uhms_refund_id) in.
 */
class PaymentRefundBridgeService
{
    public function __construct(
        protected PaymentGatewayService $gateway,
        protected ActivityLogService $logger,
    ) {}

    /**
     * @param int|null $uhmsCreditNoteId An ALREADY-APPROVED UHMS credit note the
     *        provider refund is being executed against. The bridge never creates
     *        or approves the credit note — that is the existing UHMS workflow.
     */
    public function prepare(
        PaymentProviderTransaction $transaction,
        float $amount,
        ?string $reason = null,
        ?int $uhmsRefundId = null,
        ?int $uhmsCreditNoteId = null,
    ): PaymentProviderRefund {
        $amount = round($amount, 2);

        if (! $transaction->isSuccessful() || ! $transaction->hasUhmsPayment()) {
            throw new IntegrationException('Only a verified, linked provider payment can be refunded.', 'integrations.errors.refund_not_eligible');
        }
        if ($amount <= 0) {
            throw new IntegrationException('Refund amount must be greater than zero.', 'integrations.errors.refund_amount_invalid');
        }

        // Validate the approved credit note (existing UHMS workflow) when supplied.
        $creditNote = $uhmsCreditNoteId ? \App\Models\CreditNote::find($uhmsCreditNoteId) : null;
        if ($uhmsCreditNoteId && ! $creditNote) {
            throw new IntegrationException('Approved credit note not found.', 'integrations.errors.credit_note_missing');
        }
        if ($creditNote && $transaction->invoice_id && $creditNote->invoice_id && (int) $creditNote->invoice_id !== (int) $transaction->invoice_id) {
            throw new IntegrationException('Credit note does not match the payment invoice.', 'integrations.errors.credit_note_mismatch');
        }
        if ($creditNote && round($amount, 2) > round((float) $creditNote->amount, 2) + 0.01) {
            throw new IntegrationException('Refund exceeds the approved credit note amount.', 'integrations.errors.refund_over_amount');
        }

        $alreadyRefunded = (float) $transaction->refunds()
            ->whereIn('status', [
                PaymentProviderRefund::STATUS_PENDING,
                PaymentProviderRefund::STATUS_PROCESSING,
                PaymentProviderRefund::STATUS_COMPLETED,
                PaymentProviderRefund::STATUS_PROVIDER_PENDING,
                PaymentProviderRefund::STATUS_PROVIDER_REFUNDED,
            ])->sum('amount');

        if (round($alreadyRefunded + $amount, 2) > round((float) $transaction->amount, 2) + 0.01) {
            throw new IntegrationException('Refund exceeds the original payment amount.', 'integrations.errors.refund_over_amount');
        }

        $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_REFUND_BRIDGE_PREPARED', [
            'source_type' => 'payment_provider_transaction', 'source_id' => $transaction->id,
            'metadata' => ['amount' => $amount, 'uhms_refund_id' => $uhmsRefundId, 'uhms_credit_note_id' => $uhmsCreditNoteId],
        ], $transaction, 'Provider refund bridge prepared');

        // Provider doesn't support refunds → retain as unsupported + manual-required, visibly.
        if (! $transaction->provider || ! $transaction->provider->supports_refund) {
            $refund = PaymentProviderRefund::create([
                'payment_provider_transaction_id' => $transaction->id,
                'provider_id' => $transaction->provider_id,
                'refund_reference' => 'RF-' . now()->format('ymd') . '-' . Str::upper(Str::random(8)),
                'amount' => $amount,
                'currency' => $transaction->currency,
                'status' => PaymentProviderRefund::STATUS_FAILED,
                'reason' => $reason,
                'requested_by' => Auth::id(),
                'requested_at' => now(),
                'failed_at' => now(),
                'uhms_refund_id' => $uhmsRefundId,
                'uhms_credit_note_id' => $uhmsCreditNoteId,
                'metadata_snapshot' => ['unsupported' => true, 'manual_required' => true, 'manual_instruction' => 'Process this refund manually via the provider portal / cash desk.'],
            ]);

            $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_REFUND_PROVIDER_UNSUPPORTED', [
                'source_type' => 'payment_provider_refund', 'source_id' => $refund->id,
                'severity' => LogSeverity::WARNING,
            ], $refund, 'Provider refund unsupported');

            $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_REFUND_MANUAL_REQUIRED', [
                'source_type' => 'payment_provider_refund', 'source_id' => $refund->id,
                'severity' => LogSeverity::WARNING,
                'metadata' => ['uhms_credit_note_id' => $uhmsCreditNoteId],
            ], $refund, 'Provider refund requires manual processing');

            return $refund;
        }

        // Provider supports refunds → execute through the gateway (records + calls adapter).
        $refund = $this->gateway->requestRefund($transaction, $amount, $reason);
        $refund->update(array_filter([
            'uhms_refund_id' => $uhmsRefundId,
            'uhms_credit_note_id' => $uhmsCreditNoteId,
        ]));

        if ($uhmsCreditNoteId) {
            $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_REFUND_BRIDGE_LINKED', [
                'source_type' => 'payment_provider_refund', 'source_id' => $refund->id,
                'metadata' => ['uhms_credit_note_id' => $uhmsCreditNoteId],
            ], $refund, 'Provider refund linked to approved credit note');
        }

        if ($refund->status === PaymentProviderRefund::STATUS_COMPLETED) {
            $refund->update(['status' => PaymentProviderRefund::STATUS_PROVIDER_REFUNDED]);
            $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_REFUND_PROVIDER_COMPLETED', [
                'source_type' => 'payment_provider_refund', 'source_id' => $refund->id,
            ], $refund, 'Provider refund completed');
        } else {
            $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_REFUND_PROVIDER_REQUESTED', [
                'source_type' => 'payment_provider_refund', 'source_id' => $refund->id,
                'metadata' => ['amount' => $amount, 'status' => $refund->status],
            ], $refund, 'Provider refund requested via bridge');
        }

        return $refund->refresh();
    }
}
