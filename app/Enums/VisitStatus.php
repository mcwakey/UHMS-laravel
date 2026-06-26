<?php

namespace App\Enums;

enum VisitStatus: string
{
    // Pre-arrival / arrival lifecycle
    case CREATED = 'created';
    case SCHEDULED = 'scheduled';
    case CONFIRMED = 'confirmed';
    case REGISTERED = 'registered';
    case WALKED_IN = 'walked_in';     // direct visit, patient present
    case CHECKED_IN = 'checked_in';   // appointment visit, patient present
    case QUEUED = 'queued';           // in the triage queue (was WAITING = 'waiting')
    case ACTIVE = 'active';
    case TRIAGE = 'triage';
    // Post-triage workflow states
    case WAITING = 'waiting';         // waiting for consultation (was WAITING_CONSULTATION)
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
    case ABANDONED = 'abandoned';     // patient left before completing the workflow
    case EMERGENCY = 'emergency';
    case INPATIENT = 'inpatient';
    case DECEASED = 'deceased';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Created',
            self::SCHEDULED => 'Scheduled',
            self::CONFIRMED => 'Confirmed',
            self::REGISTERED => 'Registered',
            self::WALKED_IN => 'Walked In',
            self::CHECKED_IN => 'Checked In',
            self::QUEUED => 'Queued',
            self::ACTIVE => 'Active',
            self::TRIAGE => 'Triage',
            self::WAITING => 'Waiting',
            self::CONSULTING => 'Consulting',
            self::REFERRED_CONSULTATION => 'Referred — Awaiting Consult',
            self::WAITING_INVESTIGATION => 'Waiting Investigation',
            self::LAB => 'Laboratory',
            self::PHARMACY => 'Pharmacy',
            self::BILLING => 'Billing',
            self::ADMITTING => 'Admit Patient',
            self::ADMITTED => 'Admitted',
            self::DISCHARGING => 'Discharging',
            self::DISCHARGED => 'Discharged',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::RESCHEDULED => 'Rescheduled',
            self::NO_SHOW => 'No Show',
            self::ABANDONED => 'Abandoned',
            self::EMERGENCY => 'Emergency',
            self::INPATIENT => 'Inpatient',
            self::DECEASED => 'Deceased',
        };
    }

    public function translatedLabel(): string
    {
        return __('statuses.visit.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::CREATED => 'secondary',
            self::SCHEDULED => 'secondary',
            self::CONFIRMED => 'info',
            self::REGISTERED => 'secondary',
            self::WALKED_IN => 'primary',
            self::CHECKED_IN => 'primary',
            self::QUEUED => 'warning',
            self::ACTIVE => 'primary',
            self::TRIAGE => 'info',
            self::WAITING => 'indigo',
            self::CONSULTING => 'primary',
            self::REFERRED_CONSULTATION => 'indigo',
            self::WAITING_INVESTIGATION => 'purple',
            self::LAB => 'purple',
            self::PHARMACY => 'orange',
            self::BILLING => 'dark',
            self::ADMITTING => 'warning',
            self::ADMITTED => 'info',
            self::DISCHARGING => 'warning',
            self::DISCHARGED => 'success',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::RESCHEDULED => 'warning',
            self::NO_SHOW => 'dark',
            self::ABANDONED => 'dark',
            self::EMERGENCY => 'danger',
            self::INPATIENT => 'teal',
            self::DECEASED => 'dark',
        };
    }

    /**
     * Get valid next statuses from current status.
     *
     * Arrival paths are split by source: direct visits use WALKED_IN, appointment
     * visits use CHECKED_IN — there is no crossover between the two.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            // Direct visits arrive via WALKED_IN; appointment visits via CHECKED_IN.
            self::CREATED => [self::WALKED_IN, self::CHECKED_IN, self::CANCELLED, self::ABANDONED],
            self::SCHEDULED => [self::CONFIRMED, self::REGISTERED, self::CHECKED_IN, self::CANCELLED, self::RESCHEDULED, self::NO_SHOW],
            self::CONFIRMED => [self::REGISTERED, self::CHECKED_IN, self::CANCELLED, self::RESCHEDULED, self::NO_SHOW],
            // Arrival states may go straight to ADMITTED for a direct admission
            // (elective / transfer) that bypasses OPD triage.
            self::REGISTERED => [self::WALKED_IN, self::CHECKED_IN, self::QUEUED, self::ADMITTED, self::CANCELLED, self::ABANDONED],
            self::WALKED_IN => [self::QUEUED, self::ADMITTED, self::CANCELLED, self::ABANDONED],
            self::CHECKED_IN => [self::QUEUED, self::ADMITTED, self::CANCELLED, self::ABANDONED],
            self::QUEUED => [self::TRIAGE, self::CANCELLED, self::ABANDONED, self::RESCHEDULED],
            // Triage transitions are handled by TriageController (processTriage) — manual transitions disabled
            self::TRIAGE => [self::WAITING, self::CONSULTING, self::ACTIVE, self::EMERGENCY, self::ADMITTED, self::INPATIENT, self::CANCELLED],
            self::WAITING => [self::CONSULTING, self::CANCELLED, self::ABANDONED],
            self::CONSULTING => [self::ADMITTING, self::COMPLETED, self::DECEASED],
            self::ACTIVE => [self::CONSULTING, self::ADMITTING, self::COMPLETED, self::DECEASED],
            // ADMITTING is the in-progress admission state ("Admit Patient"); completing
            // the admission (e.g. emergency disposition → bed assignment) advances it to
            // ADMITTED. Cancelling or returning to consultation remain valid.
            self::ADMITTING => [self::CONSULTING, self::ADMITTED, self::CANCELLED],
            self::REFERRED_CONSULTATION => [self::CONSULTING, self::ACTIVE, self::CANCELLED],
            self::WAITING_INVESTIGATION => [self::CONSULTING, self::ACTIVE, self::COMPLETED, self::CANCELLED],
            self::LAB => [self::CONSULTING, self::ACTIVE, self::COMPLETED, self::CANCELLED],
            self::PHARMACY => [self::CONSULTING, self::ACTIVE, self::COMPLETED, self::CANCELLED],
            self::BILLING => [self::CONSULTING, self::ACTIVE, self::COMPLETED, self::CANCELLED],
            self::ADMITTED => [self::DISCHARGING, self::DISCHARGED, self::COMPLETED, self::DECEASED],
            self::DISCHARGING => [self::ADMITTED, self::DISCHARGED, self::COMPLETED],
            self::EMERGENCY => [self::ADMITTING, self::ADMITTED, self::WAITING, self::CONSULTING, self::ACTIVE, self::COMPLETED, self::CANCELLED, self::DECEASED],
            self::INPATIENT => [self::ADMITTING, self::ADMITTED, self::CANCELLED],
            self::DISCHARGED => [],
            self::COMPLETED => [],
            self::CANCELLED => [],
            self::RESCHEDULED => [],
            self::NO_SHOW => [],
            self::ABANDONED => [],
            self::DECEASED => [],
        };
    }

    public function isDepartmentMovementStatus(): bool
    {
        return in_array($this, [
            self::WAITING_INVESTIGATION,
            self::LAB,
            self::PHARMACY,
            self::BILLING,
        ], true);
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
