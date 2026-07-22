<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

interface PatientNumberCollisionProbe
{
    /** Covers live, soft-deleted and merged patients, archives and aliases. */
    public function status(string $candidate): CollisionStatus;
}
