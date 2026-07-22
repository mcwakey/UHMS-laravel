<?php

namespace Tests\Unit\LegacyMigration\Foundation\Reconciliation;

use App\Services\LegacyMigration\Foundation\Reconciliation\ReconciliationVerdict;
use App\Services\LegacyMigration\Foundation\Reconciliation\ReconciliationVerdictService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReconciliationVerdictServiceTest extends TestCase
{
    #[Test]
    public function mandatory_missing_measurement_blocks_and_nonzero_never_passes(): void
    {
        $service = new ReconciliationVerdictService;

        $missing = $service->assess(['a', 'b'], [['id' => 'a', 'difference' => '0.00']]);
        self::assertSame(ReconciliationVerdict::BlockedNotMeasured, $missing['verdict']);
        self::assertSame(['b'], $missing['missing']);

        $failed = $service->assess(['a'], [['id' => 'a', 'difference' => '0.01']]);
        self::assertSame(ReconciliationVerdict::FailedNonZeroDifference, $failed['verdict']);

        $passed = $service->assess(['a'], [['id' => 'a', 'difference' => '-0.000']]);
        self::assertSame(ReconciliationVerdict::Passed, $passed['verdict']);
    }

    #[Test]
    public function unexplained_is_distinct_from_expected_classified_exception(): void
    {
        $service = new ReconciliationVerdictService;
        $result = $service->assess(['a'], [[
            'id' => 'a', 'difference' => 0, 'classification' => 'unexplained',
        ]]);

        self::assertSame(ReconciliationVerdict::FailedUnexplained, $result['verdict']);
    }
}
