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
        'prescription',
        'procedures',
        'tasks',
        'notes',
        'summary',
        'completion_readiness',
    ];

    private const ALIASES = [
        'eye_complaint' => 'complaints',
        'dental_complaint' => 'complaints',
        'presenting_problem' => 'complaints',
        'progress_notes' => 'notes',
        'follow_up' => 'tasks',
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
            'prescription' => 'ti-prescription',
            'procedures' => 'ti-activity-heartbeat',
            'tasks' => 'ti-checklist',
            'notes', 'summary' => 'ti-notes',
            default => 'ti-layout-board',
        };
    }
}
