<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Drug;
use App\Models\ProductStockBalance;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Services\StockAdjustmentService;
use App\Services\StockBalanceService;
use App\Services\StockReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StockController extends Controller
{
    public function __construct(
        private StockBalanceService $balances,
        private StockAdjustmentService $adjustments,
        private StockReturnService $returns,
    ) {}

    /**
     * Product Stock Balances — single source of truth for on-hand inventory.
     *
     * Reads exclusively from `product_stock_balances` joined to `products`
     * and `stock_locations`. Every physical item in the hospital is a Product
     * and lives in this one ledger; there are no parallel drug/investigation
     * stock tables surfaced here.
     */
    public function balances(Request $request)
    {
        $balances = ProductStockBalance::query()
            ->with([
                'product:id,name,code,product_type,unit,reorder_level,is_active',
                'product.departments:id,name,type',
                'stockLocation:id,name,type,department_id',
                'stockLocation.department:id,name,type',
            ])
            ->when($request->location_id, fn ($q, $id) => $q->where('stock_location_id', $id))
            ->when($request->product_id, fn ($q, $id) => $q->where('product_id', $id))
            ->when($request->product_type, function ($q, $type) {
                $q->whereHas('product', fn ($pq) => $pq->where('product_type', $type));
            })
            ->when($request->search, function ($q, $s) {
                $q->whereHas('product', function ($pq) use ($s) {
                    $pq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
                });
            })
            ->when($request->low_only, function ($q) {
                $q->whereHas('product', function ($pq) {
                    $pq->whereColumn('reorder_level', '>=',
                        DB::raw('(SELECT quantity_on_hand FROM product_stock_balances psb WHERE psb.product_id = products.id AND psb.stock_location_id = product_stock_balances.stock_location_id LIMIT 1)'));
                });
            })
            ->orderBy('product_id')
            ->paginate(25)
            ->withQueryString();

        $locations    = StockLocation::active()->orderBy('name')->get();
        $productTypes = \App\Enums\ProductType::cases();

        return view('store.stock.balances', compact('balances', 'locations', 'productTypes'));
    }

    /**
     * Stock movement ledger (paginated).
     */
    public function ledger(Request $request)
    {
        $movements = StockMovement::query()
            ->with(['drug:id,name,unit', 'location:id,name', 'performedBy:id,first_name,last_name'])
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

        StockLocation::create(array_merge($data, ['is_active' => true]));

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

        return back()->with('success', 'Stock location updated.');
    }

    /**
     * Adjustment / Damaged / Expired entry form.
     */
    public function adjustmentForm()
    {
        $drugs     = Drug::where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit']);
        $locations = StockLocation::active()->orderBy('name')->get();

        return view('store.stock.adjustment', compact('drugs', 'locations'));
    }

    public function storeAdjustment(Request $request)
    {
        $data = $request->validate([
            'drug_id'           => 'required|exists:drugs,id',
            'stock_location_id' => 'required|exists:stock_locations,id',
            'type'              => 'required|in:in,out,damaged,expired',
            'quantity'          => 'required|numeric|min:0.0001',
            'reason'            => 'required|string|max:255',
            'notes'             => 'nullable|string|max:1000',
            'batch_no'          => 'nullable|string|max:100',
            'expiry_date'       => 'nullable|date',
            'unit_cost'         => 'nullable|numeric|min:0',
        ]);

        try {
            $movement = $this->adjustments->adjust($data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.store.stock.ledger', ['drug_id' => $movement->drug_id])
            ->with('success', 'Stock adjustment recorded.');
    }

    /**
     * Return entry form.
     */
    public function returnForm()
    {
        $drugs     = Drug::where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit']);
        $locations = StockLocation::active()->orderBy('name')->get();

        return view('store.stock.return', compact('drugs', 'locations'));
    }

    public function storeReturn(Request $request)
    {
        $data = $request->validate([
            'drug_id'           => 'required|exists:drugs,id',
            'stock_location_id' => 'required|exists:stock_locations,id',
            'type'              => 'required|in:in,out',
            'quantity'          => 'required|numeric|min:0.0001',
            'reason'            => 'required|string|max:255',
            'notes'             => 'nullable|string|max:1000',
            'batch_no'          => 'nullable|string|max:100',
            'expiry_date'       => 'nullable|date',
            'unit_cost'         => 'nullable|numeric|min:0',
        ]);

        try {
            $movement = $this->returns->record($data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.store.stock.ledger', ['drug_id' => $movement->drug_id])
            ->with('success', 'Stock return recorded.');
    }
}
