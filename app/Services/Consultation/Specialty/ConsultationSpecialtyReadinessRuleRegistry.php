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
                $this->complaint('presenting_problem_recorded', 'presenting_problem'),
                $this->entryAny('pain_assessment_recorded', 'pain_assessment', ['pain_score', 'pain_location', 'pain_character']),
                $this->entryAny('physical_assessment_recorded', 'physical_assessment', ['range_of_motion', 'muscle_strength', 'posture', 'gait', 'balance', 'assessment_notes']),
                $this->entryAny('treatment_plan_recorded', 'treatment_plan', ['treatment_goals', 'modalities', 'session_frequency', 'number_of_sessions', 'expected_duration']),
                $this->custom('session_schedule_missing', 'treatment_plan', 'warning'),
                $this->custom('home_exercise_plan_missing', 'home_exercise_plan', 'warning'),
            ],
            'ophthalmology' => [
                $this->complaint('eye_complaint_recorded', 'eye_complaint'),
                $this->entryAny('visual_acuity_recorded', 'visual_acuity', ['right_eye_unaided', 'left_eye_unaided', 'right_eye_corrected', 'left_eye_corrected', 'right_eye_pinhole', 'left_eye_pinhole']),
                $this->entryAny('eye_examination_recorded', 'eye_examination', ['lids', 'conjunctiva', 'cornea', 'anterior_chamber', 'pupil', 'lens', 'fundus', 'retina', 'optic_disc', 'examination_notes']),
                $this->core('diagnosis_recorded', 'core_diagnosis'),
                $this->custom('iop_missing', 'iop', 'warning'),
                $this->custom('follow_up_missing', 'follow_up', 'warning'),
            ],
            'dental' => [
                $this->complaint('dental_complaint_recorded', 'dental_complaint'),
                $this->custom('oral_or_tooth_exam_recorded', 'tooth_chart', 'blocking'),
                // Canonical shared sections anchor these rules; the checks
                // still honor legacy dental_diagnosis/dental_procedures/
                // dental_xray entries saved before Phase 16.5.
                $this->custom('dental_diagnosis_recorded', 'diagnosis', 'blocking'),
                $this->custom('procedure_or_plan_recorded', 'procedures', 'blocking'),
                $this->custom('consent_obtained_if_required', 'consent', 'blocking', 'consent_required_missing'),
                $this->custom('xray_missing_if_extraction_planned', 'investigations', 'warning'),
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
                $this->complaint('gyne_complaint_recorded', 'gyne_complaint'),
                $this->entryAny('menstrual_history_recorded', 'menstrual_history', ['lmp', 'cycle_length', 'bleeding_pattern', 'menopause_status']),
                $this->entryAny('pelvic_examination_recorded', 'pelvic_examination', ['external_findings', 'speculum_findings', 'bimanual_findings', 'exam_notes']),
                $this->core('diagnosis_recorded', 'core_diagnosis'),
                $this->custom('follow_up_missing', 'follow_up', 'warning'),
            ],
            'ent' => [
                $this->complaint('ent_complaint_recorded', 'ent_complaint'),
                $this->entryAny('ent_assessment_recorded', 'ear_assessment', ['ear_pain', 'ear_discharge', 'hearing_loss', 'otoscopy_right', 'otoscopy_left']),
                $this->core('diagnosis_recorded', 'core_diagnosis'),
                $this->custom('follow_up_missing', 'follow_up', 'warning'),
            ],
            'pediatrics' => [
                $this->complaint('pediatric_complaint_recorded', 'pediatric_complaint'),
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
                $this->complaint('ortho_complaint_recorded', 'ortho_complaint'),
                $this->entryAny('joint_limb_examination_recorded', 'joint_limb_examination', ['deformity', 'swelling', 'tenderness', 'range_of_motion', 'exam_notes']),
                $this->entryAny('neurovascular_status_recorded', 'neurovascular_status', ['pulse_present', 'capillary_refill', 'sensation', 'motor_function']),
                // Satisfied by core procedures/treatments/notes OR a legacy
                // procedure_plan entry — hiding the duplicate section must
                // not create an impossible blocker.
                $this->custom('procedure_plan_recorded', 'procedures', 'blocking', 'procedure_plan_missing'),
                $this->custom('follow_up_missing', 'follow_up', 'warning'),
            ],
            'surgery' => [
                $this->complaint('surgical_complaint_recorded', 'surgical_complaint'),
                $this->entryAny('surgical_examination_recorded', 'local_or_abdominal_exam', ['inspection', 'palpation', 'tenderness', 'mass', 'exam_notes']),
                $this->custom('procedure_plan_recorded', 'procedures', 'blocking', 'procedure_plan_missing'),
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

    /**
     * Phase 16.6: the patient's main complaint is always captured through
     * the canonical core complaints pane. This rule is satisfied by a real
     * complaint record, or (for compatibility) by a legacy specialty entry
     * saved under the profile's old complaint-like section key before the
     * section was canonicalised.
     */
    private function complaint(string $key, ?string $legacySectionKey = null): array
    {
        return [
            'key' => $key,
            'label' => __('consultation_specialties.readiness.'.$key),
            'section_key' => 'complaints',
            'source' => 'core_complaint',
            'severity' => 'blocking',
            'legacy_section_key' => $legacySectionKey,
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
