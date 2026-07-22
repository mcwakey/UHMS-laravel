<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

final class SequenceReconciler
{
    /** @param iterable<SequenceConsumption> $consumptions */
    /** @param iterable<int> $operationalOrdinals */
    public function reconcile(
        int $before,
        int $after,
        iterable $consumptions,
        int $operationalCommitted = 0,
        iterable $operationalOrdinals = [],
    ): SequenceReconciliation {
        if ($before < 0 || $after < $before || $operationalCommitted < 0) {
            throw AllocationException::failClosed('PATIENT-NUM-SEQUENCE-UNDERFLOW');
        }

        $counts = ['committed' => 0, 'transactionally_released' => 0, 'explained' => 0];
        $lineages = [];
        $ordinals = [];
        $seenOrdinals = [];
        foreach ($consumptions as $consumption) {
            if (isset($lineages[$consumption->lineageToken])) {
                throw AllocationException::failClosed('PATIENT-NUM-DUPLICATE-CONSUMPTION');
            }
            $lineages[$consumption->lineageToken] = true;
            if ($consumption->sequenceOrdinal <= $before || isset($seenOrdinals[$consumption->sequenceOrdinal])) {
                throw AllocationException::failClosed('PATIENT-NUM-INVALID-CONSUMPTION-ORDINAL');
            }
            $seenOrdinals[$consumption->sequenceOrdinal] = true;
            $ordinals[$consumption->sequenceOrdinal] = true;
            $counts[$consumption->classification]++;

            // A transactionally released ordinal did not survive in the
            // closing coordinate and therefore cannot explain its coverage.
            if ($consumption->classification === 'transactionally_released') {
                unset($ordinals[$consumption->sequenceOrdinal]);
            } elseif ($consumption->sequenceOrdinal > $after) {
                throw AllocationException::failClosed('PATIENT-NUM-INVALID-CONSUMPTION-ORDINAL');
            }
        }

        $observedOperational = 0;
        foreach ($operationalOrdinals as $ordinal) {
            if (! is_int($ordinal) || $ordinal <= $before || $ordinal > $after || isset($ordinals[$ordinal])) {
                throw AllocationException::failClosed('PATIENT-NUM-INVALID-OPERATIONAL-ORDINAL');
            }
            $ordinals[$ordinal] = true;
            $observedOperational++;
        }
        if ($observedOperational !== $operationalCommitted) {
            throw AllocationException::failClosed('PATIENT-NUM-OPERATIONAL-COUNT-MISMATCH');
        }

        for ($ordinal = $before + 1; $ordinal <= $after; $ordinal++) {
            if (! isset($ordinals[$ordinal])) {
                throw AllocationException::failClosed('PATIENT-NUM-MISSING-CONSUMPTION-ORDINAL');
            }
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
