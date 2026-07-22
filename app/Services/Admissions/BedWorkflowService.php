<?php

namespace App\Services\Admissions;

use App\Enums\AdmissionLocationEvent;
use App\Enums\AdmissionRequestStatus;
use App\Enums\BedReservationStatus;
use App\Enums\BedStatus;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Admission;
use App\Models\AdmissionLocationHistory;
use App\Models\AdmissionRequest;
use App\Models\Bed;
use App\Models\BedReservation;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\LegacyMigration\Foundation\Runtime\OperationalEffectGate;
use App\Services\LegacyMigration\Foundation\Runtime\ProhibitedSubsystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BedWorkflowService
{
    public function __construct(private ActivityLogService $logger) {}

    public function reserveForRequest(
        AdmissionRequest $request,
        Bed $bed,
        ?User $user = null,
        $expiresAt = null,
        ?string $reason = null,
        bool $allowIsolation = false
    ): AdmissionRequest {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::BedOccupancy);

        $this->assertCanReserve($bed, $allowIsolation, $reason);

        $expiresAt ??= now()->addHours((int) config('admissions.reservations.default_expiry_hours', 6));

        return DB::transaction(function () use ($request, $bed, $user, $expiresAt, $reason) {
            $this->releaseActiveReservationsForRequest($request, $user, BedReservationStatus::RELEASED, __('admissions.reservation_replaced'));

            $reservation = BedReservation::create([
                'admission_request_id' => $request->id,
                'patient_id' => $request->patient_id,
                'visit_id' => $request->visit_id,
                'bed_id' => $bed->id,
                'status' => BedReservationStatus::ACTIVE,
                'reserved_by' => $user?->id,
                'reserved_at' => now(),
                'expires_at' => $expiresAt,
                'reason' => $reason,
            ]);

            $bed->markReserved($user?->id, $reason ?: __('admissions.reserved_for_admission_request'), $expiresAt);

            $request->update([
                'status' => AdmissionRequestStatus::RESERVED,
                'reserved_bed_id' => $bed->id,
                'accepted_by' => $request->accepted_by ?: $user?->id,
                'accepted_at' => $request->accepted_at ?: now(),
            ]);

            $this->log(LogModule::ADMISSION, 'BED_RESERVED', $request->toActivityContext() + [
                'metadata' => [
                    'bed_id' => $bed->id,
                    'reservation_id' => $reservation->id,
                    'expires_at' => $expiresAt,
                ],
                'causer' => $user,
            ], $request, 'Bed reserved for admission request');

            return $request->fresh(['patient', 'visit', 'requestedWard', 'reservedBed.ward', 'activeBedReservation']);
        });
    }

    public function releaseActiveReservationsForRequest(
        AdmissionRequest $request,
        ?User $user = null,
        BedReservationStatus $status = BedReservationStatus::CANCELLED,
        ?string $reason = null
    ): void {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::BedOccupancy);

        $reservations = $request->bedReservations()->active()->with('bed')->get();

        foreach ($reservations as $reservation) {
            $this->releaseReservation($reservation, $user, $status, $reason);
        }

        if ($request->reserved_bed_id && $status !== BedReservationStatus::FULFILLED) {
            $request->update(['reserved_bed_id' => null]);
        }
    }

    public function releaseReservation(
        BedReservation $reservation,
        ?User $user = null,
        BedReservationStatus $status = BedReservationStatus::RELEASED,
        ?string $reason = null
    ): BedReservation {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::BedOccupancy);

        return DB::transaction(function () use ($reservation, $user, $status, $reason) {
            if ($reservation->status !== BedReservationStatus::ACTIVE) {
                return $reservation->fresh(['bed']);
            }

            $reservation->update([
                'status' => $status,
                'released_by' => $user?->id,
                'released_at' => now(),
                'reason' => $reason ?: $reservation->reason,
            ]);

            $activeOtherReservation = BedReservation::active()
                ->where('bed_id', $reservation->bed_id)
                ->whereKeyNot($reservation->id)
                ->exists();

            if (! $activeOtherReservation && $reservation->bed?->status === BedStatus::RESERVED) {
                $reservation->bed->markAvailable($user?->id, $reason ?: __('admissions.reservation_released'));
            }

            return $reservation->fresh(['bed']);
        });
    }

    public function cancelReservation(BedReservation $reservation, ?User $user = null, ?string $reason = null): BedReservation
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::BedOccupancy);
        return $this->releaseReservation($reservation, $user, BedReservationStatus::CANCELLED, $reason ?: __('admissions.reservation_cancelled'));
    }

    public function expireReservation(BedReservation $reservation): bool
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::BedOccupancy);
        $reservation->loadMissing(['bed.currentAdmission', 'admissionRequest']);

        if ($reservation->status !== BedReservationStatus::ACTIVE) {
            return false;
        }

        if (! $reservation->expires_at || $reservation->expires_at->isFuture()) {
            return false;
        }

        if ($reservation->bed?->currentAdmission()->exists()) {
            return false;
        }

        return DB::transaction(function () use ($reservation) {
            $bed = $reservation->bed()->lockForUpdate()->first();
            $freshReservation = BedReservation::query()->lockForUpdate()->find($reservation->id);

            if (! $bed || ! $freshReservation || $freshReservation->status !== BedReservationStatus::ACTIVE) {
                return false;
            }

            if ($bed->status !== BedStatus::RESERVED || (int) $freshReservation->bed_id !== (int) $bed->id) {
                return false;
            }

            if ($bed->currentAdmission()->exists()) {
                return false;
            }

            $freshReservation->update([
                'status' => BedReservationStatus::EXPIRED,
                'released_at' => now(),
                'reason' => __('admissions.reservation_expired'),
            ]);

            $bed->markAvailable(null, __('admissions.reservation_expired'));
            if ($freshReservation->admissionRequest) {
                $freshReservation->admissionRequest->update([
                    'reserved_bed_id' => null,
                    'status' => $freshReservation->admissionRequest->status === AdmissionRequestStatus::RESERVED
                        ? AdmissionRequestStatus::BED_PENDING
                        : $freshReservation->admissionRequest->status,
                ]);
            }

            $this->log(LogModule::ADMISSION, 'BED_RESERVATION_EXPIRED', [
                'patient_id' => $freshReservation->patient_id,
                'visit_id' => $freshReservation->visit_id,
                'admission_request_id' => $freshReservation->admission_request_id,
                'metadata' => [
                    'bed_id' => $bed->id,
                    'reservation_id' => $freshReservation->id,
                ],
            ], $freshReservation, 'Bed reservation expired');

            return true;
        });
    }

    public function fulfillReservationForAdmission(Admission $admission, ?User $user = null): void
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::BedOccupancy);

        if (! $admission->admission_request_id) {
            return;
        }

        $reservation = BedReservation::active()
            ->where('admission_request_id', $admission->admission_request_id)
            ->where('bed_id', $admission->bed_id)
            ->latest('id')
            ->first();

        if (! $reservation) {
            return;
        }

        if (! $this->reservationCanBeFulfilled($reservation)) {
            return;
        }

        $reservation->update([
            'admission_id' => $admission->id,
            'status' => BedReservationStatus::FULFILLED,
            'released_by' => $user?->id,
            'released_at' => now(),
        ]);
    }

    public function recordAdmissionStart(Admission $admission, ?User $user = null): void
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::BedOccupancy);

        $admission->loadMissing('bed.ward');

        $this->recordLocation($admission, AdmissionLocationEvent::ADMITTED, null, null, $admission->bed, $user, __('admissions.location_admitted'));
    }

    public function transferAdmission(Admission $admission, Bed $toBed, User $user, ?string $reason = null): Admission
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::BedOccupancy);

        $admission->loadMissing('bed.ward');
        $fromBed = $admission->bed;

        if ((int) $fromBed->id === (int) $toBed->id) {
            throw ValidationException::withMessages([
                'bed_id' => __('admissions.transfer_errors.same_bed'),
            ]);
        }

        $this->assertCanTransferInto($toBed, false, $reason);

        return DB::transaction(function () use ($admission, $fromBed, $toBed, $user, $reason) {
            $fromBed->markAvailable($user->id, $reason ?: __('admissions.transfer_source_released'));
            $toBed->markOccupied($user->id, $reason ?: __('admissions.transfer_destination_occupied'));

            $admission->update(['bed_id' => $toBed->id]);
            $fresh = $admission->fresh(['patient', 'visit', 'bed.ward']);

            $event = (int) $fromBed->ward_id === (int) $toBed->ward_id
                ? AdmissionLocationEvent::BED_TRANSFERRED
                : AdmissionLocationEvent::WARD_TRANSFERRED;

            $this->recordLocation($fresh, $event, $fromBed, $toBed, $toBed, $user, $reason);

            $this->log(LogModule::ADMISSION, 'ADMISSION_LOCATION_TRANSFERRED', $fresh->toActivityContext() + [
                'severity' => LogSeverity::NOTICE,
                'metadata' => [
                    'from_bed_id' => $fromBed->id,
                    'to_bed_id' => $toBed->id,
                    'from_ward_id' => $fromBed->ward_id,
                    'to_ward_id' => $toBed->ward_id,
                ],
                'reason' => $reason,
                'causer' => $user,
            ], $fresh, 'Admission location transferred');

            return $fresh;
        });
    }

    public function releaseBedForDischarge(Admission $admission, ?User $user = null): void
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::BedOccupancy);

        $admission->loadMissing('bed.ward');
        $bed = $admission->bed;

        $releaseStatus = (string) config('admissions.bed_release_after_discharge', 'available');
        $releaseToCleaning = $releaseStatus === BedStatus::CLEANING->value;

        $releaseToCleaning
            ? $bed->updateStatus(BedStatus::CLEANING, $user?->id, __('admissions.discharge_bed_marked_cleaning'))
            : $bed->markAvailable($user?->id, __('admissions.discharge_bed_released'));

        $this->recordLocation($admission, AdmissionLocationEvent::DISCHARGED, $bed, null, null, $user, __('admissions.discharge_bed_released'));
    }

    public function updateBedStatus(Bed $bed, BedStatus $status, ?User $user = null, ?string $reason = null): Bed
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::BedOccupancy);
        $this->assertStatusReason($status, $reason);

        if ($bed->currentAdmission()->exists() && $status !== BedStatus::OCCUPIED) {
            throw ValidationException::withMessages([
                'status' => __('admissions.transfer_errors.cannot_release_occupied_bed'),
            ]);
        }

        if ($status === BedStatus::OCCUPIED && ! $bed->currentAdmission()->exists()) {
            throw ValidationException::withMessages([
                'status' => __('admissions.transfer_errors.cannot_mark_occupied_without_admission'),
            ]);
        }

        if ($status !== BedStatus::RESERVED) {
            foreach ($bed->reservations()->active()->get() as $reservation) {
                $this->releaseReservation($reservation, $user, BedReservationStatus::RELEASED, $reason);
            }
        }

        $bed->updateStatus($status, $user?->id, $reason);
        $this->log(LogModule::ADMISSION, 'BED_STATUS_UPDATED', [
            'metadata' => [
                'bed_id' => $bed->id,
                'status' => $status->value,
            ],
            'reason' => $reason,
            'causer' => $user,
        ], $bed, 'Bed status updated');

        return $bed->fresh(['ward', 'activeReservation']);
    }

    public function canReserve(Bed $bed, bool $allowIsolation = false): bool
    {
        if ($bed->currentAdmission()->exists()) {
            return false;
        }

        return match ($bed->status) {
            BedStatus::AVAILABLE => true,
            BedStatus::ISOLATION => $allowIsolation,
            default => false,
        };
    }

    public function canTransferInto(Bed $bed, bool $allowIsolation = false): bool
    {
        return $this->canReserve($bed, $allowIsolation);
    }

    public function canRelease(Bed $bed): bool
    {
        return ! $bed->currentAdmission()->exists();
    }

    public function hasActiveReservation(Bed $bed): bool
    {
        return $bed->reservations()->active()->exists();
    }

    public function reservationCanBeFulfilled(BedReservation $reservation): bool
    {
        return $reservation->status === BedReservationStatus::ACTIVE
            && (! $reservation->expires_at || $reservation->expires_at->isFuture());
    }

    private function assertCanReserve(Bed $bed, bool $allowIsolation = false, ?string $reason = null): void
    {
        if ($bed->status === BedStatus::ISOLATION && $allowIsolation && filled($reason)) {
            return;
        }

        if (! $this->canReserve($bed, $allowIsolation)) {
            throw ValidationException::withMessages([
                'bed_id' => __('admissions.transfer_errors.bed_not_available'),
            ]);
        }

        if ($bed->status === BedStatus::ISOLATION && blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => __('admissions.transfer_errors.isolation_reason_required'),
            ]);
        }
    }

    private function assertCanTransferInto(Bed $bed, bool $allowIsolation = false, ?string $reason = null): void
    {
        $this->assertCanReserve($bed, $allowIsolation, $reason);
    }

    private function assertStatusReason(BedStatus $status, ?string $reason): void
    {
        if (in_array($status, [BedStatus::BLOCKED, BedStatus::MAINTENANCE, BedStatus::ISOLATION], true) && blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => __('admissions.transfer_errors.status_reason_required'),
            ]);
        }
    }

    private function recordLocation(
        Admission $admission,
        AdmissionLocationEvent $event,
        ?Bed $fromBed,
        ?Bed $toBed,
        ?Bed $locationBed,
        ?User $user,
        ?string $reason = null
    ): AdmissionLocationHistory {
        $fromBed?->loadMissing('ward');
        $toBed?->loadMissing('ward');
        $locationBed?->loadMissing('ward');

        return AdmissionLocationHistory::create([
            'admission_id' => $admission->id,
            'patient_id' => $admission->patient_id,
            'visit_id' => $admission->visit_id,
            'event_type' => $event,
            'from_ward_id' => $fromBed?->ward_id,
            'from_bed_id' => $fromBed?->id,
            'to_ward_id' => $toBed?->ward_id ?? $locationBed?->ward_id,
            'to_bed_id' => $toBed?->id ?? $locationBed?->id,
            'moved_by' => $user?->id,
            'moved_at' => now(),
            'reason' => $reason,
            'metadata' => [
                'event_label' => $event->label(),
            ],
        ]);
    }

    private function log(string|LogModule $module, string $event, array $data, $subject, string $description): void
    {
        $this->logger->log($module, $event, $data, $subject, $description);
    }
}
