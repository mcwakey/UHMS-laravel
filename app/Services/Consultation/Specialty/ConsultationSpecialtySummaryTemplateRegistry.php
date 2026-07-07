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
            'obstetrics' => [
                'profile_code' => 'obstetrics',
                'title' => __('consultation_specialties.summary_builder.obstetrics_title'),
                'sections' => [
                    $this->section('obstetric_history', 'specialty_entries.obstetric_history', 'entry'),
                    $this->section('current_pregnancy', 'specialty_entries.current_pregnancy', 'entry'),
                    $this->section('lmp_edd_gestational_age', 'specialty_entries.lmp_edd_gestational_age', 'entry'),
                    $this->section('fetal_assessment', 'specialty_entries.fetal_assessment', 'entry'),
                    $this->section('risk_assessment', 'specialty_entries.risk_assessment', 'entry'),
                    $this->section('lab_screening', 'specialty_entries.lab_screening', 'entry'),
                    $this->section('diagnosis', 'core.diagnoses', 'diagnoses'),
                    $this->section('birth_plan', 'specialty_entries.birth_plan', 'entry'),
                    $this->section('readiness_warnings', 'readiness.warningItems', 'readiness_warnings'),
                ],
            ],
            'gynecology' => [
                'profile_code' => 'gynecology',
                'title' => __('consultation_specialties.summary_builder.gynecology_title'),
                'sections' => [
                    $this->section('gyne_complaint', 'specialty_entries.gyne_complaint', 'entry'),
                    $this->section('menstrual_history', 'specialty_entries.menstrual_history', 'entry'),
                    $this->section('obstetric_history', 'specialty_entries.obstetric_history', 'entry'),
                    $this->section('contraceptive_history', 'specialty_entries.contraceptive_history', 'entry'),
                    $this->section('sexual_sti_history', 'specialty_entries.sexual_sti_history', 'entry'),
                    $this->section('pelvic_examination', 'specialty_entries.pelvic_examination', 'entry'),
                    $this->section('diagnosis', 'core.diagnoses', 'diagnoses'),
                    $this->section('readiness_warnings', 'readiness.warningItems', 'readiness_warnings'),
                ],
            ],
            'ent' => [
                'profile_code' => 'ent',
                'title' => __('consultation_specialties.summary_builder.ent_title'),
                'sections' => [
                    $this->section('ent_complaint', 'specialty_entries.ent_complaint', 'entry'),
                    $this->section('ear_assessment', 'specialty_entries.ear_assessment', 'entry'),
                    $this->section('nose_assessment', 'specialty_entries.nose_assessment', 'entry'),
                    $this->section('throat_assessment', 'specialty_entries.throat_assessment', 'entry'),
                    $this->section('hearing_balance_assessment', 'specialty_entries.hearing_balance_assessment', 'entry'),
                    $this->section('neck_assessment', 'specialty_entries.neck_assessment', 'entry'),
                    $this->section('diagnosis', 'core.diagnoses', 'diagnoses'),
                    $this->section('readiness_warnings', 'readiness.warningItems', 'readiness_warnings'),
                ],
            ],
            'pediatrics' => [
                'profile_code' => 'pediatrics',
                'title' => __('consultation_specialties.summary_builder.pediatrics_title'),
                'sections' => [
                    $this->section('pediatric_complaint', 'specialty_entries.pediatric_complaint', 'entry'),
                    $this->section('birth_history', 'specialty_entries.birth_history', 'entry'),
                    $this->section('feeding_history', 'specialty_entries.feeding_history', 'entry'),
                    $this->section('growth_assessment', 'specialty_entries.growth_assessment', 'entry'),
                    $this->section('immunization_status', 'specialty_entries.immunization_status', 'entry'),
                    $this->section('developmental_assessment', 'specialty_entries.developmental_assessment', 'entry'),
                    $this->section('pediatric_examination', 'specialty_entries.pediatric_examination', 'entry'),
                    $this->section('caregiver_instructions', 'specialty_entries.caregiver_instructions', 'entry'),
                    $this->section('readiness_warnings', 'readiness.warningItems', 'readiness_warnings'),
                ],
            ],
            'emergency' => [
                'profile_code' => 'emergency',
                'title' => __('consultation_specialties.summary_builder.emergency_title'),
                'sections' => [
                    $this->section('triage_summary', 'specialty_entries.triage_summary', 'entry'),
                    $this->section('emergency_complaint', 'specialty_entries.emergency_complaint', 'entry'),
                    $this->section('primary_survey', 'specialty_entries.primary_survey', 'entry'),
                    $this->section('vitals_monitoring', 'specialty_entries.vitals_monitoring', 'entry'),
                    $this->section('emergency_interventions', 'specialty_entries.emergency_interventions', 'entry'),
                    $this->section('medications_given', 'specialty_entries.medications_given', 'entry'),
                    $this->section('disposition', 'specialty_entries.disposition', 'entry'),
                    $this->section('handover', 'specialty_entries.handover', 'entry'),
                ],
            ],
            'orthopedics' => [
                'profile_code' => 'orthopedics',
                'title' => __('consultation_specialties.summary_builder.orthopedics_title'),
                'sections' => [
                    $this->section('ortho_complaint', 'specialty_entries.ortho_complaint', 'entry'),
                    $this->section('injury_history', 'specialty_entries.injury_history', 'entry'),
                    $this->section('pain_mobility_assessment', 'specialty_entries.pain_mobility_assessment', 'entry'),
                    $this->section('joint_limb_examination', 'specialty_entries.joint_limb_examination', 'entry'),
                    $this->section('neurovascular_status', 'specialty_entries.neurovascular_status', 'entry'),
                    $this->section('imaging', 'specialty_entries.imaging', 'entry'),
                    $this->section('procedure_plan', 'specialty_entries.procedure_plan', 'entry'),
                    $this->section('cast_splint_plan', 'specialty_entries.cast_splint_plan', 'entry'),
                ],
            ],
            'surgery' => [
                'profile_code' => 'surgery',
                'title' => __('consultation_specialties.summary_builder.surgery_title'),
                'sections' => [
                    $this->section('surgical_complaint', 'specialty_entries.surgical_complaint', 'entry'),
                    $this->section('surgical_history', 'specialty_entries.surgical_history', 'entry'),
                    $this->section('wound_assessment', 'specialty_entries.wound_assessment', 'entry'),
                    $this->section('local_or_abdominal_exam', 'specialty_entries.local_or_abdominal_exam', 'entry'),
                    $this->section('procedure_plan', 'specialty_entries.procedure_plan', 'entry'),
                    $this->section('consent', 'specialty_entries.consent', 'entry'),
                    $this->section('theatre_referral', 'specialty_entries.theatre_referral', 'entry'),
                    $this->section('post_op_instructions', 'specialty_entries.post_op_instructions', 'entry'),
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
