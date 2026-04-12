<?php

namespace App\Listeners;

use App\Events\LabResultsCompleted;
use App\Notifications\LabRequestNotification;

class NotifyDoctorLabResults
{
    public function handle(LabResultsCompleted $event): void
    {
        $labRequest = $event->labRequest->load('visit.doctor');
        $doctor = $labRequest->visit?->doctor;

        if ($doctor) {
            $doctor->notify(new LabRequestNotification($labRequest, 'completed'));
        }
    }
}
