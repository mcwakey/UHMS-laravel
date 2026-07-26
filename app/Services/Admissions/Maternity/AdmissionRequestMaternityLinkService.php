<?php

namespace App\Services\Admissions\Maternity;

use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\LogModule;
use App\Models\AdmissionRequest;
use App\Models\AdmissionRequestMaternityLink;
use App\Models\User;
use App\Services\Maternity\Context\AbstractMaternityLinkService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Phase 14R.5 — clinical maternity context attached to an Admission Request.
 *
 * The request's OPERATIONAL origin (`source_type` / `source_id`) is never
 * touched by this service. A request stays "raised by Emergency" while also
 * carrying "about this Labor Episode".
 *
 * Rows are created only by explicit handoff actions or known idempotent
 * service paths — never inferred from a diagnosis or a legacy source id.
 */
class AdmissionRequestMaternityLinkService extends AbstractMaternityLinkService
{
    protected function linkModel(): string
    {
        return AdmissionRequestMaternityLink::class;
    }

    protected function sourceColumn(): string
    {
        return 'admission_request_id';
    }

    protected function sourcePatientId(Model $source): ?int
    {
        return $source->patient_id === null ? null : (int) $source->patient_id;
    }

    protected function logModule(): LogModule
    {
        return LogModule::ADMISSION;
    }

    protected function eventPrefix(): string
    {
        return 'ADMISSION_REQUEST_MATERNITY_CONTEXT';
    }

    protected function logContext(Model $source): array
    {
        return array_filter([
            'admission_request_id' => $source->id,
            'patient_id' => $source->patient_id,
            'visit_id' => $source->visit_id,
        ], fn ($value) => $value !== null);
    }

    /**
     * Copy a set of already-validated maternity targets onto a request as
     * handoff context. Idempotent: an identical active link is reused, and a
     * conflicting one is reported rather than overwritten.
     *
     * @param  iterable<Model>  $targets
     * @return array{linked: list<int>, reused: list<int>, conflicts: list<string>}
     */
    public function attachTargets(
        AdmissionRequest $request,
        iterable $targets,
        User $actor,
        ConsultationMaternityLinkRole $role = ConsultationMaternityLinkRole::HANDOFF,
        array $metadata = [],
    ): array {
        $result = ['linked' => [], 'reused' => [], 'conflicts' => []];

        foreach ($targets as $target) {
            if (! $target instanceof Model) {
                continue;
            }

            $contextType = $this->targets->contextTypeFor($target);

            if (! $contextType) {
                continue;
            }

            $existing = $this->getActiveLink($request, $contextType);

            if ($existing && (int) $existing->targetId() === (int) $target->getKey()) {
                $result['reused'][] = (int) $existing->id;

                continue;
            }

            if ($existing) {
                // A different active target for the same context type is a real
                // conflict — never silently replaced.
                $result['conflicts'][] = $contextType->value;

                continue;
            }

            $result['linked'][] = (int) $this->link(
                $request, $target, $actor, $role, null, $metadata
            )->id;
        }

        return $result;
    }

    /**
     * The maternity targets actively linked to a request, ready to propagate.
     *
     * @return Collection<int, Model>
     */
    public function activeTargets(AdmissionRequest $request): Collection
    {
        return $this->getActiveLinks($request)
            ->map(fn ($link) => $link->targetRecord())
            ->filter()
            ->values();
    }
}
