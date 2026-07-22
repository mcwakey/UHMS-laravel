<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use Illuminate\Database\ConnectionInterface;
use RuntimeException;

/**
 * Fail-closed boundary for installing the Phase 3 foundation schema.
 * Domain data writes are never authorized by this boundary.
 */
final class FoundationDatabaseWriteBoundary
{
    /** @param list<string> $requiredFoundationTables */
    public static function assertSchemaInstallAllowed(ConnectionInterface $connection, array $requiredFoundationTables = []): void
    {
        $driver = $connection->getDriverName();
        if (app()->runningUnitTests() && app()->environment('testing') && $driver === 'sqlite') {
            return;
        }

        // Direct Laravel migration execution cannot preserve one sealed
        // physical-identity proof while MariaDB auto-commit advances through
        // approved partial schema states. Real foundation DDL is therefore
        // permitted only through SafeFoundationDdlInstaller, whose immutable
        // per-object manifest is executed by IdentityBoundDdlGate. This legacy
        // per-migration entry point is permanently denied on MariaDB/MySQL.
        throw new RuntimeException('Direct foundation migration execution is blocked; use the identity-bound immutable DDL installer [LM-SEC-FOUNDATION-MANIFEST-INSTALL-REQUIRED].');
    }

    public static function assertSchemaRemovalAllowed(ConnectionInterface $connection): void
    {
        if (app()->runningUnitTests() && app()->environment('testing') && $connection->getDriverName() === 'sqlite') {
            return;
        }

        throw new RuntimeException('Phase 3 foundation schema removal is not authorized [LM-SEC-FOUNDATION-SCHEMA-REMOVAL-BLOCKED].');
    }
}
