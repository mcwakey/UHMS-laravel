<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Models\MedicationAdministration;
use App\Models\MedicationAdministrationLog;
use App\Models\MedicationAdministrationSchedule;
use App\Models\MedicationOrder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Canonical funnel for MAR events (orders, schedules, dose administration,
 * corrections). It keeps the MAR-specific `medication_administration_logs` table
 * AND mirrors each event to the central activity log so it surfaces on the
 * patient profile timeline with full clinical/stock context.
 */
class MedicationAdministrationLogService
{
    public function record(
        string $action,
        ?MedicationOrder $order = null,
        ?MedicationAdministrationSchedule $schedule = null,
        ?MedicationAdministration $administration = null,
        ?User $user = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?string $reason = null,
    ): MedicationAdministrationLog {
        $log = MedicationAdministrationLog::create([
            'medication_administration_id' => $administration?->id,
            'medication_order_id' => $order?->id ?? $schedule?->medication_order_id ?? $administration?->medication_order_id,
            'schedule_id' => $schedule?->id ?? $administration?->schedule_id,
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $reason,
            'performed_by' => $user?->id,
        ]);

        try {
            $this->mirrorToActivityLog($action, $order, $schedule, $administration, $user, $oldValue, $newValue, $reason);
        } catch (\Throwable $e) {
            // Logging must never break a medication action.
        }

        return $log;
    }

    private function mirrorToActivityLog(
        string $action,
        ?MedicationOrder $order,
        ?MedicationAdministrationSchedule $schedule,
        ?MedicationAdministration $administration,
        ?User $user,
        ?array $oldValue,
        ?array $newValue,
        ?string $reason,
    ): void {
        $resolvedOrder = $order
            ?? $administration?->medicationOrder
            ?? $schedule?->medicationOrder;

        $context = $this->marContext($resolvedOrder, $schedule, $administration);
        $event = $this->mapEvent($action);
        $isPrn = str_starts_with($action, 'PRN_');

        $data = $context + array_filter([
            'reason' => $reason ?: ($administration?->reason_not_given),
            'severity' => $this->isWarning($action) ? 'WARNING' : 'INFO',
        ], fn ($v) => $v !== null);

        if ($isPrn) {
            $data['metadata'] = ['prn' => true];
        }

        $changed = $this->clean((array) $newValue);
        if ($changed !== []) {
            $data['new_values'] = $changed;
            $old = array_intersect_key($this->clean((array) $oldValue), $changed);
            if ($old !== []) {
                $data['old_values'] = $old;
            }
        }

        $subject = $administration ?? $resolvedOrder ?? $schedule;

        app(ActivityLogService::class)->log(
            LogModule::MAR,
            $event,
            $data + ($user ? ['causer' => $user] : []),
            $subject,
            $this->describe($action, $resolvedOrder, $administration, $reason),
        );

        // Surface a distinct adverse-reaction event when one was recorded.
        if ($administration && filled($administration->reaction)) {
            app(ActivityLogService::class)->log(
                LogModule::MAR,
                'ADVERSE_REACTION_RECORDED',
                $context + array_filter([
                    'severity' => 'WARNING',
                    'reason' => $administration->reaction,
                ], fn ($v) => $v !== null) + ($user ? ['causer' => $user] : []),
                $administration,
                'Adverse reaction recorded: ' . ($this->drugLabel($resolvedOrder) ?: 'medication'),
            );
        }
    }

    private function marContext(?MedicationOrder $order, ?MedicationAdministrationSchedule $schedule, ?MedicationAdministration $administration): array
    {
        $primary = $administration ?: $order;

        return array_filter([
            'patient_id' => $primary?->patient_id ?? $order?->patient_id,
            'visit_id' => $primary?->visit_id ?? $order?->visit_id,
            'admission_id' => $primary?->admission_id ?? $order?->admission_id,
            'emergency_case_id' => $primary?->emergency_case_id ?? $order?->emergency_case_id,
            'medical_record_id' => $primary?->medical_record_id ?? $order?->medical_record_id,
            'consultation_route_id' => $primary?->consultation_route_id ?? $order?->consultation_route_id,
            'medication_order_id' => $order?->id ?? $administration?->medication_order_id ?? $schedule?->medication_order_id,
            'medication_schedule_id' => $schedule?->id ?? $administration?->schedule_id,
            'medication_administration_id' => $administration?->id,
            'prescription_id' => $order?->prescription_id,
            'prescription_item_id' => $order?->prescription_item_id,
            'product_id' => $order?->product_id,
            'drug_id' => $order?->drug_id,
            'stock_location_id' => $administration?->stock_location_id,
            'stock_movement_id' => $administration?->stock_movement_id,
            'source_type' => 'medication_administration',
            'source_id' => $administration?->id ?? $order?->id,
        ], fn ($v) => $v !== null);
    }

