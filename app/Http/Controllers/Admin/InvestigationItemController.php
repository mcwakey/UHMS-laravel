<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvestigationItemCategory;
use App\Enums\StockLocation;
use App\Http\Controllers\Controller;
use App\Models\InvestigationItem;
use App\Models\InvestigationItemStock;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestigationItemController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Catalog
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = InvestigationItem::withTrashed(false)
            ->withSum(['stocks as total_stock' => fn ($q) => $q->where('quantity', '>', 0)], 'quantity')
            ->when($request->search, fn ($q, $s) => $q->search($s))
            ->when($request->category, fn ($q, $c) => $q->where('category', $c))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->orderBy('name');

        $items = $query->paginate(20)->withQueryString();

        $lowStockCount = InvestigationItem::active()
            ->whereHas('stocks', fn ($q) => $q->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'reorder_level'))
            ->count();

        $categories = InvestigationItemCategory::cases();

        return view('investigations.items.index', compact('items', 'categories', 'lowStockCount'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:200'],
            'code'          => ['nullable', 'string', 'max:50', 'unique:investigation_items,code'],
            'category'      => ['required', 'string'],
            'unit'          => ['required', 'string', 'max:50'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'description'   => ['nullable', 'string'],
        ]);

        InvestigationItem::create($data);

        return back()->with('success', 'Investigation item created.');
    }

    public function update(Request $request, InvestigationItem $investigationItem)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:200'],
            'code'          => ['nullable', 'string', 'max:50', "unique:investigation_items,code,{$investigationItem->id}"],
            'category'      => ['required', 'string'],
            'unit'          => ['required', 'string', 'max:50'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'description'   => ['nullable', 'string'],
        ]);

        $investigationItem->update($data);

        return back()->with('success', 'Investigation item updated.');
    }

    public function toggle(InvestigationItem $investigationItem)
    {
        $investigationItem->update(['is_active' => !$investigationItem->is_active]);

        return back()->with('success', 'Status updated.');
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Management
    |--------------------------------------------------------------------------
    */

    public function stock(Request $request)
    {
        $query = InvestigationItemStock::with(['item', 'supplierRecord'])
            ->when($request->search, fn ($q, $s) => $q->whereHas('item', fn ($iq) => $iq->search($s)))
            ->when($request->location, fn ($q, $l) => $q->atLocation($l))
            ->when($request->status === 'low', fn ($q) => $q->lowStock())
            ->when($request->status === 'expired', fn ($q) => $q->expired())
            ->when($request->status === 'expiring', fn ($q) => $q->expiringSoon())
            ->orderBy('expiry_date')
            ->paginate(20)->withQueryString();

        $items     = InvestigationItem::active()->orderBy('name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $locations = StockLocation::cases();

        $stats = [
            'total_batches'    => InvestigationItemStock::where('quantity', '>', 0)->count(),
            'low_stock'        => InvestigationItemStock::lowStock()->count(),
            'expired'          => InvestigationItemStock::expired()->where('quantity', '>', 0)->count(),
            'expiring_soon'    => InvestigationItemStock::expiringSoon()->count(),
        ];

        return view('investigations.items.stock', compact('query', 'items', 'suppliers', 'locations', 'stats'));
    }

    public function storeStock(Request $request)
    {
        $data = $request->validate([
            'investigation_item_id' => ['required', 'exists:investigation_items,id'],
            'location'              => ['required', 'string'],
            'batch_number'          => ['nullable', 'string', 'max:100'],
            'quantity'              => ['required', 'integer', 'min:1'],
            'unit_cost'             => ['required', 'numeric', 'min:0'],
            'expiry_date'           => ['nullable', 'date'],
            'supplier_id'           => ['nullable', 'exists:suppliers,id'],
            'reorder_level'         => ['required', 'integer', 'min:0'],
        ]);

        $supplier = null;
        if (!empty($data['supplier_id'])) {
            $supplier = Supplier::find($data['supplier_id'])?->name;
        }

        InvestigationItemStock::create([
            ...$data,
            'supplier'      => $supplier,
            'received_date' => now(),
            'received_by'   => Auth::id(),
        ]);

        return back()->with('success', 'Stock added successfully.');
    }

    public function updateStock(Request $request, InvestigationItemStock $stock)
    {
        $data = $request->validate([
            'quantity'      => ['required', 'integer', 'min:0'],
            'unit_cost'     => ['required', 'numeric', 'min:0'],
            'expiry_date'   => ['nullable', 'date'],
            'reorder_level' => ['required', 'integer', 'min:0'],
        ]);

        $stock->update($data);

        return back()->with('success', 'Stock updated.');
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX
    |--------------------------------------------------------------------------
    */

    public function search(Request $request)
    {
        $items = InvestigationItem::active()
            ->search($request->q)
            ->limit(20)
            ->get(['id', 'name', 'code', 'unit', 'category']);

        return response()->json($items);
    }

    public function getStock(Request $request)
    {
        $request->validate([
            'item_id'  => ['required', 'exists:investigation_items,id'],
            'location' => ['required', 'string'],
        ]);

        $total = InvestigationItemStock::where('investigation_item_id', $request->item_id)
            ->atLocation($request->location)
            ->where('quantity', '>', 0)
            ->sum('quantity');

        return response()->json(['available' => $total]);
    }
}
