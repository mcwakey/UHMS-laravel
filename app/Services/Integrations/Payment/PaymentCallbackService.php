<?php

namespace App\Services\Integrations\Payment;

use App\Enums\LogModule;
use App\Models\PaymentProviderCallback;
use App\Models\PaymentProviderTransaction;
use App\Services\ActivityLogService;
use App\Services\Integrations\IntegrationProviderRegistry;
use App\Services\LegacyMigration\Foundation\Runtime\OperationalEffectGate;
use App\Services\LegacyMigration\Foundation\Runtime\ProhibitedSubsystem;
use App\Support\Integrations\Payment\PaymentVerificationResult;

/**
 * Stores and idempotently processes inbound payment provider callbacks.
 *
 * Every callback is stored before any processing. A callback is never trusted
 * on its own for amount — when the provider supports status verification we
 * re-verify with the provider; otherwise we use the callback as the basis but
 * still validate amount/currency in PaymentVerificationService. Duplicate
 * callbacks for an already-paid transaction never create a second payment.
 */
class PaymentCallbackService
{
    public function __construct(
        protected PaymentProviderResolver $resolver,
        protected IntegrationProviderRegistry $registry,
        protected PaymentVerificationService $verification,
        protected ActivityLogService $logger,
    ) {}

    public function store(string $providerCode, array $payload, array $headers = [], ?string $ip = null): PaymentProviderCallback
    {
        $this->assertWebhookAllowed();
        $provider = $this->resolver->findByCode($providerCode);

        return PaymentProviderCallback::create([
            'provider_id' => $provider?->id,
            'provider_code' => $providerCode,
            'raw_payload' => $payload,
            'headers_snapshot' => $this->safeHeaders($headers),
            'ip_address' => $ip,
            'payment_reference' => $payload['payment_reference'] ?? ($payload['externalId'] ?? ($payload['order_id'] ?? null)),
            'provider_transaction_id' => $payload['provider_transaction_id'] ?? ($payload['transaction_id'] ?? ($payload['referenceId'] ?? null)),
        ]);
    }

    public function process(PaymentProviderCallback $callback): PaymentProviderCallback
    {
        $this->assertWebhookAllowed();
        $provider = $this->resolver->findByCode((string) $callback->provider_code);
        if (! $provider) {
            return $this->finish($callback, 'unknown_provider');
        }

        try {
            $adapter = $this->registry->makePayment($provider);
            $result = $adapter->handleCallback((array) $callback->raw_payload, (array) $callback->headers_snapshot);
        } catch (\Throwable $e) {
            return $this->finish($callback, 'adapter_error');
        }

        $callback->update([
            'event_type' => $result->eventType,
            'signature_valid' => $result->signatureValid,
            'payment_reference' => $result->paymentReference ?: $callback->payment_reference,
            'provider_transaction_id' => $result->providerTransactionId ?: $callback->provider_transaction_id,
        ]);

        $txn = $this->locateTransaction($callback->payment_reference, $callback->provider_transaction_id);

        if (! $txn) {
            // Unknown/forged reference — retained, never posted.
            $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_TRANSACTION_CALLBACK_RECEIVED', [
                'source_type' => 'payment_provider_callback',
                'source_id' => $callback->id,
                'metadata' => ['matched' => false, 'reference' => $callback->payment_reference],
            ], $callback, 'Payment callback received for unknown reference');
            return $this->finish($callback, 'unknown_reference');
        }

        $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_TRANSACTION_CALLBACK_RECEIVED', [
            'source_type' => 'payment_provider_transaction',
            'source_id' => $txn->id,
            'metadata' => ['provider' => $callback->provider_code, 'status' => $result->status],
        ], $txn, 'Payment callback received');

        // Signature/secret hardening: a provider that requires a signature must
        // present a valid one. A signature failure NEVER creates a UHMS payment;
        // the transaction is left for provider-verified manual recheck.
        if ($provider->require_signature && ! $result->signatureValid) {
            $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_TRANSACTION_CALLBACK_RECEIVED', [
                'source_type' => 'payment_provider_transaction',
                'source_id' => $txn->id,
                'severity' => \App\Enums\LogSeverity::WARNING,
                'metadata' => ['signature_valid' => false, 'blocked' => true],
            ], $txn, 'Payment callback signature invalid — not posted');
            return $this->finish($callback, 'signature_invalid');
        }

        // Idempotency: a transaction already converted to a UHMS payment is done.
        if ($txn->hasUhmsPayment()) {
            $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_TRANSACTION_DUPLICATE_CALLBACK_IGNORED', [
                'source_type' => 'payment_provider_transaction',
                'source_id' => $txn->id,
            ], $txn, 'Duplicate payment callback ignored');
            return $this->finish($callback, null);
        }

        // Re-verify with the provider when it supports status checks; otherwise
        // use the (signature-checked) callback result, still amount-validated.
        $injected = $provider->supports_status_check
            ? null
            : new PaymentVerificationResult(
                success: true,
                status: (string) ($result->status ?? 'pending'),
                isPaid: $result->status === 'paid',
                amount: $result->amount,
                currency: $result->currency,
                providerTransactionId: $result->providerTransactionId,
                providerStatus: $result->providerStatus,
            );

        $this->verification->verify($txn, $injected);

        return $this->finish($callback, null);
    }

    public function handle(string $providerCode, array $payload, array $headers = [], ?string $ip = null): PaymentProviderCallback
    {
        $this->assertWebhookAllowed();
        return $this->process($this->store($providerCode, $payload, $headers, $ip));
    }

    private function assertWebhookAllowed(): void
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::Webhooks);
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::ExternalIntegrations);
    }

    /* ── helpers ────────────────────────────────────────────────────── */

    private function locateTransaction(?string $reference, ?string $providerTxnId): ?PaymentProviderTransaction
    {
        if ($reference) {
            $found = PaymentProviderTransaction::where('payment_reference', $reference)->first();
            if ($found) {
                return $found;
            }
        }
        if ($providerTxnId) {
            return PaymentProviderTransaction::where('provider_transaction_id', $providerTxnId)->first();
        }
        return null;
    }

    private function finish(PaymentProviderCallback $callback, ?string $error): PaymentProviderCallback
    {
        $callback->update([
            'processed' => true,
            'processed_at' => now(),
            'processing_error' => $error,
        ]);
        return $callback;
    }

    private function safeHeaders(array $headers): array
    {
        $drop = ['authorization', 'cookie', 'x-api-key'];
        $out = [];
        foreach ($headers as $key => $value) {
            $out[$key] = in_array(strtolower((string) $key), $drop, true) ? '***MASKED***' : $value;
        }
        return $out;
    }
}
