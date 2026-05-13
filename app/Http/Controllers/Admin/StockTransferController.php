<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockLocation;
use App\Enums\StockTransferStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockTransferRequest;
use App\Models\Department;
use App\Models\Drug;
use App\Models\DrugStock;
use App\Models\InvestigationItem;
use App\Models\InvestigationItemStock;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function __construct(
        private StockTransferService $stockTransferService,
    ) {}

    public function index(Request $request)
    {
        $stats = $this->stockTransferService->getStats();
        $transfers = $this->stockTransferService->list($request->all());
        $statuses = StockTransferStatus::cases();

        return view('store.transfers.index', compact('transfers', 'stats', 'statuses'));
    }

    public function create()
    {
        $drugs = Drug::active()
            ->whereHas('stocks', fn ($q) => $q->atLocation('store')->where('quantity', '>', 0))
            ->orderBy('name')
            ->get();

        // Investigation items available in store or laboratory
        $investigationItems = InvestigationItem::active()
            ->whereHas('stocks', fn ($q) => $q->where('quantity', '>', 0))
            ->orderBy('name')
            ->get();

        // Get store stock-on-hand for each drug from the new stock_balances SoT.
        $storeStock = \App\Models\StockBalance::query()
            ->whereHas('location', fn ($q) => $q->where('type', 'store'))
            ->where('quantity_on_hand', '>', 0)
            ->selectRaw('drug_id, SUM(quantity_on_hand) as total_qty')
            ->groupBy('drug_id')
            ->pluck('total_qty', 'drug_id');

        // Investigation item stock totals per location
        $investigationStock = InvestigationItemStock::where('quantity', '>', 0)
            ->selectRaw('investigation_item_id, location, SUM(quantity) as total_qty')
            ->groupBy('investigation_item_id', 'location')
            ->get()
            ->groupBy('investigation_item_id');

        $locations         = StockLocation::cases();
        $stockManagedDepts = Department::stockManaged()->orderBy('name')->get();

        return view('store.transfers.create', compact('drugs', 'storeStock', 'locations', 'investigationItems', 'investigationStock', 'stockManagedDepts'));
    }

    public function store(StoreStockTransferRequest $request)
    {
        try {
            $transfer = $this->stockTransferService->create($request->validated());

            return redirect()
                ->route('admin.store.transfers.show', $transfer)
                ->with('success', 'Stock transfer created successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(StockTransfer $transfer)
    {
        $transfer->load(['items.drug', 'transferredByUser', 'approvedByUser']);

        return view('store.transfers.show', compact('transfer'));
    }

    public function approve(StockTransfer $transfer)
    {
        try {
            $this->stockTransferService->approve($transfer);
            return back()->with('success', 'Transfer approved.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function complete(StockTransfer $transfer)
    {
        try {
            $this->stockTransferService->complete($transfer);
            return back()->with('success', 'Transfer completed. Stock has been moved.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(StockTransfer $transfer)
    {
        try {
            $this->stockTransferService->cancel($transfer);
            return back()->with('success', 'Transfer cancelled.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * AJAX: get stock info for a drug at a location.
     */
    public function drugStock(Request $request)
    {
        $request->validate([
            'drug_id' => ['required', 'exists:drugs,id'],
            'location' => ['required', 'string'],
        ]);

        $stock = $this->stockTransferService->getAvailableStock(
            $request->drug_id,
            $request->location,
        );

        return response()->json(['available' => $stock]);
    }

    /**
     * AJAX: get available stock for an investigation item at a location.
     */
    public function investigationItemStock(Request $request)
    {
        $request->validate([
            'item_id'  => ['required', 'exists:investigation_items,id'],
            'location' => ['required', 'string'],
        ]);

        $stock = $this->stockTransferService->getAvailableInvestigationStock(
            $request->item_id,
            $request->location,
        );

        return response()->json(['available' => $stock]);
    }
}
