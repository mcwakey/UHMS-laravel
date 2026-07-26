<?php

namespace App\Services\Maternity\Context;

use App\Data\Maternity\OperationalMaternityContext;
use App\Enums\ConsultationMaternityContextType;
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
 * Phase 14R.5 — the resolution behaviour Emergency and Admission share.
 *
 * Hard rules, identical to the 14R.2 consultation resolver — this class NEVER:
 *   - persists a link;
 *   - creates a pregnancy profile or any maternity record;
 *   - creates an Emergency case or starts a Labor episode;
 *   - picks "the latest" when several candidate profiles exist;
 *   - infers pregnancy from complaint, diagnosis, danger signs, risk flags,
 *     patient sex or a pregnancy test.
 */
abstract class AbstractOperationalMaternityContextResolver
{
    public function __construct(
        protected readonly AbstractMaternityLinkService $links,
        protected readonly MaternityContextTargetService $targets,
    ) {}

    /** The patient whose maternity records this source may legitimately reach. */
    abstract protected function patientId(Model $source): ?int;

    /* ── A. Explicit links ─────────────────────────────────────────────── */

    protected function fromExplicitLinks(Model $source): ?OperationalMaternityContext
    {
        $activeLinks = $this->links->getActiveLinks($source);

        if ($activeLinks->isEmpty()) {
            return null;
        }

        $warnings = [];
        $patientId = (int) $this->patientId($source);

        // Validate every link's target chain. An inconsistent explicit link is
        // never silently ignored.
        foreach ($activeLinks as $link) {
            $target = $link->targetRecord();

            if (! $target) {
                $warnings[] = __('consultation_maternity.warnings.link_target_missing', [
                    'context' => $link->context_type?->label() ?? '—',
                ]);

                continue;
            }

            if ($this->targets->owningPatientId($target) !== $patientId) {
                $warnings[] = __('consultation_maternity.warnings.link_patient_mismatch', [
                    'context' => $link->context_type?->label() ?? '—',
                ]);
            }
        }

        if ($warnings !== []) {
            return OperationalMaternityContext::invalid($source, $warnings, $activeLinks);
        }

        $byType = $activeLinks->keyBy(fn ($link) => $link->context_type?->value);

        return $this->buildResolved(
            source: $source,
            status: OperationalMaternityContext::STATUS_RESOLVED,
            resolutionSource: OperationalMaternityContext::SOURCE_EXPLICIT,
            profile: $activeLinks->firstWhere('pregnancy_profile_id', '!=', null)?->pregnancyProfile,
            maternityCase: $byType->get(ConsultationMaternityContextType::MATERNITY_CASE->value)?->maternityCase,
            antenatalVisit: $byType->get(ConsultationMaternityContextType::ANC_VISIT->value)?->antenatalVisit,
            laborEpisode: $byType->get(ConsultationMaternityContextType::LABOR->value)?->laborEpisode,
            deliveryRecord: $byType->get(ConsultationMaternityContextType::DELIVERY->value)?->deliveryRecord,
            newbornRecord: $byType->get(ConsultationMaternityContextType::NEWBORN->value)?->newbornRecord,
            postnatalCase: $byType->get(ConsultationMaternityContextType::POSTNATAL->value)?->postnatalCase,
            activeLinks: $activeLinks,
        );
    }

    /* ── Shared helpers ────────────────────────────────────────────────── */

    /**
     * Cheap existence check used as a fast path: every inferable context roots
     * in a pregnancy profile for THIS patient, so no profile means nothing to
     * infer and the multi-table scans can be skipped entirely.
     */
    protected function patientHasAnyPregnancyProfile(Model $source): bool
    {
        $patientId = $this->patientId($source);

        return $patientId !== null
            && PregnancyProfile::query()->where('patient_id', $patientId)->exists();
    }

