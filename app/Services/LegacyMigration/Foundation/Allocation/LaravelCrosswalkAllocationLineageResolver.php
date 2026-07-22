<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

use App\Models\LegacyMigration\Crosswalk;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryCoordinateHint;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryJournalAttributeFactory;
use App\Services\LegacyMigration\Foundation\Storage\ProtectedRecordSecurityRepository;
use Illuminate\Support\Facades\DB;

final readonly class LaravelCrosswalkAllocationLineageResolver implements AllocationLineageResolver
{
    public function __construct(
        private NumberReservationStore $reservations,
        private ProtectedRecordSecurityRepository $security,
        private RecoveryJournalAttributeFactory $contexts,
        private string $domain = 'patient',
        private ?string $connection = null,
    ) {}

    public function resolveSuccessful(AllocationRequest $request): ?ExistingAllocation
    {
        $connectionName = trim((string) ($this->connection ?? config('database.default')));
        try {
            $this->security->assertConnection($connectionName);
        } catch (\Throwable) {
            throw AllocationException::failClosed('PATIENT-NUM-CROSSWALK-CONNECTION-MISMATCH');
        }
        if (! hash_equals($connectionName, $this->reservations->connectionName())) {
            throw AllocationException::failClosed('PATIENT-NUM-CROSSWALK-CONNECTION-MISMATCH');
        }
        $connection = DB::connection($connectionName);
        if (! $connection->getSchemaBuilder()->hasTable('legacy_migration_crosswalks')) {
            throw AllocationException::failClosed('PATIENT-NUM-CROSSWALK-UNAVAILABLE');
        }

        $mappings = $connection->table('legacy_migration_crosswalks')
            ->where('domain', $this->domain)
            ->where('protected_source_token', $request->protectedSourceToken)
            ->where('is_active', true)
            ->limit(2)
            ->get(['id', 'run_id', 'source_snapshot_id', 'target_snapshot_id']);

        if ($mappings->count() > 1) {
            throw AllocationException::failClosed('PATIENT-NUM-CROSSWALK-CARDINALITY');
        }
        if ($mappings->isEmpty()) {
            return null;
        }
        $hint = $mappings->first();
        $this->security->readProjection(
            $this->contexts->readContext('crosswalk', new RecoveryCoordinateHint(
                (int) $hint->id,
                (int) $hint->run_id,
                $hint->source_snapshot_id === null ? null : (int) $hint->source_snapshot_id,
                $hint->target_snapshot_id === null ? null : (int) $hint->target_snapshot_id,
            )),
            Crosswalk::class,
            (int) $hint->id,
            [
                'run_id' => (int) $hint->run_id,
                'source_snapshot_id' => $hint->source_snapshot_id === null ? null : (int) $hint->source_snapshot_id,
                'target_snapshot_id' => $hint->target_snapshot_id === null ? null : (int) $hint->target_snapshot_id,
                'domain' => $this->domain,
                'protected_source_token' => $request->protectedSourceToken,
                'idempotency_token' => $request->patientCoreKey,
                'is_active' => true,
            ],
        );

        $allocation = $this->reservations->find($request);
        if ($allocation === null) {
            throw AllocationException::failClosed('PATIENT-NUM-CROSSWALK-WITHOUT-ALLOCATION');
        }

        return $allocation;
    }
}
