<?php

namespace App\Listeners;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Events\PatientDischarged;
use App\Services\NotificationService;

class NotifyAccountantsDischarge
{
    public function __construct(protected NotificationService $notifier) {}

    public function handle(PatientDischarged $event): void
    {
        $admission = $event->admission;
        $this->notifier->notifyRole('Accountant', [
            'module' => NotificationModule::BILLING,
            'priority' => NotificationPriority::HIGH,
            'title' => 'Patient discharged — final billing',
            'message' => 'Patient ' . ($admission->patient->full_name ?? '#' . $admission->patient_id) . ' is discharged. Finalise billing.',
            'url' => url("/admin/admissions/{$admission->id}"),
            'source_type' => 'admission_discharge',
            'source_id' => $admission->id,
            'admission_id' => $admission->id,
            'patient_id' => $admission->patient_id,
        ]);
    }
}
