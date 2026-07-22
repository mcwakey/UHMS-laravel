<?php

namespace Tests\Unit\LegacyMigration\Foundation\Recovery;

use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FoundationRecoveryAuditCommandTest extends TestCase
{
    #[Test]
    public function command_fails_closed_without_input_and_writes_nothing(): void
    {
        $this->artisan('legacy-migration:foundation-recovery-audit')
            ->expectsOutputToContain('No writes were performed')
            ->assertFailed();
    }

    #[Test]
    public function caller_authored_boolean_package_is_rejected_even_when_complete(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'lm-recovery-');
        self::assertIsString($path);
        file_put_contents($path, json_encode(['observations' => $this->observations()], JSON_THROW_ON_ERROR));

        try {
            self::assertSame(1, Artisan::call('legacy-migration:foundation-recovery-audit', ['--input' => $path]));
            $output = Artisan::output();
            self::assertStringContainsString('caller-authored recovery evidence is not authoritative', $output);
            self::assertStringContainsString('No writes were performed', $output);
        } finally {
            @unlink($path);
        }
    }

    /** @return list<array<string, bool|string>> */
    private function observations(): array
    {
        return [[
            'boundary' => 'PILOT-RESUME-001',
            'coordinates_match' => true,
            'lineage_compatible' => true,
            'unexpected_durable_facts' => false,
            'durable_unit_committed' => false,
            'durable_facts_complete' => false,
            'checkpoint_present' => false,
            'mandatory_reconciliation_present' => false,
            'reconciliation_passed' => false,
            'transaction_rolled_back' => false,
            'allocation_consumption_explained' => true,
        ]];
    }
}
