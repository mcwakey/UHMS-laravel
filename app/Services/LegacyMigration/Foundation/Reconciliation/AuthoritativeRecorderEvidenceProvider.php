<?php

namespace App\Services\LegacyMigration\Foundation\Reconciliation;

interface AuthoritativeRecorderEvidenceProvider
{
    public function capture(): AuthoritativeRecorderEvidence;
}
