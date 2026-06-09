<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Enums\StockRequisitionStatus;
use App\Models\Department;
use App\Models\Product;
use App\Models\StockRequisition;
use App\Models\StockRequisitionItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class StockRequisitionService
{
    public function __construct(
        private ProductStockMovementService $stockMovements,
        private StockLocationService $stockLocations,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return StockRequisition::query()
            ->with(['department', 'requestedByUser'])
            ->withCount('items')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('requisition_number', 'like', "%{$search}%"))
            ->when($filters['department_id'] ?? null, fn ($query, $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('requested_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('requested_at', '<=', $date))
            ->when($filters['product_id'] ?? null, fn ($query, $productId) => $query->whereHas('items', fn ($iq) => $iq->where('product_id', $productId)))
            ->latest('requested_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function create(array $data): StockRequisition
    {
        return DB::transaction(function () use ($data) {
            $department = Department::query()->whereKey((int) $data['department_id'])->firstOrFail();
            $lineItems = collect($data['items'] ?? [])
                ->filter(fn ($item) => ! empty($item['product_id']) && (float) ($item['quantity_requested'] ?? 0) > 0)
                ->values();

            if ($lineItems->isEmpty()) {
                throw new InvalidArgumentException('Add at least one product to request.');
            }

            $requisition = StockRequisition::create([
                'requisition_number' => StockRequisition::generateRequisitionNumber(),
                'department_id' => $department->id,
                'requested_by' => Auth::id(),
                'requested_at' => now(),
                'status' => StockRequisitionStatus::SUBMITTED,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lineItems as $item) {
                $product = Product::query()
                    ->with('departments:id')
                    ->whereKey((int) $item['product_id'])
                    ->where('is_active', true)
                    ->firstOrFail();

                if (! $product->departments->contains('id', $department->id)) {
                    throw new RuntimeException("{$product->name} is not linked to {$department->name}.");
                }

                $requisition->items()->create([
                    'product_id' => $product->id,
                    'quantity_requested' => (float) $item['quantity_requested'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $loaded = $requisition->load(['department', 'items.product']);
            $this->logRequisition($loaded, 'STOCK_REQUISITION_CREATED', 'Stock requisition created: ' . $loaded->requisition_number);

            return $loaded;
        });
    }

    private function logRequisition(StockRequisition $requisition, string $event, string $description, array $extra = []): void
    {
        try {
            app(\App\Services\ActivityLogService::class)->log(
                \App\Enums\LogModule::STOCK,
                $event,
                array_filter(array_merge([
                    'stock_requisition_id' => $requisition->id,
                    'department_id' => $requisition->department_id,
                    'metadata' => ['requisition_number' => $requisition->requisition_number],
                    'source_type' => 'stock_requisition',
                    'source_id' => $requisition->id,
                ], $extra), fn ($v) => $v !== null),
                $requisition,
                $description,
            );
        } catch (\Throwable $e) {
            // Logging must never break a requisition action.
        }
    }

    public function approve(StockRequisition $requisition, array $approvedItems): StockRequisition
    {
        return DB::transaction(function () use ($requisition, $approvedItems) {
            $requisition = StockRequisition::query()->with('items')->whereKey($requisition->id)->lockForUpdate()->firstOrFail();
            if ($requisition->status !== StockRequisitionStatus::SUBMITTED) {
                throw new RuntimeException('Only submitted requisitions can be approved.');
            }

            $requestedTotal = 0.0;
            $approvedTotal = 0.0;
            foreach ($requisition->items as $item) {
                $requested = (float) $item->quantity_requested;
                $approved = (float) ($approvedItems[$item->id] ?? $requested);
                if ($approved < 0 || $approved > $requested) {
                    throw new InvalidArgumentException('Approved quantity cannot exceed requested quantity.');
                }
                $item->update(['quantity_approved' => $approved]);
                $requestedTotal += $requested;
                $approvedTotal += $approved;
            }

            if ($approvedTotal <= 0) {
                throw new InvalidArgumentException('Approve at least one requested quantity.');
            }

            $requisition->update([
                'status' => $approvedTotal < $requestedTotal ? StockRequisitionStatus::PARTIALLY_APPROVED : StockRequisitionStatus::APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $requisition->refresh();
            $this->logRequisition($requisition, 'STOCK_REQUISITION_APPROVED', 'Stock requisition approved: ' . $requisition->requisition_number);

            return $requisition;
        });
    }

    public function issue(StockRequisition $requisition): StockRequisition
    {
        return DB::transaction(function () use ($requisition) {
            $requisition = StockRequisition::query()->with('items.product')->whereKey($requisition->id)->lockForUpdate()->firstOrFail();
            if (! in_array($requisition->status, [StockRequisitionStatus::APPROVED, StockRequisitionStatus::PARTIALLY_APPROVED], true)) {
                throw new RuntimeException('Only approved requisitions can be issued.');
            }

            $mainStore = $this->stockLocations->getMainStoreLocation();
            $issuedAny = false;

            foreach ($requisition->items as $item) {
                $remaining = (float) $item->quantity_approved - (float) $item->quantity_issued;
                if ($remaining <= 0) {
                    continue;
                }

                $movement = $this->stockMovements->createMovement([
                    'product_id' => (int) $item->product_id,
                    'stock_location_id' => $mainStore->id,
                    'movement_type' => StockMovementType::TRANSFER_OUT,
                    'quantity' => $remaining,
                    'source_type' => StockRequisitionItem::class,
                    'source_id' => $item->id,
                    'notes' => 'Issue for requisition ' . $requisition->requisition_number,
                ]);

                $item->update([
                    'quantity_issued' => (float) $item->quantity_issued + $remaining,
                    'transfer_out_movement_id' => $movement->id,
                ]);
                $issuedAny = true;
            }

            if (! $issuedAny) {
                throw new RuntimeException('No approved quantities remain to issue.');
            }

            $requisition->update([
                'status' => StockRequisitionStatus::AWAITING_ACKNOWLEDGEMENT,
                'issued_by' => Auth::id(),
                'issued_at' => now(),
            ]);

            $requisition->refresh();
            $this->logRequisition($requisition, 'STOCK_REQUISITION_ISSUED', 'Stock requisition issued: ' . $requisition->requisition_number);

            return $requisition;
        });
    }

    public function acknowledge(StockRequisition $requisition): StockRequisition
    {
        return DB::transaction(function () use ($requisition) {
            $requisition = StockRequisition::query()->with(['department', 'items.product'])->whereKey($requisition->id)->lockForUpdate()->firstOrFail();
            if ($requisition->status !== StockRequisitionStatus::AWAITING_ACKNOWLEDGEMENT) {
                throw new RuntimeException('Only issued requisitions can be acknowledged.');
            }

            $departmentLocation = $this->stockLocations->requireDefaultLocationForDepartment($requisition->department);
            $acknowledgedAny = false;

            foreach ($requisition->items as $item) {
                $remaining = (float) $item->quantity_issued - (float) $item->quantity_acknowledged;
                if ($remaining <= 0) {
                    continue;
                }

                $movement = $this->stockMovements->createMovement([
                    'product_id' => (int) $item->product_id,
                    'stock_location_id' => $departmentLocation->id,
                    'movement_type' => StockMovementType::TRANSFER_IN,
                    'quantity' => $remaining,
                    'source_type' => StockRequisitionItem::class,
                    'source_id' => $item->id,
                    'notes' => 'Acknowledgement for requisition ' . $requisition->requisition_number,
                ]);

                $item->update([
                    'quantity_acknowledged' => (float) $item->quantity_acknowledged + $remaining,
                    'transfer_in_movement_id' => $movement->id,
                ]);
                $acknowledgedAny = true;
            }

            if (! $acknowledgedAny) {
                throw new RuntimeException('No issued quantities remain to acknowledge.');
            }

            $requisition->update([
                'status' => StockRequisitionStatus::COMPLETED,
                'acknowledged_by' => Auth::id(),
                'acknowledged_at' => now(),
            ]);

            $requisition->refresh();
            $this->logRequisition($requisition, 'STOCK_REQUISITION_RECEIVED', 'Stock requisition received: ' . $requisition->requisition_number);

            return $requisition;
        });
    }

    public function cancel(StockRequisition $requisition): StockRequisition
    {
        if ((float) $requisition->items()->sum('quantity_issued') > 0) {
            throw new RuntimeException('Issued requisitions cannot be cancelled.');
        }

        $requisition->update(['status' => StockRequisitionStatus::CANCELLED]);
        $requisition->refresh();

        $this->logRequisition($requisition, 'STOCK_REQUISITION_CANCELLED', 'Stock requisition cancelled: ' . $requisition->requisition_number, ['severity' => \App\Enums\LogSeverity::WARNING]);

        return $requisition;
    }
}
