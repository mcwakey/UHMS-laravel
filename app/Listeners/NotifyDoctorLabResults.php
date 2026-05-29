<?php

namespace App\Listeners;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Events\LabResultsCompleted;
use App\Services\NotificationService;

class NotifyDoctorLabResults
{
    public function __construct(protected NotificationService $notifier) {}

    public function handle(LabResultsCompleted $event): void
    {
        $labRequest = $event->labRequest->load([
            'visit.activeConsultationRoute.doctor',
            'visit.pendingConsultationRoutes.doctor',
        ]);
        $doctor = $labRequest->visit?->currentConsultationDoctor();

        if ($doctor) {
            $this->notifier->notifyUser($doctor, [
                'module' => NotificationModule::INVESTIGATION,
                'priority' => NotificationPriority::HIGH,
                'title' => 'Lab results ready',
                'message' => 'Lab results available for patient ' . ($labRequest->patient->full_name ?? '#' . $labRequest->patient_id),
                'url' => url("/admin/lab-requests/{$labRequest->id}"),
                'source_type' => 'lab_request_completed',
                'source_id' => $labRequest->id,
                'patient_id' => $labRequest->patient_id,
                'visit_id' => $labRequest->visit_id ?? null,
            ]);
        }
    }
}
