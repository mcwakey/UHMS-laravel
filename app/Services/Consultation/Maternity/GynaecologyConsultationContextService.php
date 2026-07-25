<?php

namespace App\Services\Consultation\Maternity;

use App\Data\Consultation\Maternity\ConsultationMaternityContext;
use App\Data\Consultation\Maternity\GynaecologyWorkspaceViewModel;
use App\Enums\PregnancyDatingMethod;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use Illuminate\Support\Carbon;

/**
 * Phase 14R.4 — prepares the small Gynaecology pregnancy-context card.
 *
 * Gynaecology stays a NON-pregnancy reproductive-health workspace:
 *   - context is resolved from EXPLICIT bridge links only (never the
 *     visit/admission/single-active-profile fallback chain);
 *   - candidate profiles are queried only when the clinician opens the
 *     selector, never on page load;
 *   - the specialty profile is never switched to Obstetrics;
 *   - ANC / labor / delivery / newborn / postnatal actions are never offered.
 */
class GynaecologyConsultationContextService
{
    public const PROFILE_CODE = 'gynecology';

    /** Dating methods that outrank an LMP and therefore block adoption. */
    private const DATING_METHODS_BLOCKING_LMP = [
        PregnancyDatingMethod::EARLY_ULTRASOUND,
        PregnancyDatingMethod::LATE_ULTRASOUND,
        PregnancyDatingMethod::ASSISTED_REPRODUCTION,
    ];

    /** @var array<int, ConsultationMaternityContext> request-scoped memo */
    private array $contextMemo = [];

    public function __construct(
        private readonly ConsultationMaternityContextResolver $resolver,
        private readonly ConsultationMaternitySpecialtyWriteGuard $writeGuard,
    ) {}

    /** Explicit-only resolution, memoised for the request. */
    public function context(VisitConsultationRoute $consultation): ConsultationMaternityContext
    {
        return $this->contextMemo[$consultation->id]
            ??= $this->resolver->resolveExplicitOnly($consultation);
    }

    public function build(
        VisitConsultationRoute $consultation,
        ?ConsultationSpecialtyProfile $profile,
        ?User $user = null,
        ?string $returnUrl = null,
    ): GynaecologyWorkspaceViewModel {
        $contextEnabled = $this->writeGuard->gynaecologyContextEnabled();
        $isGynaecology = $profile?->code === self::PROFILE_CODE;

        // Flag off, or another specialty → do no work at all.
        if (! $contextEnabled || ! $isGynaecology) {
            return GynaecologyWorkspaceViewModel::disabled($contextEnabled, $isGynaecology);
        }

        $context = $this->context($consultation);
        $pregnancyProfile = $context->isResolved() ? $context->pregnancyProfile : null;
        $hasExplicitLink = $pregnancyProfile !== null;

        $guardApplies = $hasExplicitLink
            && $this->writeGuard->applies($consultation, $profile, $context);

        $savedLmp = $this->savedConsultationLmp($consultation);

        return new GynaecologyWorkspaceViewModel(
            contextEnabled: true,
            writeGuardEnabled: $guardApplies,
            isGynaecology: true,
            hasExplicitLink: $hasExplicitLink,
            pregnancyProfile: $pregnancyProfile,
            latestAncDate: $context->antenatalVisit?->visit_date?->format('d M Y'),
            activeStageBadge: $this->stageBadge($context),
            maternityProfileUrl: $pregnancyProfile
                ? $this->maternityProfileUrl($pregnancyProfile)
                : null,
            returnUrl: $returnUrl,
            availableActions: $this->availableActions($user, $hasExplicitLink),
            writePolicy: $guardApplies ? $this->writeGuard->matrixFor(self::PROFILE_CODE) : [],
            warnings: $context->warnings,
            positivePregnancyTestRecorded: $this->hasPositivePregnancyTest($consultation),
            savedConsultationLmp: $savedLmp?->toDateString(),
            lmpAdoptionState: $this->lmpAdoptionState($savedLmp, $pregnancyProfile),
        );
    }

