<?php

namespace App\Services\Integrations\Payment;

use App\Models\IntegrationProvider;
use App\Models\PaymentProviderAttempt;
use App\Models\PaymentProviderTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Creates pending provider transactions and records every provider attempt
 * (request/response snapshots) for troubleshooting and idempotency.
 */
class PaymentProviderTransactionService
{
    public function create(IntegrationProvider $provider, array $data): PaymentProviderTransaction
    {
        return PaymentProviderTransaction::create([
            'transaction_uuid' => (string) Str::uuid(),
            'provider_id' => $provider->id,
            'provider_code' => $provider->code,
            'payment_reference' => $this->generateReference(),
            'invoice_id' => $data['invoice_id'] ?? null,
            'visit_id' => $data['visit_id'] ?? null,
            'patient_id' => $data['patient_id'] ?? null,
            'payer_name' => $data['payer_name'] ?? null,
            'payer_phone' => $data['payer_phone'] ?? null,
            'payer_email' => $data['payer_email'] ?? null,
            'amount' => round((float) ($data['amount'] ?? 0), 2),
            'currency' => $data['currency'] ?? config('integrations.default_currency', 'GHS'),
            'payment_method' => $data['payment_method'] ?? null,
            'status' => PaymentProviderTransaction::STATUS_DRAFT,
            'metadata_snapshot' => $data['metadata'] ?? null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    public function recordAttempt(
        PaymentProviderTransaction $txn,
        string $type,
        string $status,
        array $request = [],
        array $response = [],
        ?int $httpStatus = null,
        ?string $errorCode = null,
        ?string $errorMessage = null,
    ): PaymentProviderAttempt {
        return $txn->attempts()->create([
            'provider_id' => $txn->provider_id,
            'attempt_type' => $type,
            'status' => $status,
            'request_payload_snapshot' => $this->scrub($request),
            'response_payload_snapshot' => $this->scrub($response),
            'http_status' => $httpStatus,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
    }

    public function generateReference(): string
    {
        return 'PG-' . now()->format('ymd') . '-' . Str::upper(Str::random(8));
    }

    /** Strip obviously-sensitive keys from request/response snapshots. */
    private function scrub(array $data): array
    {
        $masked = ['api_key', 'apikey', 'password', 'secret', 'secrete', 'subscription_key', 'authorization', 'token', 'access_token'];
        $out = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), $masked, true)) {
                $out[$key] = '***MASKED***';
            } elseif (is_array($value)) {
                $out[$key] = $this->scrub($value);
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }
}
