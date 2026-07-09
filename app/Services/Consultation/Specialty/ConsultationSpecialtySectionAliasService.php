<?php

namespace App\Services\Consultation\Specialty;

use Illuminate\Support\Facades\Lang;

/**
 * Phase 16.5/16.6/16.7 compatibility and presentation layer for the shared
 * consultation sections.
 *
 * Legacy duplicate sections are hidden from the doctor workspace but their
 * database rows and saved entries are preserved. This service is the single
 * source of truth for:
 *
 *   1. canonical section key resolution (legacy duplicate -> shared section)
 *   2. profile-aware display labels for shared/core sections
 *   3. summary preview headings (same labels, no separate hardcoding)
 *   4. legacy alias lookups
 *   5. merged legacy entry data for the summary builder
 *
 * so readiness, the workspace sidebar, quick actions, the summary builder,
 * and the admin UI all agree on the same wording.
 */
class ConsultationSpecialtySectionAliasService
{
    public function __construct(
        private readonly ConsultationSpecialtySectionComponentRegistry $registry,
    ) {}

    /**
     * Profile-scoped legacy duplicate section key => canonical shared section key.
     *
     * @var array<string, array<string, string>>
     */
    private const PROFILE_ALIASES = [
        'physiotherapy' => [
            'presenting_problem' => 'complaints',
        ],
        'ophthalmology' => [
            'eye_complaint' => 'complaints',
        ],
        'obstetrics' => [
            'lab_screening' => 'investigations',
            'ultrasound_findings' => 'investigations',
        ],
        'gynecology' => [
            'gyne_complaint' => 'complaints',
        ],
        'ent' => [
            'ent_complaint' => 'complaints',
        ],
        'pediatrics' => [
            'pediatric_complaint' => 'complaints',
        ],
        'emergency' => [
            'urgent_investigations' => 'investigations',
            'urgent_procedures' => 'procedures',
            'medications_given' => 'prescription',
            'emergency_complaint' => 'complaints',
        ],
        'orthopedics' => [
            'imaging' => 'investigations',
            'procedure_plan' => 'procedures',
            'ortho_complaint' => 'complaints',
        ],
        'surgery' => [
            'procedure_plan' => 'procedures',
            'surgical_complaint' => 'complaints',
        ],
        'dental' => [
            'dental_diagnosis' => 'diagnosis',
            'dental_xray' => 'investigations',
            'dental_procedures' => 'procedures',
            'dental_complaint' => 'complaints',
        ],
    ];

    /**
     * Phase 16.7: canonical shared/core section keys that can carry a
     * profile-aware presentation label. `follow_up` is a schema-backed
     * structured section reused across most specialty profiles (distinct
     * from `tasks`, which general medicine/physiotherapy use instead), so it
     * is treated as a labelable "canonical" concept alongside the true
     * CORE_SECTIONS entries.
     *
     * @var list<string>
     */
    private const PRESENTABLE_CANONICAL_KEYS = [
        'complaints',
        'diagnosis',
        'investigations',
        'procedures',
        'prescription',
        'tasks',
        'follow_up',
        'summary',
    ];

    /**
     * @return array<string, string> legacy => canonical for the given profile
     */
    public function aliasMapFor(string $profileCode): array
    {
        return self::PROFILE_ALIASES[$profileCode] ?? [];
    }

    /**
     * @return array<string, string> legacy key => canonical key across all profiles
     */
    public function globalAliasMap(): array
    {
        $map = [];
        foreach (self::PROFILE_ALIASES as $aliases) {
            $map += $aliases;
        }

        return $map;
    }

    /**
     * Resolve a section key to its canonical shared-section key, chaining
     * the profile-scoped duplicate-alias map (Phase 16.5/16.6) with the
     * section component registry's global aliases (e.g. `progress_notes` ->
     * `notes`) and structured-schema precedence rules.
     */
    public function canonicalSectionKey(string $profileCode, string $sectionKey): string
    {
        $viaProfileAlias = $this->aliasMapFor($profileCode)[$sectionKey] ?? $sectionKey;

        return $this->registry->canonicalSectionKey($viaProfileAlias);
    }

