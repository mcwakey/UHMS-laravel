<?php

namespace App\Data\Consultation\Maternity;

use Illuminate\Support\Collection;

/**
 * Phase 14R.3 — the complete, bounded view model for the Obstetrics
 * consultation workspace.
 *
 * Everything a ribbon/panel/card needs is precomputed here by
 * ObstetricConsultationContextService. Blade components consume this object
 * and MUST NOT query the database themselves.
 */
class ObstetricWorkspaceViewModel
{
    public const MODE_DISABLED = 'disabled';
    public const MODE_PILOT = 'pilot';          // workspace on, guard off
    public const MODE_GUARDED = 'guarded';      // workspace on, guard on

    /**
     * @param  array<string, mixed>  $pregnancy
     * @param  array<string, mixed>  $anc
     * @param  array<string, mixed>  $labor
     * @param  array<string, mixed>  $delivery
     * @param  array<string, mixed>  $newborn
     * @param  array<string, mixed>  $postnatal
     * @param  array<string, mixed>  $admission
     * @param  array<string, bool>  $availableActions
     * @param  array<string, list<string>>  $writePolicy  section => guarded fields
     * @param  list<string>  $warnings
     * @param  Collection<int, mixed>|null  $candidateProfiles
     */
    public function __construct(
        public readonly bool $workspaceEnabled,
        public readonly bool $writeGuardEnabled,
        public readonly bool $isObstetrics,
        public readonly string $status,
        public readonly string $resolutionSource,
        public readonly bool $isExplicit,
        public readonly array $pregnancy = [],
        public readonly array $anc = [],
        public readonly array $labor = [],
        public readonly array $delivery = [],
        public readonly array $newborn = [],
        public readonly array $postnatal = [],
        public readonly array $admission = [],
        public readonly array $availableActions = [],
        public readonly array $writePolicy = [],
        public readonly array $warnings = [],
        public readonly ?Collection $candidateProfiles = null,
        public readonly ?string $returnUrl = null,
    ) {}

    /** Nothing renders when the feature is off or the profile is not Obstetrics. */
    public function shouldRender(): bool
    {
        return $this->workspaceEnabled && $this->isObstetrics;
    }

    public function mode(): string
    {
        if (! $this->workspaceEnabled) {
            return self::MODE_DISABLED;
        }

        return $this->writeGuardEnabled ? self::MODE_GUARDED : self::MODE_PILOT;
    }

    public function isResolved(): bool
    {
        return $this->status === ConsultationMaternityContext::STATUS_RESOLVED;
    }

    public function isAmbiguous(): bool
    {
        return $this->status === ConsultationMaternityContext::STATUS_AMBIGUOUS;
    }

    public function isNone(): bool
    {
        return $this->status === ConsultationMaternityContext::STATUS_NONE;
    }

    public function isInvalid(): bool
    {
        return $this->status === ConsultationMaternityContext::STATUS_INVALID;
    }

    /** Inferred = resolved from visit/admission/profile but not yet confirmed. */
    public function isInferred(): bool
    {
        return $this->isResolved() && ! $this->isExplicit;
    }

    /** The full ribbon only renders for a confirmed, explicit context. */
    public function showsFullRibbon(): bool
    {
        return $this->shouldRender() && $this->isResolved() && $this->isExplicit;
    }

    /** Inferred context renders a clearly-labelled "suggested" ribbon instead. */
    public function showsSuggestion(): bool
    {
        return $this->shouldRender() && $this->isInferred();
    }

    public function can(string $action): bool
    {
        return (bool) ($this->availableActions[$action] ?? false);
    }

    /** Guarded fields for a section — empty when nothing is guarded. */
    public function guardedFields(string $sectionKey): array
    {
        return $this->writePolicy[$sectionKey] ?? [];
    }

    public function isFieldReadOnly(string $sectionKey, string $field): bool
    {
        return in_array($field, $this->guardedFields($sectionKey), true);
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }

    /** Disabled/never-rendered state, used when the flag is off or profile differs. */
    public static function disabled(bool $workspaceEnabled = false, bool $isObstetrics = false): self
    {
        return new self(
            workspaceEnabled: $workspaceEnabled,
            writeGuardEnabled: false,
            isObstetrics: $isObstetrics,
            status: ConsultationMaternityContext::STATUS_NONE,
            resolutionSource: ConsultationMaternityContext::SOURCE_NONE,
            isExplicit: false,
        );
    }
}
