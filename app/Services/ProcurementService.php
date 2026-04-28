<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
use App\Models\DrugStock;
use App\Models\InvestigationItemStock;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProcurementService
{
    /**
     * List purchase orders with filters.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return PurchaseOrder::with(['supplier', 'createdByUser'])
            ->withCount('items')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->byStatus($s))
            ->when($filters['supplier_id'] ?? null, fn ($q, $s) => $q->bySupplier($s))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('order_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('order_date', '<=', $d))
            ->latest('order_date')
            ->paginate(15);
    }

    /**
     * Create a new purchase order with items.
     */
    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePONumber(),
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => PurchaseOrderStatus::DRAFT,
                'created_by' => Auth::id(),
                'total_amount' => 0,
            ]);

            if (! empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $totalCost = $item['quantity_ordered'] * $item['unit_cost'];
                    $itemType  = isset($item['investigation_item_id']) ? 'investigation' : 'drug';
                    $po->items()->create([
                        'drug_id'               => $item['drug_id'] ?? null,
                        'investigation_item_id' => $item['investigation_item_id'] ?? null,
                        'item_type'             => $itemType,
                        'quantity_ordered'      => $item['quantity_ordered'],
                        'unit_cost'             => $item['unit_cost'],
                        'total_cost'            => $totalCost,
                    ]);
                }
                $po->recalculateTotal();
            }

            return $po;
        });
    }

    /**
     * Add item to purchase order.
     */
    public function addItem(PurchaseOrder $po, array $data): PurchaseOrderItem
    {
        $totalCost = $data['quantity_ordered'] * $data['unit_cost'];

        $itemType = isset($data['investigation_item_id']) ? 'investigation' : 'drug';

        $item = $po->items()->create([
            'drug_id'               => $data['drug_id'] ?? null,
            'investigation_item_id' => $data['investigation_item_id'] ?? null,
            'item_type'             => $itemType,
            'quantity_ordered'      => $data['quantity_ordered'],
            'unit_cost'             => $data['unit_cost'],
            'total_cost'            => $totalCost,
        ]);

        $po->recalculateTotal();

        return $item;
    }

    /**
     * Remove item from purchase order.
     */
    public function removeItem(PurchaseOrderItem $item): void
    {
        $po = $item->purchaseOrder;
        $item->delete();
        $po->recalculateTotal();
    }

    /**
     * Submit PO for approval.
     */
    public function submit(PurchaseOrder $po): void
    {
        if (! $po->status->canTransitionTo(PurchaseOrderStatus::SUBMITTED)) {
            throw new \InvalidArgumentException('Cannot submit this purchase order.');
        }

        if ($po->items()->count() === 0) {
            throw new \InvalidArgumentException('Cannot submit a purchase order with no items.');
        }

        $po->update(['status' => PurchaseOrderStatus::SUBMITTED]);
    }

    /**
     * Approve a PO.
     */
    public function approve(PurchaseOrder $po): void
    {
        if (! $po->status->canTransitionTo(PurchaseOrderStatus::APPROVED)) {
            throw new \InvalidArgumentException('Cannot approve this purchase order.');
        }

        $po->update([
            'status' => PurchaseOrderStatus::APPROVED,
            'approved_by' => Auth::id(),
        ]);
    }

    /**
     * Receive items against a PO (partial or full).
     */
    public function receiveItems(PurchaseOrder $po, array $receivedItems): void
    {
        if (! $po->is_receivable) {
            throw new \InvalidArgumentException('This purchase order cannot receive items.');
        }

        DB::transaction(function () use ($po, $receivedItems) {
            foreach ($receivedItems as $itemData) {
                $poItem = PurchaseOrderItem::findOrFail($itemData['item_id']);

                if ($poItem->purchase_order_id !== $po->id) {
                    continue;
                }

                $qtyToReceive = min(
                    (int) $itemData['quantity_received'],
                    $poItem->remaining_quantity
                );

                if ($qtyToReceive <= 0) {
                    continue;
                }

                // Update PO item received quantity
                $poItem->update([
                    'quantity_received' => $poItem->quantity_received + $qtyToReceive,
                    'batch_number'      => $itemData['batch_number'] ?? $poItem->batch_number,
                    'expiry_date'       => $itemData['expiry_date'] ?? $poItem->expiry_date,
                ]);

                if ($poItem->item_type === 'investigation') {
                    // Create investigation item stock entry
                    InvestigationItemStock::create([
                        'investigation_item_id' => $poItem->investigation_item_id,
                        'location'              => 'laboratory',
                        'batch_number'          => $itemData['batch_number'] ?? 'N/A',
                        'quantity'              => $qtyToReceive,
                        'unit_cost'             => $poItem->unit_cost,
                        'expiry_date'           => $itemData['expiry_date'] ?? null,
                        'supplier'              => $po->supplier->name,
                        'supplier_id'           => $po->supplier_id,
                        'received_date'         => now(),
                        'received_by'           => Auth::id(),
                        'reorder_level'         => $poItem->investigationItem->reorder_level ?? 10,
                    ]);
                } else {
                    // Create drug stock entry (received to store)
                    DrugStock::create([
                        'drug_id'       => $poItem->drug_id,
                        'location'      => 'store',
                        'batch_number'  => $itemData['batch_number'] ?? 'N/A',
                        'quantity'      => $qtyToReceive,
                        'unit_cost'     => $poItem->unit_cost,
                        'selling_price' => $poItem->drug->price ?? $poItem->unit_cost,
                        'expiry_date'   => $itemData['expiry_date'] ?? now()->addYear(),
                        'supplier'      => $po->supplier->name,
                        'supplier_id'   => $po->supplier_id,
                        'received_date' => now(),
                        'received_by'   => Auth::id(),
                    ]);
                }
            }

            // Determine new PO status
            $allReceived = $po->items()->get()->every(fn ($item) => $item->is_fully_received);
            $anyReceived = $po->items()->where('quantity_received', '>', 0)->exists();

            if ($allReceived) {
                $po->update([
                    'status' => PurchaseOrderStatus::RECEIVED,
                    'received_date' => now(),
                ]);
            } elseif ($anyReceived) {
                $po->update(['status' => PurchaseOrderStatus::PARTIALLY_RECEIVED]);
            }
        });
    }

    /**
     * Cancel a PO.
     */
    public function cancel(PurchaseOrder $po): void
    {
        if (! $po->status->canTransitionTo(PurchaseOrderStatus::CANCELLED)) {
            throw new \InvalidArgumentException('Cannot cancel this purchase order.');
        }

        $po->update(['status' => PurchaseOrderStatus::CANCELLED]);
    }

    /**
     * Get stats for dashboard.
     */
    public function getStats(): array
    {
        $stats = PurchaseOrder::query()
            ->selectRaw("status, COUNT(*) as count, SUM(total_amount) as total")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalValue = PurchaseOrder::whereIn('status', ['approved', 'partially_received'])
            ->sum('total_amount');

        return array_merge($stats, ['pending_value' => $totalValue]);
    }
}
