<?php

namespace App\Services;

use App\Enums\StockMovementDirection;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockLocation;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
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

    /**
     * @param  iterable<int>  $productIds
     * @return Collection<int, float>
     */
    public function getQuantitiesForProductsAtLocation(iterable $productIds, StockLocation $location): Collection
    {
        $ids = collect($productIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return StockBalance::query()
            ->whereIn('product_id', $ids)
            ->where('stock_location_id', $location->id)
            ->select('product_id', DB::raw('SUM(quantity_on_hand) as total'))
            ->groupBy('product_id')
            ->pluck('total', 'product_id')
            ->map(fn ($quantity) => (float) $quantity);
    }

    /**
     * @param  iterable<int>  $productIds
     * @param  iterable<int>  $locationIds
     * @return Collection<string, float> keyed by "product_id:location_id"
     */
    public function getQuantityMatrix(iterable $productIds, iterable $locationIds): Collection
    {
        $products = collect($productIds)->filter()->unique()->values();
        $locations = collect($locationIds)->filter()->unique()->values();

        if ($products->isEmpty() || $locations->isEmpty()) {
            return collect();
        }

        return StockBalance::query()
            ->whereIn('product_id', $products)
            ->whereIn('stock_location_id', $locations)
            ->select('product_id', 'stock_location_id', DB::raw('SUM(quantity_on_hand) as total'))
            ->groupBy('product_id', 'stock_location_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->product_id.':'.$row->stock_location_id => (float) $row->total,
            ]);
    }

    public function stockStatus(float $quantity, ?Product $product = null, bool $stocked = true): array
    {
        if (! $stocked) {
            return ['label' => 'NOT STOCKED', 'class' => 'secondary'];
        }

        $lowThreshold = (float) ($product?->reorder_level ?? 0);
        $criticalThreshold = $lowThreshold > 0 ? max(1.0, $lowThreshold / 2) : 0.0;

        if ($quantity <= 0) {
            return ['label' => 'OUT', 'class' => 'danger'];
        }

        if ($criticalThreshold > 0 && $quantity <= $criticalThreshold) {
            return ['label' => 'CRITICAL', 'class' => 'danger'];
        }

        if ($lowThreshold > 0 && $quantity <= $lowThreshold) {
            return ['label' => 'LOW', 'class' => 'warning text-dark'];
        }

        return ['label' => 'OK', 'class' => 'success'];
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
