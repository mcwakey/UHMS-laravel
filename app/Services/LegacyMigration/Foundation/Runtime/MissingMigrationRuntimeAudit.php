<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final class MissingMigrationRuntimeAudit implements MigrationRuntimeAudit
{
    public function start(MigrationRuntimeRequest $request): MigrationRuntimeAuditSession
    {
        throw new RuntimeIsolationException('FOUNDATION_RUNTIME_AUDIT_MISSING', 'A protected migration runtime-audit sink is required.');
    }

    public function record(MigrationRuntimeAuditSession $session, string $event, array $facts = []): void
    {
        throw new RuntimeIsolationException('FOUNDATION_RUNTIME_AUDIT_MISSING', 'A protected migration runtime-audit sink is required.');
    }
}
