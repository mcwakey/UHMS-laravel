<?php

namespace App\Services;

use App\Enums\StockMovementDirection;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockBalanceService
{
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
            /** @var StockBalance $balance */
            $balance = StockBalance::firstOrCreate(
                ['drug_id' => $drugId, 'stock_location_id' => $locationId],
                ['quantity_on_hand' => 0]
            );

            // Lock for update to avoid races
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
