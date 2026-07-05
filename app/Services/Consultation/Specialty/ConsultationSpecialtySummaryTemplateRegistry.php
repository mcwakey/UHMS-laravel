<?php

namespace App\Services\Consultation\Specialty;

use App\Models\ConsultationSpecialtyProfile;

class ConsultationSpecialtySummaryTemplateRegistry
{
    public function templateForProfile(ConsultationSpecialtyProfile $profile): array
    {
        return $this->templates()[$profile->code] ?? $this->templates()['general_medicine'];
    }

    private function templates(): array
    {
        return [
            'general_medicine' => [
                'profile_code' => 'general_medicine',
                'title' => __('consultation_specialties.summary_builder.general_title'),
                'fallback' => true,
                'sections' => [
                    $this->section('chief_complaint', 'core.complaints', 'complaints'),
                    $this->section('history', 'core.hopc', 'key_value_list'),
                    $this->section('examination', 'core.examination', 'key_value_list'),
                    $this->section('diagnosis', 'core.diagnoses', 'diagnoses'),
                    $this->section('investigations', 'core.investigations', 'investigations'),
                    $this->section('treatment_prescription', 'core.prescriptions', 'prescriptions'),
                    $this->section('follow_up', 'core.tasks', 'tasks'),
                ],
            ],
            'physiotherapy' => [
                'profile_code' => 'physiotherapy',
                'title' => __('consultation_specialties.summary_builder.physiotherapy_title'),
                'sections' => [
                    $this->section('presenting_problem', 'specialty_entries.presenting_problem', 'entry'),
                    $this->section('pain_assessment', 'specialty_entries.pain_assessment', 'entry'),
                    $this->section('functional_limitation', 'specialty_entries.functional_limitation', 'entry'),
                    $this->section('physical_assessment', 'specialty_entries.physical_assessment', 'entry'),
                    $this->section('treatment_plan', 'specialty_entries.treatment_plan', 'entry'),
                    $this->section('therapy_session', 'specialty_entries.therapy_session', 'entry'),
                    $this->section('home_exercise_plan', 'specialty_entries.home_exercise_plan', 'entry'),
                    $this->section('progress', 'specialty_entries.progress_notes', 'entry'),
                    $this->section('follow_up', 'core.tasks', 'tasks'),
                    $this->section('readiness_warnings', 'readiness.warningItems', 'readiness_warnings'),
                ],
            ],
            'ophthalmology' => [
                'profile_code' => 'ophthalmology',
                'title' => __('consultation_specialties.summary_builder.ophthalmology_title'),
                'sections' => [
                    $this->section('eye_complaint', 'core.complaints', 'complaints'),
                    $this->section('visual_acuity', 'specialty_entries.visual_acuity', 'entry'),
                    $this->section('refraction', 'specialty_entries.refraction', 'entry'),
                    $this->section('iop', 'specialty_entries.iop', 'entry'),
                    $this->section('eye_examination', 'specialty_entries.eye_examination', 'entry'),
                    $this->section('diagnosis', 'core.diagnoses', 'diagnoses'),
                    $this->section('investigations', 'core.investigations', 'investigations'),
                    $this->section('treatment_prescription', 'core.prescriptions', 'prescriptions'),
                    $this->section('follow_up', 'specialty_entries.follow_up', 'entry'),
                    $this->section('readiness_warnings', 'readiness.warningItems', 'readiness_warnings'),
                ],
            ],
            'dental' => [
                'profile_code' => 'dental',
                'title' => __('consultation_specialties.summary_builder.dental_title'),
                'sections' => [
                    $this->section('dental_complaint', 'core.complaints', 'complaints'),
                    $this->section('tooth_chart', 'specialty_entries.tooth_chart', 'entry'),
                    $this->section('oral_examination', 'specialty_entries.oral_examination', 'entry'),
                    $this->section('dental_diagnosis', 'specialty_entries.dental_diagnosis', 'entry_plus_diagnoses', ['extra_source' => 'core.diagnoses']),
                    $this->section('dental_xray', 'specialty_entries.dental_xray', 'entry_plus_investigations', ['extra_source' => 'core.investigations']),
                    $this->section('dental_procedures', 'specialty_entries.dental_procedures', 'entry_plus_procedures', ['extra_source' => 'core.procedures']),
                    $this->section('consent', 'specialty_entries.consent', 'entry'),
                    $this->section('treatment_prescription', 'core.prescriptions', 'prescriptions'),
                    $this->section('post_procedure_instructions', 'core.tasks', 'tasks'),
                    $this->section('readiness_warnings', 'readiness.warningItems', 'readiness_warnings'),
                ],
            ],
        ];
    }

    private function section(string $key, string $source, string $formatter, array $extra = []): array
    {
        return $extra + [
            'key' => $key,
            'label' => __('consultation_specialties.summary_builder.sections.'.$key),
            'source' => $source,
            'formatter' => $formatter,
            'include_if_empty' => false,
        ];
    }
}
