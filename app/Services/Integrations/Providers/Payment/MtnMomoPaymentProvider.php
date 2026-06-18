<?php

namespace App\Services\Integrations\Providers\Payment;

use App\Contracts\Integrations\PaymentProviderInterface;
use App\Services\Integrations\Providers\AbstractIntegrationProvider;
use App\Support\Integrations\Payment\PaymentCallbackResult;
use App\Support\Integrations\Payment\PaymentInitiationRequest;
use App\Support\Integrations\Payment\PaymentInitiationResult;
use App\Support\Integrations\Payment\PaymentRefundRequest;
use App\Support\Integrations\Payment\PaymentRefundResult;
use App\Support\Integrations\Payment\PaymentVerificationResult;
use App\Support\Integrations\ProviderTestResult;
use Illuminate\Support\Facades\Log;

/**
 * MTN Mobile Money (MoMo) Collections adapter.
 *
 * Credentials (integration_provider_credentials): subscription_key, api_user,
 * api_key, target_environment, collection_primary_key, callback_secret,
 * merchant_account_reference.
 *
 * NOTE (Phase 1): MoMo Collections uses an OAuth bearer token (POST /collection
 * /token/) obtained from api_user + api_key + subscription_key, then RequestToPay
 * + status polling. The token/RequestToPay calls below follow MoMo's documented
 * shape; sections needing per-account confirmation are marked TODO. This adapter
 * NEVER fakes success — transport/mapping errors return failed results.
 */
class MtnMomoPaymentProvider extends AbstractIntegrationProvider implements PaymentProviderInterface
{
    private const REQUIRED = ['subscription_key', 'api_user', 'api_key'];

    public function initiate(PaymentInitiationRequest $request): PaymentInitiationResult
    {
        if (! $this->ready()) {
            return new PaymentInitiationResult(false, 'failed', errorCode: 'not_configured',
                errorMessage: 'MTN MoMo credentials/base URL are not configured.');
        }

        try {
            $token = $this->accessToken();
            if (! $token) {
                return new PaymentInitiationResult(false, 'failed', errorCode: 'auth_failed',
                    errorMessage: 'Could not obtain an MTN MoMo access token.');
            }

            // RequestToPay — X-Reference-Id is OUR payment reference (UUID-like).
            // TODO: confirm payer party id format (msisdn) for the live account.
            $payload = [
                'amount' => number_format($request->amount, 2, '.', ''),
                'currency' => $request->currency,
                'externalId' => $request->paymentReference,
                'payer' => [
                    'partyIdType' => 'MSISDN',
                    'partyId' => preg_replace('/\D+/', '', (string) $request->payerPhone),
                ],
                'payerMessage' => $request->description ?: 'UHMS payment',
                'payeeNote' => $request->paymentReference,
            ];

            $response = $this->http()
                ->withToken($token)
                ->withHeaders([
                    'X-Reference-Id' => $request->paymentReference,
                    'X-Target-Environment' => $this->credential('target_environment', $this->isSandbox() ? 'sandbox' : 'mtnghana'),
                    'Ocp-Apim-Subscription-Key' => $this->credential('subscription_key'),
                ])
                ->post($this->baseUrl() . '/collection/v1_0/requesttopay', $payload);

            if ($response->status() === 202) {
                return new PaymentInitiationResult(
                    success: true,
                    status: 'pending',
                    providerTransactionId: $request->paymentReference, // MoMo keys status by our ref id
                    providerStatus: 'PENDING',
                    instructions: 'Approve the payment prompt on your MTN MoMo phone.',
                    raw: ['http_status' => 202],
                    httpStatus: 202,
                );
            }

            return new PaymentInitiationResult(false, 'failed',
                providerStatus: (string) $response->status(),
                errorCode: 'request_to_pay_failed',
                errorMessage: 'MTN MoMo did not accept the payment request.',
                raw: $this->decode($response->body()), httpStatus: $response->status());
        } catch (\Throwable $e) {
            Log::warning('MtnMomoPaymentProvider initiate failed', ['error' => $e->getMessage()]);
            return new PaymentInitiationResult(false, 'failed', errorCode: 'transport_error',
                errorMessage: 'Could not reach MTN MoMo.');
        }
    }

