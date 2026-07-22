<?php

namespace Tests\Unit\LegacyMigration\Foundation\Environment;

use App\Services\LegacyMigration\Foundation\Environment\ConfigurationFingerprintService;
use App\Services\LegacyMigration\Foundation\Environment\FoundationGuardException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigurationFingerprintServiceTest extends TestCase
{
    #[Test]
    public function hash_is_canonical_and_order_independent_for_object_keys(): void
    {
        $service = new ConfigurationFingerprintService;

        $this->assertSame(
            $service->fingerprint(['b' => 2, 'nested' => ['z' => true, 'a' => null], 'a' => 1]),
            $service->fingerprint(['a' => 1, 'nested' => ['a' => null, 'z' => true], 'b' => 2]),
        );
    }

    #[Test]
    public function secret_bearing_input_is_refused(): void
    {
        try {
            (new ConfigurationFingerprintService)->fingerprint(['database_password' => 'do-not-log']);
            $this->fail('Expected secret rejection.');
        } catch (FoundationGuardException $exception) {
            $this->assertSame('FOUNDATION_CONFIG_SECRET_REJECTED', $exception->faultCode);
            $this->assertStringNotContainsString('do-not-log', $exception->getMessage());
        }
    }
}
