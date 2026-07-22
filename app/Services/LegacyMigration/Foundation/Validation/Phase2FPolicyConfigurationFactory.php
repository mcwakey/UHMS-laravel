<?php

namespace App\Services\LegacyMigration\Foundation\Validation;

use JsonException;

final class Phase2FPolicyConfigurationFactory
{
    public function load(string $repositoryRoot, array $configuration): VerifiedPolicyBundle
    {
        $reference = $configuration['artifact_manifest'] ?? null;
        if (! is_string($reference) || trim($reference) === '' || ! $this->isAbsolute($reference)) {
            throw new ValidationException('FOUNDATION_POLICY_MANIFEST_REFERENCE_INVALID', [], 'The external Phase 2F policy manifest reference is unavailable.');
        }
        $manifestPath = realpath($reference);
        $root = realpath($repositoryRoot);
        if ($manifestPath === false || $root === false || ! is_file($manifestPath)
            || str_starts_with(strtolower($manifestPath), strtolower($root.DIRECTORY_SEPARATOR))) {
            throw new ValidationException('FOUNDATION_POLICY_MANIFEST_REFERENCE_INVALID', [], 'The external Phase 2F policy manifest reference is unavailable.');
        }
        $bytes = file_get_contents($manifestPath);
        try {
            $manifest = is_string($bytes) ? json_decode($bytes, true, 512, JSON_THROW_ON_ERROR) : null;
        } catch (JsonException) {
            $manifest = null;
        }
        if (! is_array($manifest)
            || ($manifest['manifest_version'] ?? null) !== 'phase2f-policy-manifest/1'
            || ! is_array($manifest['artifacts'] ?? null)
            || preg_match('/\A[a-f0-9]{64}\z/', (string) ($manifest['expected_bundle_hash'] ?? '')) !== 1
            || ! is_string($manifest['approval_reference'] ?? null)
            || trim($manifest['approval_reference']) === '') {
            throw new ValidationException('FOUNDATION_POLICY_MANIFEST_INVALID', [], 'The external Phase 2F policy manifest is invalid.');
        }
        $configuredHash = strtolower((string) ($configuration['expected_bundle_hash'] ?? ''));
        $configuredApproval = trim((string) ($configuration['approval_reference'] ?? ''));
        if (preg_match('/\A[a-f0-9]{64}\z/', $configuredHash) !== 1
            || ! hash_equals($configuredHash, $manifest['expected_bundle_hash'])
            || $configuredApproval === ''
            || ! hash_equals($configuredApproval, trim($manifest['approval_reference']))) {
            throw new ValidationException('FOUNDATION_POLICY_MANIFEST_AUTHORITY_INVALID', [], 'The external Phase 2F policy manifest authority does not match deployment configuration.');
        }

        $bundle = (new AuthoritativePolicyBundleLoader)->load($repositoryRoot, $manifest['artifacts']);
        $bundle->assertPinned($configuredHash);

        return $bundle;
    }

    private function isAbsolute(string $path): bool
    {
        return preg_match('/\A(?:[A-Za-z]:[\\\\\/]|\/)/', $path) === 1;
    }
}
