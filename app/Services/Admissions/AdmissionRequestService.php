<?php

namespace App\Services\Admissions;

use App\Enums\AdmissionRequestSource;
use App\Enums\AdmissionRequestStatus;
use App\Enums\BedReservationStatus;
use App\Enums\BedStatus;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Admission;
use App\Models\AdmissionRequest;
use App\Models\Bed;
use App\Models\User;
use App\Models\Visit;
use App\Services\ActivityLogService;
use App\Services\AdmissionService;
use App\Services\Admissions\Maternity\AdmissionMaternityContextPropagationService;
use App\Services\InpatientWorkspaceScope;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionRequestService
{
    public function __construct(
        private AdmissionService $admissions,
        private ActivityLogService $logger,
        private BedWorkflowService $beds,
        private InpatientWorkspaceScope $inpatientScope,
        private AdmissionMaternityContextPropagationService $maternityContext,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = AdmissionRequest::with([
            'patient',
            'visit.department',
            'requestedBy',
            'acceptedBy',
            'requestedWard',
            'reservedBed.ward',
            'admission',
        ]);
        $this->inpatientScope->admissionRequests($query);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['source_type'])) {
            $query->where('source_type', $filters['source_type']);
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query
            ->latest('requested_at')
            ->latest('id')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data, ?User $user = null): AdmissionRequest
    {
        $request = AdmissionRequest::create([
            'patient_id' => $data['patient_id'],
            'visit_id' => $data['visit_id'] ?? null,
            'source_type' => $data['source_type'] ?? AdmissionRequestSource::DIRECT->value,
            'source_id' => $data['source_id'] ?? null,
            'requested_by' => $data['requested_by'] ?? $user?->id,
            'requested_ward_id' => $data['requested_ward_id'] ?? null,
            'preferred_bed_type' => $data['preferred_bed_type'] ?? null,
            'priority' => $data['priority'] ?? null,
            'provisional_diagnosis' => $data['provisional_diagnosis'] ?? null,
            'clinical_summary' => $data['clinical_summary'] ?? null,
            'status' => $data['status'] ?? AdmissionRequestStatus::REQUESTED->value,
            'requested_at' => $data['requested_at'] ?? now(),
        ]);

        $this->log($request, 'ADMISSION_REQUEST_CREATED', 'Admission request created', $user);

        return $request->fresh(['patient', 'visit', 'requestedBy', 'requestedWard']);
    }

    public function createForVisit(
        Visit $visit,
        AdmissionRequestSource|string $source,
        ?int $sourceId = null,
        array $data = [],
        ?User $user = null
    ): AdmissionRequest {
        $source = $source instanceof AdmissionRequestSource ? $source->value : $source;

        $existing = AdmissionRequest::query()
            ->open()
            ->where('visit_id', $visit->id)
            ->where('source_type', $source)
            ->when($sourceId, fn ($query) => $query->where('source_id', $sourceId))
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->create(array_merge($data, [
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
            'source_type' => $source,
            'source_id' => $sourceId,
        ]), $user);
    }

    public function accept(AdmissionRequest $request, User $user): AdmissionRequest
    {
        $this->assertOpen($request);

        $request->update([
            'status' => AdmissionRequestStatus::ACCEPTED,
            'accepted_by' => $user->id,
            'accepted_at' => now(),
            'reason' => null,
        ]);

        $this->log($request->fresh(), 'ADMISSION_REQUEST_ACCEPTED', 'Admission request accepted', $user);

        return $request->fresh(['patient', 'visit', 'acceptedBy']);
    }

    public function reject(AdmissionRequest $request, string $reason, User $user): AdmissionRequest
    {
        $this->assertOpen($request);

        $request->update([
            'status' => AdmissionRequestStatus::REJECTED,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'reason' => $reason,
        ]);

        $this->log($request->fresh(), 'ADMISSION_REQUEST_REJECTED', 'Admission request rejected', $user, [
            'reason' => $reason,
            'severity' => LogSeverity::NOTICE,
        ]);

        return $request->fresh(['patient', 'visit', 'rejectedBy']);
    }

    public function cancel(AdmissionRequest $request, string $reason, User $user): AdmissionRequest
    {
        $this->assertOpen($request);

        $request->update([
            'status' => AdmissionRequestStatus::CANCELLED,
            'cancelled_by' => $user->id,
            'cancelled_at' => now(),
            'reason' => $reason,
        ]);

        $this->beds->releaseActiveReservationsForRequest($request, $user, BedReservationStatus::CANCELLED, $reason);
        $this->log($request->fresh(), 'ADMISSION_REQUEST_CANCELLED', 'Admission request cancelled', $user, [
            'reason' => $reason,
            'severity' => LogSeverity::NOTICE,
        ]);

        return $request->fresh(['patient', 'visit', 'cancelledBy']);
    }

    public function markBedPending(AdmissionRequest $request, User $user): AdmissionRequest
    {
        $this->assertOpen($request);

        $request->update([
            'status' => AdmissionRequestStatus::BED_PENDING,
            'accepted_by' => $request->accepted_by ?: $user->id,
            'accepted_at' => $request->accepted_at ?: now(),
        ]);

        $this->log($request->fresh(), 'ADMISSION_REQUEST_BED_PENDING', 'Admission request marked bed pending', $user);

        return $request->fresh(['patient', 'visit', 'acceptedBy']);
    }

    public function reserveBed(AdmissionRequest $request, Bed $bed, User $user): AdmissionRequest
    {
        $this->assertOpen($request);

        $reserved = $this->beds->reserveForRequest($request, $bed, $user, now()->addHours(6));

        $this->log($reserved, 'ADMISSION_REQUEST_BED_RESERVED', 'Admission request bed reserved', $user, [
            'metadata' => ['bed_id' => $bed->id],
        ]);

        return $reserved;
    }

    public function convertToAdmission(AdmissionRequest $request, array $data, User $user): Admission
    {
        if (! $request->status->canConvert()) {
            throw ValidationException::withMessages([
                'admission_request_id' => __('admissions.request_errors.cannot_convert_status'),
            ]);
        }

        if ($request->admission()->exists() || $request->converted_at) {
            throw ValidationException::withMessages([
                'admission_request_id' => __('admissions.request_errors.already_converted'),
            ]);
        }

        $bedId = $data['bed_id'] ?? $request->reserved_bed_id;
        if (! $bedId) {
            throw ValidationException::withMessages([
                'bed_id' => __('admissions.request_errors.bed_required'),
            ]);
        }

        $bed = Bed::findOrFail($bedId);
        if (
            $bed->status !== BedStatus::AVAILABLE
            && ! ($bed->status === BedStatus::RESERVED && (int) $request->reserved_bed_id === (int) $bed->id)
        ) {
            throw ValidationException::withMessages([
                'bed_id' => __('admissions.request_errors.bed_not_available'),
            ]);
        }

        return DB::transaction(function () use ($request, $data, $user, $bedId) {
            $admission = $this->admissions->admit(array_merge($data, [
                'admission_request_id' => $request->id,
                'visit_id' => $request->visit_id,
                'patient_id' => $request->patient_id,
                'bed_id' => $bedId,
                'admitting_diagnosis' => $data['admitting_diagnosis'] ?? $request->provisional_diagnosis,
                'admission_type' => $data['admission_type'] ?? 'admission',
            ]));

            $request->update([
                'status' => AdmissionRequestStatus::CONVERTED,
                'converted_at' => now(),
                'accepted_by' => $request->accepted_by ?: $user->id,
                'accepted_at' => $request->accepted_at ?: now(),
                'reserved_bed_id' => $bedId,
            ]);
            $this->beds->fulfillReservationForAdmission($admission, $user);

            // Phase 14R.5 — carry any explicit maternity context from the
            // request onto the admission, INSIDE this transaction so a failure
            // rolls the conversion back rather than leaving a converted
            // admission with silently missing context. Inert (and query-free)
            // while MATERNITY_ADMISSION_CONTEXT_ENABLED is false.
            $this->maternityContext->propagate($request->fresh(), $admission, $user);

            $this->log($request->fresh(['admission']), 'ADMISSION_REQUEST_CONVERTED', 'Admission request converted', $user, [
                'metadata' => ['admission_id' => $admission->id],
            ]);

            return $admission;
        });
    }

    private function assertOpen(AdmissionRequest $request): void
    {
        if ($request->status->isClosed()) {
            throw ValidationException::withMessages([
                'status' => __('admissions.request_errors.closed_request'),
            ]);
        }
    }

    private function log(
        AdmissionRequest $request,
        string $event,
        string $description,
        ?User $user,
        array $extra = []
    ): void {
        $metadata = array_merge($extra['metadata'] ?? [], [
            'status' => $request->status->value,
            'source_type' => $request->source_type->value,
        ]);

        unset($extra['metadata']);

        $this->logger->log(LogModule::ADMISSION, $event, array_merge($request->toActivityContext(), $extra, [
            'causer' => $user,
            'metadata' => $metadata,
        ]), $request, $description);
    }
}
