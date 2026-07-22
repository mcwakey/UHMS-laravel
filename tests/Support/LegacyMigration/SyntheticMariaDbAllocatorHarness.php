<?php

namespace Tests\Support\LegacyMigration;

use App\Services\LegacyMigration\Foundation\Allocation\AllocationFaultInjector;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationFaultPoint;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationRequest;
use App\Services\LegacyMigration\Foundation\Allocation\FoundationAllocationWriteGuard;
use App\Services\LegacyMigration\Foundation\Allocation\PatientNumberCollisionNamespace;
use App\Services\LegacyMigration\Foundation\Allocation\PinnedCollisionEvidence;
use App\Services\LegacyMigration\Foundation\Allocation\ReservationAttributeFactory;
use App\Services\LegacyMigration\Foundation\Allocation\ReservationProtectionFactory;
use App\Services\LegacyMigration\Foundation\Allocation\ReservationCoordinateContextFactory;
use App\Models\LegacyMigration\IdempotencyRecord;
use App\Services\LegacyMigration\Foundation\Security\CanonicalizationVersionRegistry;
use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\LegacyMigrationSecurityFactory;
use App\Services\LegacyMigration\Foundation\Security\PinnedProtectedStoreAccessAuthority;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordEnvelopeFactory;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordEnvelopeVerifier;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessSession;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;
use App\Services\LegacyMigration\Foundation\Storage\NumberReservationRepository;
use App\Services\LegacyMigration\Foundation\Storage\ProtectedRecordSecurityRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PDOException;

final readonly class SyntheticMariaDbReservationAttributes implements ReservationAttributeFactory
{
    public function __construct(private int $idempotencyRecordId, private int $runId) {}

    public function make(AllocationRequest $request, string $number, int $ordinal): array
    {
        return [
            'run_id' => $this->runId,
            'idempotency_record_id' => $this->idempotencyRecordId,
            'protected_number_token' => hash_hmac('sha256', $number, 'SYNTHETIC-P3B-MARIADB-ONLY'),
            'contract_version' => 'SYNTHETIC-P3B-ALLOCATOR-1',
            'transformation_version' => 'allocator-v1',
            'canonicalization_version' => 'typed-v1',
            'hmac_key_version' => 'synthetic-v1',
            'integrity_checksum' => hash_hmac('sha256', "reservation|{$ordinal}", 'SYNTHETIC-P3B-MARIADB-ONLY'),
            'access_classification' => 'synthetic_restricted',
            'retention_classification' => 'synthetic_test',
            'lock_version' => 0,
        ];
    }
}

