<?php

namespace App\Services\LegacyMigration\Foundation\Validation;

final class PatientTargetStateValidator extends VersionedTargetStateValidator
{
    public function __construct(VerifiedPolicyBundle $bundle)
    {
        parent::__construct(TargetStatePolicy::patientPhase2F($bundle));
    }
}
