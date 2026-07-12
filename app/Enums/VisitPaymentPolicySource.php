<?php

namespace App\Enums;

enum VisitPaymentPolicySource: string
{
    case GLOBAL_DEFAULT = 'global_default';
    case VISIT_TYPE = 'visit_type';
    case PATIENT_RISK = 'patient_risk';
    case INSURANCE = 'insurance';
    case CORPORATE_ACCOUNT = 'corporate_account';
    case MANUAL_OVERRIDE = 'manual_override';
    case EMERGENCY_POLICY = 'emergency_policy';
    case APPROVED_ARRANGEMENT = 'approved_arrangement';

    public function translationKey(): string
    {
        return 'payment_timing.sources.'.$this->value;
    }

    public function label(): string
    {
        return __($this->translationKey());
    }
}
