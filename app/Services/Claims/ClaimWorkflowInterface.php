<?php

namespace App\Services\Claims;

use App\Models\Claim;
use App\Models\User;
use App\Models\Visit;

interface ClaimWorkflowInterface
{
    public function prepareFromVisit(Visit $visit, User $user): Claim;

    public function validateClaim(Claim $claim): ClaimValidationResult;

    public function markReady(Claim $claim, User $user): Claim;

    public function submit(Claim $claim, User $user, array $data = []): Claim;

    public function export(Claim $claim, ?string $format = null): mixed;
}
