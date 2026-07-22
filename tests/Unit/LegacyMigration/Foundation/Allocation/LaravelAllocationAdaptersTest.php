<?php

namespace Tests\Unit\LegacyMigration\Foundation\Allocation;

use App\Services\LegacyMigration\Foundation\Allocation\AllocationException;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationFaultInjector;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationFaultPoint;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationLineageResolver;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationMode;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationRequest;
use App\Services\LegacyMigration\Foundation\Allocation\CollisionStatus;
use App\Services\LegacyMigration\Foundation\Allocation\DeterministicPatientNumberAllocator;
use App\Services\LegacyMigration\Foundation\Allocation\ExistingAllocation;
use App\Services\LegacyMigration\Foundation\Allocation\FoundationAllocationWriteGuard;
use App\Services\LegacyMigration\Foundation\Allocation\LaravelNumberReservationStore;
use App\Services\LegacyMigration\Foundation\Allocation\LaravelTargetPatientNumberCollisionProbe;
use App\Services\LegacyMigration\Foundation\Allocation\NumberingResetPeriod;
use App\Services\LegacyMigration\Foundation\Allocation\PinnedCollisionEvidence;
use App\Services\LegacyMigration\Foundation\Allocation\PinnedNumberingConfiguration;
use App\Services\LegacyMigration\Foundation\Allocation\ReservationAttributeFactory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LaravelAllocationAdaptersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSyntheticSchema();
    }

    #[Test]
    public function concrete_store_locks_the_coordinate_and_writes_only_sequence_and_foundation_reservation(): void
    {
        $request = $this->request('store');
        DB::table('legacy_migration_idempotency_records')->insert([
            'id' => 1,
            'domain' => 'patient_core',
            'idempotency_token' => $request->patientCoreKey,
        ]);
        $store = new LaravelNumberReservationStore(
            new SyntheticReservationAttributes(1),
            null,
            new SyntheticAllocationWriteGuard,
        );
        $allocator = new DeterministicPatientNumberAllocator(
            new NoPriorAllocation,
            $store,
            new LaravelTargetPatientNumberCollisionProbe(new CurrentCollisionEvidence),
        );

        $first = $allocator->allocate($request);
        $rerun = $allocator->allocate($request);

        self::assertSame('UHMS-000001/2026', $first->number);
        self::assertSame($first->number, $rerun->number);
        self::assertSame(1, DB::table('patient_number_sequences')->value('last_sequence'));
        self::assertSame(1, DB::table('legacy_migration_number_reservations')->count());
        self::assertSame(0, DB::table('patients')->count());
        self::assertNull(DB::table('legacy_migration_number_reservations')->value('created_by_token'));
    }

    #[Test]
    public function dry_run_with_concrete_store_never_creates_a_sequence_coordinate_or_reservation(): void
    {
        $request = $this->request('dry', AllocationMode::DryRun);
        $allocator = new DeterministicPatientNumberAllocator(
            new NoPriorAllocation,
            new LaravelNumberReservationStore(new SyntheticReservationAttributes(1)),
            new LaravelTargetPatientNumberCollisionProbe(new CurrentCollisionEvidence),
        );

        $result = $allocator->allocate($request);

        self::assertFalse($result->binding);
        self::assertSame(0, DB::table('patient_number_sequences')->count());
        self::assertSame(0, DB::table('legacy_migration_number_reservations')->count());
    }

    #[Test]
    public function direct_phase_three_reservation_is_blocked_before_any_target_write(): void
    {
        $request = $this->request('blocked');
        $allocator = new DeterministicPatientNumberAllocator(
            new NoPriorAllocation,
            new LaravelNumberReservationStore(new SyntheticReservationAttributes(1)),
            new LaravelTargetPatientNumberCollisionProbe(new CurrentCollisionEvidence),
        );

        try {
            $allocator->allocate($request);
            self::fail('Phase 3 reservation unexpectedly succeeded.');
        } catch (AllocationException $exception) {
            self::assertStringContainsString('PATIENT-NUM-PHASE3-RESERVATION-BLOCKED', $exception->getMessage());
        }

        self::assertSame(0, DB::table('patient_number_sequences')->count());
        self::assertSame(0, DB::table('legacy_migration_number_reservations')->count());
    }

    #[Test]
    #[DataProvider('transactionFaultPoints')]
    public function reservation_and_sequence_roll_back_together_at_every_transaction_fault_point(AllocationFaultPoint $point): void
    {
        $request = $this->request('fault-'.$point->value);
        DB::table('legacy_migration_idempotency_records')->insert([
            'id' => 1,
            'domain' => 'patient_core',
            'idempotency_token' => $request->patientCoreKey,
        ]);
        $store = new LaravelNumberReservationStore(
            new SyntheticReservationAttributes(1),
            null,
            new SyntheticAllocationWriteGuard,
            new SyntheticAllocationFaultInjector($point),
        );
        $allocator = new DeterministicPatientNumberAllocator(new NoPriorAllocation, $store, new LaravelTargetPatientNumberCollisionProbe(new CurrentCollisionEvidence));

        try {
            $allocator->allocate($request);
            self::fail('The injected transaction fault did not abort allocation.');
        } catch (\RuntimeException $exception) {
            self::assertSame('synthetic allocation interruption', $exception->getMessage());
        }

        self::assertSame(0, DB::table('legacy_migration_number_reservations')->count());
        self::assertSame(0, DB::table('patient_number_sequences')->count());
    }

    /** @return iterable<string, array{AllocationFaultPoint}> */
    public static function transactionFaultPoints(): iterable
    {
        foreach (AllocationFaultPoint::cases() as $point) {
            yield $point->value => [$point];
        }
    }

    #[Test]
    public function collision_probe_reads_all_required_empty_namespaces_without_creating_business_rows_and_fails_closed(): void
    {
        $probe = new LaravelTargetPatientNumberCollisionProbe(new CurrentCollisionEvidence);
        self::assertSame(CollisionStatus::Available, $probe->status('UHMS-000010/2026'));
        self::assertSame(0, DB::table('patients')->count());
        self::assertSame(0, DB::table('archived_patients')->count());
        self::assertSame(0, DB::table('patient_aliases')->count());
        self::assertSame(CollisionStatus::Unknown, (new LaravelTargetPatientNumberCollisionProbe(new StaleCollisionEvidence))->status('UHMS-000011/2026'));

        Schema::drop('patient_aliases');
        self::assertSame(CollisionStatus::Unknown, $probe->status('UHMS-000011/2026'));
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

    private function createSyntheticSchema(): void
    {
        Schema::create('patient_number_sequences', function (Blueprint $table): void {
            $table->id();
            $table->string('prefix');
            $table->string('period_type');
            $table->string('period_key');
            $table->unsignedBigInteger('last_sequence')->default(0);
            $table->timestamps();
            $table->unique(['prefix', 'period_type', 'period_key']);
        });
        Schema::create('legacy_migration_idempotency_records', function (Blueprint $table): void {
            $table->id();
            $table->string('domain');
            $table->char('idempotency_token', 64);
        });
        Schema::create('legacy_migration_number_reservations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('idempotency_record_id')->unique();
            $table->string('domain');
            $table->char('protected_source_token', 64);
            $table->char('numbering_coordinate_token', 64);
            $table->unsignedBigInteger('sequence_ordinal');
            $table->char('protected_number_token', 64)->unique();
            $table->char('configuration_fingerprint', 64);
            $table->string('period_key');
            $table->string('timezone');
            $table->string('state');
            $table->string('consumption_classification');
            $table->string('contract_version');
            $table->string('transformation_version');
            $table->string('canonicalization_version');
            $table->string('hmac_key_version');
            $table->text('encrypted_number_payload');
            $table->text('encrypted_explanation')->nullable();
            $table->char('integrity_checksum', 64);
            $table->string('access_classification');
            $table->string('retention_classification');
            $table->char('created_by_token', 64)->nullable();
            $table->char('updated_by_token', 64)->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->unique(['numbering_coordinate_token', 'sequence_ordinal']);
        });
        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->string('patient_number')->unique();
            $table->softDeletes();
        });
        Schema::create('archived_patients', function (Blueprint $table): void {
            $table->id();
            $table->string('patient_number');
        });
        Schema::create('patient_aliases', function (Blueprint $table): void {
            $table->id();
            $table->string('alias_type');
            $table->string('normalized_alias_value');
        });
    }
}

