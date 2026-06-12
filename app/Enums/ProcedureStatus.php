<?php

namespace App\Enums;

enum ProcedureStatus: string
{
    case REQUESTED    = 'requested';
    case ACCEPTED     = 'accepted';
    case BILLED       = 'billed';
    case SCHEDULED    = 'scheduled';
    case PRE_OP       = 'pre_op';
    case ANAESTHESIA  = 'anaesthesia';
    case IN_SURGERY   = 'in_surgery';
    case SURGERY_DONE = 'surgery_done';
    case POST_OP      = 'post_op';
    case COMPLETED    = 'completed';
    case REJECTED     = 'rejected';
    case CANCELLED    = 'cancelled';
    case ON_HOLD      = 'on_hold';
    case RESCHEDULED  = 'rescheduled';

    public function label(): string
    {
        return match ($this) {
            self::REQUESTED    => 'Requested',
            self::ACCEPTED     => 'Accepted',
            self::BILLED       => 'Billed',
            self::SCHEDULED    => 'Scheduled',
            self::PRE_OP       => 'Pre-op',
            self::ANAESTHESIA  => 'Anaesthesia',
            self::IN_SURGERY   => 'In Surgery',
            self::SURGERY_DONE => 'Surgery Done',
            self::POST_OP      => 'Post-op',
            self::COMPLETED    => 'Completed',
            self::REJECTED     => 'Rejected',
            self::CANCELLED    => 'Cancelled',
            self::ON_HOLD      => 'On Hold',
            self::RESCHEDULED  => 'Rescheduled',
        };
    }

    public function translatedLabel(): string
    {
        return __('statuses.theatre.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::REQUESTED    => 'secondary',
            self::ACCEPTED     => 'info',
            self::BILLED       => 'primary',
            self::SCHEDULED    => 'primary',
            self::PRE_OP       => 'warning',
            self::ANAESTHESIA  => 'purple',
            self::IN_SURGERY   => 'danger',
            self::SURGERY_DONE => 'warning',
            self::POST_OP      => 'teal',
            self::COMPLETED    => 'success',
            self::REJECTED     => 'dark',
            self::CANCELLED    => 'dark',
            self::ON_HOLD      => 'secondary',
            self::RESCHEDULED  => 'warning',
        };
    }

    /** Statuses considered "open" / actionable on the theatre dashboard. */
    public static function openStatuses(): array
    {
        return [
            self::REQUESTED, self::ACCEPTED, self::BILLED, self::SCHEDULED,
            self::PRE_OP, self::ANAESTHESIA, self::IN_SURGERY, self::SURGERY_DONE,
            self::POST_OP, self::ON_HOLD, self::RESCHEDULED,
        ];
    }

    /** Closed/terminal statuses. */
    public static function closedStatuses(): array
    {
        return [self::COMPLETED, self::REJECTED, self::CANCELLED];
    }

    /** Allowed forward transitions for the canonical workflow. */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::REQUESTED    => [self::ACCEPTED, self::REJECTED, self::CANCELLED, self::ON_HOLD],
            self::ACCEPTED     => [self::BILLED, self::CANCELLED, self::ON_HOLD],
            self::BILLED       => [self::SCHEDULED, self::CANCELLED, self::ON_HOLD],
            self::SCHEDULED    => [self::PRE_OP, self::RESCHEDULED, self::CANCELLED, self::ON_HOLD],
            self::PRE_OP       => [self::ANAESTHESIA, self::CANCELLED, self::ON_HOLD],
            self::ANAESTHESIA  => [self::IN_SURGERY, self::SURGERY_DONE, self::CANCELLED],
            self::IN_SURGERY   => [self::SURGERY_DONE, self::CANCELLED],
            self::SURGERY_DONE => [self::POST_OP],
            self::POST_OP      => [self::COMPLETED],
            self::RESCHEDULED  => [self::SCHEDULED, self::CANCELLED],
            self::ON_HOLD      => [self::REQUESTED, self::ACCEPTED, self::BILLED, self::SCHEDULED, self::CANCELLED],
            default            => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    public function isOpen(): bool
    {
        return ! in_array($this, self::closedStatuses(), true);
    }

    public function isClosed(): bool
    {
        return in_array($this, self::closedStatuses(), true);
    }
}
