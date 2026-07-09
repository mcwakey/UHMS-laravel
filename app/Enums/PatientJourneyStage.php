<?php

namespace App\Enums;

/**
 * The ordered, high-level stages of a patient's journey through the hospital.
 * Derived from the existing VisitStatus workflow + related operational records —
 * this enum introduces NO new storage. Labels are translatable
 * (`lang/{locale}/journey.php`); delay thresholds live in `config/journey.php`.
 */
enum PatientJourneyStage: string
{
    case REGISTERED = 'registered';
    case CHECKED_IN = 'checked_in';
    case TRIAGE = 'triage';
    case CONSULTATION = 'consultation';
    case INVESTIGATION = 'investigation';
    case PROCEDURE = 'procedure';
    case TREATMENT = 'treatment';
    case PHARMACY = 'pharmacy';
    case ADMISSION = 'admission';
    case DISCHARGE = 'discharge';
    case COMPLETED = 'completed';

    /** Canonical order along the journey. */
    public function order(): int
    {
        return match ($this) {
            self::REGISTERED => 1,
            self::CHECKED_IN => 2,
            self::TRIAGE => 3,
            self::CONSULTATION => 4,
            self::INVESTIGATION => 5,
            self::PROCEDURE => 6,
            self::TREATMENT => 7,
            self::PHARMACY => 8,
            self::ADMISSION => 9,
            self::DISCHARGE => 10,
            self::COMPLETED => 11,
        };
    }

    public function translatedLabel(): string
    {
        return __('journey.stages.'.$this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::REGISTERED => 'ti-user-plus',
            self::CHECKED_IN => 'ti-login',
            self::TRIAGE => 'ti-activity-heartbeat',
            self::CONSULTATION => 'ti-stethoscope',
            self::INVESTIGATION => 'ti-flask',
            self::PROCEDURE => 'ti-medical-cross',
            self::TREATMENT => 'ti-heartbeat',
            self::PHARMACY => 'ti-prescription',
            self::ADMISSION => 'ti-bed',
            self::DISCHARGE => 'ti-door-exit',
            self::COMPLETED => 'ti-circle-check',
        };
    }

    /** @return list<self> stages in canonical order. */
    public static function ordered(): array
    {
        $cases = self::cases();
        usort($cases, fn (self $a, self $b) => $a->order() <=> $b->order());

        return $cases;
    }

    /**
     * Map a visit's workflow status onto a journey stage. Terminal/exit statuses
     * (cancelled, no-show, abandoned, deceased, rescheduled) return null — the
     * patient left the forward journey.
     */
    public static function fromVisitStatus(VisitStatus $status): ?self
    {
        return match ($status) {
            VisitStatus::CREATED, VisitStatus::SCHEDULED, VisitStatus::CONFIRMED, VisitStatus::REGISTERED => self::REGISTERED,
            VisitStatus::WALKED_IN, VisitStatus::CHECKED_IN, VisitStatus::QUEUED => self::CHECKED_IN,
            VisitStatus::TRIAGE => self::TRIAGE,
            VisitStatus::WAITING, VisitStatus::CONSULTING, VisitStatus::REFERRED_CONSULTATION, VisitStatus::ACTIVE, VisitStatus::EMERGENCY => self::CONSULTATION,
            VisitStatus::WAITING_INVESTIGATION, VisitStatus::LAB => self::INVESTIGATION,
            VisitStatus::PHARMACY, VisitStatus::BILLING => self::PHARMACY,
            VisitStatus::ADMITTING, VisitStatus::ADMITTED, VisitStatus::INPATIENT => self::ADMISSION,
            VisitStatus::DISCHARGING, VisitStatus::DISCHARGED => self::DISCHARGE,
            VisitStatus::COMPLETED => self::COMPLETED,
            default => null,
        };
    }

    /** Statuses that mean the patient has left the forward journey. */
    public static function isTerminal(VisitStatus $status): bool
    {
        return in_array($status, [
            VisitStatus::CANCELLED, VisitStatus::NO_SHOW, VisitStatus::ABANDONED,
            VisitStatus::DECEASED, VisitStatus::RESCHEDULED,
        ], true);
    }
}
