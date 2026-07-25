<?php

namespace App\Data\Consultation\Maternity;

use App\Models\PregnancyProfile;

/**
 * Phase 14R.4 — bounded view model for the Gynaecology pregnancy-context card.
 *
 * Deliberately much smaller than ObstetricWorkspaceViewModel: Gynaecology is a
 * non-pregnancy reproductive-health workspace and must never render the full
 * stage-aware ANC/labor/delivery/postnatal projection.
 */
class GynaecologyWorkspaceViewModel
{
    /** LMP adoption eligibility outcomes. */
    public const ADOPT_AVAILABLE = 'available';       // profile LMP is null → can adopt
    public const ADOPT_IDEMPOTENT = 'already_matches'; // same date already stored
    public const ADOPT_CONFLICT = 'conflict';          // different LMP already stored
    public const ADOPT_DATING_LOCKED = 'dating_locked'; // scan/ART dating wins
    public const ADOPT_NO_SOURCE = 'no_saved_lmp';     // nothing persisted to adopt
    public const ADOPT_UNAVAILABLE = 'unavailable';    // no link / flags off

    /**
     * @param  array<string, bool>  $availableActions
     * @param  array<string, list<string>>  $writePolicy
     * @param  list<string>  $warnings
     */
    public function __construct(
        public readonly bool $contextEnabled,
        public readonly bool $writeGuardEnabled,
        public readonly bool $isGynaecology,
        public readonly bool $hasExplicitLink = false,
        public readonly ?PregnancyProfile $pregnancyProfile = null,
        public readonly ?string $latestAncDate = null,
        public readonly ?string $activeStageBadge = null,
        public readonly ?string $maternityProfileUrl = null,
        public readonly ?string $returnUrl = null,
        public readonly array $availableActions = [],
        public readonly array $writePolicy = [],
        public readonly array $warnings = [],
        public readonly bool $positivePregnancyTestRecorded = false,
        public readonly ?string $savedConsultationLmp = null,
        public readonly string $lmpAdoptionState = self::ADOPT_UNAVAILABLE,
    ) {}

    public static function disabled(bool $contextEnabled = false, bool $isGynaecology = false): self
    {
        return new self(
            contextEnabled: $contextEnabled,
            writeGuardEnabled: false,
            isGynaecology: $isGynaecology,
        );
    }

    /** Nothing renders unless the flag is on AND the profile is Gynaecology. */
    public function shouldRender(): bool
    {
        return $this->contextEnabled && $this->isGynaecology;
    }

    /** The full card only appears for an explicitly linked profile. */
    public function showsCard(): bool
    {
        return $this->shouldRender() && $this->hasExplicitLink && $this->pregnancyProfile !== null;
    }

    /**
     * The discreet "Start or Link Pregnancy Workflow" affordance — shown when
     * nothing is linked. Never an intrusive warning simply because the patient
     * is female or attending a gynaecology clinic.
     */
    public function showsLinkAffordance(): bool
    {
        return $this->shouldRender() && ! $this->hasExplicitLink;
    }

    public function canAdoptLmp(): bool
    {
        return $this->lmpAdoptionState === self::ADOPT_AVAILABLE;
    }

    public function can(string $action): bool
    {
        return (bool) ($this->availableActions[$action] ?? false);
    }

    public function guardedFields(string $sectionKey): array
    {
        return $this->writePolicy[$sectionKey] ?? [];
    }

    public function isFieldReadOnly(string $sectionKey, string $field): bool
    {
        return in_array($field, $this->guardedFields($sectionKey), true);
    }

    public function mode(): string
    {
        if (! $this->contextEnabled) {
            return 'disabled';
        }

        return $this->writeGuardEnabled ? 'guarded' : 'pilot';
    }
}
