<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

use App\Services\LegacyMigration\Foundation\Environment\IdentityBoundDdlGate;
use App\Services\LegacyMigration\Foundation\Environment\IdentityReferenceHasher;
use App\Services\LegacyMigration\Foundation\Environment\LaravelPhysicalServerIdentityObserver;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityVerification;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityVerifier;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;

final readonly class MariaDbFoundationDdlOperationExecutor implements DdlOperationExecutor
{
    public function __construct(
        private ConnectionInterface $connection,
        private MariaDbFoundationDdlManifestContract $manifest,
        private IdentityReferenceHasher $identityHasher,
    ) {}

    public function execute(
        string $version,
        string $operationId,
        PhysicalServerIdentityVerification $identity,
        IdentityBoundDdlGate $gate,
        InstallationSessionCapability $session,
    ): void {
        $gate->assertVerified($identity);
        $session->assertAuthentic($version, $identity, $this->identityHasher);
        if (! hash_equals(
            $this->manifest->payloadHash(),
            $session->installationIdentity->manifestPayloadHashes[$version] ?? '',
        )) {
            throw new RuntimeException('Foundation DDL manifest is not bound to the installation identity.');
        }
        $observed = (new LaravelPhysicalServerIdentityObserver(
            $this->connection->getName(),
            $this->connection,
            $this->identityHasher,
        ))->observe();
        $observedPhysicalReference = (new PhysicalServerIdentityVerifier($this->identityHasher))
            ->observationReference($observed);
        if (! hash_equals($identity->connectionInstanceReference, $observed->connectionInstanceReference)
            || ! hash_equals($identity->observedPhysicalReference, $observedPhysicalReference)) {
            throw new RuntimeException('Foundation DDL target connection identity changed.');
        }
        if ($version !== $this->manifest->version()
            || ! in_array($this->connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Foundation DDL execution version or driver is not approved.');
        }
        $operation = $this->manifest->operation($operationId);
        if ($operation->type === DdlObjectType::MigrationLedger) {
            $this->appendLedger($operation->migration);

            return;
        }
        $this->connection->unprepared(self::approvedCreateSql($operation->sql));
    }

    public static function approvedCreateSql(?string $sql): string
    {
        if ($sql === null || ! preg_match('/\A(CREATE\s+(TABLE|TRIGGER)|ALTER\s+TABLE\s+`legacy_migration_[a-z0-9_]+`\s+ADD\s+)/i', ltrim($sql))) {
            throw new RuntimeException('Foundation DDL operation is not an approved create operation.');
        }

        return (string) preg_replace('/\s*AUTO_INCREMENT=\d+\b/i', '', $sql);
    }

    private function appendLedger(string $migration): void
    {
        $this->connection->transaction(function () use ($migration): void {
            $query = $this->connection->table('migrations');
            $maximum = (int) $query->lockForUpdate()->max('batch');
            if (! $this->connection->table('migrations')->where('migration', $migration)->exists()) {
                $this->connection->table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => $maximum + 1,
                ]);
            }
        });
    }
}