final readonly class SyntheticReservationAttributes implements ReservationAttributeFactory
{
    public function __construct(private int $idempotencyRecordId) {}

    public function make(AllocationRequest $request, string $number, int $ordinal): array
    {
        return [
            'idempotency_record_id' => $this->idempotencyRecordId,
            'protected_number_token' => hash_hmac('sha256', $number, 'synthetic-test-only-key'),
            'contract_version' => '2F.1.0',
            'transformation_version' => 'allocator-v1',
            'canonicalization_version' => 'typed-v1',
            'hmac_key_version' => 'synthetic-v1',
            'integrity_checksum' => hash('sha256', "reservation-{$ordinal}"),
            'access_classification' => 'migration_restricted',
            'retention_classification' => 'migration_audit',
            'lock_version' => 0,
        ];
    }
}

final class SyntheticAllocationWriteGuard implements FoundationAllocationWriteGuard
{
    public function assertReservationWriteAllowed(string $connection): void
    {
        if ($connection !== 'sqlite') {
            throw new \RuntimeException('Synthetic guard rejected a non-SQLite connection.');
        }
    }
}

final readonly class SyntheticAllocationFaultInjector implements AllocationFaultInjector
{
    public function __construct(private AllocationFaultPoint $faultPoint) {}

    public function inject(AllocationFaultPoint $point): void
    {
        if ($point === $this->faultPoint) {
            throw new \RuntimeException('synthetic allocation interruption');
        }
    }
}

final class NoPriorAllocation implements AllocationLineageResolver
{
    public function resolveSuccessful(AllocationRequest $request): ?ExistingAllocation
    {
        return null;
    }
}

final class CurrentCollisionEvidence implements PinnedCollisionEvidence
{
    public function isCurrent(): bool
    {
        return true;
    }
}

final class StaleCollisionEvidence implements PinnedCollisionEvidence
{
    public function isCurrent(): bool
    {
        return false;
    }
}
