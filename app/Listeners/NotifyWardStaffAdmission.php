<?php

namespace App\Listeners;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Events\PatientAdmitted;
use App\Services\NotificationService;

class NotifyWardStaffAdmission
{
    public function __construct(protected NotificationService $notifier) {}

    public function handle(PatientAdmitted $event): void
    {
        $admission = $event->admission;
        $admission->loadMissing(['bed.ward']);

        $payload = [
            'module' => NotificationModule::ADMISSION,
            'priority' => NotificationPriority::HIGH,
            'title' => 'New admission',
            'message' => 'Patient ' . ($admission->patient->full_name ?? '#' . $admission->patient_id) . ' admitted.',
            'url' => url("/admin/admissions/{$admission->id}"),
            'source_type' => 'admission',
            'source_id' => $admission->id,
            'admission_id' => $admission->id,
            'patient_id' => $admission->patient_id,
        ];

        $departmentId = $admission->bed?->ward?->department_id ?? null;
        if ($departmentId) {
            $this->notifier->notifyDepartment((int) $departmentId, $payload);
        } else {
            $this->notifier->notifyRole('Nurse', $payload);
        }
    }
}
