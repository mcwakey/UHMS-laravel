<?php

namespace App\Data\Billing;

/**
 * Typed administrative approval requirement for a per-visit payment arrangement
 * (Payment Timing Policy Phase 7). This describes WHO must approve and WHY — it
 * makes no operational payment decision and never touches a payment gate.
 */
final readonly class VisitPaymentArrangementApprovalRequirement
{
    /**
     * @param  array<int, string>  $context  machine reason codes (not translated)
     */
    public function __construct(
        public bool $requiresApproval,
        public bool $requiresSeparateApprover,
        public bool $requiresFinanceManager,
        public bool $requiresManagementApproval,
        public string $reasonCode,
        public array $context = [],
    ) {}

    public static function directlyAllowed(string $reasonCode = 'directly_allowed'): self
    {
        return new self(false, false, false, false, $reasonCode, []);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'requires_approval' => $this->requiresApproval,
            'requires_separate_approver' => $this->requiresSeparateApprover,
            'requires_finance_manager' => $this->requiresFinanceManager,
            'requires_management_approval' => $this->requiresManagementApproval,
            'reason_code' => $this->reasonCode,
            'context' => $this->context,
        ];
    }
}
