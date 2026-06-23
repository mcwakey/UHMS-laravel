<?php

namespace App\Http\Controllers\Admin\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use App\Models\DrugCategory;
use App\Models\DrugGenericName;
use App\Models\Product;
use App\Services\PharmacyService;
use Illuminate\Http\Request;

class DrugController extends Controller
{
    public function __construct(
        protected PharmacyService $pharmacyService,
    ) {}

    /**
     * Drug catalog management.
     */
    public function index(Request $request)
    {
        $drugs = $this->pharmacyService->getDrugs([
            'search' => $request->search,
        ]);

        return view('pharmacy.drugs', compact('drugs'));
    }

    /**
     * Store a new category.
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:drug_categories,name',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['is_active'] = true;
        $this->pharmacyService->storeCategory($validated);

        return back()->with('success', __('messages.drugs.category_created'));
    }

    /**
     * Update a category.
     */
    public function updateCategory(Request $request, DrugCategory $category)
    {
        $validated = $request->validate([
            'name' => "required|string|max:255|unique:drug_categories,name,{$category->id}",
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $this->pharmacyService->updateCategory($category, $validated);

        return back()->with('success', __('messages.drugs.category_updated'));
    }

    /**
     * Delete a category.
     */
    public function destroyCategory(DrugCategory $category)
    {
        if (!$this->pharmacyService->deleteCategory($category)) {
            return back()->with('error', __('messages.drugs.category_cannot_delete'));
        }

        return back()->with('success', __('messages.drugs.category_deleted'));
    }

    /**
     * Store a new drug.
     */
    public function store(Request $request)
    {
        abort(410, 'Pharmacy drug rows are no longer created here. Create a Product and link it to Pharmacy from Product Management.');

        $validated = $request->validate([
            'product_id'      => 'required|integer|exists:products,id',
            'category_id'     => 'required|exists:drug_categories,id',
            'generic_name_id' => 'nullable|integer|exists:drug_generic_names,id',
            'brand_name'      => 'nullable|string|max:255',
            'dosage_form'     => 'required|string|max:100',
            'strength'        => 'nullable|string|max:100',
            'unit'            => 'required|string|max:50',
            'price'           => 'required|numeric|min:0',
            'opening_stock'   => 'nullable|numeric|min:0',
            'reorder_level'   => 'nullable|numeric|min:0',
            'requires_prescription' => 'nullable|boolean',
            'description'     => 'nullable|string|max:1000',
        ]);

        // Drug name is always derived from the linked Product (single source of truth).
        $product = Product::find($validated['product_id']);
        $validated['name'] = $product?->name ?? 'Unnamed Drug';
        $validated['generic_name_id'] = filled($validated['generic_name_id'] ?? null)
            ? (int) $validated['generic_name_id']
            : null;

        // Mirror the generic-name text into the legacy `generic_name` column for backwards-compat lookups.
        if (!empty($validated['generic_name_id'])) {
            $validated['generic_name'] = optional(DrugGenericName::find($validated['generic_name_id']))->name;
        }

        $validated['requires_prescription'] = $request->boolean('requires_prescription', true);
        $validated['is_active'] = true;
        $this->pharmacyService->storeDrug($validated);

        return back()->with('success', __('messages.drugs.created'));
    }

    /**
     * Update a drug.
     */
    public function update(Request $request, Drug $drug)
    {
        abort(410, 'Pharmacy drug rows are no longer edited here. Edit the Product from Product Management.');

        $validated = $request->validate([
            'product_id'      => 'required|integer|exists:products,id',
            'category_id'     => 'required|exists:drug_categories,id',
            'generic_name_id' => 'nullable|integer|exists:drug_generic_names,id',
            'brand_name'      => 'nullable|string|max:255',
            'dosage_form'     => 'required|string|max:100',
            'strength'        => 'nullable|string|max:100',
            'unit'            => 'required|string|max:50',
            'price'           => 'required|numeric|min:0',
            'requires_prescription' => 'nullable|boolean',
            'is_active'       => 'nullable|boolean',
            'description'     => 'nullable|string|max:1000',
        ]);

        // Always mirror the linked product's name into the drug row.
        $product = Product::find($validated['product_id']);
        $validated['name'] = $product?->name ?? $drug->name;
        $validated['generic_name_id'] = filled($validated['generic_name_id'] ?? null)
            ? (int) $validated['generic_name_id']
            : null;

        if (!empty($validated['generic_name_id'])) {
            $validated['generic_name'] = optional(DrugGenericName::find($validated['generic_name_id']))->name;
        } else {
            $validated['generic_name'] = null;
        }

        $validated['requires_prescription'] = $request->boolean('requires_prescription', true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $this->pharmacyService->updateDrug($drug, $validated);

        return back()->with('success', __('messages.drugs.updated'));
    }

    /**
     * Toggle drug active/inactive.
     */
    public function toggle(Drug $drug)
    {
        abort(410, 'Pharmacy drug status is controlled by the linked Product.');

        $this->pharmacyService->toggleDrug($drug);
        return back()->with('success', __('messages.drugs.toggled'));
    }

    /**
     * Search drugs (AJAX for prescription linking).
     */
    public function search(Request $request)
    {
        return response()->json(
            $this->pharmacyService->searchDrugs($request->query('q'))
        );
    }

    /**
     * Drug history — all stock movements and dispensing events.
     */
    public function history(Drug $drug)
    {
        $drug->load(['category']);

        // All dispensing records for this drug, most recent first
        $dispensingRecords = \App\Models\DispensingRecord::query()
            ->whereHas('prescriptionItem', fn($q) => $q->where('drug_id', $drug->id))
            ->with(['patient', 'prescription', 'dispensedBy', 'prescriptionItem'])
            ->latest('dispensed_at')
            ->get();

        // Current stock balances per location
        $stockBalances = $drug->product_id
            ? \App\Models\StockBalance::query()
                ->where('product_id', $drug->product_id)
                ->with('location')
                ->orderByDesc('quantity_on_hand')
                ->get()
            : collect();

        // Receipt / inbound stock movements
        $stockReceipts = \App\Models\StockMovement::query()
            ->where(function ($q) use ($drug) {
                if ($drug->product_id) {
                    $q->where('product_id', $drug->product_id);
                } else {
                    $q->where('drug_id', $drug->id);
                }
            })
            ->whereIn('movement_type', ['purchase_received', 'opening_stock', 'adjustment_in', 'transfer_in'])
            ->with(['location', 'performedBy'])
            ->latest('movement_date')
            ->limit(100)
            ->get();

        // Summary stats
        $totalDispensed  = $dispensingRecords->sum('quantity_dispensed');
        $totalReceived   = $stockReceipts->sum(fn($m) => (float) $m->quantity);
        $revenue         = $dispensingRecords->sum(fn($r) => $r->quantity_dispensed * ($r->prescriptionItem?->drug?->price ?? 0));

        $stats = [
            'total_batches'   => $stockBalances->count(),
            'total_received'  => $totalReceived,
            'total_dispensed' => $totalDispensed,
            'current_stock'   => $drug->total_stock,
            'revenue'         => $revenue,
        ];

        return view('pharmacy.drug-history', compact('drug', 'dispensingRecords', 'stockBalances', 'stockReceipts', 'stats'));
    }
}
