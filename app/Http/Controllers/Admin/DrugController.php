<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use App\Models\DrugCategory;
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
        $categories = $this->pharmacyService->getCategories();
        $drugs = $this->pharmacyService->getDrugs([
            'search' => $request->search,
            'category_id' => $request->category_id,
        ]);

        return view('pharmacy.drugs', compact('categories', 'drugs'));
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

        return back()->with('success', 'Category created successfully.');
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

        return back()->with('success', 'Category updated successfully.');
    }

    /**
     * Delete a category.
     */
    public function destroyCategory(DrugCategory $category)
    {
        if (!$this->pharmacyService->deleteCategory($category)) {
            return back()->with('error', 'Cannot delete category with existing drugs. Remove or reassign drugs first.');
        }

        return back()->with('success', 'Category deleted successfully.');
    }

    /**
     * Store a new drug.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:drug_categories,id',
            'name' => 'required|string|max:255|unique:drugs,name',
            'generic_name' => 'nullable|string|max:255',
            'brand_name' => 'nullable|string|max:255',
            'dosage_form' => 'required|string|max:100',
            'strength' => 'nullable|string|max:100',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'requires_prescription' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        $validated['requires_prescription'] = $request->boolean('requires_prescription', true);
        $validated['is_active'] = true;
        $this->pharmacyService->storeDrug($validated);

        return back()->with('success', 'Drug created successfully.');
    }

    /**
     * Update a drug.
     */
    public function update(Request $request, Drug $drug)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:drug_categories,id',
            'name' => "required|string|max:255|unique:drugs,name,{$drug->id}",
            'generic_name' => 'nullable|string|max:255',
            'brand_name' => 'nullable|string|max:255',
            'dosage_form' => 'required|string|max:100',
            'strength' => 'nullable|string|max:100',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'requires_prescription' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        $validated['requires_prescription'] = $request->boolean('requires_prescription', true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $this->pharmacyService->updateDrug($drug, $validated);

        return back()->with('success', 'Drug updated successfully.');
    }

    /**
     * Toggle drug active/inactive.
     */
    public function toggle(Drug $drug)
    {
        $this->pharmacyService->toggleDrug($drug);
        return back()->with('success', 'Drug status toggled.');
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
        $drug->load([
            'category',
            'stocks.receivedBy',
            'stocks.dispensingRecords',
        ]);

        // All dispensing records for this drug, most recent first
        $dispensingRecords = \App\Models\DispensingRecord::whereHas('drugStock', fn($q) => $q->where('drug_id', $drug->id))
            ->with(['patient', 'prescription', 'dispensedBy', 'drugStock'])
            ->latest('dispensed_at')
            ->get();

        // Summary stats
        $totalReceived = $drug->stocks->sum(function ($s) {
            return $s->quantity + $s->dispensingRecords->sum('quantity_dispensed');
        });
        $totalDispensed = $dispensingRecords->sum('quantity_dispensed');
        $revenue = $dispensingRecords->sum(fn($r) => $r->quantity_dispensed * ($r->drugStock->selling_price ?? 0));

        $stats = [
            'total_batches'   => $drug->stocks->count(),
            'total_received'  => $totalReceived,
            'total_dispensed' => $totalDispensed,
            'current_stock'   => $drug->total_stock,
            'revenue'         => $revenue,
        ];

        return view('pharmacy.drug-history', compact('drug', 'dispensingRecords', 'stats'));
    }
}
