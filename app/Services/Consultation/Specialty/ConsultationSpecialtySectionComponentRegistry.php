<?php

namespace App\Services\Consultation\Specialty;

class ConsultationSpecialtySectionComponentRegistry
{
    private const FALLBACK_COMPONENT = 'consultations.partials.specialty.generic-section';

    private const CORE_COMPONENT = 'consultations.partials.specialty.core-section';

    private const STRUCTURED_COMPONENT = 'consultations.partials.specialty.structured-section';

    private const CORE_SECTIONS = [
        'patient_summary',
        'complaints',
        'hopc',
        'examination',
        'diagnosis',
        'investigations',
        'treatments',
        'prescription',
        'procedures',
        'tasks',
        'notes',
        'summary',
        'completion_readiness',
    ];

    private const ALIASES = [
        'progress_notes' => 'notes',
        'follow_up' => 'tasks',
    ];

    /**
     * Legacy duplicate specialty sections hidden in Phase 16.5/16.6. They
     * keep their structured schemas (for saved-entry compatibility) but
     * resolve to the canonical shared section, so anchors/badges collapse
     * onto the shared panes. Kept in sync with
     * ConsultationSpecialtySectionAliasService.
     */
    private const DUPLICATE_SECTION_ALIASES = [
        'lab_screening' => 'investigations',
        'ultrasound_findings' => 'investigations',
        'urgent_investigations' => 'investigations',
        'imaging' => 'investigations',
        'dental_xray' => 'investigations',
        'urgent_procedures' => 'procedures',
        'dental_procedures' => 'procedures',
        'procedure_plan' => 'procedures',
        'dental_diagnosis' => 'diagnosis',
        'medications_given' => 'prescription',
        // Phase 16.6: complaint-like specialty sections canonicalise to the
        // core complaints pane instead of rendering their own schema form.
        'presenting_problem' => 'complaints',
        'eye_complaint' => 'complaints',
        'dental_complaint' => 'complaints',
        'gyne_complaint' => 'complaints',
        'ent_complaint' => 'complaints',
        'pediatric_complaint' => 'complaints',
        'emergency_complaint' => 'complaints',
        'ortho_complaint' => 'complaints',
        'surgical_complaint' => 'complaints',
    ];

    public function __construct(
        private readonly ConsultationSpecialtySectionSchema $schemas,
    ) {}

    public function resolveComponent(string $sectionKey, ?string $configuredComponent = null): string
    {
        if ($configuredComponent && view()->exists($configuredComponent)) {
            return $configuredComponent;
        }

        if ($this->schemas->hasSchema($sectionKey)) {
            return self::STRUCTURED_COMPONENT;
        }

        if ($this->isCoreSection($sectionKey) || isset(self::ALIASES[$sectionKey])) {
            return self::CORE_COMPONENT;
        }

        return $this->fallbackComponent();
    }

    public function isCoreSection(string $sectionKey): bool
    {
        return in_array($sectionKey, self::CORE_SECTIONS, true);
    }

    public function fallbackComponent(): string
    {
        return self::FALLBACK_COMPONENT;
    }

    public function coreSectionKeys(): array
    {
        return self::CORE_SECTIONS;
    }

    public function canonicalSectionKey(string $sectionKey): string
    {
        if (isset(self::DUPLICATE_SECTION_ALIASES[$sectionKey])) {
            return self::DUPLICATE_SECTION_ALIASES[$sectionKey];
        }

        if ($this->schemas->hasSchema($sectionKey)) {
            return $sectionKey;
        }

        return self::ALIASES[$sectionKey] ?? $sectionKey;
    }

    public function tabTargetFor(string $sectionKey): ?string
    {
        if ($this->schemas->hasSchema($sectionKey) && ! $this->isCoreSection($sectionKey)) {
            return 'specialty-'.$sectionKey.'-section';
        }

        return match ($this->canonicalSectionKey($sectionKey)) {
            'complaints' => 'complaints-section',
            'hopc' => 'hopc-section',
            'examination' => 'examination-section',
            'diagnosis' => 'diagnoses-section',
            'investigations' => 'investigations-section',
            'treatments' => 'treatments-section',
            'prescription' => 'prescriptions-section',
            'procedures' => 'procedures-section',
            'tasks' => 'tasks-section',
            'notes', 'summary' => 'summary-section',
            default => $this->isCoreSection($sectionKey) ? null : 'specialty-'.$sectionKey.'-section',
        };
    }

