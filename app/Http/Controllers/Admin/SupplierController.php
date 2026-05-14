<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Services\SupplierLedgerService;
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

    /**
     * Supplier statement / running ledger.
     */
    public function ledger(Supplier $supplier, Request $request, SupplierLedgerService $ledger)
    {
        $entries = $ledger->entriesQuery($supplier)
            ->when($request->date_from, fn ($q, $d) => $q->where('entry_date', '>=', $d))
            ->when($request->date_to,   fn ($q, $d) => $q->where('entry_date', '<=', $d))
            ->when($request->entry_type, fn ($q, $t) => $q->where('entry_type', $t))
            ->paginate(50)
            ->withQueryString();

        $balance = $ledger->balance($supplier);
        $types   = SupplierLedgerEntry::types();

        return view('store.supplier-ledger', compact('supplier', 'entries', 'balance', 'types'));
    }

    /**
     * Record a manual ledger entry (payment, return, credit/debit note, adjustment).
     */
    public function recordLedgerEntry(Request $request, Supplier $supplier, SupplierLedgerService $ledger)
    {
        $data = $request->validate([
            'entry_type'  => 'required|string|in:' . implode(',', SupplierLedgerEntry::types()),
            'entry_date'  => 'nullable|date',
            'debit'       => 'nullable|numeric|min:0',
            'credit'      => 'nullable|numeric|min:0',
            'description' => 'required|string|max:500',
        ]);

        try {
            $ledger->recordEntry(
                supplier: $supplier,
                entryType: $data['entry_type'],
                debit: (float) ($data['debit'] ?? 0),
                credit: (float) ($data['credit'] ?? 0),
                description: $data['description'],
                entryDate: isset($data['entry_date']) ? \Illuminate\Support\Carbon::parse($data['entry_date']) : null,
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Ledger entry recorded.');
    }
}
