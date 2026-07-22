<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

interface MigrationRuntimeAudit
{
    /** Verify the protected sink and durably record the activation request. */
    public function start(MigrationRuntimeRequest $request): MigrationRuntimeAuditSession;

    /** @param array<string,int|string|bool|array> $facts */
    public function record(MigrationRuntimeAuditSession $session, string $event, array $facts = []): void;
}
