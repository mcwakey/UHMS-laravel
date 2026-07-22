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
    public static function assertWriteAllowed(Model $model): void
    {
        self::assertConnectionWriteAllowed($model->getConnectionName());
    }

    public static function assertConnectionWriteAllowed(?string $connectionName = null): void
    {
        if (! app()->runningUnitTests()
            || ! app()->environment('testing')
            || DB::connection($connectionName)->getDriverName() !== 'sqlite') {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-PHASE3-WRITE-BLOCKED');
        }
    }

    public static function denyDelete(): void
    {
        throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-PURGE-NOT-AUTHORIZED');
    }
}
