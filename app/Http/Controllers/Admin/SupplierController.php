<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::query()
            ->withCount('purchaseOrders')
            ->when($request->search, fn ($q, $s) => $q->search($s))
            ->orderBy('name')
            ->paginate(15);

        return view('store.suppliers', compact('suppliers'));
    }

    public function store(StoreSupplierRequest $request)
    {
        Supplier::create($request->validated());

        return redirect()
            ->route('admin.store.suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    public function update(StoreSupplierRequest $request, Supplier $supplier)
    {
        $supplier->update($request->validated());

        return redirect()
            ->route('admin.store.suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    public function toggle(Supplier $supplier)
    {
        $supplier->update(['is_active' => ! $supplier->is_active]);

        return back()->with('success', 'Supplier status updated.');
    }
}
