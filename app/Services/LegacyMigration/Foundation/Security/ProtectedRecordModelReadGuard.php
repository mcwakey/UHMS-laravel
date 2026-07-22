<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use App\Models\LegacyMigration\ProtectedAccessAudit;
use App\Models\LegacyMigration\ProtectedFoundationModel;
use App\Models\LegacyMigration\ProtectedKeyReference;
use App\Models\LegacyMigration\ProtectedPurgeRequest;
use App\Models\LegacyMigration\ProtectedRecordEnvelopeRecord;
use App\Models\LegacyMigration\ProtectedRetentionPolicy;
use App\Models\LegacyMigration\ProtectedTokenRotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ProtectedRecordModelReadGuard
{
    public static function assertReadAllowed(ProtectedFoundationModel $model): void
    {
        if ($model instanceof ProtectedRecordEnvelopeRecord) {
            $model->markProtectedEnvelopeBound();
            if (! ProtectedStoreAccessSession::envelopeLookupAllowed()) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-ENVELOPE-DIRECT-READ-001');
            }

            return;
        }
        if (in_array($model::class, [
            ProtectedAccessAudit::class,
            ProtectedKeyReference::class,
            ProtectedPurgeRequest::class,
            ProtectedRetentionPolicy::class,
            ProtectedTokenRotation::class,
        ], true)) {
            $model->markProtectedEnvelopeBound();
            if (! ProtectedStoreAccessSession::envelopeLookupAllowed()) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-CONTROL-DIRECT-READ-001');
            }

            return;
        }
        $connection = $model->getConnectionName();
        if (! Schema::connection($connection)->hasTable('legacy_migration_protected_record_envelopes')) {
            return;
        }
        $bound = DB::connection($connection)->table('legacy_migration_protected_record_envelopes')
            ->where('record_type', $model::class)
            ->where('record_id', $model->getKey())
            ->exists();
        if (! $bound) {
            return;
        }

        $model->markProtectedEnvelopeBound();
        if (! ProtectedStoreAccessSession::isVerified($model::class, (int) $model->getKey())) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-DIRECT-MODEL-READ-001');
        }
    }
}
