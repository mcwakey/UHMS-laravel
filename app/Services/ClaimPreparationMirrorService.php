<?php

namespace App\Services;

use App\Models\Claim;

class ClaimPreparationMirrorService
{
    public function __construct(
        protected ConsultationSummaryService $consultationSummaryService,
    ) {}

    public function forClaim(Claim $claim): array
    {
        $claim->loadMissing('visit.medicalRecords');

        return $claim->visit
            ? $this->consultationSummaryService->forVisit($claim->visit)
            : ['records' => []];
    }
}
