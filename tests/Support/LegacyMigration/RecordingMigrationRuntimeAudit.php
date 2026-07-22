<?php

namespace Tests\Support\LegacyMigration;

use App\Services\LegacyMigration\Foundation\Runtime\MigrationRuntimeAudit;
use App\Services\LegacyMigration\Foundation\Runtime\MigrationRuntimeAuditSession;
use App\Services\LegacyMigration\Foundation\Runtime\MigrationRuntimeRequest;
use App\Services\LegacyMigration\Foundation\Runtime\RuntimeIsolationException;

final class RecordingMigrationRuntimeAudit implements MigrationRuntimeAudit
{
    /** @var list<array{event:string,facts:array}> */
    public array $events = [];

    public bool $available = true;

    public function start(MigrationRuntimeRequest $request): MigrationRuntimeAuditSession
    {
        if (! $this->available) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_AUDIT_UNAVAILABLE', 'The synthetic protected runtime audit is unavailable.');
        }
        $session = new MigrationRuntimeAuditSession(1, 1, $request->runToken(), $request->targetSnapshotId(), 'phase-2f/2F.1.0', 'protected', 'migration-lineage');
        $this->record($session, 'ACTIVATION_REQUESTED', ['mode' => $request->mode()->value]);

        return $session;
    }

    public function record(MigrationRuntimeAuditSession $session, string $event, array $facts = []): void
    {
        if (! $this->available) {
            throw new RuntimeIsolationException('FOUNDATION_RUNTIME_AUDIT_UNAVAILABLE', 'The synthetic protected runtime audit is unavailable.');
        }
        $this->events[] = ['event' => $event, 'facts' => $facts];
    }
}
