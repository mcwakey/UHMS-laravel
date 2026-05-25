<?php

namespace App\Services\Claims;

use App\Models\Claim;
use App\Models\InsuranceProvider;
use App\Models\InsuranceType;

class ClaimWorkflowManager
{
    public function forProvider(InsuranceProvider $provider): ClaimWorkflowInterface
    {
        $provider->loadMissing('insuranceType');

        return $this->forWorkflowCode($provider->claimWorkflowCode());
    }

    public function forInsuranceType(?InsuranceType $insuranceType): ClaimWorkflowInterface
    {
        return $this->forWorkflowCode($insuranceType?->claim_workflow ?: 'GENERIC');
    }

    public function forClaim(Claim $claim): ClaimWorkflowInterface
    {
        $code = $claim->claim_workflow_code
            ?: $claim->insuranceProvider?->claimWorkflowCode()
            ?: 'GENERIC';

        return $this->forWorkflowCode($code);
    }

    private function forWorkflowCode(?string $code): ClaimWorkflowInterface
    {
        return match (strtoupper((string) $code)) {
            'NHIA' => app(NhiaClaimWorkflow::class),
            default => app(GenericClaimWorkflow::class),
        };
    }
}
