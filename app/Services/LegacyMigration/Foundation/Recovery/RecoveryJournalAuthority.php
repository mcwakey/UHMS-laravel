<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

interface RecoveryJournalAuthority
{
    public function environment(): string;

    public function authorityReference(): string;

    public function accessClassification(): string;

    public function retentionClassification(): string;

    public function contractVersion(): string;

    public function transformationVersion(): string;

    public function canonicalizationVersion(): string;

    public function expectedContractBundleHash(): string;
}
