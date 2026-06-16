<?php

namespace App\Services;

class BudgetActualsService
{
    public function __construct(protected BudgetAvailabilityService $availability) {}

    public function summary(?int $fiscalYearId = null): array
    {
        return $this->availability->summary($fiscalYearId);
    }
}
