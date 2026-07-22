<?php

namespace Tests\Feature\LegacyMigration\Foundation;

require_once dirname(__DIR__, 3).'/Support/LegacyMigration/SyntheticMariaDbAllocatorHarness.php';

use App\Services\LegacyMigration\Foundation\Allocation\AllocationLineageResolver;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationMode;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationRequest;
use App\Services\LegacyMigration\Foundation\Allocation\DeterministicPatientNumberAllocator;
use App\Services\LegacyMigration\Foundation\Allocation\ExistingAllocation;
use App\Services\LegacyMigration\Foundation\Allocation\LaravelTargetPatientNumberCollisionProbe;
use App\Services\LegacyMigration\Foundation\Allocation\NumberingResetPeriod;
use App\Services\LegacyMigration\Foundation\Allocation\PinnedNumberingConfiguration;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessSession;
use Illuminate\Support\Facades\DB;
use Tests\Support\LegacyMigration\SyntheticCurrentCollisionEvidence;
use Tests\Support\LegacyMigration\SyntheticMariaDbCollisionNamespace;
use Tests\TestCase;

final class MariaDbAllocatorWorkerTest extends TestCase
{
    public function test_worker_allocates_only_in_the_explicit_disposable_verification_context(): void
    {
        $seed = getenv('PHASE3B_ALLOCATOR_WORKER_SEED') ?: '';
        $period = getenv('PHASE3B_ALLOCATOR_WORKER_PERIOD') ?: '2026';
        $startAt = (float) (getenv('PHASE3B_ALLOCATOR_WORKER_START_AT') ?: 0);
        if ($seed === '') {
            $this->markTestSkipped('Allocator worker is invoked only by the disposable MariaDB verification test.');
        }

        MariaDbAllocatorVerificationTest::configureDisposableConnection();
        while (microtime(true) < $startAt) {
            usleep(10_000);
        }

        $connection = 'phase3b_allocator_verification';
        $coreKey = hash_hmac('sha256', "core|{$seed}", 'SYNTHETIC-P3B-MARIADB-ONLY');
        $sourceToken = hash_hmac('sha256', "source|{$seed}", 'SYNTHETIC-P3B-MARIADB-ONLY');
        $id = DB::connection($connection)->table('legacy_migration_idempotency_records')
            ->where('domain', 'patient_core')->where('idempotency_token', $coreKey)->value('id');
        self::assertNotNull($id);

        $store = MariaDbAllocatorVerificationTest::storeFor($seed);
        $request = new AllocationRequest(
            $sourceToken,
            $coreKey,
            new PinnedNumberingConfiguration('UHMS', '{PREFIX}-{SEQUENCE}/{YEAR}', 8, NumberingResetPeriod::Yearly, 'UTC', $period),
            AllocationMode::Commit,
        );

        $result = ProtectedStoreAccessSession::run(
            MariaDbAllocatorVerificationTest::verificationContext(),
            fn () => (new DeterministicPatientNumberAllocator(
                new WorkerNoPriorAllocation,
                $store,
                new LaravelTargetPatientNumberCollisionProbe(
                    new SyntheticCurrentCollisionEvidence,
                    namespace: new SyntheticMariaDbCollisionNamespace($connection),
                ),
            ))->allocate($request),
        );

        self::assertTrue($result->binding);
        self::assertGreaterThan(0, $result->sequenceOrdinal);
    }
}

final class WorkerNoPriorAllocation implements AllocationLineageResolver
{
    public function resolveSuccessful(AllocationRequest $request): ?ExistingAllocation
    {
        return null;
    }
}
