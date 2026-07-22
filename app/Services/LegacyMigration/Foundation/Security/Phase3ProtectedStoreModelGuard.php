<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Phase 3 permits protected-store writes only inside isolated SQLite tests.
 * A later reviewed runtime must replace this hard block with a pinned
 * ProtectedStoreAccessGuard authority before any non-test store operation.
 */
final class Phase3ProtectedStoreModelGuard
{
    /** Legacy repository methods may create unsealed rows only in isolated SQLite tests. */
    public static function assertLegacyUnsealedMethodAllowed(?string $connectionName = null): void
    {
        $connection = DB::connection($connectionName);
        if (app()->runningUnitTests() && app()->environment('testing') && $connection->getDriverName() === 'sqlite') {
            return;
        }

        throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-LEGACY-UNSEALED-METHOD-001');
    }

    public static function assertLegacyMutationAllowed(Model $model): void
    {
        if ($model->getKey() !== null
            && ProtectedStoreAccessSession::isVerified($model::class, (int) $model->getKey())) {
            return;
        }

        self::assertLegacyUnsealedMethodAllowed($model->getConnectionName());
    }

    public static function assertWriteAllowed(Model $model): void
    {
        self::assertConnectionWriteAllowed($model->getConnectionName());
    }

    public static function assertConnectionWriteAllowed(?string $connectionName = null): void
    {
        $connection = DB::connection($connectionName);
        if (app()->runningUnitTests() && app()->environment('testing') && $connection->getDriverName() === 'sqlite') {
            return;
        }

        $context = ProtectedStoreAccessSession::current();
        $disposable = (array) config('legacy-migration.disposable_verification', []);
        $approvedDisposable = app()->runningUnitTests()
            && app()->environment('testing')
            && in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)
            && ($disposable['enabled'] ?? false) === true
            && ($disposable['identity_verified'] ?? false) === true
            && is_string($disposable['environment_reference'] ?? null)
            && $disposable['environment_reference'] !== ''
            && hash_equals((string) ($disposable['connection'] ?? ''), $connection->getName())
            && hash_equals((string) ($disposable['database'] ?? ''), (string) $connection->getDatabaseName())
            && ! hash_equals((string) config('legacy-migration.source.connection'), $connection->getName())
            && ! hash_equals((string) config('legacy-migration.target.connection'), $connection->getName())
            && $context !== null
            && $context->purpose() === 'mariadb_allocator_verification';
        if ($approvedDisposable) {
            return;
        }

        throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-PHASE3-WRITE-BLOCKED');
    }

    public static function denyDelete(): void
    {
        throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-PURGE-NOT-AUTHORIZED');
    }
}
