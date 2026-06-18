<?php

namespace App\Services\Integrations\Providers\Payment;

use App\Contracts\Integrations\PaymentProviderInterface;
use App\Models\PaymentProviderTransaction;
use App\Services\Integrations\Providers\AbstractIntegrationProvider;
use App\Support\Integrations\Payment\PaymentCallbackResult;
use App\Support\Integrations\Payment\PaymentInitiationRequest;
use App\Support\Integrations\Payment\PaymentInitiationResult;
use App\Support\Integrations\Payment\PaymentRefundRequest;
use App\Support\Integrations\Payment\PaymentRefundResult;
use App\Support\Integrations\Payment\PaymentVerificationResult;
use App\Support\Integrations\ProviderTestResult;
use Illuminate\Support\Str;

/**
 * Deterministic fake payment provider for local dev, demo and automated tests.
 * Never performs network I/O.
 *
 * Behaviour can be steered from a transaction's metadata_snapshot for testing:
 *   fake_force_status => paid|failed|pending|expired|cancelled  (verify outcome)
 *   fake_force_amount => float  (override the amount the "provider" reports)
 * Selectable only when fake providers are allowed (resolver-guarded).
 */
class FakePaymentProvider extends AbstractIntegrationProvider implements PaymentProviderInterface
{
    public function initiate(PaymentInitiationRequest $request): PaymentInitiationResult
    {
        return new PaymentInitiationResult(
            success: true,
            status: 'pending',
            providerTransactionId: 'FAKEPAY-' . Str::upper(Str::random(12)),
            providerStatus: 'PENDING',
            instructions: 'Fake provider: approve via the test callback or click Verify.',
            raw: ['provider' => 'fake_payment', 'payment_reference' => $request->paymentReference],
            httpStatus: 200,
        );
    }

    public function verify(string $providerTransactionId, ?string $paymentReference = null): PaymentVerificationResult
    {
        $txn = $this->lookup($providerTransactionId, $paymentReference);

        $meta = (array) ($txn?->metadata_snapshot ?? []);
        $status = (string) ($meta['fake_force_status'] ?? 'paid');
        $amount = array_key_exists('fake_force_amount', $meta)
            ? (float) $meta['fake_force_amount']
            : (float) ($txn?->amount ?? 0);

        return new PaymentVerificationResult(
            success: true,
            status: $status,
            isPaid: $status === 'paid',
            amount: $amount,
            currency: $txn?->currency ?? config('integrations.default_currency', 'GHS'),
            providerTransactionId: $providerTransactionId,
            providerStatus: 'FAKE_' . strtoupper($status),
            paidAt: $status === 'paid' ? now()->toIso8601String() : null,
            raw: ['provider' => 'fake_payment'],
            httpStatus: 200,
        );
    }

    public function handleCallback(array $payload, array $headers = []): PaymentCallbackResult
    {
        return new PaymentCallbackResult(
            paymentReference: $payload['payment_reference'] ?? null,
            providerTransactionId: $payload['provider_transaction_id'] ?? ($payload['transaction_id'] ?? null),
            status: $payload['status'] ?? 'paid',
            providerStatus: $payload['provider_status'] ?? ($payload['status'] ?? 'PAID'),
            amount: isset($payload['amount']) ? (float) $payload['amount'] : null,
            currency: $payload['currency'] ?? config('integrations.default_currency', 'GHS'),
            eventType: $payload['event_type'] ?? 'payment_status',
            signatureValid: $this->verifySignature($payload, $headers),
            raw: $payload,
        );
    }

    public function refund(PaymentRefundRequest $request): PaymentRefundResult
    {
        return new PaymentRefundResult(
            success: true,
            status: 'completed',
            providerRefundId: 'FAKEREF-' . Str::upper(Str::random(10)),
            raw: ['provider' => 'fake_payment', 'amount' => $request->amount],
        );
    }

    public function testConnection(): ProviderTestResult
    {
        return ProviderTestResult::pass('Fake payment provider is reachable (no network).');
    }

    private function lookup(string $providerTransactionId, ?string $paymentReference): ?PaymentProviderTransaction
    {
        $query = PaymentProviderTransaction::query();
        if ($paymentReference) {
            $found = (clone $query)->where('payment_reference', $paymentReference)->first();
            if ($found) {
                return $found;
            }
        }
        return $query->where('provider_transaction_id', $providerTransactionId)->first();
    }
}
