<?php

namespace App\Services\Insurance\Verification\Drivers;

use App\Contracts\InsuranceVerificationDriver;
use App\Support\Insurance\VerificationRequest;
use App\Support\Insurance\VerificationResult;

/**
 * Manual driver — operator/desk officer vouches for the insurance.
 * No external system is contacted. Used as the safe default for any
 * provider whose verification_driver column is null or 'manual'.
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

        return new VerificationResult(
            status: \App\Enums\VerificationStatus::MANUAL_OVERRIDE,
            referenceCode: $request->referenceCode,
            memberName: $insurance->patient?->full_name,
            expiresAt: $insurance->expiry_date,
            message: 'Manually accepted by operator.',
        );
    }

    public function requiresReferenceCode(): bool
    {
        return false;
    }

    public function name(): string
    {
        return 'manual';
    }
}
