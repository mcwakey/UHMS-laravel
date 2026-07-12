<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Domain error for the per-visit payment-arrangement workflow (Payment Timing
 * Policy Phase 7). Each factory carries a stable machine code so controllers can
 * map to localised, user-safe messages.
 */
class VisitPaymentArrangementException extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode = 'invalid')
    {
        parent::__construct($message);
    }

    public static function duplicatePending(): self
    {
        return new self('This visit already has a pending payment-arrangement request.', 'duplicate_pending');
    }

    public static function notPending(): self
    {
        return new self('The arrangement is no longer pending.', 'not_pending');
    }

    public static function notApproved(): self
    {
        return new self('The arrangement is not currently approved.', 'not_approved');
    }

    public static function selfApproval(): self
    {
        return new self('The requester cannot approve their own arrangement.', 'self_approval');
    }

    public static function separateApproverRequired(): self
    {
        return new self('This arrangement requires a separate finance approver.', 'separate_approver_required');
    }

    public static function staleRisk(): self
    {
        return new self('The patient risk context changed since the request; explicit confirmation is required.', 'stale_risk');
    }

    public static function nothingToRestore(): self
    {
        return new self('There is no current approved arrangement to restore from.', 'nothing_to_restore');
    }

    public static function visitNotEligible(): self
    {
        return new self('This visit is not eligible for a payment-arrangement request.', 'visit_not_eligible');
    }
}