final readonly class SyntheticMariaDbReservationProtection implements ReservationProtectionFactory, ReservationCoordinateContextFactory
{
    public function __construct(
        private int $runId,
        private int $sourceSnapshotId,
        private int $targetSnapshotId,
        private object $tokens,
        private CanonicalTypedMessageEncoder $encoder,
        private ProtectedRecordSecurityRepository $security,
    ) {}

    /** @return array{NumberReservationRepository,self} */
    public static function build(int $runId, int $sourceSnapshotId, int $targetSnapshotId, ?string $repositoryConnection = null): array
    {
        $configuration = [
            'versions' => ['canonicalization' => 'typed-length-prefix/1'],
            'hmac' => [
                'key_id' => 'allocator-key', 'key_version' => 'v1',
                'key' => hash('sha512', 'disposable-mariadb-allocator-key-material'),
                'algorithm' => 'sha256',
                'domains' => ['number_reservation', 'idempotency', 'artifact_integrity'],
            ],
        ];
        $tokens = LegacyMigrationSecurityFactory::make($configuration, 'testing');
        $encoder = new CanonicalTypedMessageEncoder(new CanonicalizationVersionRegistry(['typed-length-prefix/1'], 'typed-length-prefix/1'));
        $authority = new PinnedProtectedStoreAccessAuthority(
            'testing', ['number_reservation', 'idempotency'], 'allocator-key', 'v1', 'typed-length-prefix/1', 'artifact_integrity',
            ['mariadb_allocator_verification' => [
                'operations' => ['read', 'write'],
                'authority_references' => ['SYNTHETIC-MARIADB-ALLOCATOR-AUTHORITY'],
                'access_classifications' => ['synthetic_restricted'],
                'retention_classifications' => ['synthetic_test'],
            ]],
        );
        $guard = new ProtectedStoreAccessGuard($authority, $tokens, $encoder);
        $security = new ProtectedRecordSecurityRepository(
            new ProtectedRecordEnvelopeFactory($guard),
            new ProtectedRecordEnvelopeVerifier($guard),
            connection: 'phase3b_allocator_verification',
        );

        $protection = new self($runId, $sourceSnapshotId, $targetSnapshotId, $tokens, $encoder, $security);

        return [new NumberReservationRepository($security, $protection, $repositoryConnection ?? 'phase3b_allocator_verification'), $protection];
    }

    public function connectionName(): string
    {
        return 'phase3b_allocator_verification';
    }

    public function context(AllocationRequest $request): ProtectedStoreOperationContext
    {
        return new ProtectedStoreOperationContext(
            'mariadb_allocator_verification', ['read', 'write'], ['number_reservation'], 'testing',
            $this->runId, $this->sourceSnapshotId, $this->targetSnapshotId, 'synthetic_restricted', 'synthetic_test',
            'SYNTHETIC-MARIADB-ALLOCATOR-AUTHORITY',
            ['sequence_ordinal', 'configuration_fingerprint', 'period_key'],
        );
    }

    public function tokenSet(AllocationRequest $request, string $number, int $ordinal): array
    {
        return [
            'protected_source_token' => ['encoded_token' => $this->sourceToken($request), 'domain' => 'number_reservation'],
            'numbering_coordinate_token' => ['encoded_token' => $this->token('coordinate|'.$request->configuration->coordinateToken()), 'domain' => 'number_reservation'],
            'protected_number_token' => ['encoded_token' => $this->token('number|'.$number.'|'.$ordinal), 'domain' => 'number_reservation'],
        ];
    }

    public function idempotencyContext(int $idempotencyRecordId, int $runId, int $sourceSnapshotId, int $targetSnapshotId): ProtectedStoreOperationContext
    {
        return new ProtectedStoreOperationContext(
            'mariadb_allocator_verification', ['read'], ['idempotency'], 'testing',
            $runId, $sourceSnapshotId, $targetSnapshotId, 'synthetic_restricted', 'synthetic_test',
            'SYNTHETIC-MARIADB-ALLOCATOR-AUTHORITY',
            ['run_id', 'source_snapshot_id', 'target_snapshot_id', 'domain'],
        );
    }

    public function sealIdempotency(int $recordId, string $protectedSourceDigest, string $idempotencyDigest): void
    {
        $context = $this->idempotencyContext($recordId, $this->runId, $this->sourceSnapshotId, $this->targetSnapshotId);
        $record = ProtectedStoreAccessSession::run(
            $context,
            fn () => ProtectedStoreAccessSession::runEnvelopeLookup(fn () => IdempotencyRecord::on('phase3b_allocator_verification')->findOrFail($recordId)),
        );
        $source = $this->encodedDigestToken('idempotency', $protectedSourceDigest);
        $idempotency = $this->encodedDigestToken('idempotency', $idempotencyDigest);
        $writeContext = new ProtectedStoreOperationContext(
            'mariadb_allocator_verification', ['write'], ['idempotency'], 'testing',
            $this->runId, $this->sourceSnapshotId, $this->targetSnapshotId, 'synthetic_restricted', 'synthetic_test',
            'SYNTHETIC-MARIADB-ALLOCATOR-AUTHORITY',
        );
        ProtectedStoreAccessSession::run($writeContext, fn () => $this->security->seal(
            $writeContext, $record, $idempotency, 'idempotency', $this->runId,
            $this->sourceSnapshotId, $this->targetSnapshotId, 'synthetic_restricted', 'synthetic_test',
            [
                'protected_source_token' => ['encoded_token' => $source, 'domain' => 'idempotency'],
                'idempotency_token' => ['encoded_token' => $idempotency, 'domain' => 'idempotency'],
            ],
        ));
    }

    public function expectedProtectedSourceToken(AllocationRequest $request): string
    {
        return \App\Services\LegacyMigration\Foundation\Security\ProtectedToken::parse($this->sourceToken($request))->lookupDigest();
    }

    private function sourceToken(AllocationRequest $request): string
    {
        return $this->token('source|'.$request->protectedSourceToken);
    }

    private function token(string $label): string
    {
        return $this->tokens->tokenize(
            new TokenDomain('number_reservation'),
            $this->encoder->encode([TypedValue::string('synthetic-mariadb-allocation/v1'), TypedValue::string($label)]),
        )->encode();
    }

    private function encodedDigestToken(string $domain, string $digest): string
    {
        $binary = hex2bin($digest);
        if ($binary === false) {
            throw new \RuntimeException('Synthetic digest is invalid.');
        }

        return (new \App\Services\LegacyMigration\Foundation\Security\ProtectedToken(
            'testing', $domain, 'allocator-key', 'v1', 'typed-length-prefix/1', $binary,
        ))->encode();
    }
}

