<?php

namespace App\Listeners;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Events\PrescriptionCreated;
use App\Services\NotificationService;

class NotifyPharmacists
{
    public function __construct(protected NotificationService $notifier) {}

    public function handle(PrescriptionCreated $event): void
    {
        $rx = $event->prescription;
        $this->notifier->notifyRole('Pharmacist', [
            'module' => NotificationModule::PHARMACY,
            'priority' => NotificationPriority::HIGH,
            'title' => 'New prescription',
            'message' => 'Prescription for ' . ($rx->patient->full_name ?? '#' . $rx->patient_id),
            'url' => url("/admin/prescriptions/{$rx->id}"),
            'source_type' => 'prescription',
            'source_id' => $rx->id,
            'patient_id' => $rx->patient_id,
            'visit_id' => $rx->visit_id ?? null,
        ]);
    }
}
