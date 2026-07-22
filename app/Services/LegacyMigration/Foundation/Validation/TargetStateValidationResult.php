<?php

namespace App\Services\LegacyMigration\Foundation\Validation;

final readonly class TargetStateValidationResult
{
    /**
     * @param  array<string, array<int, string>>  $classifications
     * @param  array<int, string>  $violationCodes
     * @param  array<int, string>  $missingExplicitFields
     * @param  array<int, string>  $unexpectedFields
     */
    public function __construct(
        public string $policyVersion,
        public string $policyAuthority,
        public string $policyFingerprint,
        public bool $dryRunAllowed,
        public bool $commitAllowed,
        public array $classifications,
        public array $violationCodes,
        public array $missingExplicitFields,
        public array $unexpectedFields,
    ) {}

    public function assertCommitAllowed(): void
    {
        if (! $this->commitAllowed) {
            throw new ValidationException(
                'FOUNDATION_TARGET_STATE_COMMIT_BLOCKED',
                $this->violationCodes,
                'The target-state policy does not authorize commit.',
            );
        }
    }
}
