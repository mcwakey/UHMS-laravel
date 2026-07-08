<?php

namespace App\Services\Consultation\Specialty;

/**
 * Phase 16.5 compatibility layer between legacy duplicate specialty section
 * keys and the canonical shared consultation sections.
 *
 * Legacy duplicate sections are hidden from the doctor workspace but their
 * database rows and saved entries are preserved. This service is the single
 * source of truth for which legacy key maps to which canonical shared
 * section, so readiness, the summary builder, and the admin UI keep honoring
 * previously saved data.
 */
class ConsultationSpecialtySectionAliasService
{
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
     * Phase 16.6: profile-specific display label for a canonical section,
     * keyed by the lang string that used to be that profile's own section
     * label (so translations only need to exist once, in `sections.*`).
     *
     * @var array<string, array<string, string>>
     */
    private const DISPLAY_LABEL_TRANSLATION_KEYS = [
        'complaints' => [
            'physiotherapy' => 'presenting_problem',
            'ophthalmology' => 'eye_complaint',
            'dental' => 'dental_complaint',
            'obstetrics' => 'current_complaint',
            'gynecology' => 'gyne_complaint',
            'ent' => 'ent_complaint',
            'pediatrics' => 'pediatric_complaint',
            'emergency' => 'emergency_complaint',
            'orthopedics' => 'ortho_complaint',
            'surgery' => 'surgical_complaint',
        ],
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

    public function canonicalSectionKey(string $profileCode, string $sectionKey): string
    {
        return $this->aliasMapFor($profileCode)[$sectionKey] ?? $sectionKey;
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
     * Complaint" for ophthalmology's `complaints` section), or null when the
     * profile has no override and the generic canonical label applies.
     */
    public function displayLabelFor(string $profileCode, string $canonicalSectionKey): ?string
    {
        $translationKey = self::DISPLAY_LABEL_TRANSLATION_KEYS[$canonicalSectionKey][$profileCode] ?? null;

        return $translationKey ? __('consultation_specialties.sections.'.$translationKey) : null;
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
