<?php

namespace App\Services;

use App\Enums\StockTransferStatus;
use App\Models\DrugStock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
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
                    $transfer->items()->create([
                        'drug_id' => $item['drug_id'],
                        'quantity' => $item['quantity'],
                        'batch_number' => $item['batch_number'] ?? null,
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
            $transfer->load('items.drug');

            foreach ($transfer->items as $item) {
                // Deduct from source location
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
                    'drug_id' => $item->drug_id,
                    'location' => $transfer->to_location->value,
                    'batch_number' => $item->batch_number ?? ($sourceStock->batch_number ?? 'TRF'),
                    'quantity' => $item->quantity,
                    'unit_cost' => $sourceStock->unit_cost ?? 0,
                    'selling_price' => $sourceStock->selling_price ?? ($item->drug->price ?? 0),
                    'expiry_date' => $sourceStock->expiry_date ?? now()->addYear(),
                    'supplier' => $sourceStock->supplier ?? 'Transfer',
                    'supplier_id' => $sourceStock->supplier_id,
                    'received_date' => now(),
                    'received_by' => Auth::id(),
                ]);
            }

            $transfer->update(['status' => StockTransferStatus::COMPLETED]);
        });
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
     * Deduct stock from a location (FEFO — First Expiry First Out).
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
     */
    public function getAvailableStock(int $drugId, string $location = 'store'): int
    {
        return (int) DrugStock::where('drug_id', $drugId)
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
