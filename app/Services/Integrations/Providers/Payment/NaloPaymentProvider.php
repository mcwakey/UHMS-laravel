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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * NALOPAY (Nalo Solutions) mobile-money collection adapter — NALOPAY API v1.
 *
 * Credentials (integration_provider_credentials):
 *   merchant_id      — your NALOPAY merchant id
 *   basic_auth_token — the Basic auth token (the value after "Basic " in the docs)
 *   secret_key       — merchant secret used to sign the trans_hash
 *
 * Flow: generate-payment-token (Basic auth → JWT) → collection (token header +
 * HMAC-SHA256 trans_hash) → provider notifies the callback URL → collection-status
 * recheck before any UHMS payment is created.
 *
 * Base URL: set the provider base_url to your NALOPAY base; this adapter appends
 * the /clientapi/* paths. Never fakes success.
 */
class NaloPaymentProvider extends AbstractIntegrationProvider implements PaymentProviderInterface
{
    private const REQUIRED = ['merchant_id', 'basic_auth_token', 'secret_key'];

    /** Why the last token request failed (HTTP status + provider message), for diagnostics. */
    private ?array $lastTokenDiagnostic = null;

    /** Map UHMS payment methods to NALOPAY networks. */
    private const NETWORKS = [
        'mtn_momo' => 'MTN',
        'airteltigo_money' => 'AT',
        'vodafone_cash' => 'TELECEL',
        'telecel_cash' => 'TELECEL',
    ];

    public function requiredCredentials(): array
    {
        return self::REQUIRED;
    }

    public function initiate(PaymentInitiationRequest $request): PaymentInitiationResult
    {
        if (! $this->ready()) {
            return $this->failInit('not_configured', 'Nalo payment credentials / base URL are not configured.');
        }

        try {
            $token = $this->token();
            if (! $token) {
                return $this->failInit('auth_failed', 'Could not obtain a NALOPAY payment token' . $this->tokenFailureDetail() . '. Verify the Base URL, merchant id and Basic auth token.');
            }

            $payload = $this->buildCollectionPayload($request);
            $response = $this->postCollection($token, $payload);

            // A cached token may have expired between calls — regenerate once.
            if (in_array($response->status(), [401, 403], true)) {
                $token = $this->token(forceFresh: true);
                if ($token) {
                    $response = $this->postCollection($token, $payload);
                }
            }

            $body = $this->decode($response->body());
            $data = (array) ($body['data'] ?? []);
            $ok = $response->successful() && (($body['success'] ?? false) === true) && ! empty($data['order_id']);

            if (! $ok) {
                return new PaymentInitiationResult(
                    success: false,
                    status: 'failed',
                    providerStatus: (string) ($data['status'] ?? $body['code'] ?? $response->status()),
                    errorCode: (string) ($body['code'] ?? 'initiate_failed'),
                    errorMessage: $this->normalizeError($body) ?? 'NALOPAY did not accept the payment request.',
                    raw: $this->scrub($body),
                    httpStatus: $response->status(),
                );
            }

            $otp = $data['otp_code'] ?? null;

            return new PaymentInitiationResult(
                success: true,
                status: 'pending',
                providerTransactionId: (string) $data['order_id'],
                providerStatus: (string) ($data['status'] ?? 'PENDING'),
                instructions: $otp && $otp !== 'None'
                    ? 'Approve the payment on your phone (dial ' . $otp . ' if prompted).'
                    : 'Approve the mobile money prompt on your phone.',
                raw: $this->scrub($body),
                httpStatus: $response->status(),
            );
        } catch (\Throwable $e) {
            Log::warning('NaloPaymentProvider initiate failed', ['error' => $e->getMessage()]);
            return $this->failInit('transport_error', 'Could not reach NALOPAY.');
        }
    }

