<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final readonly class ApplicationIsolationAuthorityEvidence
{
    public function __construct(
        public bool $bindingsVerified,
        public bool $queueWorkersPaused,
        public bool $schedulerPaused,
        public bool $externalIntegrationsSinkVerified,
        public string $authorityReference,
        public string $queueWorkerBarrierReference,
        public string $schedulerBarrierReference,
        public string $nullSinkReference,
        public string $observationReference,
    ) {}
}