    public function iconFor(string $sectionKey): string
    {
        $specialtyIcon = match ($sectionKey) {
            'presenting_problem' => 'ti-clipboard-heart',
            'pain_assessment' => 'ti-mood-sick',
            'functional_limitation' => 'ti-walk',
            'physical_assessment' => 'ti-stretching',
            'treatment_plan' => 'ti-target-arrow',
            'therapy_session' => 'ti-activity',
            'home_exercise_plan' => 'ti-run',
            'progress_notes' => 'ti-progress-check',
            'eye_complaint' => 'ti-eye-exclamation',
            'visual_acuity' => 'ti-eye-check',
            'refraction' => 'ti-glasses',
            'iop' => 'ti-gauge',
            'eye_examination' => 'ti-eye-cog',
            'follow_up' => 'ti-calendar-time',
            'dental_complaint' => 'ti-dental',
            'tooth_chart' => 'ti-dental-broken',
            'oral_examination' => 'ti-mouth',
            'dental_diagnosis' => 'ti-report-medical',
            'dental_xray' => 'ti-scan',
            'dental_procedures' => 'ti-tools',
            'consent' => 'ti-shield-check',
            'obstetric_history' => 'ti-baby-carriage',
            'current_pregnancy' => 'ti-heart-plus',
            'lmp_edd_gestational_age' => 'ti-calendar-stats',
            'antenatal_vitals' => 'ti-heartbeat',
            'fetal_assessment' => 'ti-baby-carriage',
            'risk_assessment' => 'ti-alert-triangle',
            'ultrasound_findings' => 'ti-device-desktop-analytics',
            'lab_screening' => 'ti-test-pipe',
            'birth_plan' => 'ti-clipboard-check',
            'gyne_complaint' => 'ti-message-circle',
            'menstrual_history' => 'ti-calendar-heart',
            'contraceptive_history' => 'ti-shield-check',
            'sexual_sti_history' => 'ti-virus-search',
            'pelvic_examination' => 'ti-stethoscope',
            'breast_examination' => 'ti-ribbon-health',
            'ent_complaint' => 'ti-message-circle',
            'ear_assessment' => 'ti-ear',
            'nose_assessment' => 'ti-masks-theater',
            'throat_assessment' => 'ti-microphone',
            'hearing_balance_assessment' => 'ti-volume',
            'neck_assessment' => 'ti-user-search',
            'pediatric_complaint' => 'ti-message-circle',
            'birth_history' => 'ti-baby-bottle',
            'feeding_history' => 'ti-bottle',
            'growth_assessment' => 'ti-ruler-measure',
            'immunization_status' => 'ti-vaccine',
            'developmental_assessment' => 'ti-puzzle',
            'pediatric_examination' => 'ti-stethoscope',
            'caregiver_instructions' => 'ti-notes',
            'triage_summary' => 'ti-triage',
            'emergency_complaint' => 'ti-urgent',
            'primary_survey' => 'ti-clipboard-pulse',
            'vitals_monitoring' => 'ti-heart-rate-monitor',
            'trauma_assessment' => 'ti-bandage',
            'emergency_interventions' => 'ti-first-aid-kit',
            'urgent_investigations' => 'ti-test-pipe',
            'urgent_procedures' => 'ti-activity-heartbeat',
            'medications_given' => 'ti-pill',
            'disposition' => 'ti-route',
            'handover' => 'ti-user-forward',
            'ortho_complaint' => 'ti-bone',
            'injury_history' => 'ti-file-injury',
            'pain_mobility_assessment' => 'ti-walk',
            'joint_limb_examination' => 'ti-stethoscope',
            'neurovascular_status' => 'ti-pulse',
            'imaging' => 'ti-scan',
            'procedure_plan' => 'ti-tools',
            'cast_splint_plan' => 'ti-bandage',
            'surgical_complaint' => 'ti-message-circle',
            'surgical_history' => 'ti-file-description',
            'wound_assessment' => 'ti-bandage',
            'local_or_abdominal_exam' => 'ti-stethoscope',
            'theatre_referral' => 'ti-building-hospital',
            'post_op_instructions' => 'ti-notes',
            default => null,
        };

        if ($specialtyIcon) {
            return $specialtyIcon;
        }

        return match ($this->canonicalSectionKey($sectionKey)) {
            'complaints' => 'ti-message-report',
            'hopc' => 'ti-file-description',
            'examination' => 'ti-zoom-check',
            'diagnosis' => 'ti-report-medical',
            'investigations' => 'ti-test-pipe',
            'treatments' => 'ti-target-arrow',
            'prescription' => 'ti-prescription',
            'procedures' => 'ti-activity-heartbeat',
            'tasks' => 'ti-checklist',
            'notes', 'summary' => 'ti-notes',
            default => 'ti-layout-board',
        };
    }
}
