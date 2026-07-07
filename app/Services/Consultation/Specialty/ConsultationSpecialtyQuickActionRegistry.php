<?php

namespace App\Services\Consultation\Specialty;

use App\Models\ConsultationSpecialtyProfile;

class ConsultationSpecialtyQuickActionRegistry
{
    public function actionsForProfile(ConsultationSpecialtyProfile $profile): array
    {
        return $this->actions()[$profile->code] ?? $this->actions()['general_medicine'];
    }

    public function validKeysForProfile(ConsultationSpecialtyProfile $profile): array
    {
        return collect($this->actionsForProfile($profile))->pluck('key')->all();
    }

    private function actions(): array
    {
        return [
            'general_medicine' => [
                $this->action('complaints', 'section_anchor', '#complaints-section', 'ti-message-circle', 10),
                $this->action('examination', 'section_anchor', '#examination-section', 'ti-stethoscope', 20),
                $this->action('diagnosis', 'section_anchor', '#diagnosis-section', 'ti-clipboard-check', 30),
                $this->action('prescription', 'open_prescription', '#prescription-section', 'ti-pill', 40),
                $this->action('generate_summary', 'generate_summary', '#summary-section', 'ti-sparkles', 50),
                $this->action('readiness', 'open_readiness', '#completionReadinessCard', 'ti-list-check', 60),
            ],
            'physiotherapy' => [
                $this->section('presenting_problem', 10), $this->section('pain_assessment', 20), $this->section('physical_assessment', 30),
                $this->section('treatment_plan', 40), $this->section('therapy_session', 50), $this->section('home_exercise_plan', 60),
                $this->action('order_sets', 'open_order_sets', '#order-sets-panel', 'ti-packages', 70),
                $this->action('generate_summary', 'generate_summary', '#summary-section', 'ti-sparkles', 80),
                $this->action('readiness', 'open_readiness', '#completionReadinessCard', 'ti-list-check', 90),
            ],
            'ophthalmology' => [
                $this->action('eye_complaint', 'section_anchor', '#complaints-section', 'ti-eye', 10),
                $this->section('visual_acuity', 20), $this->section('refraction', 30), $this->section('iop', 40), $this->section('eye_examination', 50),
                $this->action('prescription', 'open_prescription', '#prescription-section', 'ti-pill', 60),
                $this->action('order_sets', 'open_order_sets', '#order-sets-panel', 'ti-packages', 70),
                $this->action('generate_summary', 'generate_summary', '#summary-section', 'ti-sparkles', 80),
                $this->action('readiness', 'open_readiness', '#completionReadinessCard', 'ti-list-check', 90),
            ],
            'dental' => [
                $this->action('dental_complaint', 'section_anchor', '#complaints-section', 'ti-message-circle', 10),
                $this->section('tooth_chart', 20), $this->section('oral_examination', 30), $this->section('dental_diagnosis', 40),
                $this->section('dental_procedure', 50, 'dental_procedures'), $this->section('consent', 60),
                $this->action('order_sets', 'open_order_sets', '#order-sets-panel', 'ti-packages', 70),
                $this->action('generate_summary', 'generate_summary', '#summary-section', 'ti-sparkles', 80),
                $this->action('readiness', 'open_readiness', '#completionReadinessCard', 'ti-list-check', 90),
            ],
            'obstetrics' => [
                $this->section('obstetric_history', 10), $this->section('current_pregnancy', 20), $this->section('fetal_assessment', 30),
                $this->section('risk_assessment', 40), $this->section('birth_plan', 50),
                $this->action('order_sets', 'open_order_sets', '#order-sets-panel', 'ti-packages', 60),
                $this->action('generate_summary', 'generate_summary', '#summary-section', 'ti-sparkles', 70),
                $this->action('readiness', 'open_readiness', '#completionReadinessCard', 'ti-list-check', 80),
            ],
            'gynecology' => [
                $this->section('gyne_complaint', 10), $this->section('menstrual_history', 20), $this->section('pelvic_examination', 30),
                $this->section('breast_examination', 40), $this->action('prescription', 'open_prescription', '#prescriptions-section', 'ti-pill', 50),
                $this->action('order_sets', 'open_order_sets', '#order-sets-panel', 'ti-packages', 60),
                $this->action('generate_summary', 'generate_summary', '#summary-section', 'ti-sparkles', 70),
                $this->action('readiness', 'open_readiness', '#completionReadinessCard', 'ti-list-check', 80),
            ],
            'ent' => [
                $this->section('ent_complaint', 10), $this->section('ear_assessment', 20), $this->section('nose_assessment', 30),
                $this->section('throat_assessment', 40), $this->section('hearing_balance_assessment', 50),
                $this->action('order_sets', 'open_order_sets', '#order-sets-panel', 'ti-packages', 60),
                $this->action('generate_summary', 'generate_summary', '#summary-section', 'ti-sparkles', 70),
                $this->action('readiness', 'open_readiness', '#completionReadinessCard', 'ti-list-check', 80),
            ],
            'pediatrics' => [
                $this->section('pediatric_complaint', 10), $this->section('growth_assessment', 20), $this->section('immunization_status', 30),
                $this->section('pediatric_examination', 40), $this->section('caregiver_instructions', 50),
                $this->action('order_sets', 'open_order_sets', '#order-sets-panel', 'ti-packages', 60),
                $this->action('generate_summary', 'generate_summary', '#summary-section', 'ti-sparkles', 70),
                $this->action('readiness', 'open_readiness', '#completionReadinessCard', 'ti-list-check', 80),
            ],
            'emergency' => [
                $this->section('triage_summary', 10), $this->section('primary_survey', 20), $this->section('vitals_monitoring', 30),
                $this->section('emergency_interventions', 40), $this->section('disposition', 50), $this->section('handover', 60),
                $this->action('order_sets', 'open_order_sets', '#order-sets-panel', 'ti-packages', 70),
                $this->action('generate_summary', 'generate_summary', '#summary-section', 'ti-sparkles', 80),
                $this->action('readiness', 'open_readiness', '#completionReadinessCard', 'ti-list-check', 90),
            ],
            'orthopedics' => [
                $this->section('ortho_complaint', 10), $this->section('injury_history', 20), $this->section('pain_mobility_assessment', 30),
                $this->section('joint_limb_examination', 40), $this->section('neurovascular_status', 50), $this->section('cast_splint_plan', 60),
                $this->action('order_sets', 'open_order_sets', '#order-sets-panel', 'ti-packages', 70),
                $this->action('generate_summary', 'generate_summary', '#summary-section', 'ti-sparkles', 80),
                $this->action('readiness', 'open_readiness', '#completionReadinessCard', 'ti-list-check', 90),
            ],
            'surgery' => [
                $this->section('surgical_complaint', 10), $this->section('wound_assessment', 20), $this->section('local_or_abdominal_exam', 30),
                $this->section('procedure_plan', 40), $this->section('theatre_referral', 50), $this->section('post_op_instructions', 60),
                $this->action('order_sets', 'open_order_sets', '#order-sets-panel', 'ti-packages', 70),
                $this->action('generate_summary', 'generate_summary', '#summary-section', 'ti-sparkles', 80),
                $this->action('readiness', 'open_readiness', '#completionReadinessCard', 'ti-list-check', 90),
            ],
        ];
    }

    private function section(string $key, int $priority, ?string $sectionKey = null): array
    {
        $sectionKey ??= $key;

        return $this->action($key, 'section_anchor', '#'.$sectionKey.'-section', 'ti-layout-board', $priority, $sectionKey);
    }

    private function action(string $key, string $type, string $target, string $icon, int $priority, ?string $requiresSection = null): array
    {
        return [
            'key' => $key,
            'label' => __('consultation_specialties.quick_actions.'.$key),
            'type' => $type,
            'target' => $target,
            'icon' => $icon,
            'priority' => $priority,
            'requires_section' => $requiresSection,
            'disabled' => false,
        ];
    }
}
