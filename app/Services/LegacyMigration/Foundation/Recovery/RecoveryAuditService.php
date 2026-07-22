<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final readonly class RecoveryAuditService
{
    public function __construct(private CrashBoundaryClassifier $classifier = new CrashBoundaryClassifier) {}

    /**
     * @param  iterable<RecoveryObservation>  $observations
     * @return array{boundary_count:int, classifications:array<string,int>, operator_review_count:int, target_write_count:int, writes:int}
     */
    public function audit(iterable $observations): array
    {
        $count = 0;
        $review = 0;
        $classifications = [];
        foreach ($observations as $observation) {
            $decision = $this->classifier->classify($observation->boundary, $observation->evidence);
            $count++;
            $review += (int) $decision->operatorReviewRequired;
            $classifications[$decision->disposition->value] = ($classifications[$decision->disposition->value] ?? 0) + 1;
        }
        ksort($classifications);

        return [
            'boundary_count' => $count,
            'classifications' => $classifications,
            'operator_review_count' => $review,
            'target_write_count' => 0,
            'writes' => 0,
        ];
    }
}
