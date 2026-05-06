<?php

namespace App\Services\Insurance\Verification\Drivers;

use App\Contracts\InsuranceVerificationDriver;
use App\Enums\VerificationStatus;
use App\Support\Insurance\VerificationRequest;
use App\Support\Insurance\VerificationResult;

/**
 * Code driver — provider issues a per-visit reference / authorization code
 * that is captured by the operator at the desk. The code is validated against
 * a regex pattern from verification_config.code_pattern (if set).
 *
 * This driver is generic. CCC is just one example of a code-style scheme; do
 * not introduce provider-specific branches here. If a provider also exposes
 * an HTTP API to confirm the code, configure ApiVerificationDriver instead.
 */
class CodeVerificationDriver implements InsuranceVerificationDriver
{
    public function verify(VerificationRequest $request): VerificationResult
    {
        $insurance = $request->patientInsurance;
        $provider = $insurance->insuranceProvider;

        if (! $insurance->is_active) {
            return VerificationResult::invalid('Insurance record is inactive.');
        }
        if ($insurance->expiry_date && $insurance->expiry_date->isPast()) {
            return VerificationResult::expired('Insurance expired on '.$insurance->expiry_date->format('d M Y'));
        }

        $code = trim((string) ($request->referenceCode ?? ''));
        if ($code === '') {
            return VerificationResult::pending('Enter the authorization/reference code issued by the provider.');
        }

        $config = (array) ($provider->verification_config ?? []);
        $pattern = $config['code_pattern'] ?? null;
        if ($pattern && ! @preg_match($pattern, $code)) {
            return VerificationResult::invalid('Reference code format is not recognized.');
        }

        return new VerificationResult(
            status: VerificationStatus::VALID,
            referenceCode: $code,
            memberName: $insurance->patient?->full_name,
            expiresAt: $insurance->expiry_date,
            message: 'Reference code accepted.',
        );
    }

    public function requiresReferenceCode(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'code';
    }
}
