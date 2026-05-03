<?php

namespace App\Enums;

enum VisitStatus: string
{
    case SCHEDULED = 'scheduled';
    case CONFIRMED = 'confirmed';
    case REGISTERED = 'registered';
    case WAITING = 'waiting';
    case TRIAGE = 'triage';
    // Post-triage workflow states
    case WAITING_CONSULTATION = 'waiting_consultation';
    case CONSULTING = 'consulting';
    case REFERRED_CONSULTATION = 'referred_consultation';
    case WAITING_INVESTIGATION = 'waiting_investigation';
    case LAB = 'lab';
    case PHARMACY = 'pharmacy';
    case BILLING = 'billing';
    case ADMITTING = 'admitting';
    case ADMITTED = 'admitted';
    case DISCHARGING = 'discharging';
    case DISCHARGED = 'discharged';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case RESCHEDULED = 'rescheduled';
    case NO_SHOW = 'no_show';
    case EMERGENCY = 'emergency';
    case INPATIENT = 'inpatient';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Scheduled',
            self::CONFIRMED => 'Confirmed',
            self::REGISTERED => 'Registered',
            self::WAITING => 'Waiting',
            self::TRIAGE => 'Triage',
            self::WAITING_CONSULTATION => 'Waiting Consultation',
            self::CONSULTING => 'Consulting',
            self::REFERRED_CONSULTATION => 'Referred — Awaiting Consult',
            self::WAITING_INVESTIGATION => 'Waiting Investigation',
            self::LAB => 'Laboratory',
            self::PHARMACY => 'Pharmacy',
            self::BILLING => 'Billing',
            self::ADMITTING => 'Admitting',
            self::ADMITTED => 'Admitted',
            self::DISCHARGING => 'Discharging',
            self::DISCHARGED => 'Discharged',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::RESCHEDULED => 'Rescheduled',
            self::NO_SHOW => 'No Show',
            self::EMERGENCY => 'Emergency',
            self::INPATIENT => 'Inpatient',
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
            self::WAITING_CONSULTATION => 'primary',
            self::CONSULTING => 'primary',
            self::REFERRED_CONSULTATION => 'indigo',
            self::WAITING_INVESTIGATION => 'purple',
            self::LAB => 'purple',
            self::PHARMACY => 'orange',
            self::BILLING => 'dark',
            self::ADMITTING => 'teal',
            self::ADMITTED => 'info',
            self::DISCHARGING => 'warning',
            self::DISCHARGED => 'success',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::RESCHEDULED => 'warning',
            self::NO_SHOW => 'dark',
            self::EMERGENCY => 'danger',
            self::INPATIENT => 'teal',
        };
    }

    /**
     * Get valid next statuses from current status.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::SCHEDULED   => [self::CONFIRMED, self::REGISTERED, self::CANCELLED, self::RESCHEDULED, self::NO_SHOW],
            self::CONFIRMED   => [self::REGISTERED, self::CANCELLED, self::RESCHEDULED, self::NO_SHOW],
            self::REGISTERED  => [self::WAITING, self::CANCELLED],
            self::WAITING     => [self::TRIAGE, self::CANCELLED, self::RESCHEDULED],
            // Triage transitions are handled by TriageController (processTriage) — manual transitions disabled
            self::TRIAGE      => [self::CONSULTING, self::WAITING_CONSULTATION, self::EMERGENCY, self::INPATIENT, self::CANCELLED],
            self::WAITING_CONSULTATION => [self::CONSULTING, self::CANCELLED],
            self::CONSULTING  => [self::REFERRED_CONSULTATION, self::WAITING_INVESTIGATION, self::LAB, self::PHARMACY, self::BILLING, self::ADMITTING, self::ADMITTED, self::COMPLETED, self::CANCELLED],
            self::ADMITTING   => [self::ADMITTED, self::CONSULTING, self::CANCELLED],
            self::REFERRED_CONSULTATION => [self::CONSULTING, self::CANCELLED],
            self::WAITING_INVESTIGATION => [self::LAB, self::CANCELLED],
            self::LAB         => [self::CONSULTING, self::WAITING_CONSULTATION, self::PHARMACY, self::CANCELLED],
            self::PHARMACY    => [self::BILLING, self::COMPLETED, self::CANCELLED],
            self::BILLING     => [self::COMPLETED, self::DISCHARGED, self::CANCELLED],
            self::ADMITTED    => [self::CONSULTING, self::LAB, self::PHARMACY, self::DISCHARGING],
            self::DISCHARGING => [self::BILLING, self::ADMITTED],
            self::EMERGENCY   => [self::ADMITTED, self::CONSULTING, self::COMPLETED, self::CANCELLED],
            self::INPATIENT   => [self::ADMITTING, self::ADMITTED, self::CANCELLED],
            self::DISCHARGED  => [],
            self::COMPLETED   => [],
            self::CANCELLED   => [],
            self::RESCHEDULED => [],
            self::NO_SHOW     => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
