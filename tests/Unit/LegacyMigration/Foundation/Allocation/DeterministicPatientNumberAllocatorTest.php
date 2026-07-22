<?php

namespace Tests\Unit\LegacyMigration\Foundation\Allocation;

use App\Services\LegacyMigration\Foundation\Allocation\AllocationException;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationLineageResolver;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationMode;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationRequest;
use App\Services\LegacyMigration\Foundation\Allocation\CollisionStatus;
use App\Services\LegacyMigration\Foundation\Allocation\DeterministicPatientNumberAllocator;
use App\Services\LegacyMigration\Foundation\Allocation\ExistingAllocation;
use App\Services\LegacyMigration\Foundation\Allocation\NumberingResetPeriod;
use App\Services\LegacyMigration\Foundation\Allocation\NumberReservationStore;
use App\Services\LegacyMigration\Foundation\Allocation\PatientNumberCollisionProbe;
use App\Services\LegacyMigration\Foundation\Allocation\PinnedNumberingConfiguration;
use Closure;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DeterministicPatientNumberAllocatorTest extends TestCase
{
    #[Test]
    public function dry_run_is_symbolic_and_touches_neither_lock_nor_sequence(): void
    {
        [$allocator, $store] = $this->allocator();

        $result = $allocator->allocate($this->request('a', AllocationMode::DryRun));

        self::assertSame('TARGET_GENERATED_AT_COMMIT', $result->action);
        self::assertFalse($result->binding);
        self::assertFalse($result->sequenceMutated);
        self::assertNull($result->number);
        self::assertSame(0, $store->lockCount);
        self::assertSame(0, $store->sequence);
        self::assertSame([], $store->allocations);
    }

    #[Test]
    public function successful_crosswalk_is_resolved_before_the_reservation_store(): void
    {
        $request = $this->request('b');
        $prior = $this->existing($request, 'UHMS-000041/2026', 41);
        $lineage = new FakeLineageResolver($prior);
        $store = new InMemoryReservationStore;
        $allocator = new DeterministicPatientNumberAllocator($lineage, $store, new FakeCollisionProbe);

        $result = $allocator->allocate($request);

        self::assertSame('REUSE_VERIFIED_PRIOR_MIGRATION_ALLOCATION', $result->action);
        self::assertSame('UHMS-000041/2026', $result->number);
        self::assertSame(0, $store->findCount);
        self::assertSame(0, $store->lockCount);
    }

    #[Test]
    public function locked_allocations_are_unique_and_rerun_never_allocates_a_replacement(): void
    {
        [$allocator, $store] = $this->allocator();

        $first = $allocator->allocate($this->request('c'));
        $second = $allocator->allocate($this->request('d'));
        $rerun = $allocator->allocate($this->request('c'));

        self::assertSame('UHMS-000001/2026', $first->number);
        self::assertSame('UHMS-000002/2026', $second->number);
        self::assertSame($first->number, $rerun->number);
        self::assertFalse($rerun->sequenceMutated);
        self::assertSame(2, $store->sequence);
        self::assertSame(2, $store->lockCount);
    }

    #[Test]
    public function collision_fails_closed_without_persisting_or_decorating_the_number(): void
    {
        $store = new InMemoryReservationStore;
        $allocator = new DeterministicPatientNumberAllocator(new FakeLineageResolver, $store, new FakeCollisionProbe(CollisionStatus::Collision));

        $this->expectException(AllocationException::class);
        try {
            $allocator->allocate($this->request('e'));
        } finally {
            self::assertSame(0, $store->sequence);
            self::assertSame([], $store->allocations);
        }
    }

    #[Test]
    public function transaction_failure_releases_the_ordinal_and_retry_is_deterministic(): void
    {
        $store = new InMemoryReservationStore;
        $store->failAfterPersist = true;
        $allocator = new DeterministicPatientNumberAllocator(new FakeLineageResolver, $store, new FakeCollisionProbe);

        try {
            $allocator->allocate($this->request('f'));
            self::fail('Synthetic transaction failure was expected.');
        } catch (\RuntimeException) {
            self::assertSame(0, $store->sequence);
            self::assertSame([], $store->allocations);
        }

        $store->failAfterPersist = false;
        $result = $allocator->allocate($this->request('f'));
        self::assertSame('UHMS-000001/2026', $result->number);
        self::assertSame(1, $store->sequence);
    }

    /** @return array{DeterministicPatientNumberAllocator, InMemoryReservationStore} */
    private function allocator(): array
    {
        $store = new InMemoryReservationStore;

        return [new DeterministicPatientNumberAllocator(new FakeLineageResolver, $store, new FakeCollisionProbe), $store];
    }

    private function request(string $seed, AllocationMode $mode = AllocationMode::Commit): AllocationRequest
    {
        return new AllocationRequest(
            hash('sha256', "source-{$seed}"),
            hash('sha256', "core-{$seed}"),
            new PinnedNumberingConfiguration('UHMS', '{PREFIX}-{SEQUENCE}/{YEAR}', 6, NumberingResetPeriod::Yearly, 'UTC', '2026'),
            $mode,
        );
    }

    private function existing(AllocationRequest $request, string $number, int $ordinal): ExistingAllocation
    {
        return new ExistingAllocation($request->protectedSourceToken, $request->patientCoreKey, $request->configuration->fingerprint(), $request->configuration->periodKey, $number, $ordinal, true);
    }
}

