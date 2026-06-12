<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\ConsumableUsage;
use App\Models\EmergencyCase;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmergencyConsumableService
{
    public function __construct(
        private ProductStockMovementService $movements,
        private BillingService $billing,
        private EmergencyTimelineService $timeline,
        private EmergencySessionService $sessions,
    ) {}

    public function useConsumable(EmergencyCase $case, array $data, User $user): ConsumableUsage
    {
        return DB::transaction(function () use ($case, $data, $user) {
            $session = $this->sessions->getOrCreateForCase($case, $user);
            $product = Product::query()->where('is_active', true)->findOrFail($data['product_id']);
            $quantity = (float) $data['quantity'];
            $location = $this->emergencyStockLocation($case);

            $movement = $this->movements->createMovement([
                'product_id' => $product->id,
                'stock_location_id' => $location->id,
                'movement_type' => StockMovementType::EMERGENCY_ADMINISTRATION_OUT,
                'quantity' => $quantity,
                'source_type' => 'emergency_care',
                'source_id' => $case->id,
                'performed_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $usage = ConsumableUsage::create([
                'visit_id' => $case->visit_id,
                'emergency_case_id' => $case->id,
                'emergency_session_id' => $session->id,
                'medical_record_id' => $session->medical_record_id,
                'consultation_route_id' => $session->consultation_route_id,
                'patient_id' => $case->patient_id,
                'source_type' => 'emergency_care',
                'source_id' => $case->id,
                'product_id' => $product->id,
                'stock_location_id' => $location->id,
                'quantity_used' => $quantity,
                'is_billable' => (bool) $product->is_billable,
                'stock_movement_id' => $movement->id,
                'used_by' => $user->id,
                'used_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            if ($product->is_billable) {
                $invoiceItem = $this->billing->addProductToVisitInvoice(
                    visit: $case->visit,
                    product: $product,
                    sourceType: InvoiceItem::SOURCE_EMERGENCY_CONSUMABLE,
                    sourceId: $usage->id,
                    quantity: $quantity,
                    departmentId: $location->department_id,
                    description: $product->name.' emergency consumable',
                );

                $usage->forceFill(['invoice_item_id' => $invoiceItem->id])->save();
            }

            $this->sessions->recordContribution($case, $user, 'Consumable Use');
            $this->timeline->record($case, 'CONSUMABLE_USED', 'Emergency consumable used', $product->name.' x '.$quantity, $usage, $user);

            // Audit trail: emergency consumable usage on the patient timeline.
            app(\App\Services\ActivityLogService::class)->log(
                \App\Enums\LogModule::STOCK,
                'CONSUMABLE_USED',
                $usage->toActivityContext() + [
                    'department_id' => $location->department_id,
                    'reason' => $data['notes'] ?? null,
                    'metadata' => [
                        'product' => $product->name,
                        'stock_movement_id' => $movement->id,
                        'emergency_session_id' => $session->id,
                        'context' => 'emergency',
                    ],
                ],
                $usage,
                $product->name.' x'.$quantity.' used (emergency)',
            );

            return $usage->fresh(['product', 'stockLocation', 'invoiceItem', 'user']);
        });
    }

    public function emergencyStockLocation(EmergencyCase $case): StockLocation
    {
        $location = StockLocation::query()
            ->active()
            ->where(function ($query) use ($case) {
                $query->where('type', 'emergency')
                    ->orWhere('department_id', $case->visit?->current_department_id)
                    ->orWhereHas('department', function ($department) {
                        $department->whereRaw('LOWER(name) LIKE ?', ['%emergency%'])
                            ->orWhereRaw('LOWER(code) IN (?, ?, ?, ?, ?)', ['er', 'ed', 'emr', 'emer', 'emergency'])
                            ->orWhere('type', 'emergency');
                    });
            })
            ->orderByRaw("CASE WHEN type = 'emergency' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        if (! $location) {
            throw ValidationException::withMessages([
                'stock_location_id' => __('stock.no_emergency_stock_location_configured'),
            ]);
        }

        return $location;
    }
}
