<?php

namespace App\Notifications;

use App\Models\Admission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdmissionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Admission $admission,
        public string $action = 'admitted'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $patient = $this->admission->patient;
        $bed = $this->admission->bed;
        $ward = $bed?->ward;

        $messages = [
            'admitted' => "{$patient->full_name} admitted to {$ward?->name} — Bed {$bed?->bed_number}",
            'discharged' => "{$patient->full_name} discharged from {$ward?->name}",
        ];

        return [
            'type' => 'admission',
            'action' => $this->action,
            'message' => $messages[$this->action] ?? "Admission #{$this->admission->admission_number} updated",
            'admission_id' => $this->admission->id,
            'patient_id' => $patient->id,
            'patient_name' => $patient->full_name,
            'ward_name' => $ward?->name,
            'bed_number' => $bed?->bed_number,
            'url' => route('admin.admissions.show', $this->admission),
            'icon' => 'ti-bed',
            'color' => $this->action === 'admitted' ? 'info' : 'secondary',
        ];
    }
}
