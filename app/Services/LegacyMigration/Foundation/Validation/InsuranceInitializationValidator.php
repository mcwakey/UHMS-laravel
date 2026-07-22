<?php

namespace App\Services\LegacyMigration\Foundation\Validation;

final class InsuranceInitializationValidator extends VersionedTargetStateValidator
{
    public function __construct()
    {
        parent::__construct(TargetStatePolicy::insurancePhase2F());
    }
}
