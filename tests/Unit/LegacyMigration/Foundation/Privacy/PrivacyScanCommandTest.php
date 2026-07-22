<?php

namespace Tests\Unit\LegacyMigration\Foundation\Privacy;

use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PrivacyScanCommandTest extends TestCase
{
    #[Test]
    public function exact_command_emits_the_required_aggregate_scan_contract(): void
    {
        config()->set('legacy-migration.privacy.scan_roots', ['docs/legacy-migration']);
        $exitCode = Artisan::call('legacy-migration:privacy-scan', ['--json' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('"scanner_version":"P3B-PRIVACY-SCANNER-3"', $output);
        $this->assertStringContainsString('"coverage_difference":0', $output);
        $this->assertStringContainsString('"unallowlisted_finding_count":0', $output);
        $this->assertStringContainsString('"release_blocked":false', $output);
    }

    #[Test]
    public function command_fails_closed_when_a_configured_root_is_missing(): void
    {
        config()->set('legacy-migration.privacy.scan_roots', ['synthetic-missing-required-root']);
        $exitCode = Artisan::call('legacy-migration:privacy-scan', ['--json' => true]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('"coverage_difference":1', $output);
        $this->assertStringContainsString('"detector_id":"artifact_missing"', $output);
        $this->assertStringContainsString('"release_blocked":true', $output);
        $this->assertStringContainsString('complete privacy rescan', $output);
    }
}
