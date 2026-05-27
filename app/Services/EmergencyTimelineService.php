<?php

namespace App\Services;

use App\Models\EmergencyCase;
use App\Models\EmergencyCaseLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EmergencyTimelineService
{
    public function record(
        EmergencyCase $case,
        string $action,
        string $title,
        ?string $description = null,
        ?Model $source = null,
        ?User $user = null,
    ): EmergencyCaseLog {
        return EmergencyCaseLog::create([
            'emergency_case_id' => $case->id,
            'visit_id' => $case->visit_id,
            'patient_id' => $case->patient_id,
            'action' => $action,
            'title' => $title,
            'description' => $description,
            'source_type' => $source ? $source::class : null,
            'source_id' => $source?->getKey(),
            'performed_by' => $user?->id ?? Auth::id(),
        ]);
    }
}
