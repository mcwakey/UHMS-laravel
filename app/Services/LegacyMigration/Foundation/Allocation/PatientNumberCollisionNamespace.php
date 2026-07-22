<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

interface PatientNumberCollisionNamespace
{
    public function available(): bool;

    public function collides(string $candidate): bool;
}
