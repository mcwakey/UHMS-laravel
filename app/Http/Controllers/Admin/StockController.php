<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Services\StockBalanceMatrixService;
use App\Services\StockLocationSyncService;
use App\Services\StockOperationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockController extends Controller
{
    public function __construct(
        private StockBalanceMatrixService $matrix,
        private StockLocationSyncService $locationSync,
        private StockOperationService $operations,
    ) {}

    /**
     * Product Stock Balances — single source of truth for on-hand inventory.
     */
    public function balances(Request $request)
    {
        $matrix = $this->matrix->build($request->only(['location_id', 'product_type', 'search']));

        return view('store.stock.balances', $matrix);
    }

    /**
     * Stock movement ledger (paginated).
     */
    public function ledger(Request $request)
    {
        $movements = StockMovement::query()
            ->with(['drug:id,name,unit', 'product:id,name', 'location:id,name', 'performedBy:id,first_name,last_name'])
            ->when($request->drug_id, fn ($q, $id) => $q->where('drug_id', $id))
            ->when($request->location_id, fn ($q, $id) => $q->where('stock_location_id', $id))
            ->when($request->movement_type, fn ($q, $t) => $q->where('movement_type', $t))
            ->when($request->direction, fn ($q, $d) => $q->where('direction', $d))
            ->when($request->date_from, fn ($q, $d) => $q->whereDate('movement_date', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->whereDate('movement_date', '<=', $d))
            ->latest('movement_date')
            ->paginate(30)
            ->withQueryString();

        $locations = StockLocation::active()->orderBy('name')->get();
        $types     = StockMovementType::cases();

        return view('store.stock.ledger', compact('movements', 'locations', 'types'));
    }

    /**
     * Stock locations management.
     */
    public function locations()
    {
        $locations = StockLocation::with('department:id,name')->orderBy('name')->paginate(25);
        $departments = \App\Models\Department::orderBy('name')->get(['id', 'name']);

        return view('store.stock.locations', compact('locations', 'departments'));
    }

    public function storeLocation(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:120|unique:stock_locations,name',
            'type'          => 'required|string|max:40',
            'department_id' => 'nullable|exists:departments,id',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $location = StockLocation::create(array_merge($data, ['is_active' => true]));

        // A location tied to a department implies that department manages stock.
        $this->locationSync->markDepartmentManaged($location);

        return back()->with('success', 'Stock location created.');
    }

    public function updateLocation(Request $request, StockLocation $location)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120', Rule::unique('stock_locations', 'name')->ignore($location->id)],
            'type'          => 'required|string|max:40',
            'department_id' => 'nullable|exists:departments,id',
            'is_active'     => 'nullable|boolean',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $location->update($data);

        // Keep the department's stock-managed flag in sync with its location.
        $this->locationSync->markDepartmentManaged($location);

        return back()->with('success', 'Stock location updated.');
    }

    /*
    |--------------------------------------------------------------------------
    | Movement list pages + per-movement detail
    |--------------------------------------------------------------------------
    */

    /**
     * Shared query for a batch list page of a given type. One row per batch
     * (a group of movements recorded together).
     */
    private function batchList(Request $request, string $type)
    {
        return StockBatch::query()
            ->where('type', $type)
            ->with(['sourceLocation:id,name', 'destLocation:id,name', 'createdBy:id,first_name,last_name'])
            ->withCount('movements')
            ->when($request->location_id, fn ($q, $id) => $q->where(fn ($w) => $w->where('source_location_id', $id)->orWhere('dest_location_id', $id)))
            ->when($request->date_from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();
    }

    public function adjustmentsIndex(Request $request)
    {
        $batches   = $this->batchList($request, StockBatch::TYPE_ADJUSTMENT);
        $locations = StockLocation::active()->orderBy('name')->get();

        return view('store.stock.adjustments-index', compact('batches', 'locations'));
    }

    public function returnsIndex(Request $request)
    {
        $batches   = $this->batchList($request, StockBatch::TYPE_RETURN);
        $locations = StockLocation::active()->orderBy('name')->get();

        return view('store.stock.returns-index', compact('batches', 'locations'));
    }

    public function transfersIndex(Request $request)
    {
        $batches   = $this->batchList($request, StockBatch::TYPE_TRANSFER);
        $locations = StockLocation::active()->orderBy('name')->get();

        return view('store.stock.transfers-index', compact('batches', 'locations'));
    }

    /**
     * Detail of one batch — reveals every line item recorded together.
     */
    public function batchShow(StockBatch $batch)
    {
        $batch->load([
            'sourceLocation:id,name', 'destLocation:id,name', 'createdBy:id,first_name,last_name',
            'movements' => fn ($q) => $q->with(['product:id,name,code', 'drug:id,name,unit', 'location:id,name'])->orderBy('id'),
        ]);

        return view('store.stock.batch-show', compact('batch'));
    }

    /**
     * Detail of a single stock movement (used from the ledger).
     */
    public function movementShow(StockMovement $movement)
    {
        $movement->load(['drug:id,name,unit', 'product:id,name,code', 'location:id,name', 'performedBy:id,first_name,last_name', 'source']);

        return view('store.stock.movement-show', compact('movement'));
    }

    /*
    |--------------------------------------------------------------------------
    | Adjustments — product based, multi-row, at a single location
    |--------------------------------------------------------------------------
    */

    public function adjustmentForm()
    {
        return view('store.stock.adjustment', [
            'products'  => $this->stockableProducts(),
            'locations' => StockLocation::active()->orderBy('name')->get(),
        ]);
    }

    public function storeAdjustment(Request $request)
    {
        $data = $request->validate([
            'stock_location_id'   => 'required|exists:stock_locations,id',
            'reason'              => 'required|string|max:255',
            'notes'               => 'nullable|string|max:1000',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'nullable|exists:products,id',
            'items.*.type'        => 'nullable|in:in,out,damaged,expired',
            'items.*.quantity'    => 'nullable|numeric|min:0.0001',
            'items.*.batch_no'    => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.unit_cost'   => 'nullable|numeric|min:0',
        ]);

        try {
            $batch = $this->operations->adjustBatch($data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.store.stock.batches.show', $batch)
            ->with('success', 'Stock adjustment ' . $batch->batch_number . ' recorded.');
    }

    /*
    |--------------------------------------------------------------------------
    | Returns — product based, from a managed location into the Main Store
    |--------------------------------------------------------------------------
    */

    public function returnForm()
    {
        return view('store.stock.return', [
            'products'         => $this->stockableProducts(),
            'managedLocations' => $this->managedLocations(),
            'mainStore'        => $this->mainStore(),
        ]);
    }

    public function storeReturn(Request $request)
    {
        $data = $request->validate([
            'source_location_id'  => 'required|exists:stock_locations,id',
            'reason'              => 'required|string|max:255',
            'notes'               => 'nullable|string|max:1000',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'nullable|exists:products,id',
            'items.*.quantity'    => 'nullable|numeric|min:0.0001',
            'items.*.batch_no'    => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.unit_cost'   => 'nullable|numeric|min:0',
        ]);

        try {
            $batch = $this->operations->returnToMainBatch($data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.store.stock.batches.show', $batch)
            ->with('success', 'Stock return ' . $batch->batch_number . ' recorded.');
    }

    /*
    |--------------------------------------------------------------------------
    | Transfers — product based, from the Main Store into a managed location
    |--------------------------------------------------------------------------
    */

    public function transferForm()
    {
        return view('store.stock.transfer', [
            'products'         => $this->stockableProducts(),
            'managedLocations' => $this->managedLocations(),
            'mainStore'        => $this->mainStore(),
        ]);
    }

    public function storeTransfer(Request $request)
    {
        $data = $request->validate([
            'dest_location_id'    => 'required|exists:stock_locations,id',
            'reason'              => 'required|string|max:255',
            'notes'               => 'nullable|string|max:1000',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'nullable|exists:products,id',
            'items.*.quantity'    => 'nullable|numeric|min:0.0001',
            'items.*.batch_no'    => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.unit_cost'   => 'nullable|numeric|min:0',
        ]);

        try {
            $batch = $this->operations->transferFromMainBatch($data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.store.stock.batches.show', $batch)
            ->with('success', 'Stock transfer ' . $batch->batch_number . ' recorded.');
    }

    /*
    |--------------------------------------------------------------------------
    | Shared lookups
    |--------------------------------------------------------------------------
    */

    private function stockableProducts()
    {
        return Product::active()->orderBy('name')->get(['id', 'name', 'code', 'unit']);
    }

    private function managedLocations()
    {
        return StockLocation::active()->where('is_main', false)->orderBy('name')->get(['id', 'name']);
    }

    private function mainStore(): ?StockLocation
    {
        return StockLocation::where('is_main', true)->where('is_active', true)->first(['id', 'name']);
    }
}
