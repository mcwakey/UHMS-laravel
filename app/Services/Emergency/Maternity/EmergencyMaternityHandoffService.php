<?php

namespace App\Services\Emergency\Maternity;

use App\Enums\AdmissionRequestSource;
use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\LaborEpisodeStatus;
use App\Enums\LogModule;
use App\Models\AdmissionRequest;
use App\Models\AdmissionRequestMaternityLink;
use App\Models\EmergencyCase;
use App\Models\LaborEpisode;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Admissions\AdmissionRequestService;
use App\Services\Admissions\Maternity\AdmissionRequestMaternityLinkService;
use App\Services\Maternity\Context\MaternityLinkException;
use App\Services\Maternity\LaborEpisodeService;
use App\Services\Maternity\PregnancyProfileService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Phase 14R.5 — explicit Emergency → Maternity actions.
 *
 * Every method here is triggered by a clinician pressing a button. NOTHING in
 * this class runs from a danger sign, an obstetric diagnosis, a risk flag, a
 * pregnancy test or a context match.
 *
 * Emergency keeps ownership of triage, bay, vitals, notes, treatment, tasks and
 * disposition throughout. Maternity keeps ownership of the Pregnancy Profile and
 * the Labor Episode. Admission keeps ownership of the request lifecycle.
 */
class EmergencyMaternityHandoffService
{
    public function __construct(
        private readonly EmergencyMaternityLinkService $links,
        private readonly EmergencyMaternityContextResolver $resolver,
        private readonly PregnancyProfileService $profiles,
        private readonly LaborEpisodeService $labor,
        private readonly AdmissionRequestService $admissionRequests,
        private readonly AdmissionRequestMaternityLinkService $requestLinks,
        private readonly ActivityLogService $activityLog,
    ) {}

    /* ── A. Link / create Pregnancy Profile ────────────────────────────── */

    /**
     * Link an EXISTING same-patient pregnancy profile. Idempotent for the same
     * profile; a different active profile requires an explicit relink.
     */
    public function linkPregnancyProfile(
        EmergencyCase $case,
        PregnancyProfile $profile,
        User $actor,
        ?string $reason = null,
    ): Model {
        return $this->links->link(
            $case,
            $profile,
            $actor,
            ConsultationMaternityLinkRole::PRIMARY,
            $reason,
        );
    }

    /**
     * Create a new pregnancy profile through PregnancyProfileService and link
     * it with role=created.
     *
     * Never called implicitly: a positive pregnancy test, an obstetric
     * complaint or an obstetric diagnosis does not reach this method.
     *
     * @param  array<string, mixed>  $data
     * @return array{profile: PregnancyProfile, link: Model}
     */
    public function createPregnancyProfile(EmergencyCase $case, array $data, User $actor): array
    {
        return DB::transaction(function () use ($case, $data, $actor) {
            $profile = $this->profiles->create(array_merge($data, [
                'patient_id' => $case->patient_id,
                'visit_id' => $data['visit_id'] ?? $case->visit_id,
            ]), $actor);

            $link = $this->links->link(
                $case,
                $profile,
                $actor,
                ConsultationMaternityLinkRole::CREATED,
            );

            return ['profile' => $profile, 'link' => $link];
        });
    }

    /* ── B. Start / re-use Labor Episode ───────────────────────────────── */

    /**
     * Statuses that still represent labor in progress. Delivered, referred,
     * transferred, cancelled and closed episodes are finished — reusing one
     * would silently reopen a completed record.
     */
    public const ACTIVE_LABOR_STATUSES = [
        LaborEpisodeStatus::ACTIVE,
        LaborEpisodeStatus::MONITORING,
        LaborEpisodeStatus::DELIVERY_PENDING,
    ];

    /** The in-progress labor episode for a profile, if one exists. */
    public function activeLaborEpisode(PregnancyProfile $profile): ?LaborEpisode
    {
        return LaborEpisode::query()
            ->where('pregnancy_profile_id', $profile->id)
            ->whereIn('status', array_map(
                fn (LaborEpisodeStatus $status) => $status->value,
                self::ACTIVE_LABOR_STATUSES
            ))
            ->latest('started_at')
            ->latest('id')
            ->first();
    }

    /**
     * Start a labor episode, or re-open the active one.
     *
     * Duplicate identity: emergency case + pregnancy profile + active labor
     * episode. Repeated clicks return the same episode; exactly one is ever
     * created.
     *
     * @param  array<string, mixed>  $data
     * @return array{episode: LaborEpisode, reused: bool}
     */
    public function startOrReuseLabor(
        EmergencyCase $case,
        PregnancyProfile $profile,
        array $data,
        User $actor,
    ): array {
        $this->assertProfileLinked($case, $profile);

        return DB::transaction(function () use ($case, $profile, $data, $actor) {
            // Lock the profile row so two simultaneous clicks serialise on it.
            PregnancyProfile::query()->whereKey($profile->id)->lockForUpdate()->first();

            $existing = $this->activeLaborEpisode($profile);

            if ($existing) {
                $this->links->linkForHandoff($case, $existing, $actor, [
                    'emergency_case_id' => $case->id,
                ]);

                return ['episode' => $existing, 'reused' => true];
            }

            // Carry Emergency operational context only where the labor table
            // already has a column for it — nothing new is invented.
            $episode = $this->labor->start($profile, array_merge($data, [
                'patient_id' => $case->patient_id,
                'visit_id' => $data['visit_id'] ?? $case->visit_id,
                'admission_id' => $data['admission_id'] ?? $case->admission_id,
                'referral_source' => $data['referral_source'] ?? 'emergency',
            ]), $actor);

            $this->links->linkForHandoff($case, $episode, $actor, [
                'emergency_case_id' => $case->id,
            ]);

            $this->activityLog->log(
                LogModule::EMERGENCY,
                'EMERGENCY_LABOR_EPISODE_STARTED',
                [
                    'emergency_case_id' => $case->id,
                    'patient_id' => $case->patient_id,
                    'visit_id' => $case->visit_id,
                    'causer' => $actor,
                    'metadata' => [
                        'labor_episode_id' => $episode->id,
                        'pregnancy_profile_id' => $profile->id,
                    ],
                ],
                $episode,
                'EMERGENCY_LABOR_EPISODE_STARTED',
            );

            return ['episode' => $episode, 'reused' => false];
        });
    }

