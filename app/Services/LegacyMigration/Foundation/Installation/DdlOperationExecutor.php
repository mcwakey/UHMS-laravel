<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

use App\Services\LegacyMigration\Foundation\Environment\IdentityBoundDdlGate;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityVerification;

interface DdlOperationExecutor
{
    public function execute(
        string $version,
        string $operationId,
        PhysicalServerIdentityVerification $identity,
        IdentityBoundDdlGate $gate,
        InstallationSessionCapability $session,
    ): void;
}
