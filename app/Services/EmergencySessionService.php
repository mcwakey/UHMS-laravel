<?php

namespace App\Services;

use App\Models\EmergencyCase;
use App\Models\EmergencySession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmergencySessionService
{
    public function getOrCreateForCase(EmergencyCase $case, ?User $user = null): EmergencySession
    {
        $case->loadMissing('visit');

        return DB::transaction(function () use ($case, $user) {
            $session = $case->emergencySessions()
                ->whereIn('status', [
                    EmergencySession::STATUS_PENDING,
                    EmergencySession::STATUS_ACTIVE,
                    EmergencySession::STATUS_OBSERVATION,
                ])
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($session) {
                return $session;
            }

            return $case->emergencySessions()->create([
                'visit_id' => $case->visit_id,
                'patient_id' => $case->patient_id,
                'department_id' => $case->visit?->current_department_id,
                'main_doctor_id' => $case->assigned_doctor_id,
                'primary_nurse_id' => $case->assigned_nurse_id,
                'status' => EmergencySession::STATUS_ACTIVE,
                'started_at' => $case->arrival_time ?? now(),
                'started_by' => $user?->id ?? $case->created_by,
            ]);
        });
    }

    public function syncTeam(EmergencyCase $case): ?EmergencySession
    {
        $session = $case->activeEmergencySession ?: $case->emergencySessions()->latest('id')->first();

        if (! $session) {
            return null;
        }

        $session->update([
            'main_doctor_id' => $case->assigned_doctor_id,
            'primary_nurse_id' => $case->assigned_nurse_id,
            'status' => $case->emergency_status === EmergencyCase::STATUS_OBSERVATION
                ? EmergencySession::STATUS_OBSERVATION
                : $session->status,
        ]);

        return $session->fresh(['mainDoctor', 'primaryNurse']);
    }

    public function recordContribution(EmergencyCase $case, User $user, ?string $role = null): void
    {
        $session = $case->activeEmergencySession ?: $this->getOrCreateForCase($case, $user);

        $existingFirstContribution = $session->contributors()
            ->where('user_id', $user->id)
            ->value('first_contributed_at');

        $session->contributors()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'emergency_case_id' => $case->id,
                'role' => $role,
                'first_contributed_at' => $existingFirstContribution ?: now(),
                'last_contributed_at' => now(),
            ],
        );
    }
}