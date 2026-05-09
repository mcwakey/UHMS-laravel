<?php

namespace App\Listeners;

use App\Events\LabResultsCompleted;
use App\Notifications\LabRequestNotification;

class NotifyDoctorLabResults
{
    public function handle(LabResultsCompleted $event): void
    {
        $labRequest = $event->labRequest->load('visit.assignedDoctor');
        $doctor = $labRequest->visit?->assignedDoctor;

        if ($doctor) {
            $doctor->notify(new LabRequestNotification($labRequest, 'completed'));
        }
    }
}
