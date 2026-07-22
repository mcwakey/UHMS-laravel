<?php

namespace Tests\Unit\LegacyMigration\Foundation\Security;

use App\Services\LegacyMigration\Foundation\Security\ConfiguredKeyProvider;
use App\Services\LegacyMigration\Foundation\Security\EnvironmentKeyProvider;
use App\Services\LegacyMigration\Foundation\Security\HmacKeyMaterial;
use App\Services\LegacyMigration\Foundation\Security\KeyReferenceManifestLoader;
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
    public function debug_and_print_output_never_expose_hmac_secret_bytes(): void
    {
        $syntheticKeyBytes = 'SYNTHETIC-ONLY-P3B::9876543210abcdefFEDCBA!@#$%^&*()-+=';
        $material = new HmacKeyMaterial('migration-hmac', 'v1', $syntheticKeyBytes);
        ob_start();
        var_dump($material);
        $dump = (string) ob_get_clean();
        $printed = print_r($material, true);

        $this->assertStringNotContainsString($syntheticKeyBytes, $dump);
        $this->assertStringNotContainsString($syntheticKeyBytes, $printed);
        $this->assertStringContainsString('[REDACTED]', $dump);
        $this->assertStringContainsString('[REDACTED]', $printed);
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

    #[Test]
    public function pinned_nonsecret_reference_manifest_composes_the_external_provider(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'p3b-key-refs-');
        $manifest = json_encode([
            'manifest_version' => 'P3B-KEY-REFS-1',
            'references' => [[
                'key_id' => 'migration-hmac', 'version' => 'v1', 'secret_reference' => 'vault://synthetic/migration',
                'environment' => 'testing', 'domains' => ['patient_source', 'artifact_integrity'],
                'activated_at' => '2020-01-01T00:00:00Z', 'rotation_authority' => 'SYNTHETIC-AUTHORITY',
                'revoked' => false, 'active_for_signing' => true,
            ]],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        file_put_contents($path, $manifest);

        try {
            $service = LegacyMigrationSecurityFactory::make([
                'versions' => ['canonicalization' => 'typed-length-prefix/1'],
                'hmac' => ['domains' => ['patient_source', 'artifact_integrity']],
                'key_provider' => [
                    'provider' => 'external_reference', 'references' => [],
                    'reference_manifest' => $path, 'reference_manifest_hash' => hash('sha256', $manifest),
                ],
            ], 'testing', static fn (string $reference): string => '456789abcdef0123EFABCD!@#$%^&*()-+=0123');
            $this->assertNotNull($service);
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function reference_manifest_rejects_embedded_key_material(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'p3b-key-refs-');
        $manifest = json_encode([
            'manifest_version' => 'P3B-KEY-REFS-1',
            'references' => [['key' => 'SYNTHETIC-SECRET-MUST-NOT-BE-ACCEPTED']],
        ], JSON_THROW_ON_ERROR);
        file_put_contents($path, $manifest);

        try {
            $this->expectExceptionMessage('LM-SEC-KEY-MANIFEST-SECRET-001');
            (new KeyReferenceManifestLoader)->load($path, hash('sha256', $manifest));
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function reference_manifest_rejects_non_secret_reference_uri_schemes(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'p3b-key-refs-');
        $manifest = json_encode([
            'manifest_version' => 'P3B-KEY-REFS-1',
            'references' => [['secret_reference' => 'https://example.invalid/key-material']],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        file_put_contents($path, $manifest);

        try {
            $this->expectExceptionMessage('LM-SEC-KEY-MANIFEST-REFERENCE-001');
            (new KeyReferenceManifestLoader)->load($path, hash('sha256', $manifest));
        } finally {
            @unlink($path);
        }
    }
}
