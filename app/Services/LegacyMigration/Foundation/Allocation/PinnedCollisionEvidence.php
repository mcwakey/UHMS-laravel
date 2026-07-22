<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

interface PinnedCollisionEvidence
{
    /** Verify the pinned target snapshot/configuration still matches now. */
    public function isCurrent(): bool;
}
