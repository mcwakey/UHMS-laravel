<?php

namespace App\Enums;

enum VisitFinancialClearanceBasis: string
{
    case FULLY_SETTLED = 'fully_settled';
    case ZERO_PATIENT_RESPONSIBILITY = 'zero_patient_responsibility';
    case FULLY_INSURED = 'fully_insured';
    case FULLY_SPONSORED = 'fully_sponsored';
    case CORPORATE_GUARANTEE = 'corporate_guarantee';
    case INSURANCE_PENDING_APPROVED = 'insurance_pending_approved';
    case APPROVED_OUTSTANDING_BALANCE = 'approved_outstanding_balance';
    case APPROVED_PAYMENT_PLAN = 'approved_payment_plan';
    case MANAGEMENT_APPROVAL = 'management_approval';
}
