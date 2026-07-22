<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

final readonly class FoundationPreflightResult
{
    public function __construct(
        public SchemaObservation $source,
        public SchemaObservation $target,
        public SourceAccountVerification $sourceAccount,
        public string $configurationFingerprint,
    ) {}

    /** @return array<string, mixed> */
    public function safeReport(): array
    {
        return [
            'status' => 'passed',
            'mode' => 'read_only',
            'source' => $this->source->safeSummary(),
            'target' => $this->target->safeSummary(),
            'source_account' => $this->sourceAccount->safeReport(),
            'configuration_fingerprint' => $this->configurationFingerprint,
            'business_rows_written' => 0,
        ];
    }
}
