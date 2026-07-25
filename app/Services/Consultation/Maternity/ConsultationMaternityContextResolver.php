<?php

namespace App\Services\Consultation\Maternity;

use App\Data\Consultation\Maternity\ConsultationMaternityContext;
use App\Enums\ConsultationMaternityContextType;
use App\Models\AntenatalVisit;
use App\Models\DeliveryRecord;
use App\Models\LaborEpisode;
use App\Models\MaternityCase;
use App\Models\NewbornRecord;
use App\Models\PostnatalCase;
use App\Models\PregnancyProfile;
use App\Models\VisitConsultationRoute;
use Illuminate\Support\Collection;

/**
 * Phase 14R.2 — resolve the maternity clinical context for a consultation
 * encounter.
 *
 * Resolution order (approved in 14R.1):
 *   1. explicit active link
 *   2. maternity records on the same visit
 *   3. maternity records on the same admission
 *   4. a single active pregnancy profile for the patient
 *   5. none
 *
 * Hard rules — this service NEVER:
 *   - persists a link
 *   - creates a pregnancy profile or any maternity record
 *   - picks "the latest" when several candidate profiles exist
 *   - infers pregnancy from patient sex, complaint, diagnosis, pregnancy test
 *     or specialty profile
 *   - starts ANC, labor, delivery, newborn or postnatal workflows
 */
class ConsultationMaternityContextResolver
{
    public function __construct(
        private readonly ConsultationMaternityLinkService $links,
    ) {}

    public function resolve(VisitConsultationRoute $consultation): ConsultationMaternityContext
    {
        $consultation->loadMissing(['visit.admission', 'patient']);

        $explicit = $this->fromExplicitLinks($consultation);

        if ($explicit) {
            return $explicit;
        }

        // Fast path (Phase 14R.3.1): the visit/admission fallbacks scan six
        // maternity tables each. Every inferable context ultimately roots in a
        // pregnancy profile belonging to THIS patient, so if the patient has no
        // pregnancy profile at all there is nothing to infer. One cheap
        // existence check replaces ~14 queries for the common case.
        if (! PregnancyProfile::query()->where('patient_id', $consultation->patient_id)->exists()) {
            return ConsultationMaternityContext::none($consultation);
        }

        return $this->fromVisit($consultation)
            ?? $this->fromAdmission($consultation)
            ?? $this->fromSingleActiveProfile($consultation)
            ?? ConsultationMaternityContext::none($consultation);
    }

    /**
     * Phase 14R.4 — explicit links ONLY, with no inference fallback.
     *
     * Gynaecology uses this: a gynaecology consultation must never acquire a
     * maternity context from the same visit, the same admission, or the mere
     * existence of one active pregnancy profile. Reuses the same explicit-link
     * chain validation as resolve() — nothing is duplicated.
     */
    public function resolveExplicitOnly(VisitConsultationRoute $consultation): ConsultationMaternityContext
    {
        $consultation->loadMissing(['visit.admission', 'patient']);

        return $this->fromExplicitLinks($consultation)
            ?? ConsultationMaternityContext::none($consultation);
    }

    /* ── A. Explicit links ─────────────────────────────────────────────── */

    private function fromExplicitLinks(VisitConsultationRoute $consultation): ?ConsultationMaternityContext
    {
        $activeLinks = $this->links->getActiveLinks($consultation);

        if ($activeLinks->isEmpty()) {
            return null;
        }

        $warnings = [];
        $patientId = (int) $consultation->patient_id;

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

            $targetPatientId = $target instanceof NewbornRecord || $target instanceof PostnatalCase
                ? (int) $target->mother_patient_id
                : (int) ($target->patient_id ?? 0);

            if ($targetPatientId !== $patientId) {
                $warnings[] = __('consultation_maternity.warnings.link_patient_mismatch', [
                    'context' => $link->context_type?->label() ?? '—',
                ]);
            }
        }

        if ($warnings !== []) {
            return ConsultationMaternityContext::invalid($consultation, $warnings, $activeLinks);
        }

        $byType = $activeLinks->keyBy(fn ($link) => $link->context_type?->value);

        $profile = $activeLinks->firstWhere('pregnancy_profile_id', '!=', null)?->pregnancyProfile;

