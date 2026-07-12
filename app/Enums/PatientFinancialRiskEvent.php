<?php

namespace App\Enums;

/**
 * Material transition recorded in the immutable patient financial-risk history
 * (Payment Timing Policy Phase 5). Used for human-readable, localised history
 * rendering — history rows are never edited or deleted through the application.
 */
enum PatientFinancialRiskEvent: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case SUBMITTED_FOR_REVIEW = 'submitted_for_review';
    case REVIEW_COMPLETED = 'review_completed';
    case SUSPENDED = 'suspended';
    case REACTIVATED = 'reactivated';
    case CLEARED = 'cleared';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return __('patient_financial_risk.events.'.$this->value);
    }

    /** Corresponding ActivityLog action for this event. */
    public function activityAction(): string
    {
        return 'PATIENT_FINANCIAL_RISK_'.strtoupper($this->value);
    }
}
