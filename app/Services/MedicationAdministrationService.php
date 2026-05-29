<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\ClinicalTask;
use App\Models\MedicationAdministration;
use App\Models\MedicationAdministrationSchedule;
use App\Models\MedicationOrder;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MedicationAdministrationService
{
    private const NON_GIVEN_STATUSES = [
        MedicationAdministration::STATUS_MISSED,
        MedicationAdministration::STATUS_HELD,
        MedicationAdministration::STATUS_REFUSED,
        MedicationAdministration::STATUS_SKIPPED,
        MedicationAdministration::STATUS_NOT_GIVEN,
    ];

    public function __construct(
        private ProductStockMovementService $stockMovements,
        private MedicationProgressService $progress,
        private ClinicalTaskService $tasks,
        private MedicationAdministrationLogService $logs,
    ) {}

    public function administerSchedule(MedicationAdministrationSchedule $schedule, array $data, User $user): MedicationAdministration
    {
        $schedule->loadMissing(['medicationOrder.frequency', 'clinicalTask']);
        $order = $schedule->medicationOrder;

        return DB::transaction(function () use ($schedule, $order, $data, $user) {
            $schedule = MedicationAdministrationSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            $order = MedicationOrder::query()->lockForUpdate()->findOrFail($order->id);

            $this->assertOrderCanBeAdministered($order);

            if ($schedule->administration()->exists() || in_array($schedule->status, $order->getClosedStatuses(), true)) {
                throw ValidationException::withMessages([
                    'schedule_id' => 'This scheduled dose has already been closed.',
                ]);
            }

            $status = $this->normalizeStatus($data['status'] ?? MedicationAdministration::STATUS_GIVEN);
            $this->validateAdministrationData($status, $data);

            $sourceStockType = $data['source_stock_type'] ?? MedicationAdministration::SOURCE_PATIENT_STOCK;
            $stockMovementId = $this->handleStockSource($order, $sourceStockType, $status, $data, $user);

            $administration = MedicationAdministration::create([
                'medication_order_id' => $order->id,
                'schedule_id' => $schedule->id,
                'clinical_task_id' => $schedule->clinical_task_id,
                'visit_id' => $schedule->visit_id,
                'admission_id' => $schedule->admission_id,
                'emergency_case_id' => $schedule->emergency_case_id,
                'emergency_session_id' => $schedule->emergency_session_id,
                'medical_record_id' => $schedule->medical_record_id,
                'consultation_route_id' => $schedule->consultation_route_id,
                'patient_id' => $schedule->patient_id,
                'administered_by' => $user->id,
                'administered_at' => $data['administered_at'] ?? now(),
                'scheduled_at' => $schedule->scheduled_at,
                'dose_given' => $data['dose_given'] ?? $schedule->dose ?? $order->dose,
                'dose_unit' => $data['dose_unit'] ?? $schedule->dose_unit ?? $order->dose_unit,
                'route' => $data['route'] ?? $schedule->route ?? $order->route,
                'status' => $status,
                'reason_not_given' => $data['reason_not_given'] ?? null,
                'notes' => $data['notes'] ?? null,
                'reaction' => $data['reaction'] ?? null,
                'source_stock_type' => $sourceStockType,
                'stock_location_id' => $data['stock_location_id'] ?? null,
                'stock_movement_id' => $stockMovementId,
                'witnessed_by' => $data['witnessed_by'] ?? null,
            ]);

            $scheduleStatus = $status === MedicationAdministration::STATUS_NOT_GIVEN
                ? MedicationAdministrationSchedule::STATUS_MISSED
                : $status;

            $schedule->update(['status' => $scheduleStatus]);

            if ($schedule->clinicalTask) {
                $taskStatus = $status === MedicationAdministration::STATUS_GIVEN
                    ? ClinicalTask::STATUS_COMPLETED
                    : ($status === MedicationAdministration::STATUS_NOT_GIVEN ? ClinicalTask::STATUS_MISSED : $status);
                $this->tasks->completeMedicationTask($schedule->clinicalTask, $user, $taskStatus, $data['notes'] ?? $data['reason_not_given'] ?? null);
            }

            $this->logs->record($status, $order, $schedule, $administration, $user, null, $administration->toArray(), $data['reason_not_given'] ?? null);
            $this->progress->refreshOrderStatus($order);

            return $administration->fresh(['administeredBy', 'schedule', 'medicationOrder']);
        });
    }

    public function administerPrn(MedicationOrder $order, array $data, User $user): MedicationAdministration
    {
        $order->loadMissing('frequency');

        if (! $order->frequency?->is_prn) {
            throw ValidationException::withMessages(['medication_order_id' => 'Only PRN/SOS medication orders can be administered without a fixed schedule.']);
        }

        return DB::transaction(function () use ($order, $data, $user) {
            $order = MedicationOrder::query()->lockForUpdate()->findOrFail($order->id);
            $this->assertOrderCanBeAdministered($order);

            $status = $this->normalizeStatus($data['status'] ?? MedicationAdministration::STATUS_GIVEN);
            $this->validateAdministrationData($status, $data);

            if (empty($data['reason_not_given']) && $status === MedicationAdministration::STATUS_GIVEN) {
                $data['reason_not_given'] = $data['reason'] ?? null;
            }

            $sourceStockType = $data['source_stock_type'] ?? MedicationAdministration::SOURCE_PATIENT_STOCK;
            $stockMovementId = $this->handleStockSource($order, $sourceStockType, $status, $data, $user);

            $administration = MedicationAdministration::create([
                'medication_order_id' => $order->id,
                'visit_id' => $order->visit_id,
                'admission_id' => $order->admission_id,
                'emergency_case_id' => $order->emergency_case_id,
                'emergency_session_id' => $order->emergency_session_id,
                'medical_record_id' => $order->medical_record_id,
                'consultation_route_id' => $order->consultation_route_id,
                'patient_id' => $order->patient_id,
                'administered_by' => $user->id,
                'administered_at' => $data['administered_at'] ?? now(),
                'dose_given' => $data['dose_given'] ?? $order->dose,
                'dose_unit' => $data['dose_unit'] ?? $order->dose_unit,
                'route' => $data['route'] ?? $order->route,
                'status' => $status,
                'reason_not_given' => $data['reason_not_given'] ?? $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'reaction' => $data['reaction'] ?? null,
                'source_stock_type' => $sourceStockType,
                'stock_location_id' => $data['stock_location_id'] ?? null,
                'stock_movement_id' => $stockMovementId,
                'witnessed_by' => $data['witnessed_by'] ?? null,
            ]);

            $this->logs->record('PRN_'.$status, $order, null, $administration, $user, null, $administration->toArray(), $data['reason_not_given'] ?? null);
            $this->progress->refreshOrderStatus($order);

            return $administration->fresh(['administeredBy', 'medicationOrder']);
        });
    }

    public function correct(MedicationAdministration $administration, array $data, User $user): MedicationAdministration
    {
        if (empty($data['correction_reason'])) {
            throw ValidationException::withMessages(['correction_reason' => 'A correction reason is required.']);
        }

        $old = $administration->toArray();
        $administration->update(array_merge($data, [
            'corrected_by' => $user->id,
            'corrected_at' => now(),
        ]));

        $this->logs->record('CORRECTED', $administration->medicationOrder, $administration->schedule, $administration, $user, $old, $administration->fresh()->toArray(), $data['correction_reason']);

        return $administration->fresh();
    }

    private function assertOrderCanBeAdministered(MedicationOrder $order): void
    {
        if (in_array($order->status, [
            MedicationOrder::STATUS_STOPPED,
            MedicationOrder::STATUS_CANCELLED,
            MedicationOrder::STATUS_EXPIRED,
        ], true)) {
            throw ValidationException::withMessages([
                'medication_order_id' => 'This medication order is not active for administration.',
            ]);
        }
    }

    private function validateAdministrationData(string $status, array $data): void
    {
        if ($status === MedicationAdministration::STATUS_GIVEN && empty($data['dose_given'])) {
            throw ValidationException::withMessages(['dose_given' => 'Dose given is required when medication is given.']);
        }

        if (in_array($status, self::NON_GIVEN_STATUSES, true) && empty($data['reason_not_given'])) {
            throw ValidationException::withMessages(['reason_not_given' => 'A reason is required when medication is not given.']);
        }
    }

    private function handleStockSource(MedicationOrder $order, string $sourceStockType, string $status, array $data, User $user): ?int
    {
        if (! in_array($status, [
            MedicationAdministration::STATUS_GIVEN,
            MedicationAdministration::STATUS_PARTIALLY_GIVEN,
        ], true)) {
            return null;
        }

        if ($sourceStockType === MedicationAdministration::SOURCE_PATIENT_STOCK) {
            if ($this->progress->availablePatientDispensedDoses($order) < 1) {
                throw ValidationException::withMessages([
                    'source_stock_type' => 'No patient-dispensed dose is available for this medication. Use ward or emergency stock if clinically appropriate.',
                ]);
            }

            return null;
        }

        $locationId = (int) ($data['stock_location_id'] ?? 0);
        if ($locationId <= 0) {
            throw ValidationException::withMessages(['stock_location_id' => 'A ward or emergency stock location is required.']);
        }

        $location = StockLocation::query()->where('id', $locationId)->where('is_active', true)->first();
        if (! $location) {
            throw ValidationException::withMessages(['stock_location_id' => 'The selected stock location is not active.']);
        }

        if (! $order->product_id) {
            throw ValidationException::withMessages(['medication_order_id' => 'This medication is not linked to a product stock item.']);
        }

        $movementType = $sourceStockType === MedicationAdministration::SOURCE_EMERGENCY_STOCK
            ? StockMovementType::EMERGENCY_ADMINISTRATION_OUT
            : StockMovementType::WARD_CONSUMED;

        $movement = $this->stockMovements->createMovement([
            'product_id' => $order->product_id,
            'drug_id' => $order->drug_id,
            'stock_location_id' => $locationId,
            'movement_type' => $movementType,
            'quantity' => 1,
            'source_type' => MedicationOrder::class,
            'source_id' => $order->id,
            'performed_by' => $user->id,
            'notes' => 'Medication administered from '.$sourceStockType.' for '.$order->display_name,
        ]);

        return $movement->id;
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtoupper($status);

        return match ($status) {
            'GIVEN' => MedicationAdministration::STATUS_GIVEN,
            'PARTIALLY_GIVEN' => MedicationAdministration::STATUS_PARTIALLY_GIVEN,
            'HELD' => MedicationAdministration::STATUS_HELD,
            'REFUSED' => MedicationAdministration::STATUS_REFUSED,
            'SKIPPED' => MedicationAdministration::STATUS_SKIPPED,
            'CANCELLED' => MedicationAdministration::STATUS_CANCELLED,
            'NOT_GIVEN' => MedicationAdministration::STATUS_NOT_GIVEN,
            default => MedicationAdministration::STATUS_MISSED,
        };
    }
}
