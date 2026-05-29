<?php

namespace App\Console\Commands;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Models\MedicationAdministrationSchedule;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class MedicationsCheckOverdueCommand extends Command
{
    protected $signature = 'medications:check-overdue {--minutes=30 : Dedupe window in minutes}';
    protected $description = 'Notify nurses about MAR slots past their scheduled time.';

    public function handle(NotificationService $notifier): int
    {
        $dedupe = (int) $this->option('minutes');
        $now = now();

        $slots = MedicationAdministrationSchedule::query()
            ->whereIn('status', ['SCHEDULED', 'DUE'])
            ->where('scheduled_at', '<', $now)
            ->with(['medicationOrder.visit.admission.bed.ward'])
            ->limit(500)
            ->get();

        $count = 0;
        foreach ($slots as $slot) {
            $order = $slot->medicationOrder;
            $patientId = $order?->patient_id;
            $admission = $order?->visit?->admission;
            $assignedNurse = $admission?->bed?->ward?->charge_nurse_id ?? null;

            $payload = [
                'module' => NotificationModule::MAR,
                'priority' => NotificationPriority::URGENT,
                'title' => 'Medication slot overdue',
                'message' => sprintf('Slot %d overdue since %s', $slot->id, optional($slot->scheduled_at)->format('H:i')),
                'source_type' => 'mar_slot',
                'source_id' => $slot->id,
                'patient_id' => $patientId,
                'url' => $patientId ? url("/admin/patients/{$patientId}/mar") : '#',
            ];

            if ($assignedNurse) {
                $user = \App\Models\User::find($assignedNurse);
                if ($user && $notifier->notifyUser($user, $payload, $dedupe)) {
                    $count++;
                }
            } else {
                $count += $notifier->notifyRole('Nurse', $payload, $dedupe);
            }
        }

        $this->info("MAR overdue notifications dispatched: {$count}");
        return self::SUCCESS;
    }
}
