<?php

namespace App\Data\Billing;

/**
 * Result of the enforcement-eligibility analysis for one operation (Payment
 * Timing Policy Phase 4). Diagnostic only — it NEVER enables enforcement; it
 * only reports what remains before an operation could be safely cut over.
 */
final readonly class PaymentGateEnforcementEligibility
{
    public const ELIGIBLE = 'eligible';
    public const INELIGIBLE_UNWIRED = 'ineligible_unwired';
    public const INELIGIBLE_MISSING_STAGE = 'ineligible_missing_stage';
    public const INELIGIBLE_MISSING_INVOICE_RESOLUTION = 'ineligible_missing_invoice_resolution';
    public const INELIGIBLE_EMERGENCY_BOUNDARY = 'ineligible_emergency_boundary';
    public const INELIGIBLE_COMPATIBILITY_RULE = 'ineligible_compatibility_rule';
    public const INELIGIBLE_UNAPPROVED_POLICY = 'ineligible_unapproved_policy';

    /**
     * @param  array<int, string>  $reasons
     */
    public function __construct(
        public string $operation,
        public bool $eligible,
        public string $status,
        public array $reasons = [],
    ) {}

    public static function eligible(string $operation): self
    {
        return new self($operation, true, self::ELIGIBLE, []);
    }

    /**
     * @param  array<int, string>  $reasons
     */
    public static function ineligible(string $operation, string $status, array $reasons = []): self
    {
        return new self($operation, false, $status, $reasons);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'operation' => $this->operation,
            'eligible' => $this->eligible,
            'status' => $this->status,
            'reasons' => $this->reasons,
        ];
    }
}
