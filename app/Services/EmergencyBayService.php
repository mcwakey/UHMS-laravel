<?php

namespace App\Services;

use App\Enums\BedStatus;
use App\Models\Bed;
use App\Models\EmergencyBay;
use App\Models\EmergencyBayAssignment;
use App\Models\EmergencyCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmergencyBayService
{
    public function __construct(
        private ?EmergencyTimelineService $timeline = null,
        private ?EmergencySessionService $sessions = null,
    ) {}

    public function assign(EmergencyCase $case, int $bayId, User $user, bool $override = false, ?int $wardId = null, ?int $bedId = null): EmergencyCase
    {
        return DB::transaction(function () use ($case, $bayId, $user, $override, $wardId, $bedId) {
            $session = $this->sessions()->getOrCreateForCase($case, $user);
            $bay = EmergencyBay::query()->lockForUpdate()->findOrFail($bayId);
            $bed = $bedId ? Bed::query()->lockForUpdate()->findOrFail($bedId) : ($bay->bed_id ? Bed::query()->lockForUpdate()->find($bay->bed_id) : null);

            if (! $bay->is_active) {
                throw ValidationException::withMessages(['emergency_bay_id' => 'Selected emergency bay is inactive.']);
            }

            if (! $override && $bay->status !== EmergencyBay::STATUS_AVAILABLE && (int) $case->emergency_bay_id !== $bay->id) {
                throw ValidationException::withMessages(['emergency_bay_id' => 'Selected emergency bay is not available.']);
            }

            if ($bed && ! $override && $this->bedStatus($bed) !== BedStatus::AVAILABLE->value && (int) $bay->bed_id !== (int) $bed->id) {
                throw ValidationException::withMessages(['bed_id' => 'Selected bed is not available.']);
            }

            if ($case->emergency_bay_id && (int) $case->emergency_bay_id !== $bay->id) {
                $case->bay?->markAvailable();
            }

            $activeAssignment = $case->activeBayAssignment()->with(['bed', 'emergencyBay'])->first();
            if ($activeAssignment && (int) $activeAssignment->emergency_bay_id !== $bay->id) {
                $activeAssignment->update([
                    'status' => EmergencyBayAssignment::STATUS_TRANSFERRED,
                    'released_by' => $user->id,
                    'released_at' => now(),
                ]);
                $activeAssignment->bed?->markAvailable();
            }

            if ($wardId || $bed) {
                $bay->update([
                    'ward_id' => $wardId ?: $bed?->ward_id,
                    'bed_id' => $bed?->id,
                ]);
            }

            $case->update([
                'emergency_bay_id' => $bay->id,
                'emergency_status' => in_array($case->emergency_status, [
                    EmergencyCase::STATUS_WAITING_TRIAGE,
                    EmergencyCase::STATUS_TRIAGED,
                ], true) ? EmergencyCase::STATUS_UNDER_CARE : $case->emergency_status,
            ]);

            $bay->markOccupied();
            $bed?->markOccupied();

            EmergencyBayAssignment::create([
                'emergency_case_id' => $case->id,
                'emergency_session_id' => $session->id,
                'ward_id' => $wardId ?: $bed?->ward_id ?: $bay->ward_id,
                'bed_id' => $bed?->id,
                'emergency_bay_id' => $bay->id,
                'assigned_by' => $user->id,
                'assigned_at' => now(),
                'status' => EmergencyBayAssignment::STATUS_ACTIVE,
            ]);

            $this->sessions()->recordContribution($case, $user, 'Bay Assignment');

            $this->timeline()?->record($case->fresh('bay'), 'BAY_ASSIGNED', 'Emergency bay assigned', $bay->name, $bay, $user);

            return $case->fresh(['bay', 'activeBayAssignment']);
        });
    }

    public function release(EmergencyCase $case, string $bayStatus = EmergencyBay::STATUS_AVAILABLE): void
    {
        if (! $case->emergency_bay_id) {
            return;
        }

        $assignment = $case->activeBayAssignment()->with('bed')->first();
        if ($assignment) {
            $assignment->update([
                'status' => EmergencyBayAssignment::STATUS_RELEASED,
                'released_at' => now(),
            ]);
            $assignment->bed?->markAvailable();
        }

        $case->bay?->update(['status' => $bayStatus]);
        $case->update(['emergency_bay_id' => null]);
    }

    private function timeline(): EmergencyTimelineService
    {
        return $this->timeline ??= app(EmergencyTimelineService::class);
    }

    private function sessions(): EmergencySessionService
    {
        return $this->sessions ??= app(EmergencySessionService::class);
    }

    private function bedStatus(Bed $bed): string
    {
        return $bed->status instanceof BedStatus ? $bed->status->value : (string) $bed->status;
    }
}
