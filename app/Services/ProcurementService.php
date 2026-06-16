<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\GoodsReceivedNote;
use App\Models\GoodsReceivedNoteItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierLedgerEntry;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProcurementService
{
    public function __construct(
        private ?SupplierLedgerService $supplierLedger = null,
        private ?ProductStockMovementService $productStockMovements = null,
        private ?StockLocationService $stockLocations = null,
    ) {
        $this->supplierLedger ??= app(SupplierLedgerService::class);
        $this->productStockMovements ??= app(ProductStockMovementService::class);
        $this->stockLocations ??= app(StockLocationService::class);
    }

    /**
     * Resolve the item_type for a row of PO data.
     */
    private function resolveItemType(array $item): string
    {
        return 'product';
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
            ->when($filters['product_id'] ?? null, fn ($q, $productId) => $q->whereHas('items', fn ($iq) => $iq->where('product_id', $productId)))
            ->latest('order_date')
            ->paginate(15)
            ->withQueryString();
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
                        'drug_id'               => null,
                        'investigation_item_id' => null,
                        'product_id'            => $item['product_id'] ?? null,
                        'item_type'             => $itemType,
                        'quantity_ordered'      => $item['quantity_ordered'],
                        'unit_cost'             => $item['unit_cost'],
                        'total_cost'            => $totalCost,
                    ]);
                }
                $po->recalculateTotal();
            }

            $this->logPo($po->fresh(), 'PURCHASE_ORDER_CREATED', 'Purchase order created: ' . $po->po_number);

            return $po;
        });
    }

    private function logPo(PurchaseOrder $po, string $event, string $description, array $metadata = [], array $extra = []): void
    {
        try {
            app(\App\Services\ActivityLogService::class)->log(
                \App\Enums\LogModule::PURCHASE_ORDERS,
                $event,
                array_filter(array_merge([
                    'purchase_order_id' => $po->id,
                    'supplier_id' => $po->supplier_id,
                    'metadata' => array_merge([
                        'po_number' => $po->po_number,
                        'total_amount' => (float) $po->total_amount,
                    ], $metadata),
                    'source_type' => 'purchase_order',
                    'source_id' => $po->id,
                ], $extra), fn ($v) => $v !== null),
                $po,
                $description,
            );
        } catch (\Throwable $e) {
            // Logging must never break a purchase-order action.
        }
    }

    /**
     * Add item to purchase order.
     */
    public function addItem(PurchaseOrder $po, array $data): PurchaseOrderItem
    {
        $totalCost = $data['quantity_ordered'] * $data['unit_cost'];

        $itemType = $this->resolveItemType($data);

        $item = $po->items()->create([
            'drug_id'               => null,
            'investigation_item_id' => null,
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

        $this->logPo($po->fresh(), 'PURCHASE_ORDER_SUBMITTED', 'Purchase order submitted: ' . $po->po_number);
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

        app(CommitmentService::class)->commitPurchaseOrder($po->fresh(), Auth::user());

        $this->logPo($po->fresh(), 'PURCHASE_ORDER_APPROVED', 'Purchase order approved: ' . $po->po_number);
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

                $productId = $this->resolveProductIdForPurchaseOrderItem($poItem);

                if (! $productId) {
                    throw new \InvalidArgumentException("Purchase order item #{$poItem->id} is not linked to a Product. Receive only product-backed items.");
                }

                $movement = $this->productStockMovements->createMovement([
                    'product_id'        => $productId,
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

                GoodsReceivedNoteItem::create([
                    'goods_received_note_id'    => $grn->id,
                    'purchase_order_item_id'    => $poItem->id,
                    'product_id'                => $productId,
                    'stock_location_id'         => $mainStore->id,
                    'quantity_received'         => $qtyToReceive,
                    'unit_cost'                 => $poItem->unit_cost,
                    'batch_no'                  => $itemData['batch_number'] ?? null,
                    'expiry_date'               => $itemData['expiry_date'] ?? null,
                    'product_stock_movement_id' => null,
                    'stock_movement_id'         => $movement->id,
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
            if ($grnHasItems && $receivedValue > 0 && $po->supplier) {
                $ledgerEntry = $this->supplierLedger->recordEntry(
                    supplier: $po->supplier,
                    entryType: SupplierLedgerEntry::TYPE_GOODS_RECEIVED,
                    debit: 0,
                    credit: round($receivedValue, 2),
                    description: "Goods received against PO {$po->po_number} ({$receivedQty} unit" . ($receivedQty === 1 ? '' : 's') . ').',
                    sourceType: PurchaseOrder::class,
                    sourceId: $po->id,
                );

                // Accounting Phase 5: recognise the supplier payable and post
                // Dr Inventory / Cr Supplier Payables (idempotent per GRN).
                app(SupplierPayableService::class)->recognizeFromGoodsReceipt($grn, $ledgerEntry->id);
            }

            if ($receivedValue > 0 && $po->budget_commitment_id) {
                app(CommitmentService::class)->release(
                    $po->budgetCommitment,
                    round($receivedValue, 2),
                    Auth::user(),
                    'Commitment released by goods receipt.',
                    GoodsReceivedNote::class,
                    $grnHasItems ? $grn->id : null,
                );
            }

            if ($receivedQty > 0) {
                $this->logPo(
                    $po->fresh(),
                    'PURCHASE_ORDER_RECEIVED',
                    'Goods received against PO ' . $po->po_number,
                    [
                        'received_quantity' => $receivedQty,
                        'received_value' => round($receivedValue, 2),
                        'grn_number' => $grnHasItems ? $grn->grn_number : null,
                    ],
                );
            }
        });
    }

    private function resolveProductIdForPurchaseOrderItem(PurchaseOrderItem $item): ?int
    {
        if ($item->product_id) {
            return (int) $item->product_id;
        }

        if ($item->drug_id) {
            return (int) ($item->drug?->product_id ?? 0) ?: null;
        }

        if ($item->investigation_item_id) {
            return (int) ($item->investigationItem?->product_id ?? 0) ?: null;
        }

        return null;
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

        if ($po->budget_commitment_id) {
            app(CommitmentService::class)->cancel($po->budgetCommitment, Auth::user(), 'Purchase order cancelled.');
        }

        $this->logPo($po->fresh(), 'PURCHASE_ORDER_CANCELLED', 'Purchase order cancelled: ' . $po->po_number, [], ['severity' => \App\Enums\LogSeverity::WARNING]);
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

        $valueRow = PurchaseOrderItem::query()
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->where('purchase_orders.status', '!=', PurchaseOrderStatus::CANCELLED->value)
            ->selectRaw('COALESCE(SUM(purchase_order_items.quantity_ordered * purchase_order_items.unit_cost),0) as ordered_value')
            ->selectRaw('COALESCE(SUM(purchase_order_items.quantity_received * purchase_order_items.unit_cost),0) as received_value')
            ->selectRaw('COALESCE(SUM((purchase_order_items.quantity_ordered - purchase_order_items.quantity_received) * purchase_order_items.unit_cost),0) as outstanding_value')
            ->first();

        return array_merge($stats, [
            'total_ordered_value' => (float) ($valueRow->ordered_value ?? 0),
            'total_received_value' => (float) ($valueRow->received_value ?? 0),
            'outstanding_value' => (float) ($valueRow->outstanding_value ?? 0),
            'pending_pos' => ($stats[PurchaseOrderStatus::SUBMITTED->value] ?? 0) + ($stats[PurchaseOrderStatus::APPROVED->value] ?? 0),
            'partially_received_pos' => $stats[PurchaseOrderStatus::PARTIALLY_RECEIVED->value] ?? 0,
        ]);
    }
}
