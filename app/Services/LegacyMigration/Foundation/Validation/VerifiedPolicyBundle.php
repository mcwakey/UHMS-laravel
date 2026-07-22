<?php

namespace App\Services\LegacyMigration\Foundation\Validation;

final readonly class VerifiedPolicyBundle
{
    /** @param array<string, VerifiedPolicyArtifact> $artifacts */
    public function __construct(
        public string $version,
        public string $bundleHash,
        public array $artifacts,
    ) {}

    public function artifact(string $path): VerifiedPolicyArtifact
    {
        return $this->artifacts[$path]
            ?? throw new ValidationException('FOUNDATION_POLICY_ARTIFACT_UNBOUND', [], 'A required authoritative policy artifact is not bound.');
    }

    public function assertPinned(string $expectedBundleHash): void
    {
        if (preg_match('/\A[a-f0-9]{64}\z/', $expectedBundleHash) !== 1
            || ! hash_equals($this->bundleHash, $expectedBundleHash)) {
            throw new ValidationException('FOUNDATION_POLICY_BUNDLE_DRIFT', [], 'The authoritative policy bundle changed after it was pinned.');
        }
    }
}