    /* ── C. Create / re-use Admission Request ──────────────────────────── */

    /**
     * The open request this emergency case already raised, if any.
     *
     * Duplicate identity: emergency case + open status. Emergency raises ONE
     * admission request per episode; maternity context is attached to it rather
     * than causing a second request.
     */
    public function existingOpenRequest(EmergencyCase $case): ?AdmissionRequest
    {
        return AdmissionRequest::query()
            ->open()
            ->where('source_type', AdmissionRequestSource::EMERGENCY->value)
            ->where('source_id', $case->id)
            ->latest('id')
            ->first();
    }

    /**
     * Create — or reuse — the Emergency admission request and attach the active
     * maternity contexts to it.
     *
     * The request's operational source stays `emergency` / EmergencyCase id.
     * Emergency disposition history is NOT touched: this raises a request, it
     * does not dispose the case.
     *
     * @param  array<string, mixed>  $data
     * @return array{request: AdmissionRequest, reused: bool, context: array}
     */
    public function createOrReuseAdmissionRequest(EmergencyCase $case, array $data, User $actor): array
    {
        return DB::transaction(function () use ($case, $data, $actor) {
            EmergencyCase::query()->whereKey($case->id)->lockForUpdate()->first();

            $existing = $this->existingOpenRequest($case);
            $reused = $existing !== null;

            $request = $existing ?? $this->admissionRequests->create([
                'patient_id' => $case->patient_id,
                'visit_id' => $case->visit_id,
                'source_type' => AdmissionRequestSource::EMERGENCY->value,
                'source_id' => $case->id,
                'requested_ward_id' => $data['requested_ward_id'] ?? null,
                'priority' => $data['priority'] ?? 'urgent',
                'provisional_diagnosis' => $data['provisional_diagnosis'] ?? null,
                'clinical_summary' => $data['clinical_summary'] ?? null,
            ], $actor);

            // Attach whatever maternity context is EXPLICITLY linked to the
            // emergency case. A suggested context is never propagated.
            $context = $this->resolver->resolveExplicitOnly($case);
            $targets = $context->activeLinks?->map(fn ($link) => $link->targetRecord())->filter()->values() ?? collect();

            $attached = $this->requestLinks->attachTargets(
                $request,
                $targets,
                $actor,
                ConsultationMaternityLinkRole::HANDOFF,
                ['source_emergency_case_id' => $case->id],
            );

            if ($targets->isNotEmpty()) {
                $this->activityLog->log(
                    LogModule::EMERGENCY,
                    'EMERGENCY_MATERNITY_ADMISSION_REQUEST_CREATED',
                    [
                        'emergency_case_id' => $case->id,
                        'patient_id' => $case->patient_id,
                        'visit_id' => $case->visit_id,
                        'causer' => $actor,
                        'metadata' => [
                            'admission_request_id' => $request->id,
                            'reused' => $reused,
                            'linked_context_count' => count($attached['linked']),
                            'reused_context_count' => count($attached['reused']),
                        ],
                    ],
                    $request,
                    'EMERGENCY_MATERNITY_ADMISSION_REQUEST_CREATED',
                );
            }

            return ['request' => $request, 'reused' => $reused, 'context' => $attached];
        });
    }

    /**
     * Whether a request already carries a given maternity context — used by the
     * UI to explain "duplicate prevented" rather than silently doing nothing.
     */
    public function requestCarriesContext(AdmissionRequest $request, Model $target): bool
    {
        $contextType = $this->requestLinks->contextTypeFor($target);

        return $contextType !== null
            && AdmissionRequestMaternityLink::query()
                ->forAdmissionRequest($request)
                ->forContextType($contextType)
                ->active()
                ->where($contextType->foreignKey(), $target->getKey())
                ->exists();
    }

    /* ── Guards ────────────────────────────────────────────────────────── */

    /**
     * Clinical actions require the profile to be EXPLICITLY linked to this
     * emergency case. A suggested or same-visit context is never sufficient.
     */
    private function assertProfileLinked(EmergencyCase $case, PregnancyProfile $profile): void
    {
        $context = $this->resolver->resolveExplicitOnly($case);

        if (! $context->isResolved() || (int) $context->pregnancyProfile?->id !== (int) $profile->id) {
            throw MaternityLinkException::linkNotFound();
        }
    }
}