    /**
     * @return list<string> the canonical keys that support a profile-aware presentation label
     */
    public function presentableCanonicalKeys(): array
    {
        return self::PRESENTABLE_CANONICAL_KEYS;
    }

    public function isDuplicateSection(string $profileCode, string $sectionKey): bool
    {
        return array_key_exists($sectionKey, $this->aliasMapFor($profileCode));
    }

    /**
     * @return list<string> legacy duplicate keys that map to the canonical key
     */
    public function legacySectionKeysFor(string $profileCode, string $canonicalKey): array
    {
        return array_keys(
            array_filter($this->aliasMapFor($profileCode), fn (string $canonical) => $canonical === $canonicalKey),
        );
    }

    /**
     * Key the doctor workspace should present for a section: canonical for
     * hidden duplicates, otherwise the key itself.
     */
    public function displaySectionKey(string $profileCode, string $sectionKey): string
    {
        return $this->canonicalSectionKey($profileCode, $sectionKey);
    }

    /**
     * Profile-specific display label for a canonical section (e.g. "Eye
     * Complaint" for ophthalmology's `complaints` section, "Orthopedic
     * Imaging / Investigations" for orthopedics' `investigations`), or null
     * when the profile has no override and the generic canonical label
     * applies. Single source: `section_presentation_labels.*` in the
     * consultation_specialties lang file.
     */
    public function displayLabelFor(string $profileCode, string $canonicalSectionKey): ?string
    {
        $key = "consultation_specialties.section_presentation_labels.{$profileCode}.{$canonicalSectionKey}";

        return Lang::has($key) ? __($key) : null;
    }

    /**
     * Effective presentation label for a canonical section: the profile
     * override when one exists, otherwise the generic canonical section
     * label (`sections.*`), otherwise a title-cased fallback of the key.
     */
    public function presentationLabelFor(string $profileCode, string $canonicalSectionKey): string
    {
        return $this->displayLabelFor($profileCode, $canonicalSectionKey)
            ?? $this->genericSectionLabel($canonicalSectionKey);
    }

    /**
     * Summary preview heading for a template row. `$templateKey` is the
     * summary template's own internal row key (which is often a legacy
     * name like `eye_complaint` or a renamed concept like
     * `treatment_prescription`); `$canonicalKey` is what that row actually
     * represents (e.g. `complaints`, `prescription`). This is the single
     * heading source for the summary builder — it never falls back to a
     * heading hardcoded independently of the workspace display layer.
     */
    public function previewHeadingFor(string $profileCode, string $templateKey, string $canonicalKey): string
    {
        $override = $this->displayLabelFor($profileCode, $canonicalKey);
        if ($override) {
            return $override;
        }

        $summaryKey = "consultation_specialties.summary_builder.sections.{$templateKey}";
        if (Lang::has($summaryKey)) {
            return __($summaryKey);
        }

        return $this->presentationLabelFor($profileCode, $canonicalKey);
    }

    private function genericSectionLabel(string $canonicalSectionKey): string
    {
        $key = "consultation_specialties.sections.{$canonicalSectionKey}";

        return Lang::has($key) ? __($key) : str($canonicalSectionKey)->replace('_', ' ')->title()->toString();
    }

    /**
     * @return list<string> every legacy duplicate key for the profile
     */
    public function duplicateSectionKeysFor(string $profileCode): array
    {
        return array_keys($this->aliasMapFor($profileCode));
    }

    /**
     * Merge saved legacy entries into synthetic "{canonical}_review" buckets
     * (e.g. `investigations_review`) without touching the original keys, so
     * summary templates can render legacy data under canonical headings while
     * legacy-key consumers keep working.
     *
     * @param  array<string, array<string, mixed>>  $entriesBySection
     * @return array<string, array<string, mixed>>
     */
    public function withMergedLegacyEntries(string $profileCode, array $entriesBySection): array
    {
        foreach ($this->aliasMapFor($profileCode) as $legacyKey => $canonicalKey) {
            $legacyEntry = $entriesBySection[$legacyKey] ?? [];
            if ($legacyEntry === []) {
                continue;
            }

            $bucket = $canonicalKey.'_review';
            $entriesBySection[$bucket] = ($entriesBySection[$bucket] ?? []) + $legacyEntry;
        }

        return $entriesBySection;
    }
}
