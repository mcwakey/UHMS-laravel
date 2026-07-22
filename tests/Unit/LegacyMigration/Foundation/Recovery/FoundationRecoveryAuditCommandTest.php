<?php

namespace Tests\Unit\LegacyMigration\Foundation\Recovery;

use App\Services\LegacyMigration\Foundation\Recovery\CrashBoundary;
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
    public function command_audits_all_ten_boundaries_with_zero_writes(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'lm-recovery-');
        self::assertIsString($path);
        file_put_contents($path, json_encode(['observations' => $this->observations()], JSON_THROW_ON_ERROR));

        try {
            self::assertSame(0, Artisan::call('legacy-migration:foundation-recovery-audit', ['--input' => $path]));
            $output = Artisan::output();
            self::assertStringContainsString('"boundary_count":10', $output);
            self::assertStringContainsString('"target_write_count":0', $output);
            self::assertStringContainsString('"writes":0', $output);
        } finally {
            @unlink($path);
        }
    }

    /** @return list<array<string, bool|string>> */
    private function observations(): array
    {
        $rows = [];
        foreach (CrashBoundary::cases() as $boundary) {
            $row = [
                'boundary' => $boundary->value,
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
            ];
            if ($boundary === CrashBoundary::AfterPatientCommitBeforeCrosswalkCommit) {
                $row['transaction_rolled_back'] = true;
            }
            if (in_array($boundary, [CrashBoundary::AfterCoreCommitBeforeCheckpoint, CrashBoundary::AfterTargetWritesBeforeReconciliation, CrashBoundary::AfterReconciliationBeforeCompletion], true)) {
                $row['durable_unit_committed'] = true;
                $row['durable_facts_complete'] = true;
            }
            if (in_array($boundary, [CrashBoundary::AfterCoreCommitBeforeCheckpoint, CrashBoundary::AfterReconciliationBeforeCompletion], true)) {
                $row['mandatory_reconciliation_present'] = true;
                $row['reconciliation_passed'] = true;
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
