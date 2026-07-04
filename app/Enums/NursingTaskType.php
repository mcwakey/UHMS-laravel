<?php

namespace App\Enums;

enum NursingTaskType: string
{
    case VITALS = 'vitals';
    case MEDICATION = 'medication';
    case OBSERVATION = 'observation';
    case WOUND_CARE = 'wound_care';
    case MOBILIZATION = 'mobilization';
    case FEEDING = 'feeding';
    case HYGIENE = 'hygiene';
    case ISOLATION_REVIEW = 'isolation_review';
    case DISCHARGE_PREPARATION = 'discharge_preparation';
    case OTHER = 'other';

    public function label(): string
    {
        return __('admissions.nursing_task_types.' . $this->value);
    }
}
