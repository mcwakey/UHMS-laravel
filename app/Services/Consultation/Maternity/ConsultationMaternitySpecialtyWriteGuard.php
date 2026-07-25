<?php

namespace App\Services\Consultation\Maternity;

use App\Data\Consultation\Maternity\ConsultationMaternityContext;
use App\Enums\ConsultationMaternityContextType;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\VisitConsultationRoute;

/**
 * Phase 14R.3 — server-side enforcement of the approved field-level
 * source-of-truth matrix for the Obstetrics specialty profile.
 *
 * UI read-only rendering is not enough: this guard is called by the actual
 * specialty-entry write path so a hand-crafted HTTP/API request cannot write a
 * maternity-owned field.
 *
 * It activates ONLY when all four conditions hold:
 *   1. the active specialty profile is Obstetrics
 *   2. the workspace feature flag is enabled
 *   3. the write-guard feature flag is enabled
 *   4. an explicit, valid pregnancy-profile link exists for the consultation
 *
 * If any condition is false, existing behaviour is preserved untouched.
 *
 * It never deletes, rewrites or reinterprets historical specialty entries —
 * it only blocks NEW writes to guarded fields.
 */
class ConsultationMaternitySpecialtyWriteGuard
{
    public const PROFILE_CODE = 'obstetrics';

    /**
     * Maternity-owned fields per section, keyed by section.
     *
     * Everything NOT listed here stays consultation-owned and writable —
     * notably `obstetric_history.previous_complications`,
     * `fetal_assessment.lie` and `risk_assessment.action_plan`, which remain
     * writable inside otherwise-guarded sections (mixed sections).
     *
     * `current_pregnancy` and `birth_plan` are deliberately NOT guarded in
     * this phase: order-set actions still patch them (deferred to 14R.4).
     *
     * @var array<string, list<string>>
     */
    private const GUARDED_FIELDS = [
        'obstetric_history' => [
            'gravida', 'para', 'abortions', 'living_children', 'previous_c_section',
        ],
        'lmp_edd_gestational_age' => [
            'lmp', 'edd', 'gestational_age_weeks', 'gestational_age_days', 'dating_method',
        ],
        'antenatal_vitals' => [
            'blood_pressure', 'weight', 'temperature', 'pulse', 'urine_protein', 'urine_glucose',
        ],
        'fetal_assessment' => [
            'fundal_height', 'fetal_heart_rate', 'fetal_movement', 'presentation',
        ],
        'risk_assessment' => [
            'risk_level', 'risk_factors',
        ],
    ];

    /**
     * Consultation-owned field in `obstetric_history` that maps to a different
     * maternity column name, recorded so the projection layer and any future
     * migration use the correct target.
     */
    public const FIELD_NAME_MAP = [
        'previous_c_section' => 'previous_caesarean',
    ];

    public function __construct(
        private readonly ConsultationMaternityContextResolver $resolver,
    ) {}

    public function workspaceEnabled(): bool
    {
        return (bool) config('consultation.maternity_context.obstetrics_workspace_enabled', false);
    }

    /**
     * The write guard is inert unless the workspace flag is also on — enforced
     * here rather than relying on deployment discipline.
     */
    public function guardEnabled(): bool
    {
        return $this->workspaceEnabled()
            && (bool) config('consultation.maternity_context.obstetrics_write_guard_enabled', false);
    }

    /** Fields this guard owns for a section (empty when the section is unguarded). */
    public function guardedFieldsFor(string $sectionKey): array
    {
        return self::GUARDED_FIELDS[$sectionKey] ?? [];
    }

    /** @return array<string, list<string>> */
    public function matrix(): array
    {
        return self::GUARDED_FIELDS;
    }

    /**
     * Whether the guard should apply to this consultation + profile right now.
     *
     * @param  ConsultationMaternityContext|null  $context  pass a pre-resolved
     *         context to avoid re-resolving on the workspace hot path.
     */
    public function applies(
        VisitConsultationRoute $consultation,
        ?ConsultationSpecialtyProfile $profile,
        ?ConsultationMaternityContext $context = null,
    ): bool {
        if (! $this->guardEnabled()) {
            return false;
        }

        if (! $profile || $profile->code !== self::PROFILE_CODE) {
            return false;
        }

        $context ??= $this->resolver->resolve($consultation);

        // Only an EXPLICIT, valid link activates the guard. Inferred context is
        // a suggestion until the clinician confirms it.
        return $context->isResolved()
            && $context->isExplicit()
            && $context->pregnancyProfile !== null
            && $this->hasExplicitProfileLink($context);
    }

    /**
     * Fields in the submitted entry that are blocked for this section.
     *
     * Returns [] when the guard does not apply, so callers can treat an empty
     * result as "allowed" without branching on flags themselves.
     *
     * @param  array<string, mixed>  $entry
     * @return list<string> blocked field names
     */
    public function blockedFields(
        VisitConsultationRoute $consultation,
        ?ConsultationSpecialtyProfile $profile,
        string $sectionKey,
        array $entry,
        ?ConsultationMaternityContext $context = null,
    ): array {
        $guarded = $this->guardedFieldsFor($sectionKey);

        if ($guarded === [] || $entry === []) {
            return [];
        }

        if (! $this->applies($consultation, $profile, $context)) {
            return [];
        }

        // Only fields actually present in the submission are blocked, so a
        // mixed section still saves its consultation-owned siblings.
        return array_values(array_intersect(array_keys($entry), $guarded));
    }

    /**
     * Localised validation messages for blocked fields, keyed by field name.
     *
     * @param  list<string>  $blocked
     * @return array<string, string>
     */
    public function validationMessages(array $blocked): array
    {
        $messages = [];

        foreach ($blocked as $field) {
            $messages[$field] = __('consultation_maternity.write_guard.field_blocked', [
                'field' => __('consultation_specialties.forms.fields.'.$field) !== 'consultation_specialties.forms.fields.'.$field
                    ? __('consultation_specialties.forms.fields.'.$field)
                    : str($field)->replace('_', ' ')->title()->toString(),
            ]);
        }

        return $messages;
    }

    /** An active pregnancy-profile link (not just any maternity link) must exist. */
    private function hasExplicitProfileLink(ConsultationMaternityContext $context): bool
    {
        return (bool) $context->activeLinks?->contains(
            fn ($link) => $link->context_type === ConsultationMaternityContextType::PREGNANCY_PROFILE
        );
    }
}
