<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final readonly class ConfiguredRecoveryJournalAuthority implements RecoveryJournalAuthority
{
    private function __construct(
        private string $environmentName,
        private string $reference,
        private string $access,
        private string $retention,
        private string $contract,
        private string $transformation,
        private string $canonicalization,
        private string $bundleHash,
    ) {}

    /** @param array<string,mixed> $configuration */
    public static function fromConfiguration(array $configuration, string $environment): self
    {
        $recovery = (array) ($configuration['recovery'] ?? []);
        $protected = (array) ($configuration['protected_store'] ?? []);
        $versions = (array) ($configuration['versions'] ?? []);
        $policy = (array) ($configuration['phase2f_policy'] ?? []);

        if (($recovery['persistent_journal_enabled'] ?? false) !== true
            || ($recovery['authority_verified'] ?? false) !== true
            || ($recovery['compare_and_set_required'] ?? false) !== true
            || ! is_string($recovery['connection'] ?? null)
            || $recovery['connection'] === ''
            || ($protected['population_enabled'] ?? false) !== true
            || ($protected['full_envelope_required'] ?? false) !== true
            || ($protected['keyed_integrity_required'] ?? false) !== true
            || ($protected['purpose_scoped_access_required'] ?? false) !== true
            || ($protected['direct_model_access_allowed'] ?? true) !== false) {
            throw RecoveryException::failClosed('RECOVERY-PROTECTED-AUTHORITY-NOT-ENABLED');
        }

        $values = [
            $environment,
            $recovery['authority_reference'] ?? null,
            $recovery['access_classification'] ?? null,
            $recovery['retention_classification'] ?? null,
            $versions['contract_bundle'] ?? null,
            $recovery['transformation_version'] ?? null,
            $versions['canonicalization'] ?? null,
            $policy['expected_bundle_hash'] ?? null,
            $protected['access_policy_reference'] ?? null,
        ];
        foreach ($values as $value) {
            if (! is_string($value) || trim($value) === '' || strlen($value) > 160) {
                throw RecoveryException::failClosed('RECOVERY-PROTECTED-AUTHORITY-INCOMPLETE');
            }
        }
        if (! hash_equals((string) $recovery['authority_reference'], (string) $protected['access_policy_reference'])) {
            throw RecoveryException::failClosed('RECOVERY-PROTECTED-AUTHORITY-MISMATCH');
        }
        if (preg_match('/\A[0-9a-f]{64}\z/D', (string) $policy['expected_bundle_hash']) !== 1) {
            throw RecoveryException::failClosed('RECOVERY-POLICY-BUNDLE-HASH-INVALID');
        }

        return new self(
            $environment,
            (string) $recovery['authority_reference'],
            (string) $recovery['access_classification'],
            (string) $recovery['retention_classification'],
            (string) $versions['contract_bundle'],
            (string) $recovery['transformation_version'],
            (string) $versions['canonicalization'],
            (string) $policy['expected_bundle_hash'],
        );
    }

    public function environment(): string
    {
        return $this->environmentName;
    }

    public function authorityReference(): string
    {
        return $this->reference;
    }

    public function accessClassification(): string
    {
        return $this->access;
    }

    public function retentionClassification(): string
    {
        return $this->retention;
    }

    public function contractVersion(): string
    {
        return $this->contract;
    }

    public function transformationVersion(): string
    {
        return $this->transformation;
    }

    public function canonicalizationVersion(): string
    {
        return $this->canonicalization;
    }

    public function expectedContractBundleHash(): string
    {
        return $this->bundleHash;
    }
}
