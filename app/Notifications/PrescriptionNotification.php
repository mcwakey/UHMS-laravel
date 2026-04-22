<?php

namespace App\Notifications;

use App\Models\Prescription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PrescriptionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Prescription $prescription,
        public string $action = 'created'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $patient = $this->prescription->patient;
        $messages = [
            'created' => "New prescription for {$patient->full_name} — ready for dispensing",
            'dispensed' => "Prescription for {$patient->full_name} has been dispensed",
        ];

        return [
            'type' => 'prescription',
            'action' => $this->action,
            'message' => $messages[$this->action] ?? "Prescription for {$patient->full_name} updated",
            'prescription_id' => $this->prescription->id,
            'patient_id' => $patient->id,
            'patient_name' => $patient->full_name,
            'visit_id' => $this->prescription->visit_id,
            'url' => route('admin.pharmacy.dispensing.show', $this->prescription),
            'icon' => 'ti-pill',
            'color' => 'success',
        ];
    }
}
