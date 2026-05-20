<?php

namespace App\Services;

use App\Enums\StockMovementDirection;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockLocation;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockBalanceService
{
    /**
     * Unified-inventory accessor: returns the on-hand quantity for a given
     * Product at a given StockLocation from the canonical balance ledger.
     *
     * This is the only read path department/admin code should use for
     * "how much of X is currently sitting at location Y?".
     */
    public function getQuantityForProductAtLocation(Product $product, StockLocation $location): float
    {
        $balance = StockBalance::query()
            ->where('product_id', $product->id)
            ->where('stock_location_id', $location->id)
            ->first();

        return (float) ($balance?->quantity_on_hand ?? 0);
    }

    public function getCurrentProductStock(int $productId, int $locationId): float
    {
        $balance = StockBalance::query()
            ->where('product_id', $productId)
            ->where('stock_location_id', $locationId)
            ->first();

        return (float) ($balance?->quantity_on_hand ?? 0);
    }

    public function increaseProduct(int $productId, int $locationId, float $quantity): StockBalance
    {
        return $this->adjustProduct($productId, $locationId, abs($quantity));
    }

    public function decreaseProduct(int $productId, int $locationId, float $quantity): StockBalance
    {
        return $this->adjustProduct($productId, $locationId, -abs($quantity));
    }

    protected function adjustProduct(int $productId, int $locationId, float $delta): StockBalance
    {
        return DB::transaction(function () use ($productId, $locationId, $delta) {
            /** @var StockBalance $balance */
            $balance = StockBalance::firstOrCreate(
                ['product_id' => $productId, 'stock_location_id' => $locationId],
                ['drug_id' => null, 'quantity_on_hand' => 0]
            );

            $balance = StockBalance::query()
                ->where('id', $balance->id)
                ->lockForUpdate()
                ->first();

            $balance->quantity_on_hand = (float) $balance->quantity_on_hand + $delta;
            $balance->last_movement_at = now();
            $balance->save();

            return $balance;
        });
    }

    /**
     * Get current stock for a drug at a location (creating a row if needed).
     */
    public function getCurrentStock(int $drugId, int $locationId): float
    {
        $balance = StockBalance::query()
            ->where('drug_id', $drugId)
            ->where('stock_location_id', $locationId)
            ->first();

        return (float) ($balance?->quantity_on_hand ?? 0);
    }

    /**
     * Increase the balance for a drug+location by an absolute quantity.
     */
    public function increase(int $drugId, int $locationId, float $quantity): StockBalance
    {
        return $this->adjust($drugId, $locationId, abs($quantity));
    }

    /**
     * Decrease the balance for a drug+location by an absolute quantity.
     */
    public function decrease(int $drugId, int $locationId, float $quantity): StockBalance
    {
        return $this->adjust($drugId, $locationId, -abs($quantity));
    }

    /**
     * Apply a signed delta to the balance row, creating it if needed.
     */
    protected function adjust(int $drugId, int $locationId, float $delta): StockBalance
    {
        return DB::transaction(function () use ($drugId, $locationId, $delta) {
            $productId = \App\Models\Drug::query()->whereKey($drugId)->value('product_id');

            /** @var StockBalance $balance */
            $balance = StockBalance::firstOrCreate(
                ['drug_id' => $drugId, 'stock_location_id' => $locationId],
                ['quantity_on_hand' => 0, 'product_id' => $productId]
            );

            // Lock for update to avoid races
            $balance = StockBalance::query()
                ->where('id', $balance->id)
                ->lockForUpdate()
                ->first();

            if ($productId !== null && $balance->product_id === null) {
                $balance->product_id = $productId;
            }
            $balance->quantity_on_hand = (float) $balance->quantity_on_hand + $delta;
            $balance->last_movement_at = now();
            $balance->save();

            return $balance;
        });
    }

    /**
     * Rebuild a single drug+location balance from the ledger.
     */
    public function rebuildBalance(int $drugId, int $locationId): StockBalance
    {
        return DB::transaction(function () use ($drugId, $locationId) {
            $in = (float) StockMovement::query()
                ->where('drug_id', $drugId)
                ->where('stock_location_id', $locationId)
                ->where('direction', StockMovementDirection::IN->value)
                ->sum('quantity');

            $out = (float) StockMovement::query()
                ->where('drug_id', $drugId)
                ->where('stock_location_id', $locationId)
                ->where('direction', StockMovementDirection::OUT->value)
                ->sum('quantity');

            $lastMovementAt = StockMovement::query()
                ->where('drug_id', $drugId)
                ->where('stock_location_id', $locationId)
                ->max('movement_date');

            return StockBalance::updateOrCreate(
                ['drug_id' => $drugId, 'stock_location_id' => $locationId],
                [
                    'product_id'       => \App\Models\Drug::query()->whereKey($drugId)->value('product_id'),
                    'quantity_on_hand' => $in - $out,
                    'last_movement_at' => $lastMovementAt,
                ]
            );
        });
    }

    /**
     * Rebuild ALL balances from the ledger. Optionally scoped.
     */
    public function rebuildAllBalances(?int $drugId = null, ?int $locationId = null): int
    {
        $pairs = StockMovement::query()
            ->select('drug_id', 'stock_location_id')
            ->when($drugId, fn ($q) => $q->where('drug_id', $drugId))
            ->when($locationId, fn ($q) => $q->where('stock_location_id', $locationId))
            ->groupBy('drug_id', 'stock_location_id')
            ->get();

        foreach ($pairs as $pair) {
            $this->rebuildBalance((int) $pair->drug_id, (int) $pair->stock_location_id);
        }

        return $pairs->count();
    }
}
