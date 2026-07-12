<?php

namespace App\Data\Billing;

final readonly class VisitFinancialClearanceApprovalRequirement
{
    public function __construct(
        public string $permission,
        public bool $requiresSeparateApprover,
        public bool $requiresSupportingReference,
        public bool $requiresExpiryDate,
    ) {}
}
