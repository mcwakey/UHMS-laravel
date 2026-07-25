<?php

namespace App\Data\Consultation\Maternity;

use App\Models\AntenatalVisit;
use App\Models\ConsultationMaternityLink;
use App\Models\DeliveryRecord;
use App\Models\LaborEpisode;
use App\Models\MaternityCase;
use App\Models\NewbornRecord;
use App\Models\PostnatalCase;
use App\Models\PregnancyProfile;
use App\Models\VisitConsultationRoute;
use Illuminate\Support\Collection;

/**
 * Phase 14R.2 — typed result of maternity-context resolution for a
 * consultation encounter.
 *
 * Read-only. Resolution never persists a link and never creates a maternity
 * record; this object only reports what was found.
 */
class ConsultationMaternityContext
{
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_AMBIGUOUS = 'ambiguous';
    public const STATUS_NONE = 'none';
    public const STATUS_INVALID = 'invalid';

    public const SOURCE_EXPLICIT = 'explicit';
    public const SOURCE_VISIT = 'visit';
    public const SOURCE_ADMISSION = 'admission';
    public const SOURCE_ACTIVE_PROFILE = 'active_profile';
    public const SOURCE_NONE = 'none';

    /**
     * @param  Collection<int, ConsultationMaternityLink>  $activeLinks
     * @param  Collection<int, NewbornRecord>  $newbornRecords
     * @param  Collection<int, PregnancyProfile>  $candidateProfiles
     * @param  list<string>  $warnings
     */
    public function __construct(
        public readonly string $status,
        public readonly string $resolutionSource,
        public readonly VisitConsultationRoute $consultationRoute,
        public readonly ?PregnancyProfile $pregnancyProfile = null,
        public readonly ?MaternityCase $maternityCase = null,
        public readonly ?AntenatalVisit $antenatalVisit = null,
        public readonly ?LaborEpisode $laborEpisode = null,
        public readonly ?DeliveryRecord $deliveryRecord = null,
        public readonly ?NewbornRecord $primaryNewbornRecord = null,
        public readonly ?PostnatalCase $postnatalCase = null,
        public readonly mixed $admission = null,
        public readonly ?Collection $activeLinks = null,
        public readonly ?Collection $newbornRecords = null,
        public readonly ?Collection $candidateProfiles = null,
        public readonly array $warnings = [],
    ) {}

    public static function none(VisitConsultationRoute $route, array $warnings = []): self
    {
        return new self(
            status: self::STATUS_NONE,
            resolutionSource: self::SOURCE_NONE,
            consultationRoute: $route,
            warnings: $warnings,
        );
    }

    /**
     * Several candidate pregnancy profiles were found. The caller must ask the
     * clinician to choose — the resolver never picks one.
     *
     * @param  Collection<int, PregnancyProfile>  $candidates
     */
    public static function ambiguous(
        VisitConsultationRoute $route,
        string $source,
        Collection $candidates,
        array $warnings = [],
    ): self {
        return new self(
            status: self::STATUS_AMBIGUOUS,
            resolutionSource: $source,
            consultationRoute: $route,
            candidateProfiles: $candidates,
            warnings: $warnings,
        );
    }

    /** An explicit link exists but its target chain does not validate. */
    public static function invalid(
        VisitConsultationRoute $route,
        array $warnings,
        ?Collection $activeLinks = null,
    ): self {
        return new self(
            status: self::STATUS_INVALID,
            resolutionSource: self::SOURCE_EXPLICIT,
            consultationRoute: $route,
            activeLinks: $activeLinks,
            warnings: $warnings,
        );
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
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

    /** True when the context came from an explicit clinician-made link. */
    public function isExplicit(): bool
    {
        return $this->resolutionSource === self::SOURCE_EXPLICIT;
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }

    /**
     * Small, non-clinical shape for logging/debugging. Deliberately excludes
     * clinical content — only identifiers and status.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'resolution_source' => $this->resolutionSource,
            'consultation_route_id' => $this->consultationRoute->id,
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
