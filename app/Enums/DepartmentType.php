<?php

namespace App\Enums;

/**
 * Operational classification of a hospital department. This is the canonical,
 * first-level "what does this department do" type used for routing, dashboards,
 * menus, reporting and permissions.
 *
 * Values are stored as plain strings on departments.type (NOT a DB enum), so new
 * cases can be added here without a schema change. All consumers must be
 * exhaustive or carry a safe default — adding a case must never 500 the app.
 */
enum DepartmentType: string
{
    // Clinical / patient-facing
    case CONSULTATION = 'consultation';
    case EMERGENCY = 'emergency';
    case INVESTIGATION = 'investigation';
    case RADIOLOGY = 'radiology';
    case PROCEDURE = 'procedure';
    case THEATRE = 'theatre';
    case TREATMENT = 'treatment';
    case NURSING = 'nursing';
    case PHARMACY = 'pharmacy';
    case INPATIENT = 'inpatient';
    case MATERNITY = 'maternity';
    case BLOOD_BANK = 'blood_bank';
    case MORTUARY = 'mortuary';
    case AMBULANCE = 'ambulance';

    // Operational / non-clinical
    case RECORDS = 'records';
    case FINANCE = 'finance';
    case STORES = 'stores';
    case SUPPORT = 'support';
    case ADMINISTRATIVE = 'administrative';

    public function label(): string
    {
        return match ($this) {
            self::CONSULTATION => 'Consultation',
            self::EMERGENCY => 'Emergency',
            self::INVESTIGATION => 'Investigation',
            self::RADIOLOGY => 'Radiology',
            self::PROCEDURE => 'Procedure',
            self::THEATRE => 'Theatre',
            self::TREATMENT => 'Treatment',
            self::NURSING => 'Nursing',
            self::PHARMACY => 'Pharmacy',
            self::INPATIENT => 'Inpatient',
            self::MATERNITY => 'Maternity',
            self::BLOOD_BANK => 'Blood Bank',
            self::MORTUARY => 'Mortuary',
            self::AMBULANCE => 'Ambulance',
            self::RECORDS => 'Records',
            self::FINANCE => 'Finance',
            self::STORES => 'Stores',
            self::SUPPORT => 'Support',
            self::ADMINISTRATIVE => 'Administrative',
        };
    }

    public function translatedLabel(): string
    {
        $key = 'departments.types.' . $this->value;

        return \Illuminate\Support\Facades\Lang::has($key) ? __($key) : $this->label();
    }

    public function color(): string
    {
        return match ($this) {
            self::CONSULTATION => 'primary',
            self::EMERGENCY => 'danger',
            self::INVESTIGATION => 'info',
            self::RADIOLOGY => 'teal',
            self::PROCEDURE => 'warning',
            self::THEATRE => 'purple',
            self::TREATMENT => 'success',
            self::NURSING => 'indigo',
            self::PHARMACY => 'orange',
            self::INPATIENT => 'primary',
            self::MATERNITY => 'purple',
            self::BLOOD_BANK => 'danger',
            self::MORTUARY => 'dark',
            self::AMBULANCE => 'warning',
            self::RECORDS => 'secondary',
            self::FINANCE => 'success',
            self::STORES => 'secondary',
            self::SUPPORT => 'dark',
            self::ADMINISTRATIVE => 'light',
        };
    }

    /**
     * True for department types that own a clinical, patient-facing workflow
     * (as opposed to back-office / operational departments).
     */
    public function isClinical(): bool
    {
        return in_array($this, [
            self::CONSULTATION, self::EMERGENCY, self::INVESTIGATION, self::RADIOLOGY,
            self::PROCEDURE, self::THEATRE, self::TREATMENT, self::NURSING,
            self::PHARMACY, self::INPATIENT, self::MATERNITY, self::BLOOD_BANK,
        ], true);
    }

    /**
     * Map department type to the VisitStatus a patient takes when sent here.
     * Loose semantics → safe default of ACTIVE for everything that is not an
     * explicit consultation / emergency / admission stop.
     */
    public function toVisitStatus(): \App\Enums\VisitStatus
    {
        return match ($this) {
            self::CONSULTATION => \App\Enums\VisitStatus::WAITING,
            self::EMERGENCY => \App\Enums\VisitStatus::EMERGENCY,
            self::INPATIENT, self::MATERNITY => \App\Enums\VisitStatus::ADMITTED,
            default => \App\Enums\VisitStatus::ACTIVE,
        };
    }
}
