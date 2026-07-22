<?php

namespace App\Services\LegacyMigration\Foundation\Validation;

abstract class VersionedTargetStateValidator
{
    protected function __construct(private readonly TargetStatePolicy $policy) {}

    /** @param array<string, mixed> $state */
    public function validate(array $state): TargetStateValidationResult
    {
        $classifications = [];
        $violations = [];
        $missing = [];
        $commitBlocked = false;
        $expectedFields = [];

        foreach ($this->policy->rules as $rule) {
            $classifications[$rule->field] = array_map(
                static fn (TargetFieldClassification $classification): string => $classification->value,
                $rule->classifications,
            );

            if ($rule->field === 'complete_state_tuple' || $rule->field === 'current_representation') {
                if ($rule->has(TargetFieldClassification::CommitBlocker)) {
                    $commitBlocked = true;
                    array_push($violations, ...$rule->exceptionCodes);
                }

                continue;
            }

            $expectedFields[] = $rule->field;

            if (! array_key_exists($rule->field, $state)) {
                $missing[] = $rule->field;
                array_push($violations, ...$rule->exceptionCodes);
                $commitBlocked = true;

                continue;
            }

            $value = $state[$rule->field];
            if (($rule->has(TargetFieldClassification::ApprovedConditionalNull)
                    || ($rule->has(TargetFieldClassification::ApprovedValue) && $rule->approvedValue === null)
                    || $rule->has(TargetFieldClassification::TargetOwnedOperationalState))
                && $value !== null) {
                array_push($violations, ...$rule->exceptionCodes);
                $commitBlocked = true;
            }

            if ($rule->has(TargetFieldClassification::ApprovedValue)
                && ! in_array($rule->approvedValue, [null, TargetStatePolicy::SOURCE_MAPPED_VALUE, TargetStatePolicy::OPTIONAL_SOURCE_MAPPED_VALUE], true)
                && $value !== $rule->approvedValue) {
                array_push($violations, ...$rule->exceptionCodes);
                $commitBlocked = true;
            }
            if ($rule->approvedValue === TargetStatePolicy::SOURCE_MAPPED_VALUE && ($value === null || $value === '')) {
                array_push($violations, ...$rule->exceptionCodes);
                $commitBlocked = true;
            }
            if ($rule->has(TargetFieldClassification::CommitBlocker)) {
                $commitBlocked = true;
                array_push($violations, ...$rule->exceptionCodes);
            }
        }

        $violations = array_values(array_unique($violations));
        $missing = array_values(array_unique($missing));
        $unexpected = array_values(array_diff(array_keys($state), $expectedFields));
        sort($unexpected, SORT_STRING);
        if ($unexpected !== []) {
            $violations[] = 'FOUNDATION-TARGET-STATE-UNEXPECTED-FIELD';
            $commitBlocked = true;
        }

        return new TargetStateValidationResult(
            policyVersion: $this->policy->version,
            policyAuthority: $this->policy->authority,
            policyFingerprint: $this->policy->fingerprint,
            dryRunAllowed: true,
            commitAllowed: ! $commitBlocked && $violations === [] && $missing === [],
            classifications: $classifications,
            violationCodes: $violations,
            missingExplicitFields: $missing,
            unexpectedFields: $unexpected,
        );
    }
}
