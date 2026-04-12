<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case PARTIALLY_APPROVED = 'partially_approved';
    case REJECTED = 'rejected';
    case PAID = 'paid';
    case APPEALED = 'appealed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::UNDER_REVIEW => 'Under Review',
            self::APPROVED => 'Approved',
            self::PARTIALLY_APPROVED => 'Partially Approved',
            self::REJECTED => 'Rejected',
            self::PAID => 'Paid',
            self::APPEALED => 'Appealed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::SUBMITTED => 'info',
            self::UNDER_REVIEW => 'warning',
            self::APPROVED => 'success',
            self::PARTIALLY_APPROVED => 'orange',
            self::REJECTED => 'danger',
            self::PAID => 'primary',
            self::APPEALED => 'dark',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::SUBMITTED, self::REJECTED],
            self::SUBMITTED => [self::UNDER_REVIEW, self::REJECTED],
            self::UNDER_REVIEW => [self::APPROVED, self::PARTIALLY_APPROVED, self::REJECTED],
            self::APPROVED => [self::PAID],
            self::PARTIALLY_APPROVED => [self::PAID, self::APPEALED],
            self::REJECTED => [self::APPEALED, self::DRAFT],
            self::PAID => [],
            self::APPEALED => [self::UNDER_REVIEW, self::REJECTED],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
