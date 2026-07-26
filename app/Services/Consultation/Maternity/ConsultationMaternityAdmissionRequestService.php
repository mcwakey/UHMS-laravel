<?php

namespace App\Services\Consultation\Maternity;

use App\Data\Consultation\Maternity\ConsultationMaternityContext;
use App\Enums\AdmissionRequestSource;
use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\LogModule;
use App\Models\AdmissionRequest;
use App\Models\AdmissionRequestMaternityLink;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\ActivityLogService;
use App\Services\Admissions\AdmissionRequestService;
use App\Services\Admissions\Maternity\AdmissionRequestMaternityLinkService;
use App\Services\Maternity\Context\MaternityIntegrationFlags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Phase 14R.5 — explicit "raise an Admission Request from this Obstetrics
 * consultation" handoff.
 *
 * Boundaries:
 *   - Admission owns the request; this service only calls
 *     AdmissionRequestService and attaches clinical maternity context.
 *   - The operational source stays truthful: source_type = consultation,
 *     source_id = VisitConsultationRoute id. Maternity context lives in
 *     admission_request_maternity_links, never in source_id.
 *   - Nothing is auto-admitted, no bed is reserved, no billing is posted and no
 *     specialty entry is created.
 *   - Only SAFE handover fields are copied (priority, ward, provisional
 *     diagnosis, short summary). Full consultation notes are never copied.
 */
class ConsultationMaternityAdmissionRequestService
{
    /** Hard cap on the copied clinical summary — a handover line, not a note. */
    private const SUMMARY_MAX_LENGTH = 500;

    public function __construct(
        private readonly AdmissionRequestService $admissionRequests,
        private readonly AdmissionRequestMaternityLinkService $requestLinks,
        private readonly ActivityLogService $activityLog,
        private readonly MaternityIntegrationFlags $flags,
    ) {}

    public function enabled(): bool
    {
        return $this->flags->consultationHandoffsEnabled();
    }

    /**
     * The open request already raised from this consultation for this exact
     * maternity context, if any. This is the duplicate identity:
     *
     *   consultation route + pregnancy profile + most-specific target + OPEN
     *
     * Rejected, cancelled and converted requests are closed and never reused.
     */
    public function existingOpenRequest(
        VisitConsultationRoute $consultation,
        ConsultationMaternityContext $context,
    ): ?AdmissionRequest {
        $target = $this->mostSpecificTarget($context);

        if (! $target) {
            return null;
        }

        $contextType = $this->requestLinks->contextTypeFor($target);

        if (! $contextType) {
            return null;
        }

        return AdmissionRequest::query()
            ->open()
            ->where('patient_id', $consultation->patient_id)
            ->where('source_type', AdmissionRequestSource::CONSULTATION->value)
            ->where('source_id', $consultation->id)
            ->whereIn('id', AdmissionRequestMaternityLink::query()
                ->active()
                ->forContextType($contextType)
                ->where($contextType->foreignKey(), $target->getKey())
                ->where('pregnancy_profile_id', $context->pregnancyProfile?->id)
                ->select('admission_request_id'))
            ->latest('id')
            ->first();
    }

    /**
     * Create — or reuse — the Admission Request for this consultation and
     * maternity context.
     *
     * @param  array{priority?: string|null, requested_ward_id?: int|null, provisional_diagnosis?: string|null, clinical_summary?: string|null}  $data
     * @return array{request: AdmissionRequest, reused: bool}
     */
    public function createOrReuse(
        VisitConsultationRoute $consultation,
        ConsultationMaternityContext $context,
        array $data,
        User $actor,
    ): array {
        $profile = $context->pregnancyProfile;

        if (! $profile) {
            throw ConsultationMaternityLinkException::inconsistentContext();
        }

        $target = $this->mostSpecificTarget($context) ?? $profile;

        // Serialise concurrent clicks on the same consultation: the row lock is
        // taken before the duplicate check, so two simultaneous submissions
        // cannot both pass it.
        return DB::transaction(function () use ($consultation, $context, $data, $actor, $profile, $target) {
            VisitConsultationRoute::query()->whereKey($consultation->id)->lockForUpdate()->first();

            $existing = $this->existingOpenRequest($consultation, $context);

            if ($existing) {
                return ['request' => $existing, 'reused' => true];
            }

            $request = $this->admissionRequests->create([
                'patient_id' => $consultation->patient_id,
                'visit_id' => $consultation->visit_id,
                'source_type' => AdmissionRequestSource::CONSULTATION->value,
                'source_id' => $consultation->id,
                'requested_ward_id' => $data['requested_ward_id'] ?? null,
                'priority' => $data['priority'] ?? null,
                'provisional_diagnosis' => $data['provisional_diagnosis'] ?? null,
                'clinical_summary' => $this->safeSummary($data['clinical_summary'] ?? null),
            ], $actor);

            // Attach the pregnancy profile root plus the most specific target,
            // so Admission can show exactly what this request is about.
            $targets = [$profile];
            if ($target !== $profile) {
                $targets[] = $target;
            }

            $this->requestLinks->attachTargets(
                $request,
                $targets,
                $actor,
                ConsultationMaternityLinkRole::HANDOFF,
                ['source_consultation_route_id' => $consultation->id],
            );

            $this->activityLog->log(
                LogModule::CONSULTATION,
                'CONSULTATION_MATERNITY_ADMISSION_REQUEST_CREATED',
                [
                    'patient_id' => $consultation->patient_id,
                    'visit_id' => $consultation->visit_id,
                    'causer' => $actor,
                    'metadata' => array_filter([
                        'consultation_route_id' => $consultation->id,
                        'admission_request_id' => $request->id,
                        'pregnancy_profile_id' => $profile->id,
                        'target_context_type' => $this->requestLinks->contextTypeFor($target)?->value,
                        'target_record_id' => $target->getKey(),
                    ], fn ($value) => $value !== null),
                ],
                $request,
                'CONSULTATION_MATERNITY_ADMISSION_REQUEST_CREATED',
            );

            return ['request' => $request, 'reused' => false];
        });
    }

    /**
     * The most specific EXPLICITLY LINKED maternity record to hand over.
     *
     * Ordered most → least specific. Only explicit context counts: a guessed
     * labor episode must never end up on an admission request.
     */
    public function mostSpecificTarget(ConsultationMaternityContext $context): ?Model
    {
        if (! $context->isResolved() || ! $context->isExplicit()) {
            return $context->isResolved() ? $context->pregnancyProfile : null;
        }

        return $context->laborEpisode
            ?? $context->antenatalVisit
            ?? $context->maternityCase
            ?? $context->pregnancyProfile;
    }

    /**
     * Trim the handover summary. A clinician-authored line is fine; the full
     * consultation note is not, so it is truncated rather than copied whole.
     */
    private function safeSummary(?string $summary): ?string
    {
        $summary = trim((string) $summary);

        if ($summary === '') {
            return null;
        }

        return mb_strimwidth($summary, 0, self::SUMMARY_MAX_LENGTH, '…');
    }
}
