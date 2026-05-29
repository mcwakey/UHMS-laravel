<?php

namespace App\Listeners;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Events\LabRequestCreated;
use App\Services\NotificationService;
use Spatie\Permission\Models\Role;

class NotifyLabTechnicians
{
    public function __construct(protected NotificationService $notifier) {}

    public function handle(LabRequestCreated $event): void
    {
        if (! Role::where('name', 'Lab Technician')->where('guard_name', 'web')->exists()) {
            return;
        }

        $lab = $event->labRequest;
        $this->notifier->notifyRole('Lab Technician', [
            'module' => NotificationModule::INVESTIGATION,
            'priority' => NotificationPriority::HIGH,
            'title' => 'New lab request',
            'message' => 'Lab request created for patient ' . ($lab->patient->full_name ?? '#' . $lab->patient_id),
            'url' => url("/admin/lab-requests/{$lab->id}"),
            'source_type' => 'lab_request',
            'source_id' => $lab->id,
            'patient_id' => $lab->patient_id,
            'visit_id' => $lab->visit_id ?? null,
        ]);
    }
}
