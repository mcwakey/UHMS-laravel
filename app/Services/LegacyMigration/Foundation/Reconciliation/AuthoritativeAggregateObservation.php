<?php

namespace App\Services\LegacyMigration\Foundation\Reconciliation;

use App\Services\LegacyMigration\Foundation\Snapshot\CanonicalManifestHasher;
use App\Services\LegacyMigration\Foundation\Snapshot\SnapshotManifest;

final readonly class AuthoritativeAggregateObservation
{
    private function __construct(
        public int $sourcePopulation,
        public int $terminalOutcomes,
        public int $classifiedOutcomes,
        public int $provenanceRows,
        public int $quarantineRoots,
        public int $collisionOutcomes,
        public int $idempotencyOutcomes,
        public int $checkpointOutcomes,
        public int $reconciliationDifferences,
        public int $financialProjectionAttempts,
        public int $privacyFindings,
        public int $criticalOrHighReviewFindings,
        public int $unsafeDomainOutcomes,
        public string $authorityReference,
    ) {}

    /**
     * The only in-repository complete provider is deliberately bounded to an
     * observed empty synthetic cohort. Non-empty runs require an external,
     * independently reviewed aggregate recorder implementation.
     */
    public static function fromObservedEmptyCohort(
        SnapshotManifest $source,
        SnapshotManifest $targetBefore,
        SnapshotManifest $targetAfter,
    ): self {
        foreach (['patient_root', 'patient_children', 'insurance'] as $set) {
            if (($source->setCounts[$set] ?? null) !== 0) {
                throw new \LogicException('The empty-cohort aggregate recorder cannot classify a populated source cohort.');
            }
        }

        return new self(
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            (new CanonicalManifestHasher)->hash([
                'adapter' => 'observed_empty_cohort/1',
                'source_snapshot_id' => $source->snapshotId,
                'target_before_snapshot_id' => $targetBefore->snapshotId,
                'target_after_snapshot_id' => $targetAfter->snapshotId,
            ]),
        );
    }

    /** @return array<string,int|string> */
    public function toArray(): array
    {
        return [
            'adapter_version' => 'observed_empty_cohort/1',
            'source_population' => $this->sourcePopulation,
            'terminal_outcomes' => $this->terminalOutcomes,
            'classified_outcomes' => $this->classifiedOutcomes,
            'provenance_rows' => $this->provenanceRows,
            'quarantine_roots' => $this->quarantineRoots,
            'collision_outcomes' => $this->collisionOutcomes,
            'idempotency_outcomes' => $this->idempotencyOutcomes,
            'checkpoint_outcomes' => $this->checkpointOutcomes,
            'reconciliation_differences' => $this->reconciliationDifferences,
            'financial_projection_attempts' => $this->financialProjectionAttempts,
            'privacy_findings' => $this->privacyFindings,
            'critical_or_high_review_findings' => $this->criticalOrHighReviewFindings,
            'unsafe_domain_outcomes' => $this->unsafeDomainOutcomes,
            'authority_reference' => $this->authorityReference,
        ];
    }
}
