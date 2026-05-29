<?php

namespace App\Services;

use App\Enums\ServiceType;
use App\Models\EmergencyBayAssignment;
use App\Models\EmergencyBedCharge;
use App\Models\EmergencyCase;
use App\Models\EmergencyDailyConsumableCharge;
use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmergencyBedBillingService
{
    public function __construct(
        private BillingService $billing,
        private VisitPathwayService $pathway,
    ) {}

    public function startForAssignment(EmergencyBayAssignment $assignment, ?User $user = null): void
    {
        $assignment->loadMissing(['emergencyCase.visit', 'emergencyBay.bed']);
        $case = $assignment->emergencyCase;
        if (! $case || ! $case->visit) {
            return;
        }

        DB::transaction(function () use ($assignment, $case, $user) {
            EmergencyBedCharge::firstOrCreate(
                ['emergency_bay_assignment_id' => $assignment->id],
                [
                    'emergency_case_id' => $case->id,
                    'visit_id' => $case->visit_id,
                    'patient_id' => $case->patient_id,
                    'bed_id' => $assignment->bed_id,
                    'ward_id' => $assignment->ward_id,
                    'emergency_bay_id' => $assignment->emergency_bay_id,
                    'started_at' => $assignment->assigned_at ?? now(),
                    'billing_unit' => 'PER_DAY',
                    'quantity' => 1,
                    'service_id' => $this->resolveEmergencyBedService($assignment)?->id,
                    'status' => EmergencyBedCharge::STATUS_ACTIVE,
                    'created_by' => $user?->id ?? Auth::id(),
                ]
            );

            if ($service = $this->resolveEmergencyConsumableService()) {
                EmergencyDailyConsumableCharge::firstOrCreate(
                    ['emergency_bay_assignment_id' => $assignment->id],
                    [
                        'emergency_case_id' => $case->id,
                        'visit_id' => $case->visit_id,
                        'patient_id' => $case->patient_id,
                        'bed_id' => $assignment->bed_id,
                        'ward_id' => $assignment->ward_id,
                        'emergency_bay_id' => $assignment->emergency_bay_id,
                        'started_at' => $assignment->assigned_at ?? now(),
                        'billing_unit' => 'PER_DAY',
                        'quantity' => 1,
                        'service_id' => $service->id,
                        'status' => EmergencyDailyConsumableCharge::STATUS_ACTIVE,
                        'created_by' => $user?->id ?? Auth::id(),
                    ]
                );
            }

            $this->pathway->record($case->visit, 'EMERGENCY_BED_STARTED', [
                'source' => $assignment,
                'department_id' => $case->visit->current_department_id,
                'title' => 'Emergency bed count started',
                'description' => $assignment->emergencyBay?->name,
            ]);
        });
    }

    public function endForCase(EmergencyCase $case, ?User $user = null, ?Carbon $endedAt = null): void
    {
        $endedAt ??= now();
        $case->loadMissing('visit');

        DB::transaction(function () use ($case, $user, $endedAt) {
            foreach (EmergencyBedCharge::query()->where('emergency_case_id', $case->id)->where('status', EmergencyBedCharge::STATUS_ACTIVE)->lockForUpdate()->get() as $charge) {
                $this->finalizeBedCharge($case, $charge, $user, $endedAt);
            }

            foreach (EmergencyDailyConsumableCharge::query()->where('emergency_case_id', $case->id)->where('status', EmergencyDailyConsumableCharge::STATUS_ACTIVE)->lockForUpdate()->get() as $charge) {
                $this->finalizeConsumableCharge($case, $charge, $user, $endedAt);
            }
        });
    }

    private function finalizeBedCharge(EmergencyCase $case, EmergencyBedCharge $charge, ?User $user, Carbon $endedAt): void
    {
        $quantity = $this->days($charge->started_at, $endedAt);
        $service = $charge->service_id ? ServiceCatalog::find($charge->service_id) : null;
        $service ??= $this->resolveEmergencyBedService($case->activeBayAssignment()->with('emergencyBay.bed')->first());

        $charge->forceFill([
            'ended_at' => $endedAt,
            'quantity' => $quantity,
            'service_id' => $service?->id,
            'status' => $service ? EmergencyBedCharge::STATUS_BILLED : EmergencyBedCharge::STATUS_ENDED,
            'ended_by' => $user?->id ?? Auth::id(),
        ])->save();

        if ($service && ! $charge->invoice_item_id) {
            $item = $this->billing->addItemToVisitInvoice(
                $case->visit,
                $service,
                'emergency_bed_charge',
                $charge->id,
                $quantity,
                $case->visit?->current_department_id,
                'Emergency bed charge (' . $quantity . ' day' . ($quantity === 1 ? '' : 's') . ')',
            );
            $charge->update(['invoice_item_id' => $item->id]);
        }

        if ($case->visit) {
            $this->pathway->record($case->visit, 'EMERGENCY_BED_ENDED', [
                'source' => $charge,
                'title' => 'Emergency bed count ended',
                'description' => $quantity . ' day(s) billed',
                'completed_at' => $endedAt,
            ]);
        }
    }

    private function finalizeConsumableCharge(EmergencyCase $case, EmergencyDailyConsumableCharge $charge, ?User $user, Carbon $endedAt): void
    {
        $quantity = $this->days($charge->started_at, $endedAt);
        $service = $charge->service_id ? ServiceCatalog::find($charge->service_id) : null;
        $service ??= $this->resolveEmergencyConsumableService();

        $charge->forceFill([
            'ended_at' => $endedAt,
            'quantity' => $quantity,
            'service_id' => $service?->id,
            'status' => $service ? EmergencyDailyConsumableCharge::STATUS_BILLED : EmergencyDailyConsumableCharge::STATUS_ENDED,
            'ended_by' => $user?->id ?? Auth::id(),
        ])->save();

        if ($service && ! $charge->invoice_item_id) {
            $item = $this->billing->addItemToVisitInvoice(
                $case->visit,
                $service,
                'emergency_daily_consumable_charge',
                $charge->id,
                $quantity,
                $case->visit?->current_department_id,
                'Emergency daily consumables (' . $quantity . ' day' . ($quantity === 1 ? '' : 's') . ')',
            );
            $charge->update(['invoice_item_id' => $item->id]);
        }

        if ($case->visit) {
            $this->pathway->record($case->visit, 'EMERGENCY_CONSUMABLES_ENDED', [
                'source' => $charge,
                'title' => 'Emergency daily consumables ended',
                'description' => $quantity . ' day(s) billed',
                'completed_at' => $endedAt,
            ]);
        }
    }

    private function resolveEmergencyBedService(?EmergencyBayAssignment $assignment = null): ?ServiceCatalog
    {
        $service = ServiceCatalog::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('code', 'ER-BED-DAY')
                    ->orWhere('code', 'EMERGENCY-BED-DAY')
                    ->orWhere(function ($sub) {
                        $sub->where('category', ServiceType::BED_CHARGE->value)
                            ->where('name', 'like', '%Emergency%');
                    });
            })
            ->first();

        if ($service) {
            return $service;
        }

        $rate = (float) ($assignment?->emergencyBay?->bed?->daily_rate ?? 0);
        if ($rate <= 0) {
            return null;
        }

        return ServiceCatalog::firstOrCreate(
            ['code' => 'ER-BED-DAY'],
            [
                'name' => 'Emergency Bed Daily Charge',
                'category' => ServiceType::BED_CHARGE->value,
                'price' => $rate,
                'is_active' => true,
                'is_billable' => true,
                'department_id' => $assignment?->emergencyBay?->department_id,
            ]
        );
    }

    private function resolveEmergencyConsumableService(): ?ServiceCatalog
    {
        return ServiceCatalog::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('code', 'ER-CONSUMABLE-DAY')
                    ->orWhere('code', 'EMERGENCY-CONSUMABLE-DAY')
                    ->orWhere(function ($sub) {
                        $sub->where('name', 'like', '%Emergency%')
                            ->where('name', 'like', '%Consumable%');
                    });
            })
            ->first();
    }

    private function days($startedAt, Carbon $endedAt): int
    {
        $started = $startedAt instanceof Carbon ? $startedAt : Carbon::parse($startedAt);
        return max(1, (int) ceil($started->diffInMinutes($endedAt) / 1440));
    }
}
