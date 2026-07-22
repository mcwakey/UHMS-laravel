<?php

namespace App\Services\LegacyMigration\Foundation\Validation;

final readonly class TargetStateRule
{
    /**
     * @param  array<int, TargetFieldClassification>  $classifications
     * @param  array<int, string>  $exceptionCodes
     */
    public function __construct(
        public string $ruleId,
        public string $field,
        public array $classifications,
        public mixed $approvedValue,
        public array $exceptionCodes,
        public ?string $approvalReference = null,
    ) {}

    public function has(TargetFieldClassification $classification): bool
    {
        return in_array($classification, $this->classifications, true);
    }
}
