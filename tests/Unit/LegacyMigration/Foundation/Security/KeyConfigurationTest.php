<?php

namespace Tests\Unit\LegacyMigration\Foundation\Security;

use App\Services\LegacyMigration\Foundation\Security\ConfiguredKeyProvider;
use App\Services\LegacyMigration\Foundation\Security\EnvironmentKeyProvider;
use App\Services\LegacyMigration\Foundation\Security\LegacyMigrationSecurityFactory;
use App\Services\LegacyMigration\Foundation\Security\SecurityConfigurationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class KeyConfigurationTest extends TestCase
{
    #[Test]
    public function exact_laravel_configuration_shape_builds_the_service(): void
    {
        $service = LegacyMigrationSecurityFactory::make([
            'versions' => ['canonicalization' => 'typed-length-prefix/1'],
            'hmac' => [
                'key_id' => 'migration-hmac',
                'key_version' => 'v3',
                'key' => '0123456789abcdefABCDEF!@#$%^&*()-+=',
                'algorithm' => 'sha256',
                'domains' => ['patient_source', 'artifact_integrity'],
            ],
        ], 'testing');

        $this->assertNotNull($service);
    }

    #[Test]
    public function missing_or_weak_keys_fail_without_echoing_configuration(): void
    {
        $sensitive = 'do-not-echo-this-material';

        try {
            new ConfiguredKeyProvider([
                'key_id' => 'migration-hmac',
                'key_version' => 'v1',
                'key' => $sensitive,
                'algorithm' => 'sha256',
            ]);
            $this->fail('Weak key should have failed.');
        } catch (SecurityConfigurationException $exception) {
            $this->assertStringNotContainsString($sensitive, $exception->getMessage());
            $this->assertStringNotContainsString('migration-hmac', $exception->getMessage());
        }
    }

    #[Test]
    public function repeated_byte_key_material_is_rejected_as_low_diversity(): void
    {
        $this->expectException(SecurityConfigurationException::class);
        new ConfiguredKeyProvider([
            'key_id' => 'migration-hmac',
            'key_version' => 'v1',
            'key' => str_repeat('X', 64),
            'algorithm' => 'sha256',
        ]);
    }

    #[Test]
    public function environment_provider_resolves_versions_without_exposing_variable_or_value(): void
    {
        $variable = 'SYNTHETIC_ONLY_MISSING_KEY';
        $provider = new EnvironmentKeyProvider([
            'active' => ['key_id' => 'migration-hmac', 'version' => 'v1'],
            'keys' => [[
                'key_id' => 'migration-hmac',
                'version' => 'v1',
                'environment_variable' => $variable,
            ]],
        ], static fn (string $name): null => null);

        try {
            $provider->active();
            $this->fail('Missing environment key should have failed.');
        } catch (SecurityConfigurationException $exception) {
            $this->assertStringNotContainsString($variable, $exception->getMessage());
        }
    }
}