final readonly class SyntheticMariaDbAllocationWriteGuard implements FoundationAllocationWriteGuard
{
    public function __construct(private string $approvedConnection) {}

    public function assertReservationWriteAllowed(string $connection): void
    {
        if (! hash_equals($this->approvedConnection, $connection)) {
            throw new \RuntimeException('Disposable allocator connection was not approved.');
        }
    }
}

final class SyntheticCurrentCollisionEvidence implements PinnedCollisionEvidence
{
    public function isCurrent(): bool
    {
        return true;
    }
}

final readonly class SyntheticMariaDbCollisionNamespace implements PatientNumberCollisionNamespace
{
    public function __construct(private string $connection) {}

    public function available(): bool
    {
        $schema = DB::connection($this->connection)->getSchemaBuilder();

        return $schema->hasTable('phase3b_allocator_collision_namespace')
            && $schema->hasColumn('phase3b_allocator_collision_namespace', 'candidate');
    }

    public function collides(string $candidate): bool
    {
        return DB::connection($this->connection)->table('phase3b_allocator_collision_namespace')
            ->where('candidate', $candidate)->exists();
    }
}

final readonly class SyntheticMariaDbFaultInjector implements AllocationFaultInjector
{
    public function __construct(private AllocationFaultPoint $faultPoint) {}

    public function inject(AllocationFaultPoint $point): void
    {
        if ($point === $this->faultPoint) {
            throw new \RuntimeException('synthetic MariaDB allocation interruption');
        }
    }
}

final class SyntheticMariaDbDeadlockOnceInjector implements AllocationFaultInjector
{
    private bool $injected = false;

    public function inject(AllocationFaultPoint $point): void
    {
        if (! $this->injected && $point === AllocationFaultPoint::AfterReservationInsertBeforeSequenceCas) {
            $this->injected = true;
            throw new QueryException(
                'phase3b_allocator_verification',
                'synthetic allocator deadlock',
                [],
                new PDOException('Deadlock found when trying to get lock; try restarting transaction', 40001),
            );
        }
    }
}

final readonly class SyntheticMariaDbConnectionLossInjector implements AllocationFaultInjector
{
    public function inject(AllocationFaultPoint $point): void
    {
        if ($point === AllocationFaultPoint::AfterReservationInsertBeforeSequenceCas) {
            throw new QueryException(
                'phase3b_allocator_verification',
                'synthetic allocator connection loss',
                [],
                new PDOException('MySQL server has gone away', 2006),
            );
        }
    }
}
