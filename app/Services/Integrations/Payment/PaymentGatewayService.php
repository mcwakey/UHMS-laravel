<?php

namespace App\Services\Integrations\Payment;

use App\Enums\LogModule;
use App\Models\PaymentProviderAttempt;
use App\Models\PaymentProviderRefund;
use App\Models\PaymentProviderTransaction;
use App\Services\ActivityLogService;
use App\Services\Integrations\IntegrationProviderRegistry;
use App\Services\LegacyMigration\Foundation\Runtime\OperationalEffectGate;
use App\Services\LegacyMigration\Foundation\Runtime\ProhibitedSubsystem;
use App\Support\Integrations\Payment\PaymentInitiationRequest;
use App\Support\Integrations\Payment\PaymentRefundRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Public entry point for the Payment Gateway module: initiate online/mobile-
 * money payments, manually verify/recheck, and request provider refunds.
 * Controllers call this — never an adapter directly.
 */
class PaymentGatewayService
{
    public function __construct(
        protected PaymentProviderResolver $resolver,
        protected PaymentProviderTransactionService $transactions,
        protected PaymentVerificationService $verification,
        protected IntegrationProviderRegistry $registry,
        protected ActivityLogService $logger,
    ) {}

    public function hasActiveProvider(): bool
    {
        return $this->resolver->activeProvider() !== null;
    }

    public function initiate(array $data): PaymentProviderTransaction
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::PaymentIntegrations);
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::ExternalIntegrations);

        $provider = $this->resolver->requireActiveProvider();
        $txn = $this->transactions->create($provider, $data);

        $request = new PaymentInitiationRequest(
            paymentReference: $txn->payment_reference,
            amount: (float) $txn->amount,
            currency: $txn->currency,
            payerName: $txn->payer_name,
            payerPhone: $txn->payer_phone,
            payerEmail: $txn->payer_email,
            paymentMethod: $txn->payment_method,
            description: $data['description'] ?? null,
            callbackUrl: $this->callbackUrl($provider->code),
        );

        try {
            $adapter = $this->registry->makePayment($provider);
            $result = $adapter->initiate($request);
        } catch (\Throwable $e) {
            $txn->update([
                'status' => PaymentProviderTransaction::STATUS_FAILED,
                'failed_at' => now(),
                'error_code' => 'initiate_error',
                'error_message' => 'Could not initiate the payment with the provider.',
            ]);
            $this->transactions->recordAttempt($txn, PaymentProviderAttempt::TYPE_INITIATE, PaymentProviderAttempt::STATUS_FAILED, [], [], null, 'initiate_error');

            return $txn->refresh();
        }

        $this->transactions->recordAttempt(
            $txn,
            PaymentProviderAttempt::TYPE_INITIATE,
            $result->success ? PaymentProviderAttempt::STATUS_SUCCEEDED : PaymentProviderAttempt::STATUS_FAILED,
            [], $result->raw, $result->httpStatus, $result->errorCode, $result->errorMessage,
        );

        $status = match ($result->status) {
            'pending' => PaymentProviderTransaction::STATUS_PENDING,
            'requires_customer_action' => PaymentProviderTransaction::STATUS_REQUIRES_CUSTOMER_ACTION,
            'initiated' => PaymentProviderTransaction::STATUS_INITIATED,
            default => PaymentProviderTransaction::STATUS_FAILED,
        };

        $txn->update([
            'status' => $status,
            'provider_transaction_id' => $result->providerTransactionId ?: $txn->provider_transaction_id,
            'provider_status' => $result->providerStatus,
            'initiated_at' => now(),
            'failed_at' => $status === PaymentProviderTransaction::STATUS_FAILED ? now() : null,
            'error_code' => $result->errorCode,
            'error_message' => $result->errorMessage,
            'metadata_snapshot' => array_merge((array) $txn->metadata_snapshot, array_filter([
                'redirect_url' => $result->redirectUrl,
                'instructions' => $result->instructions,
            ])),
            'updated_by' => Auth::id(),
        ]);

        $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_TRANSACTION_INITIATED', [
            'source_type' => 'payment_provider_transaction',
            'source_id' => $txn->id,
            'invoice_id' => $txn->invoice_id,
            'metadata' => ['provider' => $provider->code, 'amount' => (float) $txn->amount, 'status' => $status],
        ], $txn, 'Provider payment initiated');

        return $txn->refresh();
    }

    /** Manual verify / recheck of a pending transaction. */
    public function verify(PaymentProviderTransaction $transaction): PaymentProviderTransaction
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::PaymentIntegrations);
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::ExternalIntegrations);

        return $this->verification->verify($transaction);
    }

    /** Refund foundation — records intent and the provider's response. */
    public function requestRefund(PaymentProviderTransaction $transaction, float $amount, ?string $reason = null): PaymentProviderRefund
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::PaymentIntegrations);
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::ExternalIntegrations);

        $refund = PaymentProviderRefund::create([
            'payment_provider_transaction_id' => $transaction->id,
            'provider_id' => $transaction->provider_id,
            'refund_reference' => 'RF-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
            'amount' => round($amount, 2),
            'currency' => $transaction->currency,
            'status' => PaymentProviderRefund::STATUS_PENDING,
            'reason' => $reason,
            'requested_by' => Auth::id(),
            'requested_at' => now(),
        ]);

        $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_REFUND_REQUESTED', [
            'source_type' => 'payment_provider_refund',
            'source_id' => $refund->id,
            'metadata' => ['amount' => (float) $refund->amount, 'transaction' => $transaction->payment_reference],
        ], $refund, 'Provider refund requested');

        try {
            $adapter = $this->registry->makePayment($transaction->provider);
            $result = $adapter->refund(new PaymentRefundRequest(
                refundReference: $refund->refund_reference,
                providerTransactionId: (string) ($transaction->provider_transaction_id ?: $transaction->payment_reference),
                amount: (float) $refund->amount,
                currency: $refund->currency,
                reason: $reason,
            ));
        } catch (\Throwable $e) {
            $refund->update(['status' => PaymentProviderRefund::STATUS_FAILED, 'failed_at' => now()]);

            return $refund->refresh();
        }

        if ($result->success) {
            $refund->update([
                'status' => $result->status === 'completed' ? PaymentProviderRefund::STATUS_COMPLETED : PaymentProviderRefund::STATUS_PROCESSING,
                'provider_refund_id' => $result->providerRefundId,
                'processed_at' => $result->status === 'completed' ? now() : null,
            ]);
            $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_REFUND_COMPLETED', [
                'source_type' => 'payment_provider_refund',
                'source_id' => $refund->id,
            ], $refund, 'Provider refund completed');
        } else {
            $refund->update(['status' => PaymentProviderRefund::STATUS_FAILED, 'failed_at' => now()]);
            $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_REFUND_FAILED', [
                'source_type' => 'payment_provider_refund',
                'source_id' => $refund->id,
            ], $refund, 'Provider refund failed');
        }

        return $refund->refresh();
    }

    private function callbackUrl(string $providerCode): ?string
    {
        try {
            return route('api.integrations.payments.callback', ['providerCode' => $providerCode]);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
