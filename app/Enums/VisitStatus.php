<?php

namespace App\Enums;

enum VisitStatus: string
{
    case SCHEDULED = 'scheduled';
    case CONFIRMED = 'confirmed';
    case REGISTERED = 'registered';
    case WAITING = 'waiting';
    case TRIAGE = 'triage';
    case CONSULTING = 'consulting';
    case LAB = 'lab';
    case PHARMACY = 'pharmacy';
    case BILLING = 'billing';
    case ADMITTED = 'admitted';
    case DISCHARGING = 'discharging';
    case DISCHARGED = 'discharged';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case RESCHEDULED = 'rescheduled';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Scheduled',
            self::CONFIRMED => 'Confirmed',
            self::REGISTERED => 'Registered',
            self::WAITING => 'Waiting',
            self::TRIAGE => 'Triage',
            self::CONSULTING => 'Consulting',
            self::LAB => 'Laboratory',
            self::PHARMACY => 'Pharmacy',
            self::BILLING => 'Billing',
            self::ADMITTED => 'Admitted',
            self::DISCHARGING => 'Discharging',
            self::DISCHARGED => 'Discharged',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::RESCHEDULED => 'Rescheduled',
            self::NO_SHOW => 'No Show',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SCHEDULED => 'secondary',
            self::CONFIRMED => 'info',
            self::REGISTERED => 'secondary',
            self::WAITING => 'warning',
            self::TRIAGE => 'info',
            self::CONSULTING => 'primary',
            self::LAB => 'purple',
            self::PHARMACY => 'orange',
            self::BILLING => 'dark',
            self::ADMITTED => 'info',
            self::DISCHARGING => 'warning',
            self::DISCHARGED => 'success',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::RESCHEDULED => 'warning',
            self::NO_SHOW => 'dark',
        };
    }

    /**
     * Get valid next statuses from current status.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::SCHEDULED => [self::CONFIRMED, self::REGISTERED, self::CANCELLED, self::RESCHEDULED, self::NO_SHOW],
            self::CONFIRMED => [self::REGISTERED, self::CANCELLED, self::RESCHEDULED, self::NO_SHOW],
            self::REGISTERED => [self::WAITING, self::CANCELLED],
            self::WAITING => [self::TRIAGE, self::CONSULTING, self::CANCELLED],
            self::TRIAGE => [self::WAITING, self::CONSULTING, self::CANCELLED],
            self::CONSULTING => [self::LAB, self::PHARMACY, self::BILLING, self::ADMITTED, self::COMPLETED, self::CANCELLED],
            self::LAB => [self::CONSULTING, self::PHARMACY, self::CANCELLED],
            self::PHARMACY => [self::BILLING, self::COMPLETED, self::CANCELLED],
            self::BILLING => [self::COMPLETED, self::DISCHARGED, self::CANCELLED],
            self::ADMITTED => [self::CONSULTING, self::LAB, self::PHARMACY, self::DISCHARGING],
            self::DISCHARGING => [self::BILLING, self::ADMITTED],
            self::DISCHARGED => [],
            self::COMPLETED => [],
            self::CANCELLED => [],
            self::RESCHEDULED => [],
            self::NO_SHOW => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
