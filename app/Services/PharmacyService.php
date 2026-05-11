<?php

namespace App\Services;


use App\Enums\PrescriptionStatus;
use App\Events\StockLow;
use App\Models\DispensingRecord;
use App\Models\Drug;
use App\Models\DrugCategory;
use App\Models\DrugStock;
use App\Models\InvoiceItem;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
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
        $query = Drug::with('category')
            ->withCount('activeStocks');

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
        return Drug::create($data);
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
        $data['received_by'] = Auth::id();
        $data['received_date'] = $data['received_date'] ?? now()->toDateString();

        return DrugStock::create($data);
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
                        'unit_price'          => $unitPrice,
                        'total_price'         => $lineTotal,
                        'patient_payable'     => $lineTotal,
                        'paid_amount'         => 0,
                        'balance'             => $lineTotal,
                        'payment_status'      => 'unpaid',
                        'cash_price'          => $unitPrice,
                        'selected_price'      => $unitPrice,
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
