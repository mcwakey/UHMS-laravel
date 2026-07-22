<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\QuarantineRoot;

final class Phase3QuarantineReleaseGuard implements QuarantineReleaseGuard
{
    public function assertReleaseAuthorized(
        QuarantineRoot $root,
        string $approvalToken,
        string $revalidationChecksum,
    ): void {
        throw new StorageIntegrityException('Phase 3 does not authorize quarantine release.');
    }
}