    public function verify(string $providerTransactionId, ?string $paymentReference = null): PaymentVerificationResult
    {
        if (! $this->ready()) {
            return new PaymentVerificationResult(false, 'pending', errorCode: 'not_configured',
                errorMessage: 'NALOPAY is not configured.');
        }

        try {
            $response = $this->http()->asJson()->post($this->endpoint('collection-status/'), [
                'merchant_id' => $this->credential('merchant_id'),
                'order_id' => $providerTransactionId,
            ]);

            $body = $this->decode($response->body());
            $data = (array) ($body['data'] ?? []);
            $providerStatus = strtoupper((string) ($data['status'] ?? ''));
            $status = $this->mapStatus($providerStatus);

            return new PaymentVerificationResult(
                success: $response->successful() && (($body['success'] ?? false) === true),
                status: $status,
                isPaid: $status === 'paid',
                amount: isset($data['amount']) ? (float) $data['amount'] : null,
                currency: null,
                providerTransactionId: $providerTransactionId,
                providerStatus: $providerStatus ?: null,
                paidAt: $status === 'paid' ? now()->toIso8601String() : null,
                raw: $this->scrub($body),
                httpStatus: $response->status(),
            );
        } catch (\Throwable $e) {
            Log::warning('NaloPaymentProvider verify failed', ['error' => $e->getMessage()]);
            return new PaymentVerificationResult(false, 'pending', errorCode: 'transport_error',
                errorMessage: 'Could not reach NALOPAY.');
        }
    }

    public function handleCallback(array $payload, array $headers = []): PaymentCallbackResult
    {
        // NALOPAY callback: { order_id, status, amount, charges, transaction_fee, extra_data? }
        // It is unsigned — rely on the collection-status recheck (supports_status_check).
        $providerStatus = strtoupper((string) ($payload['status'] ?? ''));

        return new PaymentCallbackResult(
            paymentReference: data_get($payload, 'extra_data.reference'),
            providerTransactionId: $payload['order_id'] ?? null,
            status: $this->mapStatus($providerStatus),
            providerStatus: $providerStatus ?: null,
            amount: isset($payload['amount']) ? (float) $payload['amount'] : null,
            currency: null,
            eventType: 'payment_status',
            signatureValid: $this->verifySignature($payload, $headers),
            raw: $payload,
        );
    }

    public function refund(PaymentRefundRequest $request): PaymentRefundResult
    {
        // NALOPAY v1 collection API exposes no refund endpoint — handled as a
        // manual refund by the refund bridge.
        return new PaymentRefundResult(false, 'failed', errorCode: 'not_supported',
            errorMessage: 'NALOPAY does not support automated refunds.');
    }

    public function testConnection(): ProviderTestResult
    {
        if (! $this->ready()) {
            return ProviderTestResult::fail('Base URL and credentials (merchant_id, basic_auth_token, secret_key) are required.');
        }

        try {
            return $this->token(forceFresh: true)
                ? ProviderTestResult::pass('Obtained a NALOPAY payment token.')
                : ProviderTestResult::fail('Could not obtain a payment token' . $this->tokenFailureDetail() . '. Verify the Base URL, merchant id and Basic auth token.');
        } catch (\Throwable $e) {
            return ProviderTestResult::fail('Could not reach NALOPAY.');
        }
    }

    /* ── request builder ────────────────────────────────────────────── */

