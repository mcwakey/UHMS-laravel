<?php

namespace App\Notifications;

use App\Models\LabRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LabRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LabRequest $labRequest,
        public string $action = 'created'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $patient = $this->labRequest->patient;
        $messages = [
            'created' => "New lab request #{$this->labRequest->request_number} for {$patient->full_name}",
            'completed' => "Lab results ready for request #{$this->labRequest->request_number} ({$patient->full_name})",
        ];

        return [
            'type' => 'lab_request',
            'action' => $this->action,
            'message' => $messages[$this->action] ?? "Lab request #{$this->labRequest->request_number} updated",
            'lab_request_id' => $this->labRequest->id,
            'patient_id' => $patient->id,
            'patient_name' => $patient->full_name,
            'request_number' => $this->labRequest->request_number,
            'url' => route('admin.lab.requests.show', $this->labRequest),
            'icon' => 'ti-microscope',
            'color' => 'primary',
        ];
    }
}
