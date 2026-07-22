<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final readonly class CompensationPlan
{
    /**
     * @param  list<string>  $allowedActions
     * @param  list<string>  $prohibitedActions
     */
    public function __construct(
        public AtomicUnit $unit,
        public array $allowedActions,
        public array $prohibitedActions,
        public bool $operatorReviewRequired = true,
    ) {}

    public function assertActionAllowed(string $action): void
    {
        if (! in_array($action, $this->allowedActions, true) || in_array($action, $this->prohibitedActions, true)) {
            throw RecoveryException::failClosed('RECOVERY-UNAUTHORIZED-COMPENSATION');
        }
    }
}
