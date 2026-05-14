<?php

namespace App\Services;

use App\Enums\StockMovementDirection;
use App\Enums\StockMovementType;
use App\Models\ProductStockBalance;
use App\Models\ProductStockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Parallel of StockMovementService for the unified Product ledger.
 * Drug-based pharmacy stock continues to flow through StockMovementService.
 */
class ProductStockMovementService
{
    /**
     * Create a movement and update the cached balance.
     *
     * Required keys: product_id, stock_location_id, movement_type, quantity
     * Optional keys: direction, unit_cost, batch_no, expiry_date,
     *                source_type, source_id, performed_by, movement_date,
     *                notes, allow_negative
     */
    public function createMovement(array $data): ProductStockMovement
    {
        $productId  = (int) ($data['product_id'] ?? 0);
        $locationId = (int) ($data['stock_location_id'] ?? 0);
        $quantity   = (float) ($data['quantity'] ?? 0);

        if ($productId <= 0)  throw new InvalidArgumentException('product_id is required.');
        if ($locationId <= 0) throw new InvalidArgumentException('stock_location_id is required.');
        if ($quantity <= 0)   throw new InvalidArgumentException('quantity must be greater than zero.');

        $type = $data['movement_type'] instanceof StockMovementType
            ? $data['movement_type']
            : StockMovementType::tryFrom((string) ($data['movement_type'] ?? ''));
        if (! $type) throw new InvalidArgumentException('movement_type is invalid.');

        $direction = $type->direction();

        return DB::transaction(function () use ($data, $productId, $locationId, $quantity, $type, $direction) {
            if ($direction === StockMovementDirection::OUT) {
                $available = $this->getCurrentStock($productId, $locationId);
                $allowNegative = (bool) ($data['allow_negative'] ?? false);
                if (! $allowNegative && $available < $quantity) {
                    throw new RuntimeException(sprintf(
                        'Insufficient product stock at location %d for product %d: available %.4f, requested %.4f',
                        $locationId, $productId, $available, $quantity,
                    ));
                }
            }

            $movement = ProductStockMovement::create([
                'product_id'        => $productId,
                'stock_location_id' => $locationId,
                'movement_type'     => $type,
                'direction'         => $direction,
                'quantity'          => $quantity,
                'unit_cost'         => $data['unit_cost']   ?? null,
                'batch_no'          => $data['batch_no']    ?? null,
                'expiry_date'       => $data['expiry_date'] ?? null,
                'source_type'       => $data['source_type'] ?? null,
                'source_id'         => $data['source_id']   ?? null,
                'performed_by'      => $data['performed_by'] ?? Auth::id(),
                'movement_date'     => $data['movement_date'] ?? now(),
                'notes'             => $data['notes'] ?? null,
            ]);

            $this->adjustBalance($productId, $locationId, $direction === StockMovementDirection::IN ? $quantity : -$quantity);

            return $movement;
        });
    }

    public function getCurrentStock(int $productId, int $locationId): float
    {
        $balance = ProductStockBalance::query()
            ->where('product_id', $productId)
            ->where('stock_location_id', $locationId)
            ->first();
        return (float) ($balance?->quantity_on_hand ?? 0);
    }

    protected function adjustBalance(int $productId, int $locationId, float $delta): ProductStockBalance
    {
        return DB::transaction(function () use ($productId, $locationId, $delta) {
            $balance = ProductStockBalance::firstOrCreate(
                ['product_id' => $productId, 'stock_location_id' => $locationId],
                ['quantity_on_hand' => 0],
            );
            $balance = ProductStockBalance::query()->where('id', $balance->id)->lockForUpdate()->first();
            $balance->quantity_on_hand = (float) $balance->quantity_on_hand + $delta;
            $balance->last_movement_at = now();
            $balance->save();
            return $balance;
        });
    }
}
