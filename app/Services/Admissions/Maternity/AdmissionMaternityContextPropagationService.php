<?php

namespace App\Services\Admissions\Maternity;

use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\LogModule;
use App\Models\Admission;
use App\Models\AdmissionRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Maternity\Context\MaternityContextTargetService;
use App\Services\Maternity\Context\MaternityIntegrationFlags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Phase 14R.5 — carry an Admission Request's maternity context onto the
 * Admission it converts into.
 *
 * Runs inside the SAME transaction as the conversion (the caller invokes it
 * from within AdmissionRequestService::convertToAdmission's transaction), so a
 * propagation failure rolls the conversion back rather than leaving a half
 * converted admission with no context and no error.
 *
 * Invariants:
 *   - the Admission Request is preserved exactly as it is;
 *   - the operational origin (emergency / consultation / maternity / direct) is
 *     never rewritten;
 *   - active links are never duplicated — a second run is a no-op;
 *   - a maternity stage record whose `admission_id` points at a DIFFERENT
 *     admission is never overwritten; it is reported as a conflict for review.
 */
class AdmissionMaternityContextPropagationService
{
    public function __construct(
        private readonly AdmissionRequestMaternityLinkService $requestLinks,
        private readonly AdmissionMaternityLinkService $admissionLinks,
        private readonly MaternityContextTargetService $targets,
        private readonly ActivityLogService $activityLog,
        private readonly MaternityIntegrationFlags $flags,
    ) {}

    public function enabled(): bool
    {
        return $this->flags->admissionContextEnabled();
    }

    /**
     * Propagate every active maternity context from the request to the
     * admission.
     *
     * @return array{propagated: list<int>, reused: list<int>, conflicts: list<array<string,mixed>>, skipped: bool}
     */
    public function propagate(AdmissionRequest $request, Admission $admission, User $actor): array
    {
        $result = ['propagated' => [], 'reused' => [], 'conflicts' => [], 'skipped' => false];

        if (! $this->enabled()) {
            $result['skipped'] = true;

            return $result;
        }

        $links = $this->requestLinks->getActiveLinks($request);

        if ($links->isEmpty()) {
            return $result;
        }

        foreach ($links as $requestLink) {
            $target = $requestLink->targetRecord();

            if (! $target) {
                continue;
            }

            $contextType = $requestLink->context_type;

            if (! $contextType) {
                continue;
            }

            $existing = $this->admissionLinks->getActiveLink($admission, $contextType);

            if ($existing) {
                if ((int) $existing->targetId() === (int) $target->getKey()) {
                    // Idempotent: repeated propagation reuses the link.
                    $result['reused'][] = (int) $existing->id;
                } else {
                    $result['conflicts'][] = [
                        'context_type' => $contextType->value,
                        'existing_target_id' => $existing->targetId(),
                        'incoming_target_id' => $target->getKey(),
                    ];
                }

                continue;
            }

            $result['propagated'][] = (int) $this->admissionLinks->link(
                $admission,
                $target,
                $actor,
                ConsultationMaternityLinkRole::HANDOFF,
                null,
                [AdmissionMaternityLinkService::META_SOURCE_REQUEST => $request->id],
            )->id;

            $conflict = $this->adoptAdmissionId($target, $admission, $actor);

            if ($conflict) {
                $result['conflicts'][] = $conflict;
            }
        }

        $this->logPropagation($request, $admission, $actor, $result);

        return $result;
    }

    /**
     * Populate a maternity stage record's `admission_id` when — and only when —
     * it is still null.
     *
     * Already-this-admission is idempotent. Pointing at ANOTHER admission is a
     * conflict: the value is left alone and reported for review, because
     * overwriting it would silently move a delivery or labor episode from one
     * admission to another.
     *
     * `PregnancyProfile.admission_id` is deliberately excluded — the profile is
     * longitudinal and outlives any single admission; `admission_maternity_links`
     * is the durable statement of current admission linkage.
     *
     * @return array<string, mixed>|null a conflict descriptor, when one occurs
     */
    private function adoptAdmissionId(Model $target, Admission $admission, User $actor): ?array
    {
        if ($target instanceof \App\Models\PregnancyProfile) {
            return null;
        }

        if (! array_key_exists('admission_id', $target->getAttributes())) {
            return null;
        }

        $current = $target->admission_id === null ? null : (int) $target->admission_id;

        if ($current === (int) $admission->id) {
            return null;
        }

        if ($current !== null) {
            return [
                'context_type' => $this->targets->contextTypeFor($target)?->value,
                'record_id' => $target->getKey(),
                'existing_admission_id' => $current,
                'incoming_admission_id' => (int) $admission->id,
                'reason' => 'admission_id_conflict',
            ];
        }

        // Safe domain write: a single nullable operational FK on a record that
        // has none. No clinical field is touched and no status changes.
        DB::table($target->getTable())
            ->where('id', $target->getKey())
            ->whereNull('admission_id')
            ->update(['admission_id' => $admission->id, 'updated_at' => now()]);

        $target->setAttribute('admission_id', $admission->id);
        $target->syncOriginalAttribute('admission_id');

        return null;
    }

    /** Identifier-only audit. No clinical content is logged. */
    private function logPropagation(
        AdmissionRequest $request,
        Admission $admission,
        User $actor,
        array $result,
    ): void {
        $event = $result['conflicts'] === []
            ? 'ADMISSION_MATERNITY_CONTEXT_PROPAGATED'
            : 'ADMISSION_MATERNITY_CONTEXT_PROPAGATION_CONFLICT';

        $this->activityLog->log(
            LogModule::ADMISSION,
            $event,
            [
                'patient_id' => $admission->patient_id,
                'visit_id' => $admission->visit_id,
                'causer' => $actor,
                'metadata' => [
                    'source_module' => 'admission_request',
                    'admission_request_id' => $request->id,
                    'admission_id' => $admission->id,
                    'propagated_link_ids' => $result['propagated'],
                    'reused_link_ids' => $result['reused'],
                    'conflict_count' => count($result['conflicts']),
                ],
            ],
            $admission,
            $event,
        );
    }
}
