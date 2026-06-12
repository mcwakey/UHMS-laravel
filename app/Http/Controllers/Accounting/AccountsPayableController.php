<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierPayable;
use App\Models\SupplierPayment;
use App\Services\APAgingService;
use App\Services\SupplierPaymentService;
use App\Services\SupplierStatementService;
use Illuminate\Http\Request;
use Throwable;

class AccountsPayableController extends Controller
{
    public function __construct(
        private APAgingService $aging,
        private SupplierPaymentService $payments,
        private SupplierStatementService $statements,
    ) {}

    /** Outstanding supplier payables. */
    public function payables(Request $request)
    {
        $payables = SupplierPayable::query()
            ->with(['supplier:id,name', 'purchaseOrder:id,po_number', 'goodsReceivedNote:id,grn_number'])
            ->when($request->supplier_id, fn ($q, $id) => $q->where('supplier_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when(! $request->filled('status'), fn ($q) => $q->open())
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        $statuses = ['pending', 'partially_paid', 'paid', 'overdue', 'cancelled', 'written_off'];

        return view('accounting.payable.payables', compact('payables', 'suppliers', 'statuses'));
    }

    /** AP aging report. */
    public function aging(Request $request)
    {
        $report = $this->aging->report($request->only(['as_of', 'supplier_id', 'status']));
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        return view('accounting.payable.aging', compact('report', 'suppliers'));
    }

    /** Supplier payments list + record form data. */
    public function payments(Request $request)
    {
        $payments = SupplierPayment::query()
            ->with(['supplier:id,name', 'createdByUser:id,first_name,last_name'])
            ->when($request->supplier_id, fn ($q, $id) => $q->where('supplier_id', $id))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $suppliers = Supplier::active()->orderBy('name')->get(['id', 'name']);

        return view('accounting.payable.payments', compact('payments', 'suppliers'));
    }

    public function recordPayment(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'in:cash,bank,mobile_money'],
            'payment_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $supplier = Supplier::findOrFail($data['supplier_id']);

        try {
            $payment = $this->payments->record($supplier, $data);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('success', __('messages.accounting.supplier_payment_recorded', ['number' => $payment->payment_number]));
    }

    public function reversePayment(Request $request, SupplierPayment $payment)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->payments->reverse($payment, $data['reason'], $request->user());
        } catch (Throwable $e) {
            return back()->withErrors(['reason' => $e->getMessage()]);
        }

        return back()->with('success', __('messages.accounting.supplier_payment_reversed', ['number' => $payment->payment_number]));
    }

    /** Supplier statement from the ledger. */
    public function statement(Request $request, Supplier $supplier)
    {
        $statement = $this->statements->statement($supplier, $request->from, $request->to);
        $balance = app(\App\Services\SupplierLedgerService::class)->balance($supplier);

        return view('accounting.payable.statement', compact('statement', 'supplier', 'balance'));
    }
}
