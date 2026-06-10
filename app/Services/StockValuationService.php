<?php

namespace App\Services;

use App\Enums\StockMovementDirection;
use App\Models\StockBalance;
use Illuminate\Support\Facades\DB;

/**
 * Weighted-average-cost (WAC) stock valuation.
 *
 *  - Incoming stock recomputes the location's average cost.
 *  - Outgoing stock is costed at the current average cost.
 *  - stock_balances carries quantity_on_hand, average_cost and total_value so the
 *    inventory valuation report is a simple read (no full ledger replay).
 *
 * Costs come from the movement's unit_cost (e.g. PO unit cost on receipt). When
 * no cost basis exists (legacy stock received before Phase 6), outgoing cost is
 * 0 and no COGS/expense is posted — documented behaviour.
 */
class StockValuationService
{
    public const METHOD = 'weighted_average';

    public function getCurrentAverageCost(int $productId, int $locationId): float
    {
        return (float) (StockBalance::query()
            ->where('product_id', $productId)
            ->where('stock_location_id', $locationId)
            ->value('average_cost') ?? 0);
    }

    public function getStockValue(int $productId, int $locationId): float
    {
        return (float) (StockBalance::query()
            ->where('product_id', $productId)
            ->where('stock_location_id', $locationId)
            ->value('total_value') ?? 0);
    }

    /**
     * Read-only cost for the movement record (does not touch the balance).
     *
     * @return array{unit_cost: float, total_cost: float, valuation_method: string}
     */
    public function quoteMovementCost(int $productId, int $locationId, StockMovementDirection $direction, float $qty, ?float $providedUnitCost): array
    {
        $avg = $this->getCurrentAverageCost($productId, $locationId);

        if ($direction === StockMovementDirection::IN) {
            $unit = ($providedUnitCost !== null && $providedUnitCost > 0) ? (float) $providedUnitCost : $avg;
        } else {
            // Outgoing is always costed at weighted-average cost.
            $unit = $avg;
        }

        $unit = round($unit, 4);

        return [
            'unit_cost' => $unit,
            'total_cost' => round($unit * $qty, 2),
            'valuation_method' => self::METHOD,
        ];
    }

    /**
     * Update the balance: quantity, average cost (WAC on incoming) and total value.
     */
    public function applyBalance(int $productId, int $locationId, StockMovementDirection $direction, float $qty, ?float $providedUnitCost): StockBalance
    {
        return DB::transaction(function () use ($productId, $locationId, $direction, $qty, $providedUnitCost) {
            $balance = StockBalance::firstOrCreate(
                ['product_id' => $productId, 'stock_location_id' => $locationId],
                ['drug_id' => null, 'quantity_on_hand' => 0, 'average_cost' => 0, 'total_value' => 0],
            );
            $balance = StockBalance::query()->where('id', $balance->id)->lockForUpdate()->first();

            $oldQty = (float) $balance->quantity_on_hand;
            $oldAvg = (float) $balance->average_cost;

            if ($direction === StockMovementDirection::IN) {
                $inCost = ($providedUnitCost !== null && $providedUnitCost > 0) ? (float) $providedUnitCost : $oldAvg;
                $newQty = $oldQty + $qty;
                $newAvg = $newQty > 0 ? round((($oldQty * $oldAvg) + ($qty * $inCost)) / $newQty, 4) : round($inCost, 4);
            } else {
                $newQty = $oldQty - $qty;
                $newAvg = $oldAvg; // average cost is unchanged on issue
            }

            $balance->quantity_on_hand = $newQty;
            $balance->average_cost = $newAvg;
            $balance->total_value = round(max(0.0, $newQty) * $newAvg, 2);
            $balance->last_movement_at = now();
            $balance->last_valued_at = now();
            $balance->save();

            return $balance;
        });
    }
}
