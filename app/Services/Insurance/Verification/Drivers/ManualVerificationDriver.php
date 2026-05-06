<?php

namespace App\Services\Insurance\Verification\Drivers;

use App\Contracts\InsuranceVerificationDriver;
use App\Support\Insurance\VerificationRequest;
use App\Support\Insurance\VerificationResult;

/**
 * Manual driver — operator/desk officer captures the provider-issued reference.
 * No external system is contacted. A blank reference keeps the verification
 * pending so the visit desk can enter the code manually.
 */
class ManualVerificationDriver implements InsuranceVerificationDriver
{
    public function verify(VerificationRequest $request): VerificationResult
    {
        $insurance = $request->patientInsurance;

        if (! $insurance->is_active) {
            return VerificationResult::invalid('Insurance record is inactive.');
        }
        if ($insurance->expiry_date && $insurance->expiry_date->isPast()) {
            return VerificationResult::expired('Insurance expired on '.$insurance->expiry_date->format('d M Y'));
        }

        $code = trim((string) ($request->referenceCode ?? ''));
        if ($code === '') {
            return VerificationResult::pending('Enter the manually captured authorization/reference code.');
        }

        return new VerificationResult(
            status: \App\Enums\VerificationStatus::MANUAL_OVERRIDE,
            referenceCode: $code,
            memberName: $insurance->patient?->full_name,
            expiresAt: $insurance->expiry_date,
            message: 'Manually accepted by operator.',
        );
    }

    public function requiresReferenceCode(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'manual';
    }
}
