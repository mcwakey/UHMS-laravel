<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use App\Models\DrugStock;
use App\Services\PharmacyService;
use Illuminate\Http\Request;

class DrugStockController extends Controller
{
    public function __construct(
        protected PharmacyService $pharmacyService,
    ) {}

    /**
     * Stock management page.
     */
    public function index(Request $request)
    {
        $stock = $this->pharmacyService->getStock([
            'search' => $request->search,
            'status' => $request->status,
            'drug_id' => $request->drug_id,
        ]);

        $drugs = Drug::active()->orderBy('name')->get(['id', 'name', 'strength', 'unit']);

        return view('pharmacy.stock', compact('stock', 'drugs'));
    }

    /**
     * Add new stock batch.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'drug_id' => 'required|exists:drugs,id',
            'batch_number' => 'required|string|max:100',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'expiry_date' => 'required|date|after:today',
            'supplier' => 'nullable|string|max:255',
            'received_date' => 'nullable|date',
            'reorder_level' => 'nullable|integer|min:0',
        ]);

        $this->pharmacyService->addStock($validated);

        return back()->with('success', 'Stock added successfully.');
    }

    /**
     * Update a stock entry.
     */
    public function update(Request $request, DrugStock $stock)
    {
        $validated = $request->validate([
            'batch_number' => 'required|string|max:100',
            'quantity' => 'required|integer|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'expiry_date' => 'required|date',
            'supplier' => 'nullable|string|max:255',
            'reorder_level' => 'nullable|integer|min:0',
        ]);

        $this->pharmacyService->updateStock($stock, $validated);

        return back()->with('success', 'Stock updated successfully.');
    }

    /**
     * Stock alerts page.
     */
    public function alerts()
    {
        $alerts = $this->pharmacyService->getStockAlerts();

        return view('pharmacy.stock-alerts', compact('alerts'));
    }
}
