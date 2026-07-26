<?php

namespace App\Data\Maternity;

use App\Models\AntenatalVisit;
use App\Models\DeliveryRecord;
use App\Models\LaborEpisode;
use App\Models\MaternityCase;
use App\Models\NewbornRecord;
use App\Models\PostnatalCase;
use App\Models\PregnancyProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Phase 14R.5 — typed result of maternity-context resolution for an OPERATIONAL
 * source (an Emergency case or an Admission), mirroring the 14R.2
 * ConsultationMaternityContext shape.
 *
 * Read-only. Resolution never persists a link, never creates a maternity record
 * and never starts a maternity workflow.
 *
 * SUGGESTED is the one status the consultation bridge has no equivalent for: an
 * Emergency case on the same visit as a maternity record MAY be displayed as a
 * suggestion, clearly labelled, and is never persisted without a clinician
 * confirming it.
 */
class OperationalMaternityContext
{
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_SUGGESTED = 'suggested';
    public const STATUS_AMBIGUOUS = 'ambiguous';
    public const STATUS_NONE = 'none';
    public const STATUS_INVALID = 'invalid';

    public const SOURCE_EXPLICIT = 'explicit';
    public const SOURCE_REQUEST = 'admission_request';
    public const SOURCE_ADMISSION_RECORDS = 'admission_records';
    public const SOURCE_VISIT = 'visit';
    public const SOURCE_ACTIVE_PROFILE = 'active_profile';
    public const SOURCE_NONE = 'none';

    /**
     * @param  Collection<int, Model>|null  $activeLinks
     * @param  Collection<int, NewbornRecord>|null  $newbornRecords
     * @param  Collection<int, PregnancyProfile>|null  $candidateProfiles
     * @param  list<string>  $warnings
     */
    public function __construct(
        public readonly string $status,
        public readonly string $resolutionSource,
        public readonly Model $source,
        public readonly ?PregnancyProfile $pregnancyProfile = null,
        public readonly ?MaternityCase $maternityCase = null,
        public readonly ?AntenatalVisit $antenatalVisit = null,
        public readonly ?LaborEpisode $laborEpisode = null,
        public readonly ?DeliveryRecord $deliveryRecord = null,
        public readonly ?NewbornRecord $primaryNewbornRecord = null,
        public readonly ?PostnatalCase $postnatalCase = null,
        public readonly ?Collection $activeLinks = null,
        public readonly ?Collection $newbornRecords = null,
        public readonly ?Collection $candidateProfiles = null,
        public readonly array $warnings = [],
    ) {}

    public static function none(Model $source, array $warnings = []): self
    {
        return new self(
            status: self::STATUS_NONE,
            resolutionSource: self::SOURCE_NONE,
            source: $source,
            warnings: $warnings,
        );
    }

    /**
     * Several candidate pregnancy profiles exist. The clinician must choose —
     * the resolver never picks "the latest".
     *
     * @param  Collection<int, PregnancyProfile>  $candidates
     */
    public static function ambiguous(
        Model $source,
        string $resolutionSource,
        Collection $candidates,
        array $warnings = [],
    ): self {
        return new self(
            status: self::STATUS_AMBIGUOUS,
            resolutionSource: $resolutionSource,
            source: $source,
            candidateProfiles: $candidates,
            warnings: $warnings,
        );
    }

    /** An explicit link exists but its target chain does not validate. */
    public static function invalid(Model $source, array $warnings, ?Collection $activeLinks = null): self
    {
        return new self(
            status: self::STATUS_INVALID,
            resolutionSource: self::SOURCE_EXPLICIT,
            source: $source,
            activeLinks: $activeLinks,
            warnings: $warnings,
        );
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /** Displayed as a suggestion only. Never persisted without confirmation. */
    public function isSuggested(): bool
    {
        return $this->status === self::STATUS_SUGGESTED;
    }

    public function isAmbiguous(): bool
    {
        return $this->status === self::STATUS_AMBIGUOUS;
    }

    public function isNone(): bool
    {
        return $this->status === self::STATUS_NONE;
    }

    public function isInvalid(): bool
    {
        return $this->status === self::STATUS_INVALID;
    }

    public function isExplicit(): bool
    {
        return $this->resolutionSource === self::SOURCE_EXPLICIT;
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }

    /**
     * Identifier-only shape for logging. Deliberately excludes every clinical
     * measurement, observation and note.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'resolution_source' => $this->resolutionSource,
            'source_type' => class_basename($this->source),
            'source_id' => $this->source->getKey(),
            'pregnancy_profile_id' => $this->pregnancyProfile?->id,
            'maternity_case_id' => $this->maternityCase?->id,
            'antenatal_visit_id' => $this->antenatalVisit?->id,
            'labor_episode_id' => $this->laborEpisode?->id,
            'delivery_record_id' => $this->deliveryRecord?->id,
            'newborn_record_id' => $this->primaryNewbornRecord?->id,
            'postnatal_case_id' => $this->postnatalCase?->id,
            'active_link_ids' => $this->activeLinks?->pluck('id')->all() ?? [],
            'candidate_profile_ids' => $this->candidateProfiles?->pluck('id')->all() ?? [],
            'warnings' => $this->warnings,
        ];
    }
}
