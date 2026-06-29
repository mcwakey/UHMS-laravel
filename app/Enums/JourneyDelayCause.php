<?php

namespace App\Enums;

/**
 * Why a patient's journey is delayed — the operational reason and, by extension,
 * which department must act. Derived from existing records (visit status + related
 * records); never persisted. Translatable (`lang/{locale}/journey.php`), grouped by
 * domain, with a safe UNKNOWN fallback.
 */
enum JourneyDelayCause: string
{
    case AWAITING_CONSULTATION = 'awaiting_consultation';
    case AWAITING_PAYMENT = 'awaiting_payment';
    case AWAITING_LAB_REQUEST = 'awaiting_lab_request';
    case AWAITING_LAB_RESULT = 'awaiting_lab_result';
    case AWAITING_RADIOLOGY_REQUEST = 'awaiting_radiology_request';
    case AWAITING_RADIOLOGY_RESULT = 'awaiting_radiology_result';
    case AWAITING_PROCEDURE = 'awaiting_procedure';
    case AWAITING_PRESCRIPTION = 'awaiting_prescription';
    case AWAITING_DISPENSING = 'awaiting_dispensing';
    case AWAITING_ADMISSION = 'awaiting_admission';
    case AWAITING_BED = 'awaiting_bed';
    case AWAITING_DISCHARGE = 'awaiting_discharge';
    case AWAITING_CLINICAL_REVIEW = 'awaiting_clinical_review';
    case UNKNOWN = 'unknown';

    /** The delay reason, localized. */
    public function translatedLabel(): string
    {
        return __('journey.cause.'.$this->value);
    }

    /** The human-friendly next action, localized. */
    public function action(): string
    {
        return __('journey.action.'.$this->value);
    }

    /** Department-type domain responsible for resolving this cause. */
    public function ownerType(): ?string
    {
        return match ($this) {
            self::AWAITING_CONSULTATION, self::AWAITING_CLINICAL_REVIEW => 'consultation',
            self::AWAITING_PAYMENT => 'finance',
            self::AWAITING_LAB_REQUEST, self::AWAITING_LAB_RESULT => 'investigation',
            self::AWAITING_RADIOLOGY_REQUEST, self::AWAITING_RADIOLOGY_RESULT => 'radiology',
            self::AWAITING_PROCEDURE => 'theatre',
            self::AWAITING_PRESCRIPTION, self::AWAITING_DISPENSING => 'pharmacy',
            self::AWAITING_ADMISSION, self::AWAITING_BED, self::AWAITING_DISCHARGE => 'ward',
            self::UNKNOWN => null,
        };
    }

    /** Coarse domain grouping. */
    public function group(): string
    {
        return match ($this) {
            self::AWAITING_CONSULTATION, self::AWAITING_CLINICAL_REVIEW => 'clinical',
            self::AWAITING_PAYMENT => 'financial',
            self::AWAITING_LAB_REQUEST, self::AWAITING_LAB_RESULT,
            self::AWAITING_RADIOLOGY_REQUEST, self::AWAITING_RADIOLOGY_RESULT => 'diagnostic',
            self::AWAITING_PROCEDURE => 'procedure',
            self::AWAITING_PRESCRIPTION, self::AWAITING_DISPENSING => 'pharmacy',
            self::AWAITING_ADMISSION, self::AWAITING_BED, self::AWAITING_DISCHARGE => 'ward',
            self::UNKNOWN => 'unknown',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::AWAITING_CONSULTATION, self::AWAITING_CLINICAL_REVIEW => 'ti-stethoscope',
            self::AWAITING_PAYMENT => 'ti-cash',
            self::AWAITING_LAB_REQUEST, self::AWAITING_LAB_RESULT => 'ti-flask',
            self::AWAITING_RADIOLOGY_REQUEST, self::AWAITING_RADIOLOGY_RESULT => 'ti-radioactive',
            self::AWAITING_PROCEDURE => 'ti-medical-cross',
            self::AWAITING_PRESCRIPTION, self::AWAITING_DISPENSING => 'ti-prescription',
            self::AWAITING_ADMISSION, self::AWAITING_BED => 'ti-bed',
            self::AWAITING_DISCHARGE => 'ti-door-exit',
            self::UNKNOWN => 'ti-help-circle',
        };
    }
}
