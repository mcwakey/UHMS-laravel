<?php

namespace App\Services\Insurance\Verification\Drivers;

use App\Contracts\InsuranceVerificationDriver;
use App\Enums\VerificationStatus;
use App\Support\Insurance\VerificationRequest;
use App\Support\Insurance\VerificationResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generic HTTP API driver. Reads endpoint and HTTP shape from the provider's
 * verification_config column, and credentials from config/services.php (which
 * itself reads from .env). NO provider-specific code lives here.
 *
 * Expected verification_config:
 *   {
 *     "endpoint":     "https://api.example.com/v1/verify",
 *     "method":       "POST",
 *     "auth":         "bearer" | "basic" | "header",
 *     "auth_header":  "X-API-Key" (when auth=header),
 *     "membership_field": "member_id",
 *     "reference_field":  "reference",
 *     "response": {
 *         "status_field":   "data.status",
 *         "valid_value":    "active",
 *         "reference_field":"data.authorization",
 *         "expiry_field":   "data.expires_at",
 *         "name_field":     "data.member.name"
 *     }
 *   }
 *
 * Credentials come from config('services.insurance_verification.<credentials_key>').
 */
class ApiVerificationDriver implements InsuranceVerificationDriver
{
    public function verify(VerificationRequest $request): VerificationResult
    {
        $insurance = $request->patientInsurance;
        $provider = $insurance->insuranceProvider;
        $config = (array) ($provider->verification_config ?? []);
        $credKey = $provider->verification_credentials_key;

        if (! $credKey) {
            return VerificationResult::error('Provider has no verification_credentials_key configured.');
        }

        $endpoint = $config['endpoint'] ?? null;
        if (! $endpoint) {
            return VerificationResult::error('Provider verification endpoint missing in verification_config.');
        }

        $creds = (array) config("services.insurance_verification.{$credKey}", []);
        if (empty($creds)) {
            // No creds in env yet — fall back to manual capture so the desk can still proceed.
            return VerificationResult::pending(
                'Online verification is not configured for this provider. Capture the reference code manually.',
                requiresManual: true,
            );
        }

        try {
            $client = $this->buildClient($config, $creds);
            $payload = [
                ($config['membership_field'] ?? 'membership_number') => $insurance->membership_number,
                ($config['reference_field']  ?? 'reference')         => $request->referenceCode,
            ];
            $response = strtoupper($config['method'] ?? 'POST') === 'GET'
                ? $client->get($endpoint, $payload)
                : $client->post($endpoint, $payload);

            if (! $response->successful()) {
                return VerificationResult::error('Provider returned HTTP '.$response->status());
            }
            $body = $response->json() ?? [];
            return $this->mapResponse($body, $config, $insurance);
        } catch (\Throwable $e) {
            Log::warning('insurance.verification.api.exception', [
                'provider_id' => $provider->id,
                'driver' => 'api',
                'error' => $e->getMessage(),
            ]);
            return VerificationResult::error('Verification API call failed: '.$e->getMessage());
        }
    }

    private function buildClient(array $config, array $creds)
    {
        $client = Http::timeout((int) ($creds['timeout'] ?? 10))->acceptJson();
        $auth = $config['auth'] ?? 'bearer';

        if ($auth === 'bearer' && ! empty($creds['token'])) {
            $client = $client->withToken($creds['token']);
        } elseif ($auth === 'basic' && isset($creds['username'], $creds['password'])) {
            $client = $client->withBasicAuth($creds['username'], $creds['password']);
        } elseif ($auth === 'header') {
            $headerName = $config['auth_header'] ?? 'X-API-Key';
            $client = $client->withHeaders([$headerName => $creds['token'] ?? $creds['api_key'] ?? '']);
        }
        return $client;
    }

    private function mapResponse(array $body, array $config, $insurance): VerificationResult
    {
        $resp = (array) ($config['response'] ?? []);
        $statusVal = $this->dotGet($body, $resp['status_field'] ?? 'status');
        $validValue = $resp['valid_value'] ?? 'active';

        if (strtolower((string) $statusVal) !== strtolower((string) $validValue)) {
            return VerificationResult::invalid(
                (string) ($this->dotGet($body, $resp['message_field'] ?? 'message') ?? 'Provider rejected verification.'),
                payload: $body,
            );
        }
        return new VerificationResult(
            status: VerificationStatus::VALID,
            referenceCode: $this->dotGet($body, $resp['reference_field'] ?? null) ?: null,
            memberName:    $this->dotGet($body, $resp['name_field'] ?? null) ?: ($insurance->patient?->full_name),
            expiresAt:     $this->parseDate($this->dotGet($body, $resp['expiry_field'] ?? null)) ?: $insurance->expiry_date,
            message:       'Verified via provider API.',
            payload:       $body,
        );
    }

    private function dotGet(array $data, ?string $path)
    {
        if (! $path) return null;
        return data_get($data, $path);
    }

    private function parseDate($value): ?\Illuminate\Support\Carbon
    {
        if (! $value) return null;
        try { return \Illuminate\Support\Carbon::parse($value); } catch (\Throwable) { return null; }
    }

    public function requiresReferenceCode(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'api';
    }
}
