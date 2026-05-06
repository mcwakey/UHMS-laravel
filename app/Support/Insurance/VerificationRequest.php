<?php

namespace App\Support\Insurance;

use App\Models\PatientInsurance;
use App\Models\Visit;

/**
 * Driver input. Carries every piece of data a driver may need without
 * leaking the driver's particular protocol back into the call site.
 */
class VerificationRequest
{
    public function __construct(
        public readonly PatientInsurance $patientInsurance,
        public readonly ?Visit $visit = null,
        public readonly ?string $referenceCode = null,
        public readonly array $context = [],
    ) {}

    public function with(array $overrides): self
    {
        return new self(
            patientInsurance: $overrides['patientInsurance'] ?? $this->patientInsurance,
            visit:             $overrides['visit']             ?? $this->visit,
            referenceCode:     $overrides['referenceCode']     ?? $this->referenceCode,
            context:           array_replace($this->context, $overrides['context'] ?? []),
        );
    }
}
