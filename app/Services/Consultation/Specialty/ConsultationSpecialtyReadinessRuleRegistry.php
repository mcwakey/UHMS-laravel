<?php

namespace App\Services\Consultation\Specialty;

use App\Models\ConsultationSpecialtyProfile;

class ConsultationSpecialtyReadinessRuleRegistry
{
    public function rulesForProfile(ConsultationSpecialtyProfile $profile): array
    {
        return $this->rules()[$profile->code] ?? [];
    }

    private function rules(): array
    {
        return [
            'physiotherapy' => [
                $this->entryAny('presenting_problem_recorded', 'presenting_problem', ['problem_description', 'affected_area', 'referral_reason', 'mechanism_of_injury']),
                $this->entryAny('pain_assessment_recorded', 'pain_assessment', ['pain_score', 'pain_location', 'pain_character']),
                $this->entryAny('physical_assessment_recorded', 'physical_assessment', ['range_of_motion', 'muscle_strength', 'posture', 'gait', 'balance', 'assessment_notes']),
                $this->entryAny('treatment_plan_recorded', 'treatment_plan', ['treatment_goals', 'modalities', 'session_frequency', 'number_of_sessions', 'expected_duration']),
                $this->custom('session_schedule_missing', 'treatment_plan', 'warning'),
                $this->custom('home_exercise_plan_missing', 'home_exercise_plan', 'warning'),
            ],
            'ophthalmology' => [
                $this->core('eye_complaint_recorded', 'core_complaint'),
                $this->entryAny('visual_acuity_recorded', 'visual_acuity', ['right_eye_unaided', 'left_eye_unaided', 'right_eye_corrected', 'left_eye_corrected', 'right_eye_pinhole', 'left_eye_pinhole']),
                $this->entryAny('eye_examination_recorded', 'eye_examination', ['lids', 'conjunctiva', 'cornea', 'anterior_chamber', 'pupil', 'lens', 'fundus', 'retina', 'optic_disc', 'examination_notes']),
                $this->core('diagnosis_recorded', 'core_diagnosis'),
                $this->custom('iop_missing', 'iop', 'warning'),
                $this->custom('follow_up_missing', 'follow_up', 'warning'),
            ],
            'dental' => [
                $this->core('dental_complaint_recorded', 'core_complaint'),
                $this->custom('oral_or_tooth_exam_recorded', 'tooth_chart', 'blocking'),
                $this->custom('dental_diagnosis_recorded', 'dental_diagnosis', 'blocking'),
                $this->custom('procedure_or_plan_recorded', 'dental_procedures', 'blocking'),
                $this->custom('consent_obtained_if_required', 'consent', 'blocking', 'consent_required_missing'),
                $this->custom('xray_missing_if_extraction_planned', 'dental_xray', 'warning'),
                $this->custom('follow_up_missing', 'follow_up', 'warning'),
            ],
        ];
    }

    private function core(string $key, string $source): array
    {
        return [
            'key' => $key,
            'label' => __('consultation_specialties.readiness.'.$key),
            'source' => $source,
            'severity' => 'blocking',
            'message' => __('consultation_specialties.readiness.'.str_replace('_recorded', '_missing', $key)),
        ];
    }

    private function entryAny(string $key, string $sectionKey, array $fields): array
    {
        return [
            'key' => $key,
            'label' => __('consultation_specialties.readiness.'.$key),
            'section_key' => $sectionKey,
            'source' => 'specialty_entry',
            'severity' => 'blocking',
            'fields' => $fields,
            'mode' => 'any',
            'message' => __('consultation_specialties.readiness.'.str_replace('_recorded', '_missing', $key)),
        ];
    }

    private function custom(string $key, ?string $sectionKey, string $severity, ?string $messageKey = null): array
    {
        return [
            'key' => $key,
            'label' => __('consultation_specialties.readiness.'.$key),
            'section_key' => $sectionKey,
            'source' => 'custom',
            'severity' => $severity,
            'mode' => 'custom',
            'message' => __('consultation_specialties.readiness.'.($messageKey ?? $key)),
        ];
    }
}
