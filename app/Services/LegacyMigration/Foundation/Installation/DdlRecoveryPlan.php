<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

final readonly class DdlRecoveryPlan
{
    /** @param list<DdlObjectExpectation> $operations */
    public function __construct(
        public string $installationVersion,
        public array $operations,
        public bool $partialPriorInstallation,
        public bool $completed,
    ) {}
}
