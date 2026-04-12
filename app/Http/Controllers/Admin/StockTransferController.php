<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockLocation;
use App\Enums\StockTransferStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockTransferRequest;
use App\Models\Drug;
use App\Models\DrugStock;
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

        // Get store stock for each drug
        $storeStock = DrugStock::where('location', 'store')
            ->where('quantity', '>', 0)
            ->selectRaw('drug_id, SUM(quantity) as total_qty')
            ->groupBy('drug_id')
            ->pluck('total_qty', 'drug_id');

        $locations = StockLocation::cases();

        return view('store.transfers.create', compact('drugs', 'storeStock', 'locations'));
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
}
