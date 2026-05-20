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
            ->when($request->search, fn ($q, $s) => $q->where('description', 'like', "%{$s}%"))
            ->when($request->debit_credit === 'debit', fn ($q) => $q->where('debit', '>', 0))
            ->when($request->debit_credit === 'credit', fn ($q) => $q->where('credit', '>', 0))
            ->when($request->source_type, fn ($q, $s) => $q->where('source_type', $s))
            ->paginate(50)
            ->withQueryString();

        $balance = $ledger->balance($supplier);
        $types   = SupplierLedgerEntry::types();
        $manualTypes = SupplierLedgerEntry::manualTypes();
        $sourceTypes = SupplierLedgerEntry::query()
            ->where('supplier_id', $supplier->id)
            ->whereNotNull('source_type')
            ->distinct()
            ->pluck('source_type')
            ->values();

        return view('store.supplier-ledger', compact('supplier', 'entries', 'balance', 'types', 'manualTypes', 'sourceTypes'));
    }

    /**
     * Record a manual ledger entry (payment, return, credit/debit note, adjustment).
     */
    public function recordLedgerEntry(Request $request, Supplier $supplier, SupplierLedgerService $ledger)
    {
        $data = $request->validate([
            'entry_type'  => 'required|string|in:' . implode(',', SupplierLedgerEntry::manualTypes()),
            'entry_date'  => 'nullable|date',
            'amount'      => 'nullable|numeric|min:0.01',
            'debit'       => 'nullable|numeric|min:0',
            'credit'      => 'nullable|numeric|min:0',
            'description' => 'required|string|max:500',
        ]);

        try {
            $ledger->recordManualEntry($supplier, $data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Ledger entry recorded.');
    }
}
