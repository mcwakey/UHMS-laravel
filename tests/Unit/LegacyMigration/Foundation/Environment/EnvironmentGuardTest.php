<?php

namespace Tests\Unit\LegacyMigration\Foundation\Environment;

use App\Services\LegacyMigration\Foundation\Environment\EnvironmentGuard;
use App\Services\LegacyMigration\Foundation\Environment\FoundationGuardException;
use App\Services\LegacyMigration\Foundation\Environment\GuardConfiguration;
use App\Services\LegacyMigration\Foundation\Environment\SchemaObservation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnvironmentGuardTest extends TestCase
{
    #[Test]
    public function exact_approved_coordinates_pass(): void
    {
        $configuration = GuardConfiguration::fromArray($this->configuration(), 'testing');
        $guard = new EnvironmentGuard;

        $guard->assertRuntime('testing', $configuration);
        $guard->assertSource($configuration, new SchemaObservation(
            'legacy_uhms', 'uuhms', '10.4.32-MariaDB', GuardConfiguration::SOURCE_FINGERPRINT, 55, 479,
        ));
        $guard->assertTarget($configuration, new SchemaObservation(
            'mysql', 'uhms_clean', '10.4.32-MariaDB', str_repeat('b', 64), 335, 5347,
        ));

        $this->addToAssertionCount(1);
    }

    #[Test]
    #[DataProvider('unsafeRuntimeProvider')]
    public function unsafe_runtime_configuration_fails_closed(array $changes, string $faultCode): void
    {
        $configuration = $this->configuration();
        foreach ($changes as $path => $value) {
            $segments = explode('.', $path);
            $cursor =& $configuration;
            foreach ($segments as $segment) {
                $cursor =& $cursor[$segment];
            }
            $cursor = $value;
            unset($cursor);
        }

        try {
            $guardConfiguration = GuardConfiguration::fromArray($configuration, 'testing');
            (new EnvironmentGuard)->assertRuntime($changes['environment'] ?? 'testing', $guardConfiguration);
            $this->fail('Expected fail-closed guard exception.');
        } catch (FoundationGuardException $exception) {
            $this->assertSame($faultCode, $exception->faultCode);
            $this->assertStringNotContainsString('mysql://', $exception->getMessage());
        }
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function unsafeRuntimeProvider(): iterable
    {
        yield 'production' => [['environment' => 'production'], 'FOUNDATION_PRODUCTION_FORBIDDEN'];
        yield 'wrong source database' => [['guards.source.database' => 'uhms'], 'FOUNDATION_SOURCE_CONTRACT_INVALID'];
        yield 'source fingerprint override' => [['guards.source.fingerprint' => str_repeat('f', 64)], 'FOUNDATION_SOURCE_CONTRACT_INVALID'];
        yield 'target connection not allow-listed' => [['guards.targets.environments.testing.connection' => 'reporting'], 'FOUNDATION_TARGET_CONNECTION_REJECTED'];
        yield 'production-like target' => [['guards.targets.environments.testing.database' => 'uhms_production'], 'FOUNDATION_TARGET_DATABASE_REJECTED'];
    }

    #[Test]
    public function missing_fingerprint_is_invalid_instead_of_defaulted(): void
    {
        $configuration = $this->configuration();
        unset($configuration['guards']['targets']['environments']['testing']['fingerprint']);

        $this->expectException(FoundationGuardException::class);
        GuardConfiguration::fromArray($configuration, 'testing');
    }

    #[Test]
    public function observed_drift_has_a_safe_specific_fault_code(): void
    {
        $configuration = GuardConfiguration::fromArray($this->configuration(), 'testing');
        try {
            (new EnvironmentGuard)->assertSource($configuration, new SchemaObservation(
                'legacy_uhms', 'uuhms', '10.4.32-MariaDB', str_repeat('0', 64), 55, 479,
            ));
            $this->fail('Expected fingerprint drift failure.');
        } catch (FoundationGuardException $exception) {
            $this->assertSame('FOUNDATION_SOURCE_FINGERPRINT_MISMATCH', $exception->faultCode);
            $this->assertStringNotContainsString(str_repeat('0', 64), $exception->getMessage());
        }
    }

    /** @return array<string, mixed> */
    private function configuration(): array
    {
        return [
            'enabled' => true,
            'reject_production' => true,
            'execution' => [
                'dry_run_only' => true,
                'commit_authorized' => false,
                'cohort_b_selection_enabled' => false,
                'importer_execution_enabled' => false,
                'production_enabled' => false,
            ],
            'guards' => [
                'approved_environments' => ['local', 'testing'],
                'source' => [
                    'connection' => 'legacy_uhms',
                    'database' => 'uuhms',
                    'version' => '10.4.32-MariaDB',
                    'fingerprint' => GuardConfiguration::SOURCE_FINGERPRINT,
                    'table_count' => 55,
                    'column_count' => 479,
                ],
                'targets' => [
                    'connection_allow_list' => ['mysql'],
                    'environments' => [
                        'testing' => [
                            'connection' => 'mysql',
                            'database' => 'uhms_clean',
                            'version' => '10.4.32-MariaDB',
                            'fingerprint' => str_repeat('b', 64),
                            'table_count' => 335,
                            'column_count' => 5347,
                        ],
                    ],
                ],
            ],
        ];
    }
}
