<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\QuarantineRoot;

interface QuarantineReleaseGuard
{
    public function assertReleaseAuthorized(
        QuarantineRoot $root,
        string $approvalToken,
        string $revalidationChecksum,
    ): void;
}
