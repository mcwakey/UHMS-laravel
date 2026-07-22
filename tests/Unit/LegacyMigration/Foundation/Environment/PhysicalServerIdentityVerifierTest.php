<?php

namespace Tests\Unit\LegacyMigration\Foundation\Environment;

use App\Services\LegacyMigration\Foundation\Environment\FoundationGuardException;
use App\Services\LegacyMigration\Foundation\Environment\HmacIdentityReferenceHasher;
use App\Services\LegacyMigration\Foundation\Environment\IdentityBoundDdlGate;
use App\Services\LegacyMigration\Foundation\Environment\ObservedPhysicalServerIdentity;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityObserver;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityVerifier;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalTargetIdentityContract;
use App\Services\LegacyMigration\Foundation\Environment\SchemaObservation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhysicalServerIdentityVerifierTest extends TestCase
{
    private HmacIdentityReferenceHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new HmacIdentityReferenceHasher(
            'synthetic-disposable', 'identity-key', 'v1', str_repeat('s', 64)
        );
    }

    public function test_correct_approved_non_production_identity_passes_and_report_is_redacted(): void
    {
        $verification = $this->verify($this->observed());

        $report = $verification->redactedReport();
        $this->assertSame('verified', $report['status']);
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $report['approved_identity_reference']);
        $this->assertStringNotContainsString('synthetic-db-host', json_encode($report, JSON_THROW_ON_ERROR));
        $this->assertSame('ddl-ran', (new IdentityBoundDdlGate($this->hasher))->execute($verification, fn (): string => 'ddl-ran'));
    }

    /** @return array<string, array{string, mixed}> */
    public static function mismatches(): array
    {
        return [
            'wrong host' => ['hostIdentityReference', hash('sha256', 'wrong-host')],
            'wrong port' => ['port', 4407],
            'wrong server uuid-equivalent' => ['serverIdentityReference', hash('sha256', 'wrong-server')],
            'same schema on unapproved network' => ['networkEnvironmentIdentityReference', hash('sha256', 'wrong-network')],
            'production role under local labels' => ['serverRoleClassification', 'production'],
        ];
    }

    #[DataProvider('mismatches')]
    public function test_physical_or_environment_mismatch_fails_closed(string $property, mixed $value): void
    {
        $observed = $this->observed([$property => $value]);

        try {
            $this->verify($observed);
            $this->fail('Unapproved physical identity passed.');
        } catch (FoundationGuardException $exception) {
            $this->assertSame('FOUNDATION_PHYSICAL_TARGET_IDENTITY_MISMATCH', $exception->faultCode);
            $this->assertStringNotContainsString('synthetic-db-host', $exception->getMessage());
            $this->assertStringNotContainsString('secret', $exception->getMessage());
        }
    }

    public function test_missing_tls_identity_fails_when_required(): void
    {
        $this->expectException(FoundationGuardException::class);
        $this->verify($this->observed(['tlsPeerIdentityReference' => null]));
    }

    public function test_ddl_callback_does_not_begin_before_identity_verification(): void
    {
        $began = false;
        try {
            $verification = $this->verify($this->observed(['hostIdentityReference' => hash('sha256', 'clone')]));
            (new IdentityBoundDdlGate($this->hasher))->execute($verification, function () use (&$began): void {
                $began = true;
            });
        } catch (FoundationGuardException) {
            // Expected fail-closed identity rejection.
        }

        $this->assertFalse($began);
    }

    public function test_identity_proof_cannot_be_replayed_under_another_key_context(): void
    {
        $verification = $this->verify($this->observed());
        $otherHasher = new HmacIdentityReferenceHasher(
            'synthetic-disposable', 'other-identity-key', 'v2', str_repeat('x', 64)
        );

        $this->expectException(FoundationGuardException::class);
        (new IdentityBoundDdlGate($otherHasher))->execute($verification, fn (): string => 'must-not-run');
    }

    public function test_missing_physical_identity_configuration_fails_closed(): void
    {
        $this->expectException(FoundationGuardException::class);
        PhysicalTargetIdentityContract::fromArray([
            'contract_version' => 'P3B-IDENTITY-1',
            'tls_required' => true,
            'tls_peer_identity_required' => true,
        ]);
    }

    public function test_identity_reference_hasher_does_not_expose_runtime_key_material(): void
    {
        $dump = print_r($this->hasher, true);

        $this->assertStringContainsString('[REDACTED_EXTERNAL_KEY_MATERIAL]', $dump);
        $this->assertStringNotContainsString(str_repeat('s', 64), $dump);
    }

    private function verify(ObservedPhysicalServerIdentity $observed): object
    {
        $observer = new class($observed) implements PhysicalServerIdentityObserver
        {
            public function __construct(private readonly ObservedPhysicalServerIdentity $identity) {}

            public function observe(): ObservedPhysicalServerIdentity
            {
                return $this->identity;
            }
        };

        return (new PhysicalServerIdentityVerifier($this->hasher))->verify(
            $this->contract(),
            $observer,
            new SchemaObservation('mysql', 'synthetic_foundation', '10.4.32-MariaDB', hash('sha256', 'schema'), 42, 400),
            hash('sha256', 'foundation-coordinate'),
            hash('sha256', 'configuration'),
        );
    }

    /** @param array<string, mixed> $replace */
    private function observed(array $replace = []): ObservedPhysicalServerIdentity
    {
        $values = array_replace([
            'connection' => 'mysql',
            'database' => 'synthetic_foundation',
            'driver' => 'mariadb',
            'databaseVersion' => '10.4.32-MariaDB',
            'hostIdentityReference' => $this->hasher->reference('server_hostname', 'synthetic-db-host'),
            'port' => 4406,
            'tlsActive' => true,
            'tlsCipherReference' => $this->hasher->reference('tls_cipher', 'SYNTHETIC-CIPHER'),
            'tlsPeerIdentityReference' => $this->hasher->reference('tls_peer_identity', 'SYNTHETIC-PEER'),
            'serverIdentityReference' => $this->hasher->reference('server_equivalent_identity', 'synthetic-db-host|4406|991|10.4.32-MariaDB'),
            'serverRoleClassification' => 'disposable_non_production',
            'networkEnvironmentIdentityReference' => $this->hasher->reference('network_environment_identity', 'synthetic-endpoint|PHASE3B-DISPOSABLE'),
            'environmentAttestationVersion' => 'P3B-IDENTITY-1',
        ], $replace);

        return new ObservedPhysicalServerIdentity(...$values);
    }

    private function contract(): PhysicalTargetIdentityContract
    {
        $observed = $this->observed();

        return new PhysicalTargetIdentityContract(
            contractVersion: 'P3B-IDENTITY-1',
            connection: $observed->connection,
            database: $observed->database,
            driver: $observed->driver,
            databaseVersion: $observed->databaseVersion,
            hostIdentityReference: $observed->hostIdentityReference,
            port: $observed->port,
            tlsRequired: true,
            tlsCipherReference: $observed->tlsCipherReference,
            tlsPeerIdentityRequired: true,
            tlsPeerIdentityReference: $observed->tlsPeerIdentityReference,
            serverIdentityReference: $observed->serverIdentityReference,
            serverRoleClassification: $observed->serverRoleClassification,
            networkEnvironmentIdentityReference: $observed->networkEnvironmentIdentityReference,
            environmentAttestationVersion: $observed->environmentAttestationVersion,
            structuralSchemaFingerprint: hash('sha256', 'schema'),
            tableCount: 42,
            columnCount: 400,
            foundationSchemaCoordinate: hash('sha256', 'foundation-coordinate'),
            configurationFingerprint: hash('sha256', 'configuration'),
            ownerApprovalReference: 'OWNER-DIRECTIVE-2026-07-21',
        );
    }
}
