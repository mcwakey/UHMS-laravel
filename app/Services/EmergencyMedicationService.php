<?php

namespace App\Services;

use App\Models\EmergencyCase;
use App\Models\MedicationOrder;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmergencyMedicationService
{
    public function __construct(
        private MedicationFrequencyService $frequencies,
        private MedicationScheduleService $schedules,
        private BillingService $billing,
        private EmergencyTimelineService $timeline,
    ) {}

    public function order(EmergencyCase $case, array $data, User $user): MedicationOrder
    {
        return DB::transaction(function () use ($case, $data, $user) {
            $product = Product::findOrFail($data['product_id']);
            $frequency = $this->frequencies->resolve($data['frequency_code'] ?? 'STAT');
            [$durationValue, $durationUnit] = $this->frequencies->parseDuration($data['duration'] ?? null);
            [$dose, $doseUnit] = $this->frequencies->splitDose($data['dose'] ?? null);
            $quantity = max(0, (int) ($data['quantity_ordered'] ?? 1));
            $totalDoses = $this->frequencies->expectedDoses($frequency, $durationValue, $durationUnit, $quantity);

            $order = MedicationOrder::create([
                'visit_id' => $case->visit_id,
                'emergency_case_id' => $case->id,
                'patient_id' => $case->patient_id,
                'prescribed_by' => $user->id,
                'product_id' => $product->id,
                'drug_name' => $data['drug_name'] ?? $product->name,
                'dose' => $dose ?: ($data['dose'] ?? null),
                'dose_unit' => $doseUnit,
                'route' => $data['route'] ?? null,
                'frequency_id' => $frequency?->id,
                'frequency_code' => $frequency?->code ?? $this->frequencies->normalizeCode($data['frequency_code'] ?? 'STAT'),
                'duration_value' => $durationValue,
                'duration_unit' => $durationUnit,
                'total_doses' => $totalDoses,
                'quantity_ordered' => $quantity ?: $totalDoses,
                'quantity_dispensed' => (float) ($data['quantity_dispensed'] ?? 0),
                'start_at' => $data['start_at'] ?? now(),
                'instructions' => $data['instructions'] ?? null,
                'status' => MedicationOrder::STATUS_DISPENSED,
                'source_type' => EmergencyCase::class,
                'source_id' => $case->id,
            ]);

            if ($product->is_billable) {
                $this->billing->addProductToVisitInvoice(
                    visit: $case->visit,
                    product: $product,
                    sourceType: 'emergency_medication_order',
                    sourceId: $order->id,
                    quantity: max(1, (float) ($order->quantity_ordered ?: 1)),
                    description: $order->display_name,
                );
            }

            $this->schedules->generateForOrder($order->fresh('frequency'));
            $this->timeline->record($case, 'MEDICATION_ORDERED', 'Emergency medication ordered', $order->display_name, $order, $user);

            return $order->fresh(['frequency', 'schedules.clinicalTask']);
        });
    }
}
