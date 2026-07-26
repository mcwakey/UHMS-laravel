<?php

namespace App\Services\Consultation\Maternity;

use App\Enums\ConsultationMaternityContextType;
use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\LogModule;
use App\Models\PostnatalCase;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\ActivityLogService;
use App\Services\Maternity\Context\MaternityIntegrationFlags;
use Illuminate\Support\Collection;

/**
 * Phase 14R.5 — postnatal review from a Consultation encounter.
 *
 * The consultation may LINK an existing same-patient PostnatalCase and show its
 * readiness and latest observations READ-ONLY. It never records an observation:
 * mother and newborn observations stay Maternity-owned and are entered in the
 * Maternity postnatal workspace.
 *
 * A second PostnatalCase is never created, consultation completion and
 * readiness are unchanged, and the consultation note remains encounter-owned.
 */
class ConsultationPostnatalReviewService
{
    /** How many recent observations the read-only projection shows. */
    private const RECENT_OBSERVATIONS = 3;

    public function __construct(
        private readonly ConsultationMaternityLinkService $links,
        private readonly ActivityLogService $activityLog,
        private readonly MaternityIntegrationFlags $flags,
    ) {}

    public function enabled(): bool
    {
        return $this->flags->consultationHandoffsEnabled();
    }

    /**
     * Postnatal cases belonging to this consultation's patient (as mother).
     * Queried only when the clinician opens the selector.
     *
     * @return Collection<int, PostnatalCase>
     */
    public function candidates(VisitConsultationRoute $consultation): Collection
    {
        return PostnatalCase::query()
            ->where('mother_patient_id', $consultation->patient_id)
            ->latest('opened_at')
            ->latest('id')
            ->limit(20)
            ->get();
    }

    /**
     * Link an existing PostnatalCase for review. Idempotent for the same case;
     * a different active case requires an explicit relink.
     *
     * Patient ownership is validated by the link service through
     * `mother_patient_id`, exactly as the 14R.2 bridge does.
     */
    public function linkForReview(
        VisitConsultationRoute $consultation,
        PostnatalCase $case,
        User $actor,
        ConsultationMaternityLinkRole $role = ConsultationMaternityLinkRole::REVIEWED,
    ) {
        $link = $this->links->link($consultation, $case, $actor, $role);

        $this->activityLog->log(
            LogModule::CONSULTATION,
            'POSTNATAL_CONSULTATION_REVIEW_LINKED',
            [
                'patient_id' => $consultation->patient_id,
                'visit_id' => $consultation->visit_id,
                'causer' => $actor,
                'metadata' => [
                    'source_module' => 'maternity',
                    'source_record_id' => $case->id,
                    'target_module' => 'consultation',
                    'target_record_id' => $consultation->id,
                    'pregnancy_profile_id' => $case->pregnancy_profile_id,
                    'handoff_role' => $role->value,
                ],
            ],
            $link,
            'POSTNATAL_CONSULTATION_REVIEW_LINKED',
        );

        return $link;
    }

    /** The PostnatalCase actively linked to this consultation, if any. */
    public function linkedCase(VisitConsultationRoute $consultation): ?PostnatalCase
    {
        return $this->links
            ->getActiveLink($consultation, ConsultationMaternityContextType::POSTNATAL)
            ?->postnatalCase;
    }

    /**
     * READ-ONLY projection of a postnatal case for the consultation view.
     *
     * Every value is projected from the maternity record; nothing here is
     * writable from the consultation, and no observation is duplicated into a
     * consultation entry.
     *
     * @return array<string, mixed>
     */
    public function projection(PostnatalCase $case): array
    {
        $case->loadMissing(['motherObservations', 'newbornObservations']);

        return [
            'case_id' => $case->id,
            'status' => $case->status instanceof \BackedEnum
                ? (method_exists($case->status, 'label') ? $case->status->label() : $case->status->value)
                : $case->status,
            'owner_label' => __('maternity_handoffs.ownership.maternity_longitudinal_record'),
            'readiness' => [
                'mother_ready_at' => $case->mother_ready_at?->format('d M Y H:i'),
                'newborn_ready_at' => $case->newborn_ready_at?->format('d M Y H:i'),
                'ready_for_discharge_at' => $case->ready_for_discharge_at?->format('d M Y H:i'),
                'referral_required' => (bool) $case->referral_required,
                'advisory_note' => __('maternity_handoffs.postnatal.readiness_advisory'),
            ],
            'mother_observations' => $this->recentObservations($case->motherObservations),
            'newborn_observations' => $this->recentObservations($case->newbornObservations),
            'record_url' => $this->postnatalUrl($case),
            'record_label' => __('maternity_handoffs.cards.record_ref', [
                'type' => __('maternity_handoffs.cards.postnatal'), 'id' => $case->id,
            ]),
        ];
    }

    /**
     * Timestamps and identifiers only — the projection deliberately shows WHEN
     * an observation was recorded and by whom, not its clinical values, which
     * belong to the maternity workspace.
     *
     * @return list<array<string, mixed>>
     */
    private function recentObservations(?Collection $observations): array
    {
        if (! $observations || $observations->isEmpty()) {
            return [];
        }

        return $observations
            ->sortByDesc(fn ($observation) => $observation->observed_at ?? $observation->created_at)
            ->take(self::RECENT_OBSERVATIONS)
            ->map(fn ($observation) => [
                'id' => $observation->id,
                'observed_at' => ($observation->observed_at ?? $observation->created_at)?->format('d M Y H:i'),
                'status' => $observation->status instanceof \BackedEnum
                    ? $observation->status->value
                    : $observation->status,
            ])
            ->values()
            ->all();
    }

    private function postnatalUrl(PostnatalCase $case): ?string
    {
        try {
            return route('admin.maternity.postnatal.show', $case);
        } catch (\Throwable) {
            return null;
        }
    }
}
