<?php

namespace App\Enums;

/**
 * Lifecycle status of a per-visit payment arrangement (Payment Timing Policy
 * Phase 7). Status is administrative only — it NEVER controls a payment gate in
 * Phase 7.
 */
enum VisitPaymentArrangementStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';
    case REVOKED = 'revoked';
    case EXPIRED = 'expired';
    case REPLACED = 'replaced';

    public function label(): string
    {
        return __('visit_payment_arrangement.statuses.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::WITHDRAWN => 'secondary',
            self::REVOKED => 'dark',
            self::EXPIRED => 'secondary',
            self::REPLACED => 'info',
        };
    }

    /** A terminal status cannot transition further. */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::PENDING, self::APPROVED => false,
            default => true,
        };
    }

    /** Statuses reached only after a prior approval. */
    public function isPostApproval(): bool
    {
        return match ($this) {
            self::APPROVED, self::REVOKED, self::EXPIRED, self::REPLACED => true,
            default => false,
        };
    }
}
