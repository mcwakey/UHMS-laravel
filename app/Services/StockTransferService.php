<?php

namespace App\Services;

use App\Enums\StockTransferStatus;
use App\Enums\StockMovementType;
use App\Models\DrugStock;
use App\Models\InvestigationItemStock;
use App\Models\StockLocation;
use App\Models\StockTransfer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    /**
     * List transfers with filters.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return StockTransfer::with(['transferredByUser', 'approvedByUser'])
            ->withCount('items')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->byStatus($s))
            ->latest('transfer_date')
            ->paginate(15);
    }

    /**
     * Create a new stock transfer with items.
     */
    public function create(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $transfer = StockTransfer::create([
                'transfer_number' => StockTransfer::generateTransferNumber(),
                'from_location' => $data['from_location'] ?? 'store',
                'to_location' => $data['to_location'] ?? 'pharmacy',
                'transferred_by' => Auth::id(),
                'transfer_date' => $data['transfer_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'status' => StockTransferStatus::PENDING,
            ]);

            if (! empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $itemType = isset($item['investigation_item_id']) ? 'investigation' : 'drug';
                    $transfer->items()->create([
                        'drug_id'               => $item['drug_id'] ?? null,
                        'investigation_item_id' => $item['investigation_item_id'] ?? null,
                        'item_type'             => $itemType,
                        'quantity'              => $item['quantity'],
                        'batch_number'          => $item['batch_number'] ?? null,
                    ]);
                }
            }

            return $transfer;
        });
    }

    /**
     * Approve a transfer.
     */
    public function approve(StockTransfer $transfer): void
    {
        if (! $transfer->status->canTransitionTo(StockTransferStatus::APPROVED)) {
            throw new \InvalidArgumentException('Cannot approve this transfer.');
        }

        $transfer->update([
            'status' => StockTransferStatus::APPROVED,
            'approved_by' => Auth::id(),
        ]);
    }

    /**
     * Complete a transfer — moves stock between locations.
     */
    public function complete(StockTransfer $transfer): void
    {
        if (! $transfer->status->canTransitionTo(StockTransferStatus::COMPLETED)) {
            throw new \InvalidArgumentException('Cannot complete this transfer.');
        }

        DB::transaction(function () use ($transfer) {
            $transfer->load('items.drug', 'items.investigationItem');

            foreach ($transfer->items as $item) {
                if ($item->item_type === 'investigation') {
                    // Deduct investigation item stock
                    $this->deductInvestigationStock(
                        $item->investigation_item_id,
                        $transfer->from_location->value,
                        $item->quantity,
                        $item->batch_number,
                    );

                    // Add to destination
                    $sourceStock = InvestigationItemStock::where('investigation_item_id', $item->investigation_item_id)
                        ->atLocation($transfer->from_location->value)
                        ->when($item->batch_number, fn ($q, $b) => $q->where('batch_number', $b))
                        ->first();

                    InvestigationItemStock::create([
                        'investigation_item_id' => $item->investigation_item_id,
                        'location'              => $transfer->to_location->value,
                        'batch_number'          => $item->batch_number ?? ($sourceStock->batch_number ?? 'TRF'),
                        'quantity'              => $item->quantity,
                        'unit_cost'             => $sourceStock->unit_cost ?? 0,
                        'expiry_date'           => $sourceStock->expiry_date ?? null,
                        'supplier'              => $sourceStock->supplier ?? 'Transfer',
                        'supplier_id'           => $sourceStock->supplier_id,
                        'received_date'         => now(),
                        'received_by'           => Auth::id(),
                        'reorder_level'         => $sourceStock->reorder_level ?? $item->investigationItem->reorder_level ?? 10,
                    ]);
                } else {
                    // Deduct drug stock
                    $this->deductStock(
                        $item->drug_id,
                        $transfer->from_location->value,
                        $item->quantity,
                        $item->batch_number,
                    );

                    // Add to destination location
                    $sourceStock = DrugStock::where('drug_id', $item->drug_id)
                        ->atLocation($transfer->from_location->value)
                        ->when($item->batch_number, fn ($q, $b) => $q->where('batch_number', $b))
                        ->first();

                    DrugStock::create([
                        'drug_id'       => $item->drug_id,
                        'location'      => $transfer->to_location->value,
                        'batch_number'  => $item->batch_number ?? ($sourceStock->batch_number ?? 'TRF'),
                        'quantity'      => $item->quantity,
                        'unit_cost'     => $sourceStock->unit_cost ?? 0,
                        'selling_price' => $sourceStock->selling_price ?? ($item->drug->price ?? 0),
                        'expiry_date'   => $sourceStock->expiry_date ?? now()->addYear(),
                        'supplier'      => $sourceStock->supplier ?? 'Transfer',
                        'supplier_id'   => $sourceStock->supplier_id,
                        'received_date' => now(),
                        'received_by'   => Auth::id(),
                    ]);
                }
            }

            $transfer->update(['status' => StockTransferStatus::COMPLETED]);

            // Mirror to the unified movement ledger.
            $this->emitTransferMovements($transfer->fresh('items'));
        });
    }

    /**
     * Resolve a legacy location string (e.g. "store", "pharmacy") to a
     * stock_locations row. Falls back to the first row whose type matches.
     */
    protected function resolveStockLocation(string $name): ?StockLocation
    {
        $name = (string) $name;
        return StockLocation::query()
            ->where('name', $name)
            ->orWhere('type', $name)
            ->orderBy('id')
            ->first();
    }

    /**
     * Emit paired TRANSFER_OUT + TRANSFER_IN ledger movements for a transfer.
     */
    public function emitTransferMovements(StockTransfer $transfer): void
    {
        $fromKey = $transfer->from_location instanceof \BackedEnum
            ? $transfer->from_location->value
            : (string) $transfer->from_location;
        $toKey = $transfer->to_location instanceof \BackedEnum
            ? $transfer->to_location->value
            : (string) $transfer->to_location;

        $fromLoc = $this->resolveStockLocation($fromKey);
        $toLoc   = $this->resolveStockLocation($toKey);

        if (! $fromLoc || ! $toLoc) {
            return; // Locations not mapped — skip ledger writes silently.
        }

        $svc = app(StockMovementService::class);
        $productSvc = app(ProductStockMovementService::class);

        foreach ($transfer->items as $item) {
            if ($item->item_type !== 'drug' || ! $item->drug_id) {
                continue;
            }

            $shared = [
                'drug_id'     => $item->drug_id,
                'quantity'    => $item->quantity,
                'batch_no'    => $item->batch_number,
                'source_type' => StockTransfer::class,
                'source_id'   => $transfer->id,
                'notes'       => 'Stock transfer ' . $transfer->transfer_number,
            ];

            $svc->createMovement(array_merge($shared, [
                'stock_location_id' => $fromLoc->id,
                'movement_type'     => StockMovementType::TRANSFER_OUT,
                // Source-of-truth deduction (DrugStock) already validated above
                // by deductStock(); the ledger is a mirror so allow_negative is safe here.
                'allow_negative'    => true,
            ]));

            $svc->createMovement(array_merge($shared, [
                'stock_location_id' => $toLoc->id,
                'movement_type'     => StockMovementType::TRANSFER_IN,
            ]));

            // Phase 3: also write the unified product ledger when the drug is
            // linked to a product. Failures are logged but do not block the transfer.
            $linkedProductId = $item->drug?->product_id;
            if ($linkedProductId) {
                $productShared = [
                    'product_id'  => $linkedProductId,
                    'quantity'    => $item->quantity,
                    'batch_no'    => $item->batch_number,
                    'source_type' => StockTransfer::class,
                    'source_id'   => $transfer->id,
                    'notes'       => 'Stock transfer ' . $transfer->transfer_number,
                ];

                try {
                    $productSvc->createMovement(array_merge($productShared, [
                        'stock_location_id' => $fromLoc->id,
                        'movement_type'     => StockMovementType::TRANSFER_OUT,
                        'allow_negative'    => true,
                    ]));
                    $productSvc->createMovement(array_merge($productShared, [
                        'stock_location_id' => $toLoc->id,
                        'movement_type'     => StockMovementType::TRANSFER_IN,
                    ]));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('stock_transfer.product_ledger_failed', [
                        'transfer_id' => $transfer->id,
                        'drug_id'     => $item->drug_id,
                        'product_id'  => $linkedProductId,
                        'error'       => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Cancel a transfer.
     */
    public function cancel(StockTransfer $transfer): void
    {
        if (! $transfer->status->canTransitionTo(StockTransferStatus::CANCELLED)) {
            throw new \InvalidArgumentException('Cannot cancel this transfer.');
        }

        $transfer->update(['status' => StockTransferStatus::CANCELLED]);
    }

    /**
     * Deduct investigation item stock from a location (FEFO).
     */
    private function deductInvestigationStock(int $itemId, string $location, int $quantity, ?string $batchNumber = null): void
    {
        $stocks = InvestigationItemStock::where('investigation_item_id', $itemId)
            ->atLocation($location)
            ->where('quantity', '>', 0)
            ->when($batchNumber, fn ($q, $b) => $q->where('batch_number', $b))
            ->orderBy('expiry_date')
            ->get();

        $remaining = $quantity;

        foreach ($stocks as $stock) {
            if ($remaining <= 0) break;
            $deduct = min($remaining, $stock->quantity);
            $stock->update(['quantity' => $stock->quantity - $deduct]);
            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            throw new \InvalidArgumentException(
                "Insufficient investigation item stock ID {$itemId} at {$location}. Short by {$remaining} units."
            );
        }
    }

    /**
     * Deduct drug stock from a location (FEFO — First Expiry First Out).
     */
    private function deductStock(int $drugId, string $location, int $quantity, ?string $batchNumber = null): void
    {
        $stocks = DrugStock::where('drug_id', $drugId)
            ->atLocation($location)
            ->where('quantity', '>', 0)
            ->when($batchNumber, fn ($q, $b) => $q->where('batch_number', $b))
            ->orderBy('expiry_date')
            ->get();

        $remaining = $quantity;

        foreach ($stocks as $stock) {
            if ($remaining <= 0) break;

            $deduct = min($remaining, $stock->quantity);
            $stock->update(['quantity' => $stock->quantity - $deduct]);
            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            throw new \InvalidArgumentException(
                "Insufficient stock for drug ID {$drugId} at {$location}. Short by {$remaining} units."
            );
        }
    }

    /**
     * Get available stock at a location for a drug.
     * Reads from the new stock_balances source of truth, matched by location name OR type.
     */
    public function getAvailableStock(int $drugId, string $location = 'store'): int
    {
        return (int) \App\Models\StockBalance::query()
            ->where('drug_id', $drugId)
            ->whereHas('location', function ($q) use ($location) {
                $q->where('type', $location)->orWhere('name', $location);
            })
            ->sum('quantity_on_hand');
    }

    /**
     * Get available investigation item stock at a location.
     */
    public function getAvailableInvestigationStock(int $itemId, string $location = 'laboratory'): int
    {
        return (int) InvestigationItemStock::where('investigation_item_id', $itemId)
            ->atLocation($location)
            ->available()
            ->sum('quantity');
    }

    /**
     * Get stats for dashboard.
     */
    public function getStats(): array
    {
        return StockTransfer::query()
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }
}
