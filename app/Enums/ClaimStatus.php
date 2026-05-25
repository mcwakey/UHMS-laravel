<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case DRAFT = 'draft';
    case READY = 'ready';
    case SUBMITTED = 'submitted';
    case ACKNOWLEDGED = 'acknowledged';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case PARTIALLY_APPROVED = 'partially_approved';
    case REJECTED = 'rejected';
    case RESUBMITTED = 'resubmitted';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    case APPEALED = 'appealed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::READY => 'Ready',
            self::SUBMITTED => 'Submitted',
            self::ACKNOWLEDGED => 'Acknowledged',
            self::UNDER_REVIEW => 'Under Review',
            self::APPROVED => 'Approved',
            self::PARTIALLY_APPROVED => 'Partially Approved',
            self::REJECTED => 'Rejected',
            self::RESUBMITTED => 'Resubmitted',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::PAID => 'Paid',
            self::CANCELLED => 'Cancelled',
            self::APPEALED => 'Appealed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::READY => 'primary',
            self::SUBMITTED => 'info',
            self::ACKNOWLEDGED => 'info',
            self::UNDER_REVIEW => 'warning',
            self::APPROVED => 'success',
            self::PARTIALLY_APPROVED => 'orange',
            self::REJECTED => 'danger',
            self::RESUBMITTED => 'dark',
            self::PARTIALLY_PAID => 'info',
            self::PAID => 'primary',
            self::CANCELLED => 'secondary',
            self::APPEALED => 'dark',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::READY, self::SUBMITTED, self::REJECTED, self::CANCELLED],
            self::READY => [self::SUBMITTED, self::DRAFT, self::CANCELLED],
            self::SUBMITTED => [self::ACKNOWLEDGED, self::UNDER_REVIEW, self::REJECTED],
            self::ACKNOWLEDGED => [self::UNDER_REVIEW, self::REJECTED],
            self::UNDER_REVIEW => [self::APPROVED, self::PARTIALLY_APPROVED, self::REJECTED],
            self::APPROVED => [self::PARTIALLY_PAID, self::PAID],
            self::PARTIALLY_APPROVED => [self::PARTIALLY_PAID, self::PAID, self::APPEALED],
            self::REJECTED => [self::APPEALED, self::DRAFT, self::RESUBMITTED],
            self::RESUBMITTED => [self::ACKNOWLEDGED, self::UNDER_REVIEW, self::REJECTED],
            self::PARTIALLY_PAID => [self::PAID],
            self::PAID => [],
            self::CANCELLED => [],
            self::APPEALED => [self::UNDER_REVIEW, self::REJECTED],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
