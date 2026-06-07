<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Enums\PrescriptionStatus;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Events\StockLow;
use App\Models\Department;
use App\Models\DispensingRecord;
use App\Models\Drug;
use App\Models\DrugCategory;
use App\Models\PharmacyBillingSelection;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use App\Models\StockLocation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PharmacyService
{
    public function __construct(
        private ?ProductService $products = null,
        private ?StockBalanceService $stockBalances = null,
        private ?StockLocationService $stockLocations = null,
        private ?PharmacyBillingSelectionService $billingSelections = null,
    ) {
        $this->products ??= app(ProductService::class);
        $this->stockBalances ??= app(StockBalanceService::class);
        $this->stockLocations ??= app(StockLocationService::class);
        $this->billingSelections ??= app(PharmacyBillingSelectionService::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Drug Catalog
    |--------------------------------------------------------------------------
    */

    public function getCategories(): Collection
    {
        return DrugCategory::withCount('drugs')->orderBy('name')->get();
    }

    public function getActiveCategories(): Collection
    {
        return DrugCategory::active()->with('activeDrugs')->orderBy('name')->get();
    }

    public function storeCategory(array $data): DrugCategory
    {
        return DrugCategory::create($data);
    }

    public function updateCategory(DrugCategory $category, array $data): DrugCategory
    {
        $category->update($data);

        return $category;
    }

    public function deleteCategory(DrugCategory $category): bool
    {
        if ($category->drugs()->exists()) {
            return false;
        }
        $category->delete();

        return true;
    }

    public function getDrugs(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $pharmacyDept = Department::query()
            ->where('type', DepartmentType::PHARMACY->value)
            ->first();

        $query = $pharmacyDept
            ? $this->products->queryProductsForDepartment($pharmacyDept, [ProductType::DRUG])
            : Product::query()->whereRaw('1 = 0');

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            });
        }

        $drugs = $query->orderBy('name')->paginate($perPage)->withQueryString();

        $pharmacyLocation = $pharmacyDept
            ? $this->stockLocations->getDefaultLocationForDepartment($pharmacyDept)
            : null;

        $mainLocation = null;
        try {
            $mainLocation = $this->stockLocations->getMainStoreLocation();
        } catch (\Throwable) {
            $mainLocation = null;
        }

        $productIds = $drugs->getCollection()->pluck('id')->all();
        $pharmacyQuantities = $pharmacyLocation
            ? $this->stockBalances->getQuantitiesForProductsAtLocation($productIds, $pharmacyLocation)
            : collect();
        $mainQuantities = $mainLocation
            ? $this->stockBalances->getQuantitiesForProductsAtLocation($productIds, $mainLocation)
            : collect();

        $drugs->getCollection()->transform(function (Product $product) use ($pharmacyQuantities, $mainQuantities) {
            $available = (float) ($pharmacyQuantities[$product->id] ?? 0);
            $mainStock = (float) ($mainQuantities[$product->id] ?? 0);

            $product->available_in_pharmacy = $available;
            $product->available_in_main_store = $mainStock;
            $product->pharmacy_stock_status = $this->stockBalances->stockStatus($available, $product);
            $product->main_stock_status = $this->stockBalances->stockStatus($mainStock, $product);
            $product->is_low_stock = in_array($product->pharmacy_stock_status['label'], ['LOW', 'CRITICAL', 'OUT'], true);

            return $product;
        });

        return $drugs;
    }

    public function storeDrug(array $data): Drug
    {
        return DB::transaction(function () use ($data) {
            $opening = (float) ($data['opening_stock'] ?? 0);
            $drug = Drug::create($data);

            if ($opening > 0) {
                $store = StockLocation::query()
                    ->where('name', 'Main Store')
                    ->orWhere('type', 'store')
                    ->orderBy('id')
                    ->first();

                if ($store) {
                    app(StockMovementService::class)->createMovement([
                        'drug_id' => $drug->id,
                        'stock_location_id' => $store->id,
                        'movement_type' => StockMovementType::OPENING_STOCK,
                        'quantity' => $opening,
                        'unit_cost' => $drug->price ?? null,
                        'notes' => 'Opening stock when drug was created.',
                    ]);
                }
            }

            return $drug;
        });
    }

    public function updateDrug(Drug $drug, array $data): Drug
    {
        $drug->update($data);

        return $drug;
    }

    public function toggleDrug(Drug $drug): Drug
    {
        $drug->update(['is_active' => ! $drug->is_active]);

        return $drug;
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Management
    |--------------------------------------------------------------------------
    */

    public function getStock(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Pharmacy stock is tracked in stock_balances at pharmacy-type locations.
        $pharmacyLocIds = StockLocation::where('type', 'pharmacy')->pluck('id');

        $query = \App\Models\StockBalance::with(['drug.category', 'product'])
            ->whereIn('stock_location_id', $pharmacyLocIds)
            ->where('quantity_on_hand', '>', 0);

        if (! empty($filters['search'])) {
            $query->whereHas('drug', function ($q) use ($filters) {
                $q->search($filters['search']);
            });
        }

        if (! empty($filters['drug_id'])) {
            $query->where('drug_id', $filters['drug_id']);
        }

        return $query->latest('last_movement_at')->paginate($perPage)->withQueryString();
    }

    /**
     * Receive stock into a pharmacy location via ProductStockMovementService.
     * Required keys: drug_id (or product_id), stock_location_id, quantity.
     * Optional: unit_cost, batch_no, expiry_date, notes.
     */
    public function addStock(array $data): \App\Models\StockBalance
    {
        return DB::transaction(function () use ($data) {
            $drug       = isset($data['drug_id']) ? Drug::find($data['drug_id']) : null;
            $productId  = $data['product_id'] ?? $drug?->product_id;
            $locationId = $data['stock_location_id'] ?? null;

            if (! $productId || ! $locationId) {
                throw new \InvalidArgumentException('product_id and stock_location_id are required to add stock.');
            }

            app(ProductStockMovementService::class)->createMovement([
                'drug_id'           => $drug?->id,
                'product_id'        => $productId,
                'stock_location_id' => $locationId,
                'movement_type'     => StockMovementType::PURCHASE_RECEIVED,
                'quantity'          => $data['quantity'],
                'unit_cost'         => $data['unit_cost'] ?? null,
                'batch_no'          => $data['batch_no'] ?? $data['batch_number'] ?? null,
                'expiry_date'       => $data['expiry_date'] ?? null,
                'notes'             => $data['notes'] ?? 'Stock received via pharmacy.',
            ]);

            return \App\Models\StockBalance::firstOrNew(
                ['product_id' => $productId, 'stock_location_id' => $locationId]
            );
        });
    }

    public function getStockAlerts(): array
    {
        $pharmacyLocIds = StockLocation::where('type', 'pharmacy')->pluck('id');

        $low_stock = \App\Models\StockBalance::with('drug')
            ->whereIn('stock_location_id', $pharmacyLocIds)
            ->where('quantity_on_hand', '>', 0)
            ->whereHas('drug', fn ($q) => $q->whereColumn('stock_balances.quantity_on_hand', '<=', 'drugs.reorder_level'))
            ->get();

        $expiring_soon = \App\Models\StockMovement::with('drug')
            ->whereIn('stock_location_id', $pharmacyLocIds)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->whereDate('expiry_date', '>', now())
            ->where('direction', \App\Enums\StockMovementDirection::IN)
            ->where('quantity', '>', 0)
            ->get();

        $expired = \App\Models\StockMovement::with('drug')
            ->whereIn('stock_location_id', $pharmacyLocIds)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now())
            ->where('direction', \App\Enums\StockMovementDirection::IN)
            ->where('quantity', '>', 0)
            ->get();

        return compact('low_stock', 'expiring_soon', 'expired');
    }

    /*
    |--------------------------------------------------------------------------
    | Dispensing
    |--------------------------------------------------------------------------
    */

    public function getPendingPrescriptions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Prescription::with(['patient', 'doctor', 'visit', 'items.drug', 'items.dispensingRecords'])
            ->whereIn('status', [
                PrescriptionStatus::PENDING->value,
                PrescriptionStatus::PARTIALLY_SELECTED->value,
                PrescriptionStatus::PARTIALLY_BILLED->value,
                PrescriptionStatus::BILLED->value,
                PrescriptionStatus::PARTIALLY_DISPENSED->value,
            ]);

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('prescription_number', 'like', "%{$term}%")
                    ->orWhereHas('patient', function ($pq) use ($term) {
                        $pq->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhere('patient_number', 'like', "%{$term}%");
                    });
            });
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function getDispensingDetails(Prescription $prescription): Prescription
    {
        $prescription->load([
            'patient',
            'doctor',
            'visit',
            'items.drug.product',
            'items.dispensingRecords.dispensedBy',
            'items.billingSelections.invoiceItem',
        ]);

        // Auto-resolve drug_id for items that have drug_name but no drug_id (backward compat)
        foreach ($prescription->items as $item) {
            if (! $item->drug_id && $item->drug_name) {
                $drug = Drug::where('name', $item->drug_name)->first();
                if ($drug) {
                    $item->updateQuietly(['drug_id' => $drug->id]);
                    $item->setRelation('drug', $drug->load('product'));
                }
            }
        }

        $pharmacyLocation = $this->billingSelections->pharmacyLocation();
        $mainLocation = null;
        try {
            $mainLocation = $this->stockLocations->getMainStoreLocation();
        } catch (\Throwable) {
            $mainLocation = null;
        }

        $productIds = $prescription->items
            ->map(fn (PrescriptionItem $item) => $item->drug?->product_id)
            ->filter()
            ->unique()
            ->values();

        $pharmacyQuantities = $this->stockBalances->getQuantitiesForProductsAtLocation($productIds, $pharmacyLocation);
        $mainQuantities = $mainLocation
            ? $this->stockBalances->getQuantitiesForProductsAtLocation($productIds, $mainLocation)
            : collect();

        $prescription->items->each(function (PrescriptionItem $item) use ($pharmacyQuantities, $mainQuantities) {
            $activeSelections = $item->billingSelections
                ->where('status', '!=', PharmacyBillingSelection::STATUS_CANCELLED);
            $billed = (float) $activeSelections->sum('billed_quantity');
            $dispensed = (float) $activeSelections->sum('dispensed_quantity');
            $prescribed = (float) ($item->quantity ?? 0);
            $product = $item->drug?->product;
            $productId = $product?->id;
            $pharmacyQty = $productId ? (float) ($pharmacyQuantities[$productId] ?? 0) : 0.0;
            $mainQty = $productId ? (float) ($mainQuantities[$productId] ?? 0) : 0.0;

            $item->billed_quantity = $billed;
            $item->dispensed_billed_quantity = $dispensed;
            $item->remaining_billed_to_dispense = max(0.0, $billed - $dispensed);
            $item->remaining_prescribed_to_bill = max(0.0, $prescribed - $billed);
            $item->pharmacy_available_quantity = $pharmacyQty;
            $item->main_store_quantity = $mainQty;
            $item->pharmacy_stock_status = $this->stockBalances->stockStatus($pharmacyQty, $product);
            $item->main_stock_status = $this->stockBalances->stockStatus($mainQty, $product);

            // Pay-before-dispense: billed-but-undispensed charges must be fully paid.
            $undispensed = $activeSelections->filter(fn ($s) => (float) $s->dispensed_quantity < (float) $s->billed_quantity);
            $item->is_settled = $undispensed->isNotEmpty()
                && $undispensed->every(fn ($s) => $s->invoiceItem && $s->invoiceItem->isPaid());
        });

        $prescription->setRelation(
            'dispensableItems',
            $prescription->items
                ->filter(fn (PrescriptionItem $item) => $item->remaining_billed_to_dispense > 0 && $item->drug?->product_id)
                ->values(),
        );

        return $prescription;
    }

    public function dispenseItem(PrescriptionItem $item, int $quantity, ?string $notes = null): DispensingRecord
    {
        return DB::transaction(function () use ($item, $quantity, $notes) {
            $remaining = $quantity;
            $prescription = $item->prescription;
            $lastRecord = null;

            // Find drug - try linked drug_id first, then fall back to drug_name lookup
            $drug = $item->drug_id ? Drug::find($item->drug_id) : null;

            if (! $drug && $item->drug_name) {
                $drug = Drug::where('name', $item->drug_name)->first();
                if ($drug) {
                    $item->updateQuietly(['drug_id' => $drug->id]);
                }
            }

            if (! $drug) {
                throw new \RuntimeException('No drug linked to this prescription item. Please link a drug first.');
            }

            $remainingBilled = $this->billingSelections->remainingBilledQuantityForItem($item);
            if ($remainingBilled <= 0) {
                throw new \RuntimeException('This prescription item has not been billed or has already been fully dispensed.');
            }

            // Pay-before-dispense: the bill must be settled before the drug is released.
            if (! $this->billingSelections->isItemSettled($item)) {
                throw new \RuntimeException('This item cannot be dispensed until its bill is settled (paid).');
            }

            if ($quantity > $remainingBilled) {
                throw new \RuntimeException("Cannot dispense {$quantity}; only {$remainingBilled} billed quantity remains.");
            }

            // ── Stock availability check (pharmacy locations only) ────────
            // Dispensing draws exclusively from stock_balances at pharmacy-type
            // locations. Use stock transfers to move stock from Main Store first.
            $pharmacyLocIds = StockLocation::where('type', 'pharmacy')->pluck('id');

            $productBalances = ($drug->product_id && $pharmacyLocIds->isNotEmpty())
                ? \App\Models\StockBalance::where('product_id', $drug->product_id)
                    ->whereIn('stock_location_id', $pharmacyLocIds)
                    ->where('quantity_on_hand', '>', 0)
                    ->orderByDesc('quantity_on_hand')
                    ->get()
                : collect();

            $totalAvailable = (float) $productBalances->sum('quantity_on_hand');

            if ($totalAvailable <= 0) {
                throw new \RuntimeException("No available stock for {$drug->display_name}.");
            }

            if ($totalAvailable < $quantity) {
                throw new \RuntimeException("Insufficient stock for {$drug->display_name}. Available: {$totalAvailable}, Requested: {$quantity}.");
            }

            // Deduct from pharmacy stock_balances via the movement ledger.
            // createMovement handles both the StockMovement record and balance update.
            $leftToDeduct = $quantity;
            $stockMovementIds = [];
            $dispenseLocationId = null;
            foreach ($productBalances as $balance) {
                if ($leftToDeduct <= 0) {
                    break;
                }
                $deduct = min($leftToDeduct, (float) $balance->quantity_on_hand);
                try {
                    $movement = app(ProductStockMovementService::class)->createMovement([
                        'product_id'        => $drug->product_id,
                        'stock_location_id' => $balance->stock_location_id,
                        'movement_type'     => StockMovementType::PHARMACY_DISPENSED,
                        'quantity'          => $deduct,
                        'source_type'       => PrescriptionItem::class,
                        'source_id'         => $item->id,
                        'allow_negative'    => false,
                        'notes'             => 'Dispensed for prescription '.($prescription->prescription_number ?? $prescription->id),
                    ]);
                    $stockMovementIds[] = $movement->id;
                    $dispenseLocationId = $balance->stock_location_id;
                } catch (\Throwable $e) {
                    Log::warning('pharmacy.dispense.product_ledger_failed', [
                        'drug_id'    => $drug->id,
                        'product_id' => $drug->product_id,
                        'error'      => $e->getMessage(),
                    ]);
                    throw $e; // re-throw so the DB transaction rolls back
                }
                $leftToDeduct -= $deduct;
            }

            $lastRecord = DispensingRecord::create([
                'prescription_id'      => $prescription->id,
                'prescription_item_id' => $item->id,
                'patient_id'           => $prescription->patient_id,
                'visit_id'             => $prescription->visit_id,
                'quantity_dispensed'   => $quantity,
                'dispensed_by'         => Auth::id(),
                'dispensed_at'         => now(),
                'notes'                => $notes,
            ]);

            $this->billingSelections->recordDispensed($item, $quantity, Auth::id());

            // Mark item as dispensed if fully dispensed
            $totalDispensed = $item->dispensingRecords()->sum('quantity_dispensed');
            if ($totalDispensed >= ($item->quantity ?? 0)) {
                $item->update(['is_dispensed' => true]);
            }

            // Update prescription status
            $this->updatePrescriptionStatus($prescription);

            if ($prescription->visit) {
                app(VisitPathwayService::class)->record($prescription->visit, 'PHARMACY_DISPENSED', [
                    'source' => $lastRecord,
                    'title' => 'Medication dispensed',
                    'description' => ($item->drug_name ?? 'Medication') . ' x ' . $quantity,
                ]);
            }

            // Pharmacy DISPENSING event (distinct from billing and MAR administration),
            // carrying stock-deduction context (references the ledger movement ids).
            $isPartial = $totalDispensed < (float) ($item->quantity ?? 0);
            $drugLabel = $item->drug_name ?: $drug->display_name;
            app(\App\Services\ActivityLogService::class)->log(
                \App\Enums\LogModule::PHARMACY,
                $isPartial ? 'PARTIAL_DISPENSE_COMPLETED' : 'DRUG_DISPENSED',
                $lastRecord->toActivityContext() + array_filter([
                    'product_id' => $drug->product_id,
                    'drug_id' => $drug->id,
                    'stock_location_id' => $dispenseLocationId,
                    'stock_movement_id' => end($stockMovementIds) ?: null,
                    'quantity' => $quantity,
                    'metadata' => [
                        'product' => $drugLabel,
                        'dispensed_quantity' => $quantity,
                        'stock_movement_ids' => $stockMovementIds,
                    ],
                ], fn ($v) => $v !== null),
                $lastRecord,
                ($isPartial ? 'Partial dispensing: ' : 'Drug dispensed: ') . $drugLabel . ' x ' . $quantity,
            );

            // Track dispensed quantity in the MAR system (non-critical — do not roll back dispense on failure)
            try {
                app(MedicationOrderService::class)->recordDispensedQuantity($item, $quantity);
            } catch (\Throwable $e) {
                Log::warning('pharmacy.dispense.mar_tracking_failed', [
                    'prescription_item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Check stock levels and fire alert if low
            $totalStock = (float) \App\Models\StockBalance::where('product_id', $drug->product_id)
                ->whereIn('stock_location_id', $pharmacyLocIds)
                ->sum('quantity_on_hand');
            $reorderLevel = (float) ($drug->reorder_level ?? 10);
            if ($totalStock <= $reorderLevel) {
                StockLow::dispatch($drug->display_name, (int) $totalStock, (int) $reorderLevel);
            }

            return $lastRecord;
        });
    }

    public function batchDispense(Prescription $prescription, array $items): Prescription
    {
        return DB::transaction(function () use ($prescription, $items) {
            foreach ($items as $itemId => $data) {
                if (empty($data['quantity']) || $data['quantity'] <= 0) {
                    continue;
                }

                $item = $prescription->items()->findOrFail($itemId);
                if ($item->is_dispensed) {
                    continue;
                }

                $this->dispenseItem($item, (int) $data['quantity'], $data['notes'] ?? null);
            }

            return $prescription->fresh([
                'items.drug',
                'items.dispensingRecords',
            ]);
        });
    }

    public function getDispensingHistory(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DispensingRecord::with([
            'prescription',
            'prescriptionItem.drug',
            'patient',
            'dispensedBy',
        ])->latest('dispensed_at');

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->whereHas('patient', function ($pq) use ($term) {
                    $pq->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('patient_number', 'like', "%{$term}%");
                })
                    ->orWhereHas('prescription', function ($pq) use ($term) {
                        $pq->where('prescription_number', 'like', "%{$term}%");
                    });
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('dispensed_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('dispensed_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function updatePrescriptionStatus(Prescription $prescription): void
    {
        $this->billingSelections->updatePrescriptionStatus($prescription);
    }

    public function getPharmacyStats(): array
    {
        return [
            'pending_prescriptions' => Prescription::where('status', PrescriptionStatus::PENDING->value)->count(),
            'billed_prescriptions' => Prescription::whereIn('status', [
                PrescriptionStatus::BILLED->value,
                PrescriptionStatus::PARTIALLY_BILLED->value,
            ])->count(),
            'partially_dispensed' => Prescription::where('status', PrescriptionStatus::PARTIALLY_DISPENSED->value)->count(),
            'dispensed_today' => DispensingRecord::whereDate('dispensed_at', today())->count(),
            'low_stock_count' => (function () {
                $ids = StockLocation::where('type', 'pharmacy')->pluck('id');
                return \App\Models\StockBalance::whereIn('stock_location_id', $ids)
                    ->where('quantity_on_hand', '>', 0)
                    ->whereHas('drug', fn ($q) => $q->whereColumn('stock_balances.quantity_on_hand', '<=', 'drugs.reorder_level'))
                    ->count();
            })(),
            'expiring_soon_count' => (function () {
                $ids = StockLocation::where('type', 'pharmacy')->pluck('id');
                return \App\Models\StockMovement::whereIn('stock_location_id', $ids)
                    ->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '<=', now()->addDays(30))
                    ->whereDate('expiry_date', '>', now())
                    ->where('direction', \App\Enums\StockMovementDirection::IN)
                    ->where('quantity', '>', 0)->count();
            })(),
            'total_drugs' => Drug::active()->count(),
        ];
    }

    public function searchDrugs(?string $term): Collection
    {
        if (! $term) {
            return collect();
        }

        return Drug::active()
            ->search($term)
            ->with('activeStocks')
            ->limit(20)
            ->get()
            ->map(function ($drug) {
                return [
                    'id' => $drug->id,
                    'name' => $drug->display_name,
                    'generic_name' => $drug->generic_name,
                    'dosage_form' => $drug->dosage_form,
                    'unit' => $drug->unit,
                    'price' => $drug->price,
                    'total_stock' => $drug->total_stock,
                ];
            });
    }
}