    public function verify(string $providerTransactionId, ?string $paymentReference = null): PaymentVerificationResult
    {
        if (! $this->ready()) {
            return new PaymentVerificationResult(false, 'pending', errorCode: 'not_configured',
                errorMessage: 'MTN MoMo is not configured.');
        }

        try {
            $token = $this->accessToken();
            $response = $this->http()
                ->withToken($token)
                ->withHeaders([
                    'X-Target-Environment' => $this->credential('target_environment', $this->isSandbox() ? 'sandbox' : 'mtnghana'),
                    'Ocp-Apim-Subscription-Key' => $this->credential('subscription_key'),
                ])
                ->get($this->baseUrl() . '/collection/v1_0/requesttopay/' . $providerTransactionId);

            $body = $this->decode($response->body());
            $providerStatus = strtoupper((string) ($body['status'] ?? ''));
            $status = match ($providerStatus) {
                'SUCCESSFUL' => 'paid',
                'FAILED' => 'failed',
                'PENDING' => 'pending',
                default => 'pending',
            };

            return new PaymentVerificationResult(
                success: $response->successful(),
                status: $status,
                isPaid: $status === 'paid',
                amount: isset($body['amount']) ? (float) $body['amount'] : null,
                currency: $body['currency'] ?? null,
                providerTransactionId: $providerTransactionId,
                providerStatus: $providerStatus ?: null,
                paidAt: $status === 'paid' ? now()->toIso8601String() : null,
                raw: $body, httpStatus: $response->status(),
            );
        } catch (\Throwable $e) {
            Log::warning('MtnMomoPaymentProvider verify failed', ['error' => $e->getMessage()]);
            return new PaymentVerificationResult(false, 'pending', errorCode: 'transport_error',
                errorMessage: 'Could not reach MTN MoMo.');
        }
    }

    public function handleCallback(array $payload, array $headers = []): PaymentCallbackResult
    {
        // MoMo can push status to a configured callback host. TODO: confirm the
        // signature/secret scheme for the live account; we re-verify regardless.
        $providerStatus = strtoupper((string) ($payload['status'] ?? ''));
        $status = match ($providerStatus) {
            'SUCCESSFUL' => 'paid',
            'FAILED' => 'failed',
            default => 'pending',
        };

        return new PaymentCallbackResult(
            paymentReference: $payload['externalId'] ?? null,
            providerTransactionId: $payload['referenceId'] ?? ($payload['financialTransactionId'] ?? null),
            status: $status,
            providerStatus: $providerStatus ?: null,
            amount: isset($payload['amount']) ? (float) $payload['amount'] : null,
            currency: $payload['currency'] ?? null,
            eventType: 'payment_status',
            signatureValid: $this->verifySignature($payload, $headers),
            raw: $payload,
        );
    }

    public function refund(PaymentRefundRequest $request): PaymentRefundResult
    {
        // MoMo refunds use the Disbursement product (separate keys). Foundation
        // only in Phase 1.
        return new PaymentRefundResult(false, 'failed', errorCode: 'not_implemented',
            errorMessage: 'MTN MoMo refund/disbursement is not implemented in Phase 1.');
    }

    public function testConnection(): ProviderTestResult
    {
        if (! $this->ready()) {
            return ProviderTestResult::fail('Base URL and credentials (subscription_key, api_user, api_key) are required.');
        }
        try {
            $token = $this->accessToken();
            return $token
                ? ProviderTestResult::pass('Obtained an MTN MoMo access token.')
                : ProviderTestResult::fail('Could not obtain an access token. Check credentials.');
        } catch (\Throwable $e) {
            return ProviderTestResult::fail('Could not reach MTN MoMo.');
        }
    }

    /* ── helpers ────────────────────────────────────────────────────── */

    private function ready(): bool
    {
        return $this->baseUrl() !== null && $this->hasCredentials(self::REQUIRED);
    }

    /** Obtain (and locally cache) an OAuth access token for Collections. */
    private function accessToken(): ?string
    {
        $response = $this->http()
            ->withBasicAuth((string) $this->credential('api_user'), (string) $this->credential('api_key'))
            ->withHeaders(['Ocp-Apim-Subscription-Key' => $this->credential('subscription_key')])
            ->post($this->baseUrl() . '/collection/token/');

        if (! $response->successful()) {
            return null;
        }
        $token = $this->decode($response->body())['access_token'] ?? null;
        return $token ? (string) $token : null;
    }

    private function decode(string $body): array
    {
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : ['raw' => $body];
    }
}
