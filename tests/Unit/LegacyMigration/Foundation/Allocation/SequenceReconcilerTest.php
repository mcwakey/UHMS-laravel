<?php

namespace Tests\Unit\LegacyMigration\Foundation\Allocation;

use App\Services\LegacyMigration\Foundation\Allocation\AllocationException;
use App\Services\LegacyMigration\Foundation\Allocation\SequenceConsumption;
use App\Services\LegacyMigration\Foundation\Allocation\SequenceReconciler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SequenceReconcilerTest extends TestCase
{
    #[Test]
    public function it_reconciles_committed_operational_released_and_explained_consumptions(): void
    {
        $result = (new SequenceReconciler)->reconcile(20, 24, [
            new SequenceConsumption(hash('sha256', 'a'), 21, 'committed'),
            new SequenceConsumption(hash('sha256', 'b'), 22, 'committed'),
            new SequenceConsumption(hash('sha256', 'c'), 23, 'explained'),
            new SequenceConsumption(hash('sha256', 'd'), 25, 'transactionally_released'),
        ], operationalCommitted: 1, operationalOrdinals: [24]);

        self::assertTrue($result->passed);
        self::assertSame(0, $result->difference);
        self::assertSame(1, $result->transactionallyReleased);
    }

    #[Test]
    public function unexplained_sequence_difference_cannot_pass(): void
    {
        $this->expectException(AllocationException::class);
        (new SequenceReconciler)->reconcile(20, 22, [
            new SequenceConsumption(hash('sha256', 'a'), 21, 'committed'),
        ]);
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

    #[Test]
    public function operational_counts_require_exact_observed_ordinals(): void
    {
        $this->expectException(AllocationException::class);
        (new SequenceReconciler)->reconcile(20, 22, [
            new SequenceConsumption(hash('sha256', 'a'), 21, 'committed'),
        ], operationalCommitted: 1, operationalOrdinals: []);
    }

    #[Test]
    public function duplicate_lineage_is_rejected_even_when_ordinals_differ(): void
    {
        $this->expectException(AllocationException::class);
        (new SequenceReconciler)->reconcile(20, 22, [
            new SequenceConsumption(hash('sha256', 'same'), 21, 'committed'),
            new SequenceConsumption(hash('sha256', 'same'), 22, 'explained'),
        ]);
    }

    #[Test]
    public function zero_and_negative_ordinals_are_rejected_at_admission(): void
    {
        $this->expectException(AllocationException::class);
        new SequenceConsumption(hash('sha256', 'a'), 0, 'committed');
    }
}
