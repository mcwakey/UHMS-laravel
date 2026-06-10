<?php

namespace App\Services;

use App\Enums\StockMovementDirection;
use App\Enums\StockMovementType;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Product-native facade for the canonical stock ledger.
 *
 * The historical class name is retained for compatibility, but it writes only
 * to stock_movements and stock_balances. It never writes product_stock_* rows.
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
    public function createMovement(array $data): StockMovement
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

            // Weighted-average cost: cost the movement, then maintain the balance's
            // quantity + average cost + total value (Phase 6).
            $valuation = app(StockValuationService::class);
            $providedUnitCost = isset($data['unit_cost']) ? (float) $data['unit_cost'] : null;
            $cost = $valuation->quoteMovementCost($productId, $locationId, $direction, $quantity, $providedUnitCost);

            $movement = StockMovement::create([
                'stock_batch_id'    => $data['stock_batch_id'] ?? null,
                'drug_id'           => $data['drug_id'] ?? null,
                'product_id'        => $productId,
                'stock_location_id' => $locationId,
                'movement_type'     => $type,
                'direction'         => $direction,
                'quantity'          => $quantity,
                'unit_cost'         => $cost['unit_cost'],
                'total_cost'        => $cost['total_cost'],
                'valuation_method'  => $cost['valuation_method'],
                'batch_no'          => $data['batch_no']    ?? null,
                'expiry_date'       => $data['expiry_date'] ?? null,
                'source_type'       => $data['source_type'] ?? null,
                'source_id'         => $data['source_id']   ?? null,
                'performed_by'      => $data['performed_by'] ?? Auth::id(),
                'movement_date'     => $data['movement_date'] ?? now(),
                'notes'             => $data['notes'] ?? null,
            ]);

            $valuation->applyBalance($productId, $locationId, $direction, $quantity, $providedUnitCost);

            // Inventory accounting (Phase 6): post Dr COGS/Expense/Adjustment / Cr Inventory
            // where applicable. Self-guarded (idempotent) and never breaks the movement.
            app(InventoryAccountingPostingService::class)->postForMovement($movement);

            return $movement;
        });
    }

    public function getCurrentStock(int $productId, int $locationId): float
    {
        $balance = StockBalance::query()
            ->where('product_id', $productId)
            ->where('stock_location_id', $locationId)
            ->first();
        return (float) ($balance?->quantity_on_hand ?? 0);
    }

    protected function adjustBalance(int $productId, int $locationId, float $delta): StockBalance
    {
        return DB::transaction(function () use ($productId, $locationId, $delta) {
            $balance = StockBalance::firstOrCreate(
                ['product_id' => $productId, 'stock_location_id' => $locationId],
                ['drug_id' => null, 'quantity_on_hand' => 0],
            );
            $balance = StockBalance::query()->where('id', $balance->id)->lockForUpdate()->first();
            $balance->quantity_on_hand = (float) $balance->quantity_on_hand + $delta;
            $balance->last_movement_at = now();
            $balance->save();
            return $balance;
        });
    }

    /**
     * Emit an opposite-direction movement that cancels an earlier one (e.g. when an invoice
     * line that consumed stock is voided). Linked back to the original via source.
     */
    public function reverseMovement(StockMovement $original, ?string $notes = null): StockMovement
    {
        $reverseType = $original->direction === StockMovementDirection::IN
            ? StockMovementType::REVERSAL_OUT
            : StockMovementType::REVERSAL_IN;

        return $this->createMovement([
            'product_id'        => $original->product_id,
            'stock_location_id' => $original->stock_location_id,
            'movement_type'     => $reverseType,
            'quantity'          => (float) $original->quantity,
            'unit_cost'         => $original->unit_cost,
            'batch_no'          => $original->batch_no,
            'expiry_date'       => $original->expiry_date,
            'source_type'       => $original->source_type,
            'source_id'         => $original->source_id,
            'notes'             => $notes ?? ('Reversal of movement #' . $original->id),
            // Reversals must always succeed so the ledger stays consistent even if the
            // current balance is below zero due to other concurrent activity.
            'allow_negative'    => true,
        ]);
    }

    /**
    * Rebuild StockBalance rows from the canonical movement ledger.
     * Optionally scope to a single product and/or location.
     *
     * @return int number of (product, location) pairs rebuilt
     */
    public function rebuildAllBalances(?int $productId = null, ?int $locationId = null): int
    {
        $pairs = StockMovement::query()
            ->select('product_id', 'stock_location_id')
            ->whereNotNull('product_id')
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->when($locationId, fn ($q) => $q->where('stock_location_id', $locationId))
            ->groupBy('product_id', 'stock_location_id')
            ->get();

        foreach ($pairs as $pair) {
            $this->rebuildBalance((int) $pair->product_id, (int) $pair->stock_location_id);
        }

        return $pairs->count();
    }

    public function rebuildBalance(int $productId, int $locationId): StockBalance
    {
        return DB::transaction(function () use ($productId, $locationId) {
            $in = (float) StockMovement::query()
                ->where('product_id', $productId)
                ->where('stock_location_id', $locationId)
                ->where('direction', StockMovementDirection::IN->value)
                ->sum('quantity');

            $out = (float) StockMovement::query()
                ->where('product_id', $productId)
                ->where('stock_location_id', $locationId)
                ->where('direction', StockMovementDirection::OUT->value)
                ->sum('quantity');

            $lastMovementAt = StockMovement::query()
                ->where('product_id', $productId)
                ->where('stock_location_id', $locationId)
                ->latest('movement_date')
                ->value('movement_date');

            return StockBalance::updateOrCreate(
                ['product_id' => $productId, 'stock_location_id' => $locationId],
                [
                    'drug_id'           => null,
                    'quantity_on_hand' => $in - $out,
                    'last_movement_at' => $lastMovementAt,
                ]
            );
        });
    }
}
