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
 * Nalo Solutions Payments (mobile money collection) adapter.
 *
 * Credentials: api_key, merchant_id, client_id, client_secret, callback_secret.
 *
 * NOTE (Phase 1): Nalo PayPlus exposes a collection endpoint that returns a
 * pending order, then notifies the configured callback URL. The payload below
 * follows Nalo's documented shape; per-account specifics are marked TODO. This
 * adapter NEVER fakes success.
 */
class NaloPaymentProvider extends AbstractIntegrationProvider implements PaymentProviderInterface
{
    private const REQUIRED = ['api_key', 'merchant_id'];

    public function initiate(PaymentInitiationRequest $request): PaymentInitiationResult
    {
        if (! $this->ready()) {
            return new PaymentInitiationResult(false, 'failed', errorCode: 'not_configured',
                errorMessage: 'Nalo payment credentials/base URL are not configured.');
        }

        try {
            // TODO: confirm exact field names + signing with the live Nalo account.
            $payload = array_filter([
                'merchant_id' => $this->credential('merchant_id'),
                'secrete' => $this->credential('api_key'),
                'key' => $this->credential('client_secret'),
                'order_id' => $request->paymentReference,
                'customerName' => $request->payerName,
                'amount' => number_format($request->amount, 2, '.', ''),
                'item_desc' => $request->description ?: 'UHMS payment',
                'customerNumber' => preg_replace('/\D+/', '', (string) $request->payerPhone),
                'payby' => 'MTN',
                'callback' => $request->callbackUrl,
                'newVodaPayment' => false,
            ], fn ($v) => $v !== null && $v !== '');

            $response = $this->http()->asJson()->post($this->baseUrl(), $payload);
            $body = $this->decode($response->body());
            $ok = $response->successful() && $this->looksAccepted($body);

            return new PaymentInitiationResult(
                success: $ok,
                status: $ok ? 'pending' : 'failed',
                providerTransactionId: $body['transaction_id'] ?? ($body['order_id'] ?? $request->paymentReference),
                providerStatus: $body['status'] ?? (string) $response->status(),
                instructions: $ok ? 'Approve the prompt on your mobile money phone.' : null,
                errorCode: $ok ? null : 'initiate_failed',
                errorMessage: $ok ? null : ($body['message'] ?? 'Nalo did not accept the payment request.'),
                raw: $body, httpStatus: $response->status(),
            );
        } catch (\Throwable $e) {
            Log::warning('NaloPaymentProvider initiate failed', ['error' => $e->getMessage()]);
            return new PaymentInitiationResult(false, 'failed', errorCode: 'transport_error',
                errorMessage: 'Could not reach Nalo payments.');
        }
    }

    public function verify(string $providerTransactionId, ?string $paymentReference = null): PaymentVerificationResult
    {
        // TODO: implement Nalo status-query endpoint when available for the
        // account. Until then verification relies on the signed callback.
        return new PaymentVerificationResult(
            success: false,
            status: 'pending',
            errorCode: 'not_implemented',
            errorMessage: 'Nalo status query is not implemented; rely on the signed callback.',
        );
    }

    public function handleCallback(array $payload, array $headers = []): PaymentCallbackResult
    {
        // TODO: validate the callback HMAC against credential `callback_secret`.
        $providerStatus = strtoupper((string) ($payload['Status'] ?? $payload['status'] ?? ''));
        $status = match (true) {
            in_array($providerStatus, ['PAID', 'SUCCESS', 'SUCCESSFUL', 'COMPLETED'], true) => 'paid',
            in_array($providerStatus, ['FAILED', 'DECLINED', 'CANCELLED'], true) => 'failed',
            default => 'pending',
        };

        return new PaymentCallbackResult(
            paymentReference: $payload['order_id'] ?? ($payload['Order_id'] ?? null),
            providerTransactionId: $payload['transaction_id'] ?? ($payload['InvoiceNo'] ?? null),
            status: $status,
            providerStatus: $providerStatus ?: null,
            amount: isset($payload['amount']) ? (float) $payload['amount'] : null,
            currency: $payload['currency'] ?? null,
            eventType: 'payment_status',
            signatureValid: false,
            raw: $payload,
        );
    }

    public function refund(PaymentRefundRequest $request): PaymentRefundResult
    {
        return new PaymentRefundResult(false, 'failed', errorCode: 'not_implemented',
            errorMessage: 'Nalo refund is not implemented in Phase 1.');
    }

    public function testConnection(): ProviderTestResult
    {
        return $this->ready()
            ? ProviderTestResult::pass('Configuration present. Initiate a sandbox payment to confirm.')
            : ProviderTestResult::fail('Base URL and credentials (api_key, merchant_id) are required.');
    }

    /* ── helpers ────────────────────────────────────────────────────── */

    private function ready(): bool
    {
        return $this->baseUrl() !== null && $this->hasCredentials(self::REQUIRED);
    }

    private function decode(string $body): array
    {
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : ['raw' => $body];
    }

    private function looksAccepted(array $body): bool
    {
        $status = strtoupper((string) ($body['status'] ?? ($body['Status'] ?? '')));
        return in_array($status, ['ACCEPTED', 'PENDING', 'SUCCESS', 'OK', '1'], true)
            || isset($body['transaction_id']);
    }
}
