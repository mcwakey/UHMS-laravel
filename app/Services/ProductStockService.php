<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockLocation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Higher-level orchestration for the product ledger:
 * receive (intake), adjustments, returns and transfers between locations.
 *
 * Delegates the actual movement creation to ProductStockMovementService
 * so the cached balances stay consistent.
 */
class ProductStockService
{
    public function __construct(private ProductStockMovementService $movements) {}

    /**
     * Receive stock at a location (opening stock or purchase).
     *
     * $data keys: product_id, stock_location_id, quantity,
     *             unit_cost?, batch_no?, expiry_date?, notes?, movement_type? (default PURCHASE_RECEIVED)
     */
    public function receive(array $data): StockMovement
    {
        $type = $data['movement_type'] ?? StockMovementType::PURCHASE_RECEIVED;
        if (is_string($type)) {
            $type = StockMovementType::tryFrom($type) ?? StockMovementType::PURCHASE_RECEIVED;
        }
        if ($type->direction()->value !== 'in') {
            throw new InvalidArgumentException('Receive requires an IN movement type.');
        }
        return $this->movements->createMovement([
            'product_id'        => (int) ($data['product_id'] ?? 0),
            'stock_location_id' => (int) ($data['stock_location_id'] ?? 0),
            'movement_type'     => $type,
            'quantity'          => (float) ($data['quantity'] ?? 0),
            'unit_cost'         => $data['unit_cost']   ?? null,
            'batch_no'          => $data['batch_no']    ?? null,
            'expiry_date'       => $data['expiry_date'] ?? null,
            'notes'             => $data['notes']       ?? null,
        ]);
    }

    /**
     * Adjust stock up or down (audit-style correction).
     *
     * $data keys: product_id, stock_location_id, quantity (signed delta),
     *             reason (notes), allow_negative?, type? (ADJUSTMENT_IN/OUT, DAMAGED, EXPIRED)
     */
    public function adjust(array $data): StockMovement
    {
        $delta = (float) ($data['quantity'] ?? 0);
        if ($delta == 0.0) {
            throw new InvalidArgumentException('Adjustment quantity must be non-zero.');
        }
        $type = $data['type'] ?? null;
        if ($type instanceof StockMovementType) {
            $movementType = $type;
        } elseif (is_string($type) && $type !== '') {
            $movementType = StockMovementType::tryFrom($type)
                ?? throw new InvalidArgumentException('Unknown adjustment type.');
        } else {
            $movementType = $delta >= 0
                ? StockMovementType::ADJUSTMENT_IN
                : StockMovementType::ADJUSTMENT_OUT;
        }
        return $this->movements->createMovement([
            'product_id'        => (int) ($data['product_id'] ?? 0),
            'stock_location_id' => (int) ($data['stock_location_id'] ?? 0),
            'movement_type'     => $movementType,
            'quantity'          => abs($delta),
            'notes'             => $data['reason']         ?? ($data['notes'] ?? null),
            'allow_negative'    => (bool) ($data['allow_negative'] ?? false),
        ]);
    }

    /**
     * Transfer stock between two locations.
     * Creates a TRANSFER_OUT at source and a TRANSFER_IN at destination
     * inside a single DB transaction. The two movements share the same
     * source_type / source_id so they can be matched in reports.
     *
     * $data keys: product_id, from_location_id, to_location_id, quantity,
     *             notes?, unit_cost?, batch_no?, expiry_date?
     *
    * @return array{out: StockMovement, in: StockMovement}
     */
    public function transfer(array $data): array
    {
        $productId = (int) ($data['product_id'] ?? 0);
        $fromId    = (int) ($data['from_location_id'] ?? 0);
        $toId      = (int) ($data['to_location_id'] ?? 0);
        $qty       = (float) ($data['quantity'] ?? 0);

        if ($productId <= 0)                throw new InvalidArgumentException('product_id is required.');
        if ($fromId <= 0 || $toId <= 0)     throw new InvalidArgumentException('Both source and destination are required.');
        if ($fromId === $toId)              throw new InvalidArgumentException('Source and destination must be different.');
        if ($qty <= 0)                      throw new InvalidArgumentException('quantity must be greater than zero.');

        $from = StockLocation::findOrFail($fromId);
        $to   = StockLocation::findOrFail($toId);

        // Enforce "Transfers must involve Main Store" unless inter-department flag set.
        $allowInterDept = (bool) config('inventory.allow_inter_department_transfers', false);
        if (! $allowInterDept && ! ($from->is_main || $to->is_main)) {
            throw new RuntimeException('Direct department-to-department transfers are disabled. One side must be Main Store.');
        }

        return DB::transaction(function () use ($productId, $fromId, $toId, $qty, $data) {
            $transferRef = (string) \Illuminate\Support\Str::uuid();

            $out = $this->movements->createMovement([
                'product_id'        => $productId,
                'stock_location_id' => $fromId,
                'movement_type'     => StockMovementType::TRANSFER_OUT,
                'quantity'          => $qty,
                'source_type'       => 'stock_transfer',
                'source_id'         => null,
                'notes'             => $data['notes'] ?? "Transfer ref {$transferRef}",
                'batch_no'          => $data['batch_no'] ?? null,
                'expiry_date'       => $data['expiry_date'] ?? null,
            ]);

            $in = $this->movements->createMovement([
                'product_id'        => $productId,
                'stock_location_id' => $toId,
                'movement_type'     => StockMovementType::TRANSFER_IN,
                'quantity'          => $qty,
                'unit_cost'         => $data['unit_cost'] ?? null,
                'source_type'       => 'stock_transfer',
                'source_id'         => $out->id, // link OUT id for back-reference
                'batch_no'          => $data['batch_no'] ?? null,
                'expiry_date'       => $data['expiry_date'] ?? null,
                'notes'             => "Transfer from #{$fromId} ref {$transferRef}",
            ]);

            // Link OUT back to IN so both ends can be navigated.
            $out->source_id = $in->id;
            $out->save();

            return ['out' => $out, 'in' => $in];
        });
    }

    /**
     * Compose per-location balance + product info for the stock dashboard.
     *
     * @return \Illuminate\Support\Collection<int,array{location:StockLocation, balances:\Illuminate\Database\Eloquent\Collection}>
     */
    public function balancesGroupedByLocation(): \Illuminate\Support\Collection
    {
        $locations = StockLocation::query()->where('is_active', true)->orderBy('name')->get();
        $balances  = StockBalance::with('product')
            ->whereNotNull('product_id')
            ->whereIn('stock_location_id', $locations->pluck('id'))
            ->get()
            ->groupBy('stock_location_id');
        return $locations->map(fn ($loc) => [
            'location' => $loc,
            'balances' => $balances->get($loc->id, collect()),
        ]);
    }
}
