<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\ConsumableUsage;
use App\Models\Product;
use App\Models\ServiceCatalog;
use App\Models\ServiceConsumable;
use App\Models\Visit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ConsumableUsageService
{
    public function __construct(
        private ProductStockMovementService $movements,
        private StockLocationResolver $locationResolver,
    ) {}

    /**
     * Map source_type → StockMovementType
     */
    public const SOURCE_MOVEMENT_TYPES = [
        'procedure_request'      => StockMovementType::PROCEDURE_CONSUMED,
        'investigation_result'   => StockMovementType::INVESTIGATION_CONSUMED,
        'ward_care'              => StockMovementType::WARD_CONSUMED,
    ];

    /**
     * Load default consumables for a service (joined with product).
     *
     * @return \Illuminate\Support\Collection<ServiceConsumable>
     */
    public function defaultsForService(int $serviceId)
    {
        return ServiceConsumable::with('product')
            ->where('service_id', $serviceId)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->get();
    }

    /**
     * Record actual usage for a source (procedure/investigation/ward).
     *
     * $items: array of [
     *     'product_id'  => int,
     *     'quantity'    => float (>0),
     *     'notes'       => ?string,
     * ]
     *
     * @return array{usages: ConsumableUsage[]}
     */
    public function recordUsageForSource(
        Visit $visit,
        ?ServiceCatalog $service,
        string $sourceType,
        int $sourceId,
        array $items,
        ?int $userId = null,
    ): array {
        if (! array_key_exists($sourceType, self::SOURCE_MOVEMENT_TYPES)) {
            throw new InvalidArgumentException("Unsupported consumable source_type: {$sourceType}");
        }
        if ($sourceId <= 0) {
            throw new InvalidArgumentException('source_id is required.');
        }

        // Resolve stock location from the service's department, falling back to the visit's department.
        $department = $service?->department ?? $visit->department ?? null;
        $location = $this->locationResolver->getDefaultLocationForDepartment($department);
        if (! $location) {
            throw new InvalidArgumentException('No stock location resolved for this consumption.');
        }

        $movementType = self::SOURCE_MOVEMENT_TYPES[$sourceType];
        $userId ??= Auth::id();
        $created = [];

        DB::transaction(function () use (&$created, $items, $visit, $service, $sourceType, $sourceId, $location, $movementType, $userId) {
            foreach ($items as $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $qty       = (float) ($item['quantity'] ?? 0);
                if ($productId <= 0 || $qty <= 0) continue;

                $product = Product::find($productId);
                if (! $product || ! $product->is_active) {
                    throw new InvalidArgumentException("Product {$productId} is not available.");
                }

                // Create stock-out movement (will validate available qty)
                $movement = $this->movements->createMovement([
                    'product_id'        => $productId,
                    'stock_location_id' => $location->id,
                    'movement_type'     => $movementType,
                    'quantity'          => $qty,
                    'source_type'       => $sourceType,
                    'source_id'         => $sourceId,
                    'performed_by'      => $userId,
                    'notes'             => $item['notes'] ?? null,
                ]);

                $usage = ConsumableUsage::create([
                    'visit_id'          => $visit?->id,
                    'patient_id'        => $visit?->patient_id,
                    'service_id'        => $service?->id,
                    'source_type'       => $sourceType,
                    'source_id'         => $sourceId,
                    'product_id'        => $productId,
                    'stock_location_id' => $location->id,
                    'quantity_used'     => $qty,
                    'stock_movement_id' => $movement->id,
                    'used_by'           => $userId,
                    'used_at'           => now(),
                    'notes'             => $item['notes'] ?? null,
                ]);

                $created[] = $usage;

                Log::info('consumable.usage.recorded', [
                    'usage_id'    => $usage->id,
                    'source'      => $sourceType . ':' . $sourceId,
                    'product_id'  => $productId,
                    'quantity'    => $qty,
                    'movement_id' => $movement->id,
                ]);
            }
        });

        return ['usages' => $created];
    }
}