    private function buildCollectionPayload(PaymentInitiationRequest $request): array
    {
        $merchantId = (string) $this->credential('merchant_id');
        $account = $this->accountNumber($request->payerPhone);
        $amount = number_format($request->amount, 2, '.', '');
        $reference = $request->paymentReference;

        return array_filter([
            'merchant_id' => $merchantId,
            'service_name' => 'MOMO_TRANSACTION',
            'trans_hash' => $this->transHash($merchantId, $account, $amount, $reference),
            'account_number' => $account,
            'account_name' => $request->payerName ?: 'UHMS Patient',
            'network' => $this->network($request->paymentMethod),
            'amount' => $amount,
            'reference' => $reference,
            'callback' => $request->callbackUrl,
            'description' => $request->description ?: 'UHMS payment',
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * trans_hash = HMAC-SHA256( merchant_id + account_number + amount + reference, secret_key )
     * concatenated with NO separators (per NALOPAY docs). Amount is the same
     * 2-decimal string sent in the body to keep the hash and payload consistent.
     */
    private function transHash(string $merchantId, string $account, string $amount, string $reference): string
    {
        $message = $merchantId . $account . $amount . $reference;
        return hash_hmac('sha256', $message, (string) $this->credential('secret_key'));
    }

    private function postCollection(string $token, array $payload)
    {
        return $this->http()
            ->withHeaders(['token' => $token])
            ->asJson()
            ->post($this->endpoint('collection/'), $payload);
    }

    /* ── auth (token auto-generated from merchant_id, cached per validity) ── */

    /**
     * Return a NALOPAY payment token, auto-generated from the merchant_id + Basic
     * auth token. The JWT is valid for ~15 min, so it is cached and reused; pass
     * forceFresh to bypass + refresh the cache (used on expiry / provider test).
     */
    private function token(bool $forceFresh = false): ?string
    {
        $key = $this->tokenCacheKey();

        if ($forceFresh) {
            Cache::forget($key);
        } elseif ($cached = Cache::get($key)) {
            return (string) $cached;
        }

        $token = $this->requestToken();
        if ($token) {
            $ttl = (int) config('integrations.nalo_token_ttl', 600); // < 15-min JWT lifetime
            Cache::put($key, $token, now()->addSeconds($ttl));
        }

        return $token;
    }

    private function requestToken(): ?string
    {
        $url = $this->endpoint('generate-payment-token/');

        try {
            $response = $this->http()
                ->withHeaders(['Authorization' => $this->basicAuthHeader()])
                ->asJson()
                ->post($url, ['merchant_id' => $this->credential('merchant_id')]);
        } catch (\Throwable $e) {
            $this->lastTokenDiagnostic = ['status' => 0, 'message' => 'network error (could not reach the base URL)'];
            Log::warning('NALOPAY token request transport error', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }

        $body = $this->decode($response->body());
        $token = data_get($body, 'data.token');

        if ($response->successful() && $token) {
            $this->lastTokenDiagnostic = null;
            return (string) $token;
        }

        $this->lastTokenDiagnostic = [
            'status' => $response->status(),
            'message' => $this->normalizeError($body),
        ];
        Log::warning('NALOPAY token generation failed', [
            'url' => $url,
            'http_status' => $response->status(),
            'provider_code' => $body['code'] ?? null,
            'provider_message' => $this->normalizeError($body),
        ]);

        return null;
    }

    private function tokenFailureDetail(): string
    {
        $d = $this->lastTokenDiagnostic;
        if (! $d) {
            return '';
        }
        $status = ($d['status'] ?? 0) ?: '—';
        return ' (HTTP ' . $status . (! empty($d['message']) ? ' — ' . $d['message'] : '') . ')';
    }

    private function tokenCacheKey(): string
    {
        return 'integrations.nalopay.token.' . sha1((string) $this->credential('merchant_id') . '|' . (string) $this->baseUrl());
    }

    private function basicAuthHeader(): string
    {
        $token = trim((string) $this->credential('basic_auth_token'));
        return str_starts_with($token, 'Basic ') ? $token : 'Basic ' . $token;
    }

    /* ── helpers ────────────────────────────────────────────────────── */

    private function ready(): bool
    {
        return $this->baseUrl() !== null && $this->hasCredentials(self::REQUIRED);
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) $this->baseUrl(), '/') . '/clientapi/' . ltrim($path, '/');
    }

    private function network(?string $paymentMethod): string
    {
        return self::NETWORKS[(string) $paymentMethod] ?? 'MTN';
    }

    private function accountNumber(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    private function mapStatus(string $providerStatus): string
    {
        return match (true) {
            in_array($providerStatus, ['COMPLETED', 'SUCCESS', 'SUCCESSFUL', 'PAID'], true) => 'paid',
            in_array($providerStatus, ['FAILED', 'DECLINED', 'CANCELLED', 'REVERSED'], true) => 'failed',
            default => 'pending',
        };
    }

    private function failInit(string $code, string $message): PaymentInitiationResult
    {
        return new PaymentInitiationResult(false, 'failed', errorCode: $code, errorMessage: $message);
    }

    private function normalizeError(array $body): ?string
    {
        foreach (['message', 'error', 'detail', 'code'] as $key) {
            if (! empty($body[$key]) && is_string($body[$key])) {
                return $body[$key];
            }
        }
        return null;
    }

    private function decode(string $body): array
    {
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : ['raw' => $body];
    }

    /** Strip the JWT token from any stored response snapshot — never persist it. */
    private function scrub(array $body): array
    {
        if (isset($body['data']['token'])) {
            $body['data']['token'] = '***MASKED***';
        }
        return $body;
    }
}
