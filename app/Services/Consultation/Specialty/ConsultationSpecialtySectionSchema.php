<?php

namespace App\Services\Consultation\Specialty;

class ConsultationSpecialtySectionSchema
{
    public function structuredSectionKeys(): array
    {
        return array_keys($this->schemas());
    }

    public function hasSchema(string $sectionKey): bool
    {
        return isset($this->schemas()[$sectionKey]);
    }

    public function fieldsFor(string $sectionKey): array
    {
        return $this->schemas()[$sectionKey]['fields'] ?? [];
    }

    public function rulesFor(string $sectionKey): array
    {
        $rules = [];

        foreach ($this->fieldsFor($sectionKey) as $field) {
            $rules[$field['name']] = $field['rules'];

            if (($field['type'] ?? null) === 'array') {
                $rules[$field['name'].'.*'] = ['string', 'max:255'];
            }
        }

        return $rules;
    }

    public function sanitizedEntry(string $sectionKey, array $validated): array
    {
        $entry = [];

        foreach ($this->fieldsFor($sectionKey) as $field) {
            $name = $field['name'];
            if (array_key_exists($name, $validated)) {
                $value = $validated[$name];
                if ($field['type'] === 'boolean') {
                    $value = (bool) $value;
                }
                if ($field['type'] === 'array') {
                    $value = array_values(array_filter((array) $value, fn ($item) => trim((string) $item) !== ''));
                }
                $entry[$name] = $value;
            }
        }

        return array_filter($entry, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    private function schemas(): array
    {
        return [
            'presenting_problem' => ['fields' => [
                $this->textarea('problem_description', 2000), $this->date('onset_date'), $this->text('onset_type', 100),
                $this->textarea('mechanism_of_injury', 1000), $this->text('affected_area', 255), $this->textarea('referral_reason', 1000),
            ]],
            'pain_assessment' => ['fields' => [
                $this->number('pain_score', ['nullable', 'integer', 'min:0', 'max:10'], 0, 10),
                $this->text('pain_location', 255), $this->text('pain_character', 255),
                $this->textarea('aggravating_factors', 1000), $this->textarea('relieving_factors', 1000), $this->text('pain_pattern', 255),
            ]],
            'functional_limitation' => ['fields' => [
                $this->textarea('mobility_limitation', 1000), $this->textarea('work_limitation', 1000), $this->textarea('self_care_limitation', 1000),
                $this->text('walking_tolerance', 255), $this->text('standing_tolerance', 255), $this->textarea('functional_goal', 1000),
            ]],
            'physical_assessment' => ['fields' => [
                $this->textarea('range_of_motion', 1000), $this->textarea('muscle_strength', 1000), $this->textarea('posture', 1000),
                $this->textarea('gait', 1000), $this->textarea('balance', 1000), $this->textarea('special_tests', 1000), $this->textarea('assessment_notes', 2000),
            ]],
            'treatment_plan' => ['fields' => [
                $this->textarea('treatment_goals', 2000), $this->arrayField('modalities'), $this->text('session_frequency', 100),
                $this->number('number_of_sessions', ['nullable', 'integer', 'min:1', 'max:100'], 1, 100),
                $this->text('expected_duration', 100), $this->textarea('precautions', 1000),
            ]],
            'therapy_session' => ['fields' => [
                $this->number('session_number', ['nullable', 'integer', 'min:1', 'max:100'], 1, 100),
                $this->textarea('therapy_given', 2000), $this->textarea('patient_response', 1000),
                $this->number('post_session_pain_score', ['nullable', 'integer', 'min:0', 'max:10'], 0, 10),
                $this->textarea('next_session_plan', 1000),
            ]],
            'home_exercise_plan' => ['fields' => [
                $this->arrayField('exercises'), $this->text('frequency', 100), $this->textarea('instructions', 2000), $this->textarea('warnings', 1000),
            ]],
            'progress_notes' => ['fields' => [
                $this->textarea('progress_summary', 2000), $this->number('improvement_score', ['nullable', 'integer', 'min:0', 'max:100'], 0, 100),
                $this->textarea('barriers', 1000), $this->date('next_review_date'),
            ]],
            'visual_acuity' => ['fields' => [
                $this->text('right_eye_unaided', 50), $this->text('left_eye_unaided', 50), $this->text('right_eye_pinhole', 50), $this->text('left_eye_pinhole', 50),
                $this->text('right_eye_corrected', 50), $this->text('left_eye_corrected', 50), $this->textarea('notes', 1000),
            ]],
            'refraction' => ['fields' => [
                $this->number('right_sphere', ['nullable', 'numeric', 'min:-30', 'max:30'], -30, 30), $this->number('right_cylinder', ['nullable', 'numeric', 'min:-20', 'max:20'], -20, 20),
                $this->number('right_axis', ['nullable', 'integer', 'min:0', 'max:180'], 0, 180), $this->number('right_add', ['nullable', 'numeric', 'min:0', 'max:10'], 0, 10),
                $this->number('left_sphere', ['nullable', 'numeric', 'min:-30', 'max:30'], -30, 30), $this->number('left_cylinder', ['nullable', 'numeric', 'min:-20', 'max:20'], -20, 20),
                $this->number('left_axis', ['nullable', 'integer', 'min:0', 'max:180'], 0, 180), $this->number('left_add', ['nullable', 'numeric', 'min:0', 'max:10'], 0, 10),
                $this->textarea('refraction_notes', 1000),
            ]],
            'iop' => ['fields' => [
                $this->number('right_eye_iop', ['nullable', 'numeric', 'min:0', 'max:80'], 0, 80), $this->number('left_eye_iop', ['nullable', 'numeric', 'min:0', 'max:80'], 0, 80),
                $this->text('method', 100), $this->date('measured_at'), $this->textarea('notes', 1000),
            ]],
            'eye_examination' => ['fields' => [
                $this->textarea('lids', 1000), $this->textarea('conjunctiva', 1000), $this->textarea('cornea', 1000), $this->textarea('anterior_chamber', 1000),
                $this->textarea('pupil', 1000), $this->textarea('lens', 1000), $this->textarea('fundus', 1000), $this->textarea('retina', 1000),
                $this->textarea('optic_disc', 1000), $this->textarea('examination_notes', 2000),
            ]],
            'follow_up' => ['fields' => [
                $this->date('follow_up_date'), $this->textarea('follow_up_reason', 1000), $this->textarea('warning_signs', 1000), $this->textarea('patient_instructions', 2000),
            ]],
            'tooth_chart' => ['fields' => [
                $this->text('tooth_number', 20), $this->text('tooth_surface', 100), $this->text('condition', 255), $this->text('mobility', 100), $this->text('percussion', 100), $this->textarea('notes', 1000),
            ]],
            'oral_examination' => ['fields' => [
                $this->text('oral_hygiene', 255), $this->textarea('gingiva', 1000), $this->textarea('mucosa', 1000), $this->textarea('occlusion', 1000),
                $this->textarea('swelling', 1000), $this->textarea('bleeding', 1000), $this->textarea('examination_notes', 2000),
            ]],
            'dental_diagnosis' => ['fields' => [
                $this->textarea('diagnosis_text', 2000), $this->text('tooth_involved', 100), $this->text('severity', 100), $this->textarea('differential_diagnosis', 1000),
            ]],
            'dental_xray' => ['fields' => [
                $this->text('xray_type', 100), $this->boolean('xray_requested'), $this->textarea('xray_findings', 2000), $this->text('attachment_reference', 255),
            ]],
            'dental_procedures' => ['fields' => [
                $this->textarea('procedure_planned', 1000), $this->textarea('procedure_performed', 1000), $this->text('anaesthesia_used', 255),
                $this->textarea('materials_used', 1000), $this->textarea('post_procedure_notes', 2000),
            ]],
            'consent' => ['fields' => [
                $this->boolean('consent_required'), $this->boolean('consent_obtained'), $this->text('consent_type', 255), $this->textarea('consent_notes', 1000),
            ]],
            'obstetric_history' => ['fields' => [
                $this->number('gravida', ['nullable', 'integer', 'min:0', 'max:30'], 0, 30), $this->number('para', ['nullable', 'integer', 'min:0', 'max:30'], 0, 30),
                $this->number('abortions', ['nullable', 'integer', 'min:0', 'max:30'], 0, 30), $this->number('living_children', ['nullable', 'integer', 'min:0', 'max:30'], 0, 30),
                $this->boolean('previous_c_section'), $this->textarea('previous_complications', 2000),
            ]],
            'current_pregnancy' => ['fields' => [
                $this->boolean('pregnancy_confirmed'), $this->text('booking_status', 100), $this->textarea('current_complaints', 2000),
                $this->textarea('danger_signs', 2000), $this->textarea('high_risk_notes', 2000),
            ]],
            'lmp_edd_gestational_age' => ['fields' => [
                $this->date('lmp'), $this->date('edd'), $this->number('gestational_age_weeks', ['nullable', 'integer', 'min:0', 'max:45'], 0, 45),
                $this->number('gestational_age_days', ['nullable', 'integer', 'min:0', 'max:6'], 0, 6), $this->text('dating_method', 100),
            ]],
            'antenatal_vitals' => ['fields' => [
                $this->text('blood_pressure', 50), $this->number('weight', ['nullable', 'numeric', 'min:0', 'max:300'], 0, 300),
                $this->number('temperature', ['nullable', 'numeric', 'min:20', 'max:45'], 20, 45), $this->number('pulse', ['nullable', 'integer', 'min:0', 'max:250'], 0, 250),
                $this->text('urine_protein', 100), $this->text('urine_glucose', 100),
            ]],
            'fetal_assessment' => ['fields' => [
                $this->text('fundal_height', 100), $this->number('fetal_heart_rate', ['nullable', 'integer', 'min:0', 'max:250'], 0, 250),
                $this->text('fetal_movement', 255), $this->text('presentation', 255), $this->text('lie', 255),
            ]],
            'risk_assessment' => ['fields' => [
                $this->text('risk_level', 100), $this->arrayField('risk_factors'), $this->textarea('action_plan', 2000),
            ]],
            'ultrasound_findings' => ['fields' => [
                $this->boolean('ultrasound_requested'), $this->date('ultrasound_date'), $this->text('gestational_age_scan', 100),
                $this->text('placenta', 255), $this->text('liquor', 255), $this->textarea('findings', 2000),
            ]],
            'lab_screening' => ['fields' => [
                $this->text('hb', 100), $this->text('blood_group', 20), $this->text('rhesus', 20), $this->text('hiv_status', 100),
                $this->text('hepatitis_b', 100), $this->text('syphilis', 100), $this->textarea('urinalysis', 1000),
            ]],
            'birth_plan' => ['fields' => [
                $this->text('planned_place', 255), $this->textarea('delivery_plan', 2000), $this->boolean('danger_signs_counseling'), $this->date('next_visit_date'),
            ]],
            'gyne_complaint' => ['fields' => [
                $this->textarea('complaint_text', 2000), $this->text('duration', 255), $this->textarea('associated_symptoms', 2000),
            ]],
            'menstrual_history' => ['fields' => [
                $this->date('lmp'), $this->text('cycle_length', 100), $this->text('cycle_regularity', 100), $this->textarea('bleeding_pattern', 1000),
                $this->boolean('dysmenorrhea'), $this->text('menopause_status', 100),
            ]],
            'contraceptive_history' => ['fields' => [
                $this->text('current_method', 255), $this->textarea('past_methods', 1000), $this->textarea('side_effects', 1000), $this->textarea('family_planning_goal', 1000),
            ]],
            'sexual_sti_history' => ['fields' => [
                $this->textarea('sti_symptoms', 1000), $this->textarea('discharge', 1000), $this->boolean('pelvic_pain'),
                $this->text('pregnancy_test', 100), $this->textarea('screening_notes', 2000),
            ]],
            'pelvic_examination' => ['fields' => [
                $this->textarea('external_findings', 1000), $this->textarea('speculum_findings', 1000), $this->textarea('bimanual_findings', 1000),
                $this->textarea('cervix', 1000), $this->textarea('uterus', 1000), $this->textarea('adnexa', 1000), $this->textarea('exam_notes', 2000),
            ]],
            'breast_examination' => ['fields' => [
                $this->textarea('breast_symptoms', 1000), $this->textarea('inspection', 1000), $this->textarea('palpation', 1000),
                $this->textarea('lumps', 1000), $this->textarea('nipple_discharge', 1000), $this->textarea('axillary_nodes', 1000),
            ]],
            'ent_complaint' => ['fields' => [
                $this->textarea('complaint_text', 2000), $this->text('duration', 255), $this->text('side', 50), $this->textarea('associated_symptoms', 2000),
            ]],
            'ear_assessment' => ['fields' => [
                $this->boolean('ear_pain'), $this->textarea('ear_discharge', 1000), $this->textarea('hearing_loss', 1000),
                $this->boolean('tinnitus'), $this->boolean('vertigo'), $this->textarea('otoscopy_right', 1000), $this->textarea('otoscopy_left', 1000),
            ]],
            'nose_assessment' => ['fields' => [
                $this->textarea('nasal_blockage', 1000), $this->boolean('epistaxis'), $this->textarea('nasal_discharge', 1000),
                $this->boolean('sneezing'), $this->textarea('sinus_tenderness', 1000), $this->textarea('rhinitis_notes', 1000),
            ]],
            'throat_assessment' => ['fields' => [
                $this->boolean('sore_throat'), $this->boolean('difficulty_swallowing'), $this->boolean('voice_change'),
                $this->textarea('tonsils', 1000), $this->textarea('pharynx', 1000), $this->textarea('oral_cavity', 1000),
            ]],
            'hearing_balance_assessment' => ['fields' => [
                $this->textarea('hearing_test_notes', 1000), $this->textarea('balance_notes', 1000), $this->boolean('audiology_requested'),
            ]],
            'neck_assessment' => ['fields' => [
                $this->textarea('neck_swelling', 1000), $this->textarea('lymph_nodes', 1000), $this->textarea('thyroid_notes', 1000),
            ]],
            'pediatric_complaint' => ['fields' => [
                $this->textarea('complaint_text', 2000), $this->text('duration', 255), $this->textarea('caregiver_concern', 2000), $this->textarea('danger_signs', 2000),
            ]],
            'birth_history' => ['fields' => [
                $this->text('delivery_mode', 100), $this->text('gestational_age_birth', 100), $this->number('birth_weight', ['nullable', 'numeric', 'min:0', 'max:10'], 0, 10),
                $this->textarea('neonatal_complications', 2000),
            ]],
            'feeding_history' => ['fields' => [
                $this->text('feeding_type', 100), $this->text('feeding_frequency', 100), $this->text('appetite', 255), $this->boolean('vomiting'), $this->textarea('feeding_notes', 2000),
            ]],
            'growth_assessment' => ['fields' => [
                $this->number('weight', ['nullable', 'numeric', 'min:0', 'max:300'], 0, 300), $this->number('height', ['nullable', 'numeric', 'min:0', 'max:250'], 0, 250),
                $this->number('muac', ['nullable', 'numeric', 'min:0', 'max:100'], 0, 100), $this->textarea('growth_concern', 1000), $this->textarea('growth_notes', 2000),
            ]],
            'immunization_status' => ['fields' => [
                $this->boolean('immunization_up_to_date'), $this->textarea('missed_vaccines', 1000), $this->text('next_vaccine_due', 255), $this->textarea('immunization_notes', 2000),
            ]],
            'developmental_assessment' => ['fields' => [
                $this->textarea('milestones', 2000), $this->textarea('speech', 1000), $this->textarea('motor', 1000),
                $this->textarea('social', 1000), $this->textarea('development_concern', 1000),
            ]],
            'pediatric_examination' => ['fields' => [
                $this->textarea('general_appearance', 1000), $this->textarea('hydration', 1000), $this->textarea('respiratory', 1000),
                $this->textarea('cardiovascular', 1000), $this->textarea('abdomen', 1000), $this->textarea('cns', 1000), $this->textarea('exam_notes', 2000),
            ]],
            'caregiver_instructions' => ['fields' => [
                $this->textarea('instructions', 2000), $this->textarea('danger_signs', 2000), $this->date('follow_up_date'),
            ]],
            'triage_summary' => ['fields' => [
                $this->text('triage_category', 100), $this->text('arrival_mode', 100), $this->date('arrival_time'), $this->textarea('chief_risk', 1000),
            ]],
            'emergency_complaint' => ['fields' => [
                $this->textarea('complaint_text', 2000), $this->text('onset_time', 255), $this->textarea('mechanism', 1000),
            ]],
            'primary_survey' => ['fields' => [
                $this->textarea('airway', 1000), $this->textarea('breathing', 1000), $this->textarea('circulation', 1000),
                $this->textarea('disability', 1000), $this->textarea('exposure', 1000), $this->number('gcs', ['nullable', 'integer', 'min:3', 'max:15'], 3, 15),
            ]],
            'vitals_monitoring' => ['fields' => [
                $this->text('blood_pressure', 50), $this->number('pulse', ['nullable', 'integer', 'min:0', 'max:250'], 0, 250),
                $this->number('respiratory_rate', ['nullable', 'integer', 'min:0', 'max:100'], 0, 100), $this->number('temperature', ['nullable', 'numeric', 'min:20', 'max:45'], 20, 45),
                $this->number('spo2', ['nullable', 'integer', 'min:0', 'max:100'], 0, 100), $this->number('pain_score', ['nullable', 'integer', 'min:0', 'max:10'], 0, 10),
            ]],
            'trauma_assessment' => ['fields' => [
                $this->boolean('trauma_present'), $this->textarea('mechanism_of_injury', 1000), $this->textarea('injury_sites', 2000),
                $this->textarea('bleeding', 1000), $this->boolean('fracture_suspected'),
            ]],
            'emergency_interventions' => ['fields' => [
                $this->textarea('interventions_done', 2000), $this->boolean('oxygen_given'), $this->boolean('iv_access'),
                $this->textarea('fluids_given', 1000), $this->textarea('resuscitation_notes', 2000),
            ]],
            'urgent_investigations' => ['fields' => [
                $this->arrayField('investigations'), $this->textarea('urgency_reason', 1000), $this->textarea('results_summary', 2000),
            ]],
            'urgent_procedures' => ['fields' => [
                $this->textarea('procedure_planned', 2000), $this->textarea('procedure_done', 2000), $this->textarea('notes', 2000),
            ]],
            'medications_given' => ['fields' => [
                $this->textarea('medications', 2000), $this->textarea('response', 2000),
            ]],
            'disposition' => ['fields' => [
                $this->text('disposition', 255), $this->text('admit_to', 255), $this->text('refer_to', 255), $this->textarea('disposition_notes', 2000),
            ]],
            'handover' => ['fields' => [
                $this->text('handover_to', 255), $this->textarea('handover_notes', 2000),
            ]],
            'ortho_complaint' => ['fields' => [
                $this->textarea('complaint_text', 2000), $this->text('affected_limb', 255), $this->text('duration', 255),
            ]],
            'injury_history' => ['fields' => [
                $this->textarea('injury_mechanism', 1000), $this->date('injury_date'), $this->text('fall_height', 255),
                $this->boolean('sports_related'), $this->boolean('work_related'),
            ]],
            'pain_mobility_assessment' => ['fields' => [
                $this->number('pain_score', ['nullable', 'integer', 'min:0', 'max:10'], 0, 10), $this->text('weight_bearing_status', 255),
                $this->text('mobility_aid', 255), $this->textarea('functional_limitation', 2000),
            ]],
            'joint_limb_examination' => ['fields' => [
                $this->textarea('deformity', 1000), $this->textarea('swelling', 1000), $this->textarea('tenderness', 1000),
                $this->textarea('range_of_motion', 1000), $this->textarea('stability', 1000), $this->textarea('exam_notes', 2000),
            ]],
            'neurovascular_status' => ['fields' => [
                $this->boolean('pulse_present'), $this->text('capillary_refill', 100), $this->textarea('sensation', 1000),
                $this->textarea('motor_function', 1000), $this->textarea('neurovascular_notes', 2000),
            ]],
            'imaging' => ['fields' => [
                $this->boolean('xray_requested'), $this->textarea('xray_findings', 2000), $this->boolean('ct_mri_requested'), $this->textarea('imaging_notes', 2000),
            ]],
            'procedure_plan' => ['fields' => [
                $this->textarea('procedure_planned', 2000), $this->textarea('procedure_done', 2000), $this->textarea('anaesthesia_plan', 1000), $this->textarea('notes', 2000),
            ]],
            'cast_splint_plan' => ['fields' => [
                $this->text('immobilization_type', 255), $this->boolean('cast_applied'), $this->boolean('splint_applied'),
                $this->textarea('care_instructions', 2000), $this->date('review_date'),
            ]],
            'surgical_complaint' => ['fields' => [
                $this->textarea('complaint_text', 2000), $this->text('duration', 255), $this->textarea('associated_symptoms', 2000),
            ]],
            'surgical_history' => ['fields' => [
                $this->textarea('previous_surgeries', 2000), $this->textarea('anaesthesia_history', 1000), $this->textarea('bleeding_history', 1000), $this->textarea('allergy_notes', 1000),
            ]],
            'wound_assessment' => ['fields' => [
                $this->text('wound_site', 255), $this->text('wound_size', 100), $this->textarea('wound_discharge', 1000),
                $this->textarea('infection_signs', 1000), $this->textarea('dressing_status', 1000),
            ]],
            'local_or_abdominal_exam' => ['fields' => [
                $this->textarea('inspection', 1000), $this->textarea('palpation', 1000), $this->textarea('tenderness', 1000), $this->textarea('mass', 1000), $this->textarea('exam_notes', 2000),
            ]],
            'theatre_referral' => ['fields' => [
                $this->boolean('theatre_required'), $this->text('urgency', 100), $this->date('proposed_date'), $this->textarea('referral_notes', 2000),
            ]],
            'post_op_instructions' => ['fields' => [
                $this->textarea('instructions', 2000), $this->textarea('wound_care', 2000), $this->textarea('warning_signs', 2000), $this->date('review_date'),
            ]],
        ];
    }

    private function text(string $name, int $max): array
    {
        return ['name' => $name, 'type' => 'text', 'rules' => ['nullable', 'string', 'max:'.$max]];
    }

    private function textarea(string $name, int $max): array
    {
        return ['name' => $name, 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:'.$max]];
    }

    private function date(string $name): array
    {
        return ['name' => $name, 'type' => 'date', 'rules' => ['nullable', 'date']];
    }

    private function boolean(string $name): array
    {
        return ['name' => $name, 'type' => 'boolean', 'rules' => ['nullable', 'boolean']];
    }

    private function arrayField(string $name): array
    {
        return ['name' => $name, 'type' => 'array', 'rules' => ['nullable', 'array']];
    }

    private function number(string $name, array $rules, int|float|null $min = null, int|float|null $max = null): array
    {
        return ['name' => $name, 'type' => 'number', 'rules' => $rules, 'min' => $min, 'max' => $max];
    }
}
