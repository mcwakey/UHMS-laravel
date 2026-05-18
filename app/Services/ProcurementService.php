<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\DrugStock;
use App\Models\GoodsReceivedNote;
use App\Models\GoodsReceivedNoteItem;
use App\Models\InvestigationItemStock;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierLedgerEntry;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProcurementService
{
    public function __construct(
        private ?StockMovementService $stockMovements = null,
        private ?SupplierLedgerService $supplierLedger = null,
        private ?ProductStockMovementService $productStockMovements = null,
        private ?StockLocationService $stockLocations = null,
    ) {
        $this->stockMovements ??= app(StockMovementService::class);
        $this->supplierLedger ??= app(SupplierLedgerService::class);
        $this->productStockMovements ??= app(ProductStockMovementService::class);
        $this->stockLocations ??= app(StockLocationService::class);
    }

    /**
     * Resolve the item_type for a row of PO data.
     */
    private function resolveItemType(array $item): string
    {
        if (! empty($item['item_type'])) {
            return $item['item_type'];
        }
        if (! empty($item['investigation_item_id'])) {
            return 'investigation';
        }
        if (! empty($item['product_id'])) {
            return 'product';
        }
        return 'drug';
    }

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
                    $itemType  = $this->resolveItemType($item);
                    $po->items()->create([
                        'drug_id'               => $item['drug_id'] ?? null,
                        'investigation_item_id' => $item['investigation_item_id'] ?? null,
                        'product_id'            => $item['product_id'] ?? null,
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

        $itemType = $this->resolveItemType($data);

        $item = $po->items()->create([
            'drug_id'               => $data['drug_id'] ?? null,
            'investigation_item_id' => $data['investigation_item_id'] ?? null,
            'product_id'            => $data['product_id'] ?? null,
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
            $receivedValue = 0.0;
            $receivedQty   = 0;

            // GRN: Per the unified inventory rule (prompt.md §10-11), every PO receive
            // call produces a Goods Received Note that anchors the movements at Main Store.
            $mainStore = $this->stockLocations->getMainStoreLocation();
            $grn = GoodsReceivedNote::create([
                'grn_number'        => GoodsReceivedNote::generateGrnNumber(),
                'purchase_order_id' => $po->id,
                'supplier_id'       => $po->supplier_id,
                'received_date'     => now(),
                'received_by'       => Auth::id(),
                'notes'             => null,
            ]);
            $grnHasItems = false;

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

                $receivedValue += (float) $qtyToReceive * (float) $poItem->unit_cost;
                $receivedQty   += $qtyToReceive;

                // Update PO item received quantity
                $poItem->update([
                    'quantity_received' => $poItem->quantity_received + $qtyToReceive,
                    'batch_number'      => $itemData['batch_number'] ?? $poItem->batch_number,
                    'expiry_date'       => $itemData['expiry_date'] ?? $poItem->expiry_date,
                ]);

                $productMovementId = null;
                $drugMovementId    = null;
                $grnProductId      = null;

                if ($poItem->item_type === 'product') {
                    // GP-1: receive a generic Product into the unified product stock ledger.
                    $movement = $this->productStockMovements->createMovement([
                        'product_id'        => $poItem->product_id,
                        'stock_location_id' => $mainStore->id,
                        'movement_type'     => StockMovementType::PURCHASE_RECEIVED,
                        'quantity'          => $qtyToReceive,
                        'unit_cost'         => $poItem->unit_cost,
                        'batch_no'          => $itemData['batch_number'] ?? null,
                        'expiry_date'       => $itemData['expiry_date'] ?? null,
                        'source_type'       => PurchaseOrderItem::class,
                        'source_id'         => $poItem->id,
                        'notes'             => 'Received against PO ' . $po->po_number . ' (' . $grn->grn_number . ')',
                    ]);
                    $productMovementId = $movement->id;
                    $grnProductId      = $poItem->product_id;
                } elseif ($poItem->item_type === 'investigation') {
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

                    // Ledger: record a PURCHASE_RECEIVED IN movement at Main Store.
                    $drugMovement = $this->stockMovements->createMovement([
                        'drug_id'           => $poItem->drug_id,
                        'stock_location_id' => $mainStore->id,
                        'movement_type'     => StockMovementType::PURCHASE_RECEIVED,
                        'quantity'          => $qtyToReceive,
                        'unit_cost'         => $poItem->unit_cost,
                        'batch_no'          => $itemData['batch_number'] ?? null,
                        'expiry_date'       => $itemData['expiry_date'] ?? null,
                        'source_type'       => PurchaseOrderItem::class,
                        'source_id'         => $poItem->id,
                        'notes'             => 'Received against PO ' . $po->po_number . ' (' . $grn->grn_number . ')',
                    ]);
                    $drugMovementId = $drugMovement?->id;

                    // Phase 3: also write the unified product ledger when the drug is
                    // linked to a product. Failure is logged but does not block receiving.
                    $linkedProductId = $poItem->drug?->product_id;
                    if ($linkedProductId) {
                        try {
                            $productMovement = $this->productStockMovements->createMovement([
                                'product_id'        => $linkedProductId,
                                'stock_location_id' => $mainStore->id,
                                'movement_type'     => StockMovementType::PURCHASE_RECEIVED,
                                'quantity'          => $qtyToReceive,
                                'unit_cost'         => $poItem->unit_cost,
                                'batch_no'          => $itemData['batch_number'] ?? null,
                                'expiry_date'       => $itemData['expiry_date'] ?? null,
                                'source_type'       => PurchaseOrderItem::class,
                                'source_id'         => $poItem->id,
                                'notes'             => 'Received against PO ' . $po->po_number . ' (' . $grn->grn_number . ')',
                            ]);
                            $productMovementId = $productMovement->id;
                            $grnProductId      = $linkedProductId;
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('procurement.receive.product_ledger_failed', [
                                'po_item_id' => $poItem->id,
                                'drug_id'    => $poItem->drug_id,
                                'product_id' => $linkedProductId,
                                'error'      => $e->getMessage(),
                            ]);
                        }
                    }
                }

                GoodsReceivedNoteItem::create([
                    'goods_received_note_id'    => $grn->id,
                    'purchase_order_item_id'    => $poItem->id,
                    'product_id'                => $grnProductId,
                    'stock_location_id'         => $mainStore->id,
                    'quantity_received'         => $qtyToReceive,
                    'unit_cost'                 => $poItem->unit_cost,
                    'batch_no'                  => $itemData['batch_number'] ?? null,
                    'expiry_date'               => $itemData['expiry_date'] ?? null,
                    'product_stock_movement_id' => $productMovementId,
                    'stock_movement_id'         => $drugMovementId,
                ]);
                $grnHasItems = true;
            }

            // If nothing was received, drop the empty GRN so the audit trail stays clean.
            if (! $grnHasItems) {
                $grn->delete();
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

            // Supplier ledger: GOODS_RECEIVED creates a credit (facility now owes supplier).
            if ($receivedValue > 0 && $po->supplier) {
                $this->supplierLedger->recordEntry(
                    supplier: $po->supplier,
                    entryType: SupplierLedgerEntry::TYPE_GOODS_RECEIVED,
                    debit: 0,
                    credit: round($receivedValue, 2),
                    description: "Goods received against PO {$po->po_number} ({$receivedQty} unit" . ($receivedQty === 1 ? '' : 's') . ').',
                    sourceType: PurchaseOrder::class,
                    sourceId: $po->id,
                );
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
