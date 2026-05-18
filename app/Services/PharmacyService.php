<?php

namespace App\Services;


use App\Enums\PrescriptionStatus;
use App\Enums\StockMovementType;
use App\Events\StockLow;
use App\Models\DispensingRecord;
use App\Models\Drug;
use App\Models\DrugCategory;
use App\Models\DrugStock;
use App\Models\InvoiceItem;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\StockLocation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PharmacyService
{
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
        // Drugs are the Pharmacy-facing **filtered view** of products linked to
        // the Pharmacy department with product_type=DRUG. We only surface drug
        // catalogue rows that have a backing product on that pivot — anything
        // else is legacy data that pre-dates the unified inventory.
        $pharmacyDept = \App\Models\Department::where('type', 'pharmacy')->first();

        $query = Drug::with(['category', 'product'])
            ->whereNotNull('product_id')
            ->when($pharmacyDept, function ($q) use ($pharmacyDept) {
                $q->whereHas('product.departments', fn ($dq) => $dq->where('departments.id', $pharmacyDept->id));
            });

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
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
                        'drug_id'           => $drug->id,
                        'stock_location_id' => $store->id,
                        'movement_type'     => StockMovementType::OPENING_STOCK,
                        'quantity'          => $opening,
                        'unit_cost'         => $drug->price ?? null,
                        'notes'             => 'Opening stock when drug was created.',
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
        $drug->update(['is_active' => !$drug->is_active]);
        return $drug;
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Management
    |--------------------------------------------------------------------------
    */

    public function getStock(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DrugStock::with(['drug.category', 'receivedBy']);

        if (!empty($filters['search'])) {
            $query->whereHas('drug', function ($q) use ($filters) {
                $q->search($filters['search']);
            });
        }

        if (!empty($filters['status'])) {
            match ($filters['status']) {
                'low' => $query->lowStock(),
                'expiring' => $query->expiringSoon(),
                'expired' => $query->expired(),
                'available' => $query->available(),
                default => null,
            };
        }

        if (!empty($filters['drug_id'])) {
            $query->where('drug_id', $filters['drug_id']);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function addStock(array $data): DrugStock
    {
        return DB::transaction(function () use ($data) {
            $data['received_by']   = Auth::id();
            $data['received_date'] = $data['received_date'] ?? now()->toDateString();

            $stock = DrugStock::create($data);

            // Phase 3: post a PURCHASE_RECEIVED movement to both ledgers so the
            // on-hand balance stays in sync with DrugStock. Skipped silently
            // when the location string does not resolve to a StockLocation row.
            $locationName = $data['location'] ?? null;
            $location = $locationName
                ? StockLocation::query()
                    ->where('name', $locationName)
                    ->orWhere('type', $locationName)
                    ->orderBy('id')
                    ->first()
                : null;

            if ($location) {
                $shared = [
                    'drug_id'           => $stock->drug_id,
                    'stock_location_id' => $location->id,
                    'movement_type'     => StockMovementType::PURCHASE_RECEIVED,
                    'quantity'          => $stock->quantity,
                    'unit_cost'         => $stock->unit_cost,
                    'batch_no'          => $stock->batch_number,
                    'expiry_date'       => $stock->expiry_date,
                    'source_type'       => DrugStock::class,
                    'source_id'         => $stock->id,
                    'notes'             => 'Stock added manually via pharmacy.',
                ];

                try {
                    app(StockMovementService::class)->createMovement($shared);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('pharmacy.add_stock.drug_ledger_failed', [
                        'drug_stock_id' => $stock->id,
                        'error'         => $e->getMessage(),
                    ]);
                }

                $linkedProductId = $stock->drug?->product_id;
                if ($linkedProductId) {
                    try {
                        app(ProductStockMovementService::class)->createMovement(array_merge($shared, [
                            'product_id' => $linkedProductId,
                        ]));
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('pharmacy.add_stock.product_ledger_failed', [
                            'drug_stock_id' => $stock->id,
                            'product_id'    => $linkedProductId,
                            'error'         => $e->getMessage(),
                        ]);
                    }
                }
            }

            return $stock;
        });
    }

    public function updateStock(DrugStock $stock, array $data): DrugStock
    {
        $stock->update($data);
        return $stock;
    }

    public function getStockAlerts(): array
    {
        return [
            'low_stock' => DrugStock::lowStock()->with('drug')->get(),
            'expiring_soon' => DrugStock::expiringSoon()->with('drug')->get(),
            'expired' => DrugStock::expired()->where('quantity', '>', 0)->with('drug')->get(),
        ];
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
                PrescriptionStatus::PARTIALLY_DISPENSED->value,
            ]);

        if (!empty($filters['search'])) {
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
            'items.drug.activeStocks',
            'items.dispensingRecords.drugStock',
            'items.dispensingRecords.dispensedBy',
        ]);

        // Auto-resolve drug_id for items that have drug_name but no drug_id (backward compat)
        foreach ($prescription->items as $item) {
            if (!$item->drug_id && $item->drug_name) {
                $drug = Drug::where('name', $item->drug_name)->first();
                if ($drug) {
                    $item->updateQuietly(['drug_id' => $drug->id]);
                    $item->setRelation('drug', $drug->load('activeStocks'));
                }
            }
        }

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

            if (!$drug && $item->drug_name) {
                $drug = Drug::where('name', $item->drug_name)->first();
                if ($drug) {
                    $item->updateQuietly(['drug_id' => $drug->id]);
                }
            }

            if (!$drug) {
                throw new \RuntimeException("No drug linked to this prescription item. Please link a drug first.");
            }

            // Get available stock batches (FEFO - First Expiry, First Out)
            $stocks = $drug->activeStocks()->get();

            if ($stocks->isEmpty()) {
                throw new \RuntimeException("No available stock for {$drug->display_name}.");
            }

            $totalAvailable = $stocks->sum('quantity');
            if ($totalAvailable < $quantity) {
                throw new \RuntimeException("Insufficient stock for {$drug->display_name}. Available: {$totalAvailable}, Requested: {$quantity}.");
            }

            foreach ($stocks as $stock) {
                if ($remaining <= 0) break;

                $deduct = min($remaining, $stock->quantity);

                $lastRecord = DispensingRecord::create([
                    'prescription_id' => $prescription->id,
                    'prescription_item_id' => $item->id,
                    'drug_stock_id' => $stock->id,
                    'patient_id' => $prescription->patient_id,
                    'visit_id' => $prescription->visit_id,
                    'quantity_dispensed' => $deduct,
                    'dispensed_by' => Auth::id(),
                    'dispensed_at' => now(),
                    'notes' => $notes,
                ]);

                $stock->decrement('quantity', $deduct);
                $remaining -= $deduct;

                // Ledger: PHARMACY_DISPENSED OUT movement.
                $pharmacyLocation = StockLocation::query()
                    ->where('name', 'Pharmacy')
                    ->orWhere('type', 'pharmacy')
                    ->orderBy('id')
                    ->first();
                if ($pharmacyLocation) {
                    app(StockMovementService::class)->createMovement([
                        'drug_id'           => $drug->id,
                        'stock_location_id' => $pharmacyLocation->id,
                        'movement_type'     => StockMovementType::PHARMACY_DISPENSED,
                        'quantity'          => $deduct,
                        'unit_cost'         => $stock->unit_cost ?? null,
                        'batch_no'          => $stock->batch_number ?? null,
                        'expiry_date'       => $stock->expiry_date ?? null,
                        'source_type'       => PrescriptionItem::class,
                        'source_id'         => $item->id,
                        'allow_negative'    => true, // legacy drug_stock is the SoT for now
                        'notes'             => 'Dispensed for prescription ' . ($prescription->prescription_number ?? $prescription->id),
                    ]);

                    // Phase 2: also write the unified product ledger so the system
                    // converges on a single source of truth. Skipped if the drug
                    // has not been linked to a product yet (run inventory:link-drugs-to-products).
                    if ($drug->product_id) {
                        try {
                            app(ProductStockMovementService::class)->createMovement([
                                'product_id'        => $drug->product_id,
                                'stock_location_id' => $pharmacyLocation->id,
                                'movement_type'     => StockMovementType::PHARMACY_DISPENSED,
                                'quantity'          => $deduct,
                                'unit_cost'         => $stock->unit_cost ?? null,
                                'batch_no'          => $stock->batch_number ?? null,
                                'expiry_date'       => $stock->expiry_date ?? null,
                                'source_type'       => PrescriptionItem::class,
                                'source_id'         => $item->id,
                                // Phase 3: opening balances seeded via
                                // `inventory:seed-pharmacy-opening-stock`, so the
                                // product ledger now enforces non-negative balances.
                                'allow_negative'    => false,
                                'notes'             => 'Dispensed for prescription ' . ($prescription->prescription_number ?? $prescription->id),
                            ]);
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('pharmacy.dispense.product_ledger_failed', [
                                'drug_id'    => $drug->id,
                                'product_id' => $drug->product_id,
                                'error'      => $e->getMessage(),
                            ]);
                        }
                    } else {
                        \Illuminate\Support\Facades\Log::info('pharmacy.dispense.product_ledger_skipped', [
                            'drug_id' => $drug->id,
                            'reason'  => 'drugs.product_id is null — run `php artisan inventory:link-drugs-to-products`',
                        ]);
                    }
                }
            }

            // Mark item as dispensed if fully dispensed
            $totalDispensed = $item->dispensingRecords()->sum('quantity_dispensed');
            if ($totalDispensed >= ($item->quantity ?? 0)) {
                $item->update(['is_dispensed' => true]);
            }

            // Update prescription status
            $this->updatePrescriptionStatus($prescription);

            // ── BILLING ──────────────────────────────────────────────────
            // Add an invoice line on the visit's single invoice for the dispensed drugs
            if ($prescription->visit_id && $drug->price > 0) {
                $unitPrice = (float) $drug->price;
                $lineTotal = round($unitPrice * $quantity, 2);

                $visit = \App\Models\Visit::find($prescription->visit_id);
                if ($visit) {
                    $invoice = app(\App\Services\InvoiceService::class)->getOrCreateVisitInvoice($visit);

                    InvoiceItem::create([
                        'invoice_id'          => $invoice->id,
                        'visit_id'            => $visit->id,
                        'patient_id'          => $prescription->patient_id,
                        'department_id'       => null,
                        'source_type'         => 'prescription_item',
                        'source_id'           => $item->id,
                        'description'         => $drug->display_name . ' × ' . $quantity . ' ' . ($drug->unit ?? 'unit(s)'),
                        'quantity'            => $quantity,
                        'cash_price'          => $unitPrice,
                        'insurance_price'     => null,
                        'selected_price'      => $unitPrice,
                        'insurance_covered'   => 0,
                        'discount_amount'     => 0,
                        'patient_payable'     => $lineTotal,
                        'paid_amount'         => 0,
                        'balance'             => $lineTotal,
                        'payment_status'      => $lineTotal > 0 ? 'unpaid' : 'paid',
                        'total_price'         => $lineTotal,
                        'payer_type'          => 'cash',
                        'pricing_source'      => 'drug_price',
                        'created_by'          => Auth::id(),
                    ]);

                    app(\App\Services\InvoiceService::class)->recalculateTotals($invoice->fresh('items'));
                }
            }
            // ─────────────────────────────────────────────────────────────

            // Check stock levels and fire alert if low
            $totalStock = $drug->activeStocks()->sum('quantity');
            $reorderLevel = $drug->activeStocks()->max('reorder_level') ?? 10;
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
                if (empty($data['quantity']) || $data['quantity'] <= 0) continue;

                $item = $prescription->items()->findOrFail($itemId);
                if ($item->is_dispensed) continue;

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
            'prescriptionItem',
            'drugStock.drug',
            'patient',
            'dispensedBy',
        ])->latest('dispensed_at');

        if (!empty($filters['search'])) {
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

        if (!empty($filters['date_from'])) {
            $query->whereDate('dispensed_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
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
        $prescription->load('items');

        $total = $prescription->items->count();
        $dispensed = $prescription->items->where('is_dispensed', true)->count();

        if ($total > 0 && $dispensed === $total) {
            $prescription->update(['status' => PrescriptionStatus::DISPENSED->value]);
        } elseif ($dispensed > 0) {
            $prescription->update(['status' => PrescriptionStatus::PARTIALLY_DISPENSED->value]);
        }
    }

    public function getPharmacyStats(): array
    {
        return [
            'pending_prescriptions' => Prescription::where('status', PrescriptionStatus::PENDING->value)->count(),
            'partially_dispensed' => Prescription::where('status', PrescriptionStatus::PARTIALLY_DISPENSED->value)->count(),
            'dispensed_today' => DispensingRecord::whereDate('dispensed_at', today())->count(),
            'low_stock_count' => DrugStock::lowStock()->count(),
            'expiring_soon_count' => DrugStock::expiringSoon()->count(),
            'total_drugs' => Drug::active()->count(),
        ];
    }

    public function searchDrugs(?string $term): Collection
    {
        if (!$term) return collect();

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
