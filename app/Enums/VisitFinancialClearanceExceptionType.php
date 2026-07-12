<?php

namespace App\Enums;

enum VisitFinancialClearanceExceptionType: string
{
    case OUTSTANDING_BALANCE_APPROVAL = 'outstanding_balance_approval';
    case PAYMENT_PLAN = 'payment_plan';
    case INSURANCE_PENDING = 'insurance_pending';
    case CORPORATE_GUARANTEE = 'corporate_guarantee';
    case MANAGEMENT_APPROVAL = 'management_approval';

    public function basis(): VisitFinancialClearanceBasis
    {
        return match ($this) {
            self::OUTSTANDING_BALANCE_APPROVAL => VisitFinancialClearanceBasis::APPROVED_OUTSTANDING_BALANCE,
            self::PAYMENT_PLAN => VisitFinancialClearanceBasis::APPROVED_PAYMENT_PLAN,
            self::INSURANCE_PENDING => VisitFinancialClearanceBasis::INSURANCE_PENDING_APPROVED,
            self::CORPORATE_GUARANTEE => VisitFinancialClearanceBasis::CORPORATE_GUARANTEE,
            self::MANAGEMENT_APPROVAL => VisitFinancialClearanceBasis::MANAGEMENT_APPROVAL,
        };
    }
}
