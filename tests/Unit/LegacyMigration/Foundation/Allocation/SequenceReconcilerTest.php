<?php

namespace Tests\Unit\LegacyMigration\Foundation\Allocation;

use App\Services\LegacyMigration\Foundation\Allocation\SequenceConsumption;
use App\Services\LegacyMigration\Foundation\Allocation\SequenceReconciler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationException;

final class SequenceReconcilerTest extends TestCase
{
    #[Test]
    public function it_reconciles_committed_operational_released_and_explained_consumptions(): void
    {
        $result = (new SequenceReconciler)->reconcile(20, 24, [
            new SequenceConsumption(hash('sha256', 'a'), 21, 'committed'),
            new SequenceConsumption(hash('sha256', 'b'), 22, 'committed'),
            new SequenceConsumption(hash('sha256', 'c'), 23, 'explained'),
            new SequenceConsumption(hash('sha256', 'd'), 24, 'transactionally_released'),
        ], operationalCommitted: 1);

        self::assertTrue($result->passed);
        self::assertSame(0, $result->difference);
        self::assertSame(1, $result->transactionallyReleased);
    }

    #[Test]
    public function unexplained_sequence_difference_cannot_pass(): void
    {
        $result = (new SequenceReconciler)->reconcile(20, 22, [
            new SequenceConsumption(hash('sha256', 'a'), 21, 'committed'),
        ]);

        self::assertFalse($result->passed);
        self::assertSame(1, $result->difference);
    }

    #[Test]
    public function duplicate_or_out_of_range_ordinals_are_rejected(): void
    {
        $this->expectException(AllocationException::class);
        (new SequenceReconciler)->reconcile(20, 22, [
            new SequenceConsumption(hash('sha256', 'a'), 21, 'committed'),
            new SequenceConsumption(hash('sha256', 'b'), 21, 'explained'),
        ]);
    }
}
