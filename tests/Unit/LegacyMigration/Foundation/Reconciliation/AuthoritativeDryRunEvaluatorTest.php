<?php

namespace Tests\Unit\LegacyMigration\Foundation\Reconciliation;

use App\Services\LegacyMigration\Foundation\Reconciliation\AuthoritativeDryRunEvaluator;
use App\Services\LegacyMigration\Foundation\Reconciliation\AuthoritativeMeasurement;
use App\Services\LegacyMigration\Foundation\Reconciliation\MeasurementIntegrityService;
use App\Services\LegacyMigration\Foundation\Reconciliation\MeasurementSource;
use App\Services\LegacyMigration\Foundation\Reconciliation\Phase2FMeasurementPlan;
use App\Services\LegacyMigration\Foundation\Security\CanonicalizationVersionRegistry;
use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\ConfiguredKeyProvider;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\LegacyMigration\Phase2FPolicyFixture;

final class AuthoritativeDryRunEvaluatorTest extends TestCase
{
    #[Test]
    public function caller_declared_measurement_arrays_are_rejected_even_when_correctly_sealed(): void
    {
        $bundle = Phase2FPolicyFixture::load();
        $measurement = new AuthoritativeMeasurement(
            'synthetic:contract_assertion', 'synthetic', MeasurementSource::DatabaseBeforeAfter,
            str_repeat('1', 64), str_repeat('2', 64), str_repeat('3', 64), str_repeat('4', 64),
            'declared', '0', '0', '0', 'declared', '',
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Caller-declared');
        (new AuthoritativeDryRunEvaluator($this->integrity()))->evaluate($bundle, $bundle->bundleHash, [$measurement]);
    }

    #[Test]
    public function policy_plan_remains_complete_but_requires_recorder_issued_evidence(): void
    {
        $plan = Phase2FMeasurementPlan::fromBundle(Phase2FPolicyFixture::load());

        self::assertCount(476, $plan->measurements);
        self::assertCount(341, array_filter($plan->adapterKinds, static fn (string $kind): bool => $kind === 'contract_assertion'));
        self::assertCount(135, array_filter($plan->adapterKinds, static fn (string $kind): bool => $kind !== 'contract_assertion'));
        self::assertArrayHasKey('A-001:contract_assertion', $plan->measurements);
        self::assertSame('required_outputs', $plan->adapterKinds['PILOT-DRY-004:patient_entity_outcomes']);
        self::assertSame('required_zero', $plan->adapterKinds['PILOT-RECON-008:dry_run_target_writes']);
    }

    private function integrity(): MeasurementIntegrityService
    {
        $versions = new CanonicalizationVersionRegistry;
        $hmac = new HmacTokenService(new ConfiguredKeyProvider([
            'key_id' => 'synthetic-p3b', 'key_version' => 'v1',
            'key' => '789abcdef0123456BCDEFA!@#$%^&*()-+=0123456', 'algorithm' => 'sha256',
        ]), $versions, 'testing');

        return new MeasurementIntegrityService($hmac, new CanonicalTypedMessageEncoder($versions));
    }
}
