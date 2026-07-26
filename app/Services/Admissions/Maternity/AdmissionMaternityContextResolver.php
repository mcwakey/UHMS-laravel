<?php

namespace App\Services\Admissions\Maternity;

use App\Data\Maternity\OperationalMaternityContext;
use App\Models\Admission;
use App\Services\Maternity\Context\AbstractOperationalMaternityContextResolver;
use App\Services\Maternity\Context\MaternityContextTargetService;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 14R.5 — resolve the maternity context for an Admission.
 *
 * Resolution order (as specified):
 *   1. explicit active Admission ↔ Maternity links
 *   2. active links carried from the originating Admission Request
 *   3. maternity records whose `admission_id` matches this admission
 *   4. a SINGLE pregnancy-profile candidate
 *   5. none / ambiguous
 *
 * Never chooses between multiple pregnancy profiles, never creates a link
 * during resolution, never creates a maternity record. Direct non-maternity
 * admissions short-circuit on the fast path and cost nothing.
 */
class AdmissionMaternityContextResolver extends AbstractOperationalMaternityContextResolver
{
    /** @var array<int, OperationalMaternityContext> request-scoped memo */
    private array $memo = [];

    public function __construct(
        AdmissionMaternityLinkService $links,
        MaternityContextTargetService $targets,
        private readonly AdmissionRequestMaternityLinkService $requestLinks,
    ) {
        parent::__construct($links, $targets);
    }

    public function resolve(Admission $admission): OperationalMaternityContext
    {
        return $this->memo[$admission->id] ??= $this->doResolve($admission);
    }

    /** Explicit admission links only — used by write paths. */
    public function resolveExplicitOnly(Admission $admission): OperationalMaternityContext
    {
        return $this->fromExplicitLinks($admission) ?? OperationalMaternityContext::none($admission);
    }

    private function doResolve(Admission $admission): OperationalMaternityContext
    {
        $explicit = $this->fromExplicitLinks($admission);

        if ($explicit) {
            return $explicit;
        }

        // Fast path: a direct non-maternity admission has no pregnancy profile
        // at all, so none of the fallbacks below can produce anything.
        if (! $this->patientHasAnyPregnancyProfile($admission)) {
            return OperationalMaternityContext::none($admission);
        }

        return $this->fromAdmissionRequest($admission)
            ?? $this->fromAdmissionRecords($admission)
            ?? $this->fromSingleProfile($admission)
            ?? OperationalMaternityContext::none($admission);
    }

    /**
     * Context the originating Admission Request still carries. Read only — the
     * corresponding Admission link is created by explicit propagation, never
     * here.
     */
    private function fromAdmissionRequest(Admission $admission): ?OperationalMaternityContext
    {
        if (! $admission->admission_request_id) {
            return null;
        }

        $request = $admission->admissionRequest;

        if (! $request) {
            return null;
        }

        $profileIds = $this->requestLinks->getActiveLinks($request)
            ->pluck('pregnancy_profile_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $this->resolveFromCandidates(
            $admission,
            array_map('intval', $profileIds),
            OperationalMaternityContext::SOURCE_REQUEST,
            OperationalMaternityContext::STATUS_RESOLVED,
            [__('maternity_handoffs.admission.context_from_request')],
        );
    }

    /** Maternity stage records that already point at this admission. */
    private function fromAdmissionRecords(Admission $admission): ?OperationalMaternityContext
    {
        return $this->resolveFromCandidates(
            $admission,
            $this->profileIdsFrom('admission_id', (int) $admission->id),
            OperationalMaternityContext::SOURCE_ADMISSION_RECORDS,
        );
    }

    /**
     * A single active pregnancy profile for the patient. More than one is
     * ambiguous — never "the latest".
     */
    private function fromSingleProfile(Admission $admission): ?OperationalMaternityContext
    {
        $profiles = \App\Models\PregnancyProfile::query()
            ->where('patient_id', $admission->patient_id)
            ->active()
            ->orderBy('id')
            ->get();

        if ($profiles->isEmpty()) {
            return null;
        }

        if ($profiles->count() > 1) {
            return OperationalMaternityContext::ambiguous(
                $admission,
                OperationalMaternityContext::SOURCE_ACTIVE_PROFILE,
                $profiles,
                [__('consultation_maternity.warnings.multiple_active_profiles')],
            );
        }

        return $this->buildFromProfile(
            $admission,
            $profiles->first(),
            OperationalMaternityContext::SOURCE_ACTIVE_PROFILE,
        );
    }

    protected function patientId(Model $source): ?int
    {
        return $source->patient_id === null ? null : (int) $source->patient_id;
    }
}