    /**
     * Distinct pregnancy-profile ids reachable from maternity records sharing a
     * column value (visit_id / admission_id).
     *
     * Six tables are scanned, but as a single UNION rather than six round
     * trips. `toBase()` keeps each model's global scopes (notably soft
     * deletes), so the results are identical to querying them separately.
     *
     * @return list<int>
     */
    protected function profileIdsFrom(string $column, int $value): array
    {
        $union = null;

        foreach ([MaternityCase::class, AntenatalVisit::class, LaborEpisode::class,
            DeliveryRecord::class, PostnatalCase::class, NewbornRecord::class] as $model) {
            $query = $model::query()
                ->where($column, $value)
                ->whereNotNull('pregnancy_profile_id')
                ->select('pregnancy_profile_id')
                ->toBase();

            $union = $union === null ? $query : $union->union($query);
        }

        return collect($union?->pluck('pregnancy_profile_id') ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Turn a candidate id list into a resolved/suggested/ambiguous context, or
     * null to continue to the next fallback.
     *
     * @param  list<int>  $candidateIds
     */
    protected function resolveFromCandidates(
        Model $source,
        array $candidateIds,
        string $resolutionSource,
        string $status = OperationalMaternityContext::STATUS_RESOLVED,
        array $warnings = [],
    ): ?OperationalMaternityContext {
        if ($candidateIds === []) {
            return null;
        }

        if (count($candidateIds) > 1) {
            return OperationalMaternityContext::ambiguous(
                $source,
                $resolutionSource,
                PregnancyProfile::query()->whereIn('id', $candidateIds)->orderBy('id')->get(),
                [__('consultation_maternity.warnings.multiple_candidate_profiles')],
            );
        }

        $profile = PregnancyProfile::query()->find($candidateIds[0]);

        if (! $profile) {
            return null;
        }

        // A SUGGESTION only has to identify the pregnancy — it is not a linked
        // context, so the surrounding stage records are not fetched. That keeps
        // an unconfirmed suggestion cheap; confirming it produces an explicit
        // link, which then resolves the full picture.
        if ($status === OperationalMaternityContext::STATUS_SUGGESTED) {
            return $this->buildResolved(
                source: $source,
                status: $status,
                resolutionSource: $resolutionSource,
                profile: $profile,
                warnings: $warnings,
            );
        }

        return $this->buildFromProfile($source, $profile, $resolutionSource, $status, $warnings);
    }

    /**
     * Assemble the surrounding maternity records for a resolved profile.
     * Bounded (latest-of-each) with the newborn collection fetched once, so a
     * multi-newborn delivery costs no extra queries.
     */
    protected function buildFromProfile(
        Model $source,
        PregnancyProfile $profile,
        string $resolutionSource,
        string $status = OperationalMaternityContext::STATUS_RESOLVED,
        array $warnings = [],
    ): OperationalMaternityContext {
        $newbornRecords = NewbornRecord::query()
            ->where('pregnancy_profile_id', $profile->id)
            ->orderBy('birth_order')->orderBy('id')
            ->get();

        return $this->buildResolved(
            source: $source,
            status: $status,
            resolutionSource: $resolutionSource,
            profile: $profile,
            maternityCase: MaternityCase::query()
                ->where('pregnancy_profile_id', $profile->id)
                ->latest('id')->first(),
            antenatalVisit: AntenatalVisit::query()
                ->where('pregnancy_profile_id', $profile->id)
                ->latest('visit_date')->latest('id')->first(),
            laborEpisode: LaborEpisode::query()
                ->where('pregnancy_profile_id', $profile->id)
                ->latest('started_at')->latest('id')->first(),
            deliveryRecord: DeliveryRecord::query()
                ->where('pregnancy_profile_id', $profile->id)
                ->latest('delivery_at')->latest('id')->first(),
            newbornRecord: $newbornRecords->first(),
            postnatalCase: PostnatalCase::query()
                ->where('pregnancy_profile_id', $profile->id)
                ->latest('id')->first(),
            newbornRecords: $newbornRecords,
            warnings: $warnings,
        );
    }

    protected function buildResolved(
        Model $source,
        string $status,
        string $resolutionSource,
        ?PregnancyProfile $profile,
        ?MaternityCase $maternityCase = null,
        ?AntenatalVisit $antenatalVisit = null,
        ?LaborEpisode $laborEpisode = null,
        ?DeliveryRecord $deliveryRecord = null,
        ?NewbornRecord $newbornRecord = null,
        ?PostnatalCase $postnatalCase = null,
        ?Collection $activeLinks = null,
        ?Collection $newbornRecords = null,
        array $warnings = [],
    ): OperationalMaternityContext {
        return new OperationalMaternityContext(
            status: $status,
            resolutionSource: $resolutionSource,
            source: $source,
            pregnancyProfile: $profile,
            maternityCase: $maternityCase,
            antenatalVisit: $antenatalVisit,
            laborEpisode: $laborEpisode,
            deliveryRecord: $deliveryRecord,
            primaryNewbornRecord: $newbornRecord,
            postnatalCase: $postnatalCase,
            activeLinks: $activeLinks ?? collect(),
            newbornRecords: $newbornRecords ?? collect(),
            candidateProfiles: collect(),
            warnings: $warnings,
        );
    }
}
