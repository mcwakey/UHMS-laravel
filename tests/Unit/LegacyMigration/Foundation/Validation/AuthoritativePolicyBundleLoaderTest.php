<?php

namespace Tests\Unit\LegacyMigration\Foundation\Validation;

use App\Services\LegacyMigration\Foundation\Validation\AuthoritativePolicyBundleLoader;
use App\Services\LegacyMigration\Foundation\Validation\Phase2FPolicyConfigurationFactory;
use App\Services\LegacyMigration\Foundation\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\LegacyMigration\Phase2FPolicyFixture;

final class AuthoritativePolicyBundleLoaderTest extends TestCase
{
    #[Test]
    public function exact_authoritative_artifacts_versions_contract_ids_and_approvals_are_bound(): void
    {
        $bundle = Phase2FPolicyFixture::load();

        self::assertCount(23, $bundle->artifacts);
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $bundle->bundleHash);
        self::assertSame('2F.1.0', $bundle->artifact('docs/legacy-migration/phase-2f/specifications/patient_pilot_state_matrix.json')->specificationVersion);
        $bundle->assertPinned($bundle->bundleHash);
    }

    #[Test]
    public function missing_unknown_changed_or_unapproved_artifact_fails_closed(): void
    {
        $expected = Phase2FPolicyFixture::expected();
        unset($expected[array_key_first($expected)]);
        $this->assertFault('FOUNDATION_POLICY_ARTIFACT_SET_INVALID', fn () => (new AuthoritativePolicyBundleLoader)->load(dirname(__DIR__, 5), $expected));

        $expected = Phase2FPolicyFixture::expected();
        $path = array_key_first($expected);
        $expected[$path]['sha256'] = str_repeat('0', 64);
        $this->assertFault('FOUNDATION_POLICY_ARTIFACT_AUTHORITY_INVALID', fn () => (new AuthoritativePolicyBundleLoader)->load(dirname(__DIR__, 5), $expected));

        $expected = Phase2FPolicyFixture::expected();
        $jsonPath = 'docs/legacy-migration/phase-2f/specifications/patient_pilot_state_matrix.json';
        $expected[$jsonPath]['contract_ids'][] = 'UNKNOWN-CONTRACT';
        sort($expected[$jsonPath]['contract_ids'], SORT_STRING);
        $this->assertFault('FOUNDATION_POLICY_CONTRACT_SET_MISMATCH', fn () => (new AuthoritativePolicyBundleLoader)->load(dirname(__DIR__, 5), $expected));

        $expected = Phase2FPolicyFixture::expected();
        $expected[$jsonPath]['approval_reference'] = '';
        $this->assertFault('FOUNDATION_POLICY_ARTIFACT_AUTHORITY_INVALID', fn () => (new AuthoritativePolicyBundleLoader)->load(dirname(__DIR__, 5), $expected));
    }

    #[Test]
    public function bundle_drift_after_run_pinning_fails_closed(): void
    {
        $bundle = Phase2FPolicyFixture::load();
        $this->assertFault('FOUNDATION_POLICY_BUNDLE_DRIFT', fn () => $bundle->assertPinned(str_repeat('f', 64)));
    }

    #[Test]
    public function external_nonsecret_manifest_factory_loads_and_pins_the_exact_bundle(): void
    {
        $expected = Phase2FPolicyFixture::expected();
        $bundle = Phase2FPolicyFixture::load();
        $path = tempnam(sys_get_temp_dir(), 'phase2f-policy-');
        self::assertIsString($path);
        file_put_contents($path, json_encode([
            'manifest_version' => 'phase2f-policy-manifest/1',
            'expected_bundle_hash' => $bundle->bundleHash,
            'approval_reference' => 'synthetic-deployment-policy-authority',
            'artifacts' => $expected,
        ], JSON_THROW_ON_ERROR));
        try {
            $loaded = (new Phase2FPolicyConfigurationFactory)->load(dirname(__DIR__, 5), [
                'artifact_manifest' => $path,
                'expected_bundle_hash' => $bundle->bundleHash,
                'approval_reference' => 'synthetic-deployment-policy-authority',
            ]);
            self::assertSame($bundle->bundleHash, $loaded->bundleHash);
        } finally {
            @unlink($path);
        }
    }

    private function assertFault(string $expected, callable $operation): void
    {
        try {
            $operation();
            self::fail('Expected policy binding failure.');
        } catch (ValidationException $exception) {
            self::assertSame($expected, $exception->faultCode);
            self::assertStringNotContainsString('docs/', $exception->getMessage());
        }
    }
}