final class FakeLineageResolver implements AllocationLineageResolver
{
    public function __construct(private readonly ?ExistingAllocation $existing = null) {}

    public function resolveSuccessful(AllocationRequest $request): ?ExistingAllocation
    {
        return $this->existing;
    }
}

final class FakeCollisionProbe implements PatientNumberCollisionProbe
{
    public function __construct(private readonly CollisionStatus $result = CollisionStatus::Available) {}

    public function status(string $candidate): CollisionStatus
    {
        return $this->result;
    }
}

final class InMemoryReservationStore implements NumberReservationStore
{
    public function connectionName(): string
    {
        return 'in_memory';
    }

    /** @var array<string, ExistingAllocation> */
    public array $allocations = [];

    public int $sequence = 0;

    public int $lockCount = 0;

    public int $findCount = 0;

    public bool $failAfterPersist = false;

    private bool $locked = false;

    public function find(AllocationRequest $request): ?ExistingAllocation
    {
        $this->findCount++;

        return $this->allocations[$request->patientCoreKey] ?? null;
    }

    public function withLockedCoordinate(PinnedNumberingConfiguration $configuration, Closure $operation): mixed
    {
        if ($this->locked) {
            throw new \RuntimeException('Coordinate critical sections may not overlap.');
        }
        $this->locked = true;
        $this->lockCount++;
        $sequence = $this->sequence;
        $allocations = $this->allocations;
        try {
            return $operation();
        } catch (\Throwable $error) {
            $this->sequence = $sequence;
            $this->allocations = $allocations;
            throw $error;
        } finally {
            $this->locked = false;
        }
    }

    public function nextOrdinal(PinnedNumberingConfiguration $configuration): int
    {
        if (! $this->locked) {
            throw new \RuntimeException('Sequence read outside lock.');
        }

        return $this->sequence + 1;
    }

    public function persist(AllocationRequest $request, string $number, int $ordinal): ExistingAllocation
    {
        if (! $this->locked) {
            throw new \RuntimeException('Reservation write outside lock.');
        }
        $this->sequence = $ordinal;
        $allocation = new ExistingAllocation($request->protectedSourceToken, $request->patientCoreKey, $request->configuration->fingerprint(), $request->configuration->periodKey, $number, $ordinal, true);
        $this->allocations[$request->patientCoreKey] = $allocation;
        if ($this->failAfterPersist) {
            throw new \RuntimeException('synthetic rollback');
        }

        return $allocation;
    }
}