    /**
     * The LMP persisted in this consultation's `menstrual_history` entry.
     *
     * Read server-side only — a client-submitted value is never trusted, and an
     * unsaved browser form value cannot be adopted.
     */
    public function savedConsultationLmp(VisitConsultationRoute $consultation): ?Carbon
    {
        $entry = ConsultationSpecialtyEntry::query()
            ->where('consultation_id', $consultation->id)
            ->where('section_key', 'menstrual_history')
            ->latest('id')
            ->first();

        $lmp = $entry?->entry['lmp'] ?? null;

        if (! filled($lmp)) {
            return null;
        }

        try {
            return Carbon::parse($lmp)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Whether the saved consultation LMP may be adopted for pregnancy dating.
     * One-way only, and never an automatic sync.
     */
    public function lmpAdoptionState(?Carbon $savedLmp, ?PregnancyProfile $profile): string
    {
        if (! $profile) {
            return GynaecologyWorkspaceViewModel::ADOPT_UNAVAILABLE;
        }

        if (! $savedLmp) {
            return GynaecologyWorkspaceViewModel::ADOPT_NO_SOURCE;
        }

        // Scan/ART dating outranks an LMP — never silently replace it.
        if (in_array($profile->dating_method, self::DATING_METHODS_BLOCKING_LMP, true)) {
            return GynaecologyWorkspaceViewModel::ADOPT_DATING_LOCKED;
        }

        $profileLmp = $profile->last_menstrual_period
            ? Carbon::parse($profile->last_menstrual_period)->startOfDay()
            : null;

        if (! $profileLmp) {
            return GynaecologyWorkspaceViewModel::ADOPT_AVAILABLE;
        }

        return $profileLmp->equalTo($savedLmp)
            ? GynaecologyWorkspaceViewModel::ADOPT_IDEMPOTENT
            : GynaecologyWorkspaceViewModel::ADOPT_CONFLICT;
    }

    /**
     * Tolerant positive-pregnancy-test detection.
     *
     * `sexual_sti_history.pregnancy_test` is a free-text field, so the stored
     * representation is not guaranteed — match common positive spellings and
     * treat anything else (including blank) as not positive. This only ever
     * surfaces a non-blocking affordance; it never creates or links anything.
     */
    public function hasPositivePregnancyTest(VisitConsultationRoute $consultation): bool
    {
        $entry = ConsultationSpecialtyEntry::query()
            ->where('consultation_id', $consultation->id)
            ->where('section_key', 'sexual_sti_history')
            ->latest('id')
            ->first();

        $value = strtolower(trim((string) ($entry?->entry['pregnancy_test'] ?? '')));

        if ($value === '') {
            return false;
        }

        // Guard against "not positive" / "negative" phrasing.
        if (str_contains($value, 'neg') || str_contains($value, 'not ')) {
            return false;
        }

        return str_contains($value, 'pos')
            || in_array($value, ['+', '++', 'yes', 'reactive'], true);
    }

    private function stageBadge(ConsultationMaternityContext $context): ?string
    {
        return match (true) {
            $context->postnatalCase !== null => __('consultation_maternity.panel.postnatal'),
            $context->deliveryRecord !== null => __('consultation_maternity.ribbon.delivery_status'),
            $context->laborEpisode !== null => __('consultation_maternity.panel.labor_delivery'),
            $context->antenatalVisit !== null => __('consultation_maternity.panel.anc'),
            $context->pregnancyProfile !== null => __('consultation_maternity.panel.pregnancy'),
            default => null,
        };
    }

    private function maternityProfileUrl(PregnancyProfile $profile): ?string
    {
        try {
            return route('admin.maternity.pregnancies.show', $profile);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Gynaecology exposes only context management + profile creation + LMP
     * adoption. ANC / labor / delivery / newborn / postnatal actions are NEVER
     * offered here, even to a user who holds those maternity permissions.
     *
     * @return array<string, bool>
     */
    private function availableActions(?User $user, bool $hasExplicitLink): array
    {
        if (! $user) {
            return [];
        }

        return [
            'view' => $user->can('consultation.maternity_context.view'),
            'link' => $user->can('consultation.maternity_context.link'),
            'unlink' => $hasExplicitLink && $user->can('consultation.maternity_context.unlink'),
            'relink' => $hasExplicitLink && $user->can('consultation.maternity_context.link'),
            'create_profile' => $user->can('consultation.maternity_context.create_profile')
                && $user->can('maternity.pregnancy.create'),
            'adopt_lmp' => $hasExplicitLink
                && $user->can('consultation.maternity_context.adopt_lmp')
                && $user->can('maternity.pregnancy.update'),
        ];
    }
}
