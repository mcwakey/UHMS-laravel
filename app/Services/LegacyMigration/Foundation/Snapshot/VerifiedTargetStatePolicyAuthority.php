<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Validation\TargetStatePolicy;
use App\Services\LegacyMigration\Foundation\Validation\VerifiedPolicyBundle;

final readonly class VerifiedTargetStatePolicyAuthority implements VerifiedRunAuthority
{
    private function __construct(private string $reference) {}

    public static function fromPolicyBundle(VerifiedPolicyBundle $bundle): self
    {
        $patient = TargetStatePolicy::patientPhase2F($bundle);
        $insurance = TargetStatePolicy::insurancePhase2F($bundle);

        return new self((new CanonicalManifestHasher)->hash([
            'authority' => 'phase2f_target_state',
            'bundle_hash' => $bundle->bundleHash,
            'patient_policy' => $patient->authority,
            'insurance_policy' => $insurance->authority,
        ]));
    }

    public function reference(): string
    {
        return $this->reference;
    }
}
