<?php

namespace App\Enums;

enum NursingNoteType: string
{
    case GENERAL = 'general';
    case HANDOVER = 'handover';
    case OBSERVATION = 'observation';
    case MEDICATION = 'medication';
    case WOUND = 'wound';
    case FALL_RISK = 'fall_risk';
    case ISOLATION = 'isolation';
    case DISCHARGE_PREPARATION = 'discharge_preparation';

    public function label(): string
    {
        return __('admissions.nursing_note_types.' . $this->value);
    }
}
