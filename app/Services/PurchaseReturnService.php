<?php

namespace App\Services;

use App\Enums\PurchaseReturnStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SupplierLedgerEntry;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class PurchaseReturnService
{
    public function __construct(
        private ProductStockMovementService $stockMovements,
        private SupplierLedgerService $supplierLedger,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return PurchaseReturn::query()
            ->with(['supplier', 'stockLocation', 'createdByUser'])
            ->withCount('items')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('return_number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['supplier_id'] ?? null, fn ($query, $supplierId) => $query->where('supplier_id', $supplierId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['product_id'] ?? null, fn ($query, $productId) => $query->whereHas('items', fn ($iq) => $iq->where('product_id', $productId)))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->where('return_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->where('return_date', '<=', $date))
            ->latest('return_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function create(array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($data) {
            $lineItems = collect($data['items'] ?? [])
                ->filter(fn ($item) => ! empty($item['product_id']) && (float) ($item['quantity'] ?? 0) > 0)
                ->values();

            if ($lineItems->isEmpty()) {
                throw new InvalidArgumentException('Add at least one product to return.');
            }

            $purchaseReturn = PurchaseReturn::create([
                'return_number' => PurchaseReturn::generateReturnNumber(),
                'supplier_id' => (int) $data['supplier_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'goods_received_note_id' => $data['goods_received_note_id'] ?? null,
                'stock_location_id' => (int) $data['stock_location_id'],
                'return_date' => $data['return_date'] ?? now()->toDateString(),
                'status' => PurchaseReturnStatus::DRAFT,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $total = 0.0;
            foreach ($lineItems as $item) {
                Product::query()->whereKey((int) $item['product_id'])->where('is_active', true)->firstOrFail();
                $quantity = (float) $item['quantity'];
                $unitCost = (float) ($item['unit_cost'] ?? 0);
                $lineTotal = round($quantity * $unitCost, 2);
                $total += $lineTotal;

                $purchaseReturn->items()->create([
                    'purchase_order_item_id' => $item['purchase_order_item_id'] ?? null,
                    'product_id' => (int) $item['product_id'],
                    'stock_location_id' => (int) ($item['stock_location_id'] ?? $data['stock_location_id']),
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineTotal,
                    'batch_no' => $item['batch_no'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);
            }

            $purchaseReturn->update(['total_amount' => $total]);

            $loaded = $purchaseReturn->load(['supplier', 'items.product', 'stockLocation']);
            $this->logReturn($loaded, 'PURCHASE_RETURN_CREATED', 'Purchase return created: ' . $loaded->return_number, \App\Enums\LogSeverity::INFO);

            return $loaded;
        });
    }

    private function logReturn(PurchaseReturn $return, string $event, string $description, \App\Enums\LogSeverity $severity): void
    {
        try {
            app(ActivityLogService::class)->log(
                \App\Enums\LogModule::PURCHASE_ORDERS,
                $event,
                [
                    'purchase_order_id' => $return->purchase_order_id,
                    'supplier_id' => $return->supplier_id,
                    'severity' => $severity,
                    'metadata' => [
                        'return_number' => $return->return_number,
                        'total_amount' => (float) $return->total_amount,
                    ],
                    'source_type' => 'purchase_return',
                    'source_id' => $return->id,
                ],
                $return,
                $description,
            );
        } catch (\Throwable $e) {
            // Logging must never break a purchase-return action.
        }
    }

    public function approve(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        if ($purchaseReturn->status !== PurchaseReturnStatus::DRAFT) {
            throw new RuntimeException('Only draft purchase returns can be approved.');
        }

        $purchaseReturn->update([
            'status' => PurchaseReturnStatus::APPROVED,
            'approved_by' => Auth::id(),
        ]);

        $purchaseReturn->refresh();
        $this->logReturn($purchaseReturn, 'PURCHASE_RETURN_APPROVED', 'Purchase return approved: ' . $purchaseReturn->return_number, \App\Enums\LogSeverity::WARNING);

        return $purchaseReturn;
    }

    public function post(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        return DB::transaction(function () use ($purchaseReturn) {
            $purchaseReturn = PurchaseReturn::query()
                ->with(['supplier', 'items.product'])
                ->whereKey($purchaseReturn->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($purchaseReturn->status !== PurchaseReturnStatus::APPROVED) {
                throw new RuntimeException('Only approved purchase returns can be posted.');
            }

            foreach ($purchaseReturn->items as $item) {
                if ($item->stock_movement_id) {
                    continue;
                }

                $movement = $this->stockMovements->createMovement([
                    'product_id' => (int) $item->product_id,
                    'stock_location_id' => (int) $item->stock_location_id,
                    'movement_type' => StockMovementType::RETURN_OUT,
                    'quantity' => (float) $item->quantity,
                    'unit_cost' => (float) $item->unit_cost,
                    'batch_no' => $item->batch_no,
                    'expiry_date' => $item->expiry_date,
                    'source_type' => PurchaseReturnItem::class,
                    'source_id' => $item->id,
                    'notes' => 'Purchase return ' . $purchaseReturn->return_number,
                ]);

                $item->update(['stock_movement_id' => $movement->id]);
            }

            if ((float) $purchaseReturn->total_amount > 0) {
                $this->supplierLedger->recordEntry(
                    supplier: $purchaseReturn->supplier,
                    entryType: SupplierLedgerEntry::TYPE_RETURN_TO_SUPPLIER,
                    debit: (float) $purchaseReturn->total_amount,
                    credit: 0.0,
                    description: 'Purchase return ' . $purchaseReturn->return_number,
                    sourceType: PurchaseReturn::class,
                    sourceId: $purchaseReturn->id,
                );

                // Accounting Phase 5: post Dr Supplier Payables / Cr Inventory
                // and reduce open supplier payables (idempotent per return).
                $inventoryLines = [];
                $accounts = app(SupplierAccountingService::class);
                foreach ($purchaseReturn->items as $item) {
                    $amount = round((float) $item->quantity * (float) $item->unit_cost, 2);
                    if ($amount <= 0) {
                        continue;
                    }
                    $account = $accounts->inventoryAccountForProduct($item->product);
                    $inventoryLines[$account->id] ??= ['account' => $account, 'amount' => 0.0];
                    $inventoryLines[$account->id]['amount'] += $amount;
                }
                if (! empty($inventoryLines)) {
                    app(SupplierAccountingPostingService::class)->postPurchaseReturn($purchaseReturn, array_values($inventoryLines));
                    app(SupplierPayableService::class)->applyReturnFifo($purchaseReturn->supplier, (float) $purchaseReturn->total_amount);
                }
            }

            $purchaseReturn->update([
                'status' => PurchaseReturnStatus::POSTED,
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ]);

            $purchaseReturn->refresh();
            $this->logReturn($purchaseReturn, 'PURCHASE_RETURN_POSTED', 'Purchase return posted to stock + supplier ledger: ' . $purchaseReturn->return_number, \App\Enums\LogSeverity::WARNING);

            return $purchaseReturn;
        });
    }

    public function cancel(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        if ($purchaseReturn->status === PurchaseReturnStatus::POSTED) {
            throw new RuntimeException('Posted purchase returns cannot be cancelled.');
        }

        $purchaseReturn->update(['status' => PurchaseReturnStatus::CANCELLED]);
        $purchaseReturn->refresh();

        $this->logReturn($purchaseReturn, 'PURCHASE_RETURN_CANCELLED', 'Purchase return cancelled: ' . $purchaseReturn->return_number, \App\Enums\LogSeverity::WARNING);

        return $purchaseReturn;
    }
}
