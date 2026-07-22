<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

interface VerifiedRunAuthority
{
    public function reference(): string;
}
