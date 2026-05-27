<?php

namespace App\Services;

use App\Enums\AdmissionStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\MedicationOrder;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MedicationOrderService
{
    public function __construct(
        private MedicationFrequencyService $frequencies,
        private MedicationScheduleService $schedules,
        private MedicationProgressService $progress,
        private MedicationAdministrationLogService $logs,
    ) {}

    public function createOrdersForPrescription(Prescription $prescription): void
    {
        $prescription->loadMissing(['visit.admission', 'items.drug.product', 'medicalRecord', 'consultationRoute']);

        if (! $this->shouldTrackPrescription($prescription)) {
            return;
        }

        foreach ($prescription->items as $item) {
            $this->ensureOrderForPrescriptionItem($item);
        }
    }

    public function ensureOrderForPrescriptionItem(PrescriptionItem $item): ?MedicationOrder
    {
        $item->loadMissing(['prescription.visit.admission', 'prescription.medicalRecord', 'prescription.consultationRoute', 'drug.product']);
        $prescription = $item->prescription;

        if (! $prescription || ! $this->shouldTrackPrescription($prescription)) {
            return null;
        }

        return DB::transaction(function () use ($item, $prescription) {
            $existing = MedicationOrder::query()
                ->where('prescription_item_id', $item->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            $visit = $prescription->visit;
            $admission = $visit?->admission;
            $frequency = $this->frequencies->resolve($item->frequency);
            [$durationValue, $durationUnit] = $this->frequencies->parseDuration($item->duration);
            [$dose, $doseUnit] = $this->frequencies->splitDose($item->dosage);
            $quantity = max(0, (int) ($item->quantity ?? 0));
            $totalDoses = $this->frequencies->expectedDoses($frequency, $durationValue, $durationUnit, $quantity);
            $drug = $item->drug;

            $order = MedicationOrder::create([
                'visit_id' => $prescription->visit_id,
                'admission_id' => $admission?->id,
                'emergency_case_id' => null,
                'patient_id' => $prescription->patient_id,
                'medical_record_id' => $prescription->medical_record_id,
                'consultation_route_id' => $prescription->consultation_route_id,
                'prescription_id' => $prescription->id,
                'prescription_item_id' => $item->id,
                'prescribed_by' => $prescription->doctor_id ?: $prescription->created_by,
                'product_id' => $drug?->product_id,
                'drug_id' => $item->drug_id,
                'drug_name' => $item->drug_name ?: $drug?->display_name,
                'dose' => $dose,
                'dose_unit' => $doseUnit,
                'route' => $item->route,
                'frequency_id' => $frequency?->id,
                'frequency_code' => $frequency?->code ?? $this->frequencies->normalizeCode($item->frequency),
                'duration_value' => $durationValue,
                'duration_unit' => $durationUnit,
                'total_doses' => $totalDoses,
                'quantity_ordered' => $quantity,
                'quantity_dispensed' => 0,
                'start_at' => now(),
                'end_at' => null,
                'instructions' => $item->instructions,
                'status' => MedicationOrder::STATUS_PENDING_DISPENSING,
                'source_type' => PrescriptionItem::class,
                'source_id' => $item->id,
            ]);

            $this->logs->record('ORDER_CREATED', $order, null, null, null, null, $order->toArray());

            if ($frequency?->is_stat && $this->isEmergencyVisit($visit)) {
                $this->schedules->generateForOrder($order);
            }

            return $order;
        });
    }

    public function recordDispensedQuantity(PrescriptionItem $item, int|float $quantity): ?MedicationOrder
    {
        $order = $this->ensureOrderForPrescriptionItem($item);

        if (! $order) {
            return null;
        }

        return DB::transaction(function () use ($order, $quantity) {
            $order = MedicationOrder::query()->lockForUpdate()->find($order->id);
            $old = $order->only(['quantity_dispensed', 'status']);
            $dispensed = (float) $order->quantity_dispensed + (float) $quantity;

            $status = $dispensed >= (float) $order->quantity_ordered
                ? MedicationOrder::STATUS_DISPENSED
                : MedicationOrder::STATUS_PARTIALLY_DISPENSED;

            $order->update([
                'quantity_dispensed' => $dispensed,
                'status' => $status,
            ]);

            if ($order->frequency?->requires_schedule) {
                $this->schedules->generateForOrder($order->fresh('frequency'));
            }

            $order = $this->progress->refreshOrderStatus($order->fresh());
            $this->logs->record('DISPENSED_QUANTITY_RECORDED', $order, null, null, null, $old, $order->only(['quantity_dispensed', 'status']));

            return $order;
        });
    }

    public function hold(MedicationOrder $order, User $user, string $reason): MedicationOrder
    {
        $old = $order->only(['status']);
        $order->update(['status' => MedicationOrder::STATUS_HELD, 'instructions' => trim(($order->instructions ? $order->instructions."\n" : '').'Held: '.$reason)]);
        $this->logs->record('ORDER_HELD', $order, null, null, $user, $old, $order->only(['status']), $reason);

        return $order->fresh();
    }

    public function stop(MedicationOrder $order, User $user, string $reason): MedicationOrder
    {
        return DB::transaction(function () use ($order, $user, $reason) {
            $old = $order->only(['status']);
            $order->update(['status' => MedicationOrder::STATUS_STOPPED, 'end_at' => now()]);
            $this->schedules->cancelFutureSchedules($order, $user, $reason);
            $this->logs->record('ORDER_STOPPED', $order, null, null, $user, $old, $order->only(['status', 'end_at']), $reason);

            return $order->fresh();
        });
    }

    private function shouldTrackPrescription(Prescription $prescription): bool
    {
        $visit = $prescription->visit;

        if (! $visit) {
            return false;
        }

        if ($visit->admission && (! $visit->admission->status || $visit->admission->status === AdmissionStatus::ADMITTED)) {
            return true;
        }

        return $this->isEmergencyVisit($visit)
            || $visit->status === VisitStatus::ADMITTED
            || $visit->status === VisitStatus::INPATIENT
            || $visit->visit_type === VisitType::INPATIENT;
    }

    private function isEmergencyVisit($visit): bool
    {
        return $visit
            && ($visit->status === VisitStatus::EMERGENCY || $visit->visit_type === VisitType::EMERGENCY);
    }
}