        return $this->buildResolved(
            consultation: $consultation,
            source: ConsultationMaternityContext::SOURCE_EXPLICIT,
            profile: $profile,
            maternityCase: $byType->get(ConsultationMaternityContextType::MATERNITY_CASE->value)?->maternityCase,
            antenatalVisit: $byType->get(ConsultationMaternityContextType::ANC_VISIT->value)?->antenatalVisit,
            laborEpisode: $byType->get(ConsultationMaternityContextType::LABOR->value)?->laborEpisode,
            deliveryRecord: $byType->get(ConsultationMaternityContextType::DELIVERY->value)?->deliveryRecord,
            newbornRecord: $byType->get(ConsultationMaternityContextType::NEWBORN->value)?->newbornRecord,
            postnatalCase: $byType->get(ConsultationMaternityContextType::POSTNATAL->value)?->postnatalCase,
            activeLinks: $activeLinks,
        );
    }

    /* ── B. Same visit ─────────────────────────────────────────────────── */

    private function fromVisit(VisitConsultationRoute $consultation): ?ConsultationMaternityContext
    {
        $visitId = $consultation->visit_id;

        if (! $visitId) {
            return null;
        }

        $candidates = $this->profileIdsFrom('visit_id', $visitId);

        return $this->resolveFromCandidates(
            $consultation,
            $candidates,
            ConsultationMaternityContext::SOURCE_VISIT,
        );
    }

    /* ── C. Same admission ─────────────────────────────────────────────── */

    private function fromAdmission(VisitConsultationRoute $consultation): ?ConsultationMaternityContext
    {
        $admission = $consultation->visit?->admission;

        if (! $admission) {
            return null;
        }

        $candidates = $this->profileIdsFrom('admission_id', $admission->id);

        return $this->resolveFromCandidates(
            $consultation,
            $candidates,
            ConsultationMaternityContext::SOURCE_ADMISSION,
        );
    }

    /* ── D. Single active pregnancy profile ────────────────────────────── */

    private function fromSingleActiveProfile(VisitConsultationRoute $consultation): ?ConsultationMaternityContext
    {
        $profiles = PregnancyProfile::query()
            ->where('patient_id', $consultation->patient_id)
            ->active()
            ->orderBy('id')
            ->get();

        if ($profiles->isEmpty()) {
            return null;
        }

        if ($profiles->count() > 1) {
            return ConsultationMaternityContext::ambiguous(
                $consultation,
                ConsultationMaternityContext::SOURCE_ACTIVE_PROFILE,
                $profiles,
                [__('consultation_maternity.warnings.multiple_active_profiles')],
            );
        }

        return $this->buildFromProfile(
            $consultation,
            $profiles->first(),
            ConsultationMaternityContext::SOURCE_ACTIVE_PROFILE,
        );
    }

    /* ── Shared helpers ────────────────────────────────────────────────── */

    /**
     * Distinct pregnancy-profile ids reachable from maternity records that
     * share the given column value (visit_id / admission_id).
     *
     * @return list<int>
     */
    private function profileIdsFrom(string $column, int $value): array
    {
        $ids = collect();

        foreach ([MaternityCase::class, AntenatalVisit::class, LaborEpisode::class,
            DeliveryRecord::class, PostnatalCase::class, NewbornRecord::class] as $model) {
            $ids = $ids->merge(
                $model::query()
                    ->where($column, $value)
                    ->whereNotNull('pregnancy_profile_id')
                    ->distinct()
                    ->pluck('pregnancy_profile_id')
            );
        }

        return $ids->filter()->unique()->values()->all();
    }

    /**
     * Turn a candidate id list into a resolved / ambiguous context, or null to
     * continue to the next fallback.
     *
     * @param  list<int>  $candidateIds
     */
    private function resolveFromCandidates(
        VisitConsultationRoute $consultation,
        array $candidateIds,
        string $source,
    ): ?ConsultationMaternityContext {
        if ($candidateIds === []) {
            return null;
        }

        if (count($candidateIds) > 1) {
            return ConsultationMaternityContext::ambiguous(
                $consultation,
                $source,
                PregnancyProfile::query()->whereIn('id', $candidateIds)->orderBy('id')->get(),
                [__('consultation_maternity.warnings.multiple_candidate_profiles')],
            );
        }

        $profile = PregnancyProfile::query()->find($candidateIds[0]);

        return $profile
            ? $this->buildFromProfile($consultation, $profile, $source)
            : null;
    }

    /**
     * Assemble the surrounding maternity records for a resolved profile.
     * Queries are bounded (latest-of-each) and eager-loaded.
     */
    private function buildFromProfile(
        VisitConsultationRoute $consultation,
        PregnancyProfile $profile,
        string $source,
    ): ConsultationMaternityContext {
        $laborEpisode = LaborEpisode::query()
            ->where('pregnancy_profile_id', $profile->id)
            ->latest('started_at')->latest('id')
            ->first();

        $deliveryRecord = DeliveryRecord::query()
            ->where('pregnancy_profile_id', $profile->id)
            ->latest('delivery_at')->latest('id')
            ->first();

        $newbornRecords = NewbornRecord::query()
            ->where('pregnancy_profile_id', $profile->id)
            ->orderBy('birth_order')->orderBy('id')
            ->get();

        return $this->buildResolved(
            consultation: $consultation,
            source: $source,
            profile: $profile,
            maternityCase: MaternityCase::query()
                ->where('pregnancy_profile_id', $profile->id)
                ->latest('id')->first(),
            antenatalVisit: AntenatalVisit::query()
                ->where('pregnancy_profile_id', $profile->id)
                ->latest('visit_date')->latest('id')->first(),
            laborEpisode: $laborEpisode,
            deliveryRecord: $deliveryRecord,
            newbornRecord: $newbornRecords->first(),
            postnatalCase: PostnatalCase::query()
                ->where('pregnancy_profile_id', $profile->id)
                ->latest('id')->first(),
            newbornRecords: $newbornRecords,
        );
    }

    private function buildResolved(
        VisitConsultationRoute $consultation,
        string $source,
        ?PregnancyProfile $profile,
        ?MaternityCase $maternityCase = null,
        ?AntenatalVisit $antenatalVisit = null,
        ?LaborEpisode $laborEpisode = null,
        ?DeliveryRecord $deliveryRecord = null,
        ?NewbornRecord $newbornRecord = null,
        ?PostnatalCase $postnatalCase = null,
        ?Collection $activeLinks = null,
        ?Collection $newbornRecords = null,
    ): ConsultationMaternityContext {
        return new ConsultationMaternityContext(
            status: ConsultationMaternityContext::STATUS_RESOLVED,
            resolutionSource: $source,
            consultationRoute: $consultation,
            pregnancyProfile: $profile,
            maternityCase: $maternityCase,
            antenatalVisit: $antenatalVisit,
            laborEpisode: $laborEpisode,
            deliveryRecord: $deliveryRecord,
            primaryNewbornRecord: $newbornRecord,
            postnatalCase: $postnatalCase,
            admission: $consultation->visit?->admission,
            activeLinks: $activeLinks ?? collect(),
            newbornRecords: $newbornRecords ?? collect(),
            candidateProfiles: collect(),
        );
    }
}
