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
            'obstetrics' => [
                $this->entryAny('obstetric_history_recorded', 'obstetric_history', ['gravida', 'para', 'previous_complications']),
                $this->entryAny('current_pregnancy_recorded', 'current_pregnancy', ['pregnancy_confirmed', 'booking_status', 'current_complaints']),
                $this->entryAny('fetal_assessment_recorded', 'fetal_assessment', ['fundal_height', 'fetal_heart_rate', 'fetal_movement', 'presentation']),
                $this->entryAny('risk_assessment_recorded', 'risk_assessment', ['risk_level', 'risk_factors', 'action_plan']),
                $this->entryAny('birth_plan_recorded', 'birth_plan', ['planned_place', 'delivery_plan', 'next_visit_date']),
                $this->custom('follow_up_missing', 'follow_up', 'warning'),
            ],
            'gynecology' => [
                $this->entryAny('gyne_complaint_recorded', 'gyne_complaint', ['complaint_text', 'duration']),
                $this->entryAny('menstrual_history_recorded', 'menstrual_history', ['lmp', 'cycle_length', 'bleeding_pattern', 'menopause_status']),
                $this->entryAny('pelvic_examination_recorded', 'pelvic_examination', ['external_findings', 'speculum_findings', 'bimanual_findings', 'exam_notes']),
                $this->core('diagnosis_recorded', 'core_diagnosis'),
                $this->custom('follow_up_missing', 'follow_up', 'warning'),
            ],
            'ent' => [
                $this->entryAny('ent_complaint_recorded', 'ent_complaint', ['complaint_text', 'duration', 'side']),
                $this->entryAny('ent_assessment_recorded', 'ear_assessment', ['ear_pain', 'ear_discharge', 'hearing_loss', 'otoscopy_right', 'otoscopy_left']),
                $this->core('diagnosis_recorded', 'core_diagnosis'),
                $this->custom('follow_up_missing', 'follow_up', 'warning'),
            ],
            'pediatrics' => [
                $this->entryAny('pediatric_complaint_recorded', 'pediatric_complaint', ['complaint_text', 'duration', 'danger_signs']),
                $this->entryAny('growth_assessment_recorded', 'growth_assessment', ['weight', 'height', 'muac', 'growth_concern']),
                $this->entryAny('pediatric_examination_recorded', 'pediatric_examination', ['general_appearance', 'hydration', 'respiratory', 'cardiovascular', 'exam_notes']),
                $this->entryAny('caregiver_instructions_recorded', 'caregiver_instructions', ['instructions', 'danger_signs', 'follow_up_date']),
                $this->core('diagnosis_recorded', 'core_diagnosis'),
            ],
            'emergency' => [
                $this->entryAny('triage_summary_recorded', 'triage_summary', ['triage_category', 'arrival_mode', 'chief_risk']),
                $this->entryAny('primary_survey_recorded', 'primary_survey', ['airway', 'breathing', 'circulation', 'disability', 'gcs']),
                $this->entryAny('vitals_monitoring_recorded', 'vitals_monitoring', ['blood_pressure', 'pulse', 'respiratory_rate', 'spo2']),
                $this->entryAny('disposition_recorded', 'disposition', ['disposition', 'admit_to', 'refer_to', 'disposition_notes']),
                $this->custom('handover_missing', 'handover', 'warning'),
            ],
            'orthopedics' => [
                $this->entryAny('ortho_complaint_recorded', 'ortho_complaint', ['complaint_text', 'affected_limb', 'duration']),
                $this->entryAny('joint_limb_examination_recorded', 'joint_limb_examination', ['deformity', 'swelling', 'tenderness', 'range_of_motion', 'exam_notes']),
                $this->entryAny('neurovascular_status_recorded', 'neurovascular_status', ['pulse_present', 'capillary_refill', 'sensation', 'motor_function']),
                $this->entryAny('procedure_plan_recorded', 'procedure_plan', ['procedure_planned', 'procedure_done', 'notes']),
                $this->custom('follow_up_missing', 'follow_up', 'warning'),
            ],
            'surgery' => [
                $this->entryAny('surgical_complaint_recorded', 'surgical_complaint', ['complaint_text', 'duration', 'associated_symptoms']),
                $this->entryAny('surgical_examination_recorded', 'local_or_abdominal_exam', ['inspection', 'palpation', 'tenderness', 'mass', 'exam_notes']),
                $this->entryAny('procedure_plan_recorded', 'procedure_plan', ['procedure_planned', 'procedure_done', 'anaesthesia_plan', 'notes']),
                $this->custom('consent_obtained_if_required', 'consent', 'blocking', 'consent_required_missing'),
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
