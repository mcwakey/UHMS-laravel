<?php

namespace App\Services;

use App\Models\BloodRequest;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class BloodRequestService
{
    public function createForVisit(Visit $visit, array $data, User $user): BloodRequest
    {
        return DB::transaction(function () use ($visit, $data, $user) {
            return BloodRequest::create([
                'request_number' => BloodRequest::generateRequestNumber(),
                'visit_id' => $visit->id,
                'patient_id' => $visit->patient_id,
                'admission_id' => $data['admission_id'] ?? $visit->admission?->id,
                'emergency_case_id' => $data['emergency_case_id'] ?? $visit->emergencyCase?->id,
                'department_id' => $data['department_id'] ?? $visit->current_department_id,
                'requested_by' => $user->id,
                'requested_at' => $data['requested_at'] ?? now(),
                'needed_at' => $data['needed_at'] ?? null,
                'blood_group' => $data['blood_group'],
                'component_type' => $data['component_type'] ?? 'WHOLE_BLOOD',
                'units_requested' => $data['units_requested'] ?? 1,
                'priority' => $data['priority'] ?? 'ROUTINE',
                'status' => BloodRequest::STATUS_PENDING,
                'hb_level' => $data['hb_level'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'indication' => $data['indication'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function approve(BloodRequest $request, User $user): BloodRequest
    {
        $request->update([
            'status' => BloodRequest::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return $request->refresh();
    }
}
