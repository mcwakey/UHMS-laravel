<?php

namespace App\Services;

use App\Models\MedicationAdministration;
use App\Models\MedicationAdministrationLog;
use App\Models\MedicationAdministrationSchedule;
use App\Models\MedicationOrder;
use App\Models\User;

class MedicationAdministrationLogService
{
    public function record(
        string $action,
        ?MedicationOrder $order = null,
        ?MedicationAdministrationSchedule $schedule = null,
        ?MedicationAdministration $administration = null,
        ?User $user = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?string $reason = null,
    ): MedicationAdministrationLog {
        return MedicationAdministrationLog::create([
            'medication_administration_id' => $administration?->id,
            'medication_order_id' => $order?->id ?? $schedule?->medication_order_id ?? $administration?->medication_order_id,
            'schedule_id' => $schedule?->id ?? $administration?->schedule_id,
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $reason,
            'performed_by' => $user?->id,
        ]);
    }
}
