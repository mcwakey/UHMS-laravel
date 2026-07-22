<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Validation\VerifiedPolicyBundle;

final readonly class VerifiedCohortAuthority implements VerifiedRunAuthority
{
    private function __construct(private string $reference) {}

    public static function fromPolicyBundle(VerifiedPolicyBundle $bundle): self
    {
        $artifact = $bundle->artifact('docs/legacy-migration/phase-2f/specifications/patient_pilot_cohort_rules.json');

        return new self((new CanonicalManifestHasher)->hash([
            'authority' => 'phase2f_cohort',
            'bundle_hash' => $bundle->bundleHash,
            'artifact_hash' => $artifact->sha256,
            'approval_reference' => $artifact->approvalReference,
        ]));
    }

    public function reference(): string
    {
        return $this->reference;
    }
}