    private function mapEvent(string $action): string
    {
        $base = str_starts_with($action, 'PRN_') ? substr($action, 4) : $action;

        return match ($base) {
            'GIVEN', 'PARTIALLY_GIVEN', 'ADMINISTERED' => 'DOSE_ADMINISTERED',
            'HELD' => 'DOSE_HELD',
            'MISSED', 'NOT_GIVEN' => 'DOSE_MISSED',
            'REFUSED' => 'DOSE_REFUSED',
            'SKIPPED' => 'DOSE_SKIPPED',
            'CANCELLED' => 'DOSE_CANCELLED',
            'CORRECTED' => 'DOSE_CORRECTED',
            'ORDER_CREATED' => 'MEDICATION_ORDER_CREATED',
            'ORDER_HELD' => 'MEDICATION_ORDER_HELD',
            'ORDER_RESUMED' => 'MEDICATION_ORDER_RESUMED',
            'ORDER_STOPPED' => 'MEDICATION_ORDER_STOPPED',
            'ORDER_CANCELLED' => 'MEDICATION_ORDER_CANCELLED',
            'DISPENSED_QUANTITY_RECORDED' => 'MEDICATION_ORDER_UPDATED',
            'SCHEDULE_GENERATED' => 'MEDICATION_SCHEDULE_GENERATED',
            default => 'MAR_' . $base,
        };
    }

    private function isWarning(string $action): bool
    {
        $base = str_starts_with($action, 'PRN_') ? substr($action, 4) : $action;

        return in_array($base, ['CORRECTED', 'ORDER_STOPPED', 'ORDER_CANCELLED', 'MISSED', 'NOT_GIVEN', 'REFUSED'], true);
    }

    private function describe(string $action, ?MedicationOrder $order, ?MedicationAdministration $administration, ?string $reason): string
    {
        $drug = $this->drugLabel($order) ?: 'medication';
        $isPrn = str_starts_with($action, 'PRN_');
        $base = $isPrn ? substr($action, 4) : $action;
        $prnPrefix = $isPrn ? 'PRN ' : '';
        $suffix = $reason ? ' — ' . $reason : '';

        return match ($base) {
            'GIVEN', 'PARTIALLY_GIVEN', 'ADMINISTERED' => "{$prnPrefix}Medication administered: {$drug}",
            'HELD' => "Dose held: {$drug}{$suffix}",
            'MISSED', 'NOT_GIVEN' => "Dose missed: {$drug}{$suffix}",
            'REFUSED' => "Dose refused: {$drug}{$suffix}",
            'SKIPPED' => "Dose skipped: {$drug}{$suffix}",
            'CANCELLED' => "Dose cancelled: {$drug}{$suffix}",
            'CORRECTED' => "Dose corrected: {$drug}{$suffix}",
            'ORDER_CREATED' => "Medication order created: {$drug}",
            'ORDER_HELD' => "Medication order held: {$drug}{$suffix}",
            'ORDER_RESUMED' => "Medication order resumed: {$drug}",
            'ORDER_STOPPED' => "Medication stopped: {$drug}{$suffix}",
            'ORDER_CANCELLED' => "Medication order cancelled: {$drug}{$suffix}",
            'DISPENSED_QUANTITY_RECORDED' => "Dispensed quantity recorded: {$drug}",
            'SCHEDULE_GENERATED' => "Medication schedule generated: {$drug}",
            default => "Medication {$base}: {$drug}",
        };
    }

    private function drugLabel(?MedicationOrder $order): ?string
    {
        if (! $order) {
            return null;
        }
        $name = $order->drug_name ?: $order->product?->name;
        $dose = trim(($order->dose ?? '') . ' ' . ($order->dose_unit ?? ''));

        return trim($name . ($dose !== '' ? ' ' . $dose : '')) ?: null;
    }

    /** Strip context / id / timestamp keys, keep clinically meaningful fields. */
    private function clean(array $values): array
    {
        $drop = [
            'id', 'created_at', 'updated_at', 'deleted_at',
            'patient_id', 'visit_id', 'admission_id', 'emergency_case_id', 'emergency_session_id',
            'medical_record_id', 'consultation_route_id', 'medication_order_id', 'schedule_id',
            'clinical_task_id', 'administered_by', 'witnessed_by', 'corrected_by', 'stock_movement_id',
            'stock_location_id',
        ];

        return array_diff_key($values, array_flip($drop));
    }
}
