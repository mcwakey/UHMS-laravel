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
