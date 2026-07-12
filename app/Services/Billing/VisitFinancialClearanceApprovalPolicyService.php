<?php

namespace App\Services\Billing;

use App\Data\Billing\VisitFinancialClearanceApprovalRequirement;
use App\Enums\VisitFinancialClearanceExceptionType as Type;

class VisitFinancialClearanceApprovalPolicyService
{
    public function requirement(Type $type): VisitFinancialClearanceApprovalRequirement
    {
        return new VisitFinancialClearanceApprovalRequirement(
            permission: 'visits.financial_clearance_exception.approve',
            requiresSeparateApprover: (bool) config('visit_financial_clearance.conditional_clearance.require_separate_approver', true),
            requiresSupportingReference: in_array($type, [Type::INSURANCE_PENDING, Type::CORPORATE_GUARANTEE, Type::MANAGEMENT_APPROVAL], true),
            requiresExpiryDate: false,
        );
    }
}
