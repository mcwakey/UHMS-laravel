<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

final class SequenceReconciler
{
    /** @param iterable<SequenceConsumption> $consumptions */
    public function reconcile(int $before, int $after, iterable $consumptions, int $operationalCommitted = 0): SequenceReconciliation
    {
        if ($before < 0 || $after < $before || $operationalCommitted < 0) {
            throw AllocationException::failClosed('PATIENT-NUM-SEQUENCE-UNDERFLOW');
        }

        $counts = ['committed' => 0, 'transactionally_released' => 0, 'explained' => 0];
        $lineages = [];
        $ordinals = [];
        foreach ($consumptions as $consumption) {
            if (isset($lineages[$consumption->lineageToken])) {
                throw AllocationException::failClosed('PATIENT-NUM-DUPLICATE-CONSUMPTION');
            }
            $lineages[$consumption->lineageToken] = true;
            if ($consumption->sequenceOrdinal <= $before
                || $consumption->sequenceOrdinal > $after
                || isset($ordinals[$consumption->sequenceOrdinal])) {
                throw AllocationException::failClosed('PATIENT-NUM-INVALID-CONSUMPTION-ORDINAL');
            }
            $ordinals[$consumption->sequenceOrdinal] = true;
            $counts[$consumption->classification]++;
        }

        $delta = $after - $before;
        $difference = $delta - $counts['committed'] - $operationalCommitted - $counts['explained'];

        return new SequenceReconciliation(
            $delta,
            $counts['committed'],
            $operationalCommitted,
            $counts['transactionally_released'],
            $counts['explained'],
            $difference,
            $difference === 0,
        );
    }
}
