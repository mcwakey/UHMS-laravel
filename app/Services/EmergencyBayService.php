<?php

namespace App\Services;

use App\Models\EmergencyBay;
use App\Models\EmergencyCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmergencyBayService
{
    public function __construct(private ?EmergencyTimelineService $timeline = null) {}

    public function assign(EmergencyCase $case, int $bayId, User $user, bool $override = false): EmergencyCase
    {
        return DB::transaction(function () use ($case, $bayId, $user, $override) {
            $bay = EmergencyBay::query()->lockForUpdate()->findOrFail($bayId);

            if (! $bay->is_active) {
                throw ValidationException::withMessages(['emergency_bay_id' => 'Selected emergency bay is inactive.']);
            }

            if (! $override && $bay->status !== EmergencyBay::STATUS_AVAILABLE && (int) $case->emergency_bay_id !== $bay->id) {
                throw ValidationException::withMessages(['emergency_bay_id' => 'Selected emergency bay is not available.']);
            }

            if ($case->emergency_bay_id && (int) $case->emergency_bay_id !== $bay->id) {
                $case->bay?->markAvailable();
            }

            $case->update([
                'emergency_bay_id' => $bay->id,
                'emergency_status' => in_array($case->emergency_status, [
                    EmergencyCase::STATUS_WAITING_TRIAGE,
                    EmergencyCase::STATUS_TRIAGED,
                ], true) ? EmergencyCase::STATUS_UNDER_CARE : $case->emergency_status,
            ]);

            $bay->markOccupied();

            $this->timeline()?->record($case->fresh('bay'), 'BAY_ASSIGNED', 'Emergency bay assigned', $bay->name, $bay, $user);

            return $case->fresh(['bay']);
        });
    }

    public function release(EmergencyCase $case, string $bayStatus = EmergencyBay::STATUS_AVAILABLE): void
    {
        if (! $case->emergency_bay_id) {
            return;
        }

        $case->bay?->update(['status' => $bayStatus]);
        $case->update(['emergency_bay_id' => null]);
    }

    private function timeline(): EmergencyTimelineService
    {
        return $this->timeline ??= app(EmergencyTimelineService::class);
    }
}
