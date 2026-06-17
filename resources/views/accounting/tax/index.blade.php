@extends('layouts.app')
@section('title', __('accounting.tax_accounting'))

@php $money = fn ($n) => 'GHS '.number_format((float) $n, 2); @endphp

@section('content')
<x-page-header :title="__('accounting.tax_accounting')" icon="ti-receipt-tax" description="Tax ledgers, returns, payments and reconciliation foundation." />

<div class="row g-2 mb-3">
    <div class="col-md-6"><div class="card h-100"><div class="card-body text-center"><small class="text-muted d-block">Ledger Total</small><strong>{{ $money($summary['totals']['ledger_total'] ?? 0) }}</strong></div></div></div>
    <div class="col-md-6"><div class="card h-100"><div class="card-body text-center"><small class="text-muted d-block">Open Tax Balance</small><strong class="text-primary">{{ $money($summary['totals']['open_total'] ?? 0) }}</strong></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0">Prepare Return</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.accounting.tax.returns.prepare') }}" class="row g-2">
                    @csrf
                    <div class="col-12"><select name="tax_code" class="form-select" required>@foreach($taxTypes as $type)<option value="{{ $type->code }}">{{ $type->code }} - {{ $type->name }}</option>@endforeach</select></div>
                    <div class="col-6"><input name="period_start" type="date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}" required></div>
                    <div class="col-6"><input name="period_end" type="date" class="form-control" value="{{ now()->endOfMonth()->toDateString() }}" required></div>
                    <div class="col-12"><button class="btn btn-outline-primary w-100">Prepare</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0">Record Payment</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.accounting.tax.payments.store') }}" class="row g-2">
                    @csrf
                    <div class="col-12"><select name="tax_code" class="form-select" required>@foreach($taxTypes as $type)<option value="{{ $type->code }}">{{ $type->code }} - {{ $type->name }}</option>@endforeach</select></div>
                    <div class="col-6"><input name="payment_date" type="date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                    <div class="col-6"><input name="amount" type="number" step="0.01" min="0.01" class="form-control" placeholder="Amount" required></div>
                    <div class="col-12"><select name="payment_account_id" class="form-select" required>@foreach($paymentAccounts as $account)<option value="{{ $account->id }}">{{ $account->code }} {{ $account->name }}</option>@endforeach</select></div>
                    <div class="col-12"><button class="btn btn-outline-success w-100">Post Payment</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0">Tax Ledger Summary</h5></div>
            <div class="table-responsive"><table class="table table-sm mb-0"><thead class="table-light"><tr><th>Tax</th><th class="text-end">Ledger</th><th class="text-end">Open</th></tr></thead><tbody>
                @foreach($summary['rows'] as $row)<tr><td>{{ $row['tax_type']->code }}</td><td class="text-end">{{ $money($row['ledger_total']) }}</td><td class="text-end">{{ $money($row['open_total']) }}</td></tr>@endforeach
            </tbody></table></div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">Returns</h5></div>
    <div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Return</th><th>Tax</th><th>Status</th><th class="text-end">Due</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th></th></tr></thead><tbody>
        @forelse($returns as $return)
            <tr>
                <td>{{ $return->return_number }}</td><td>{{ $return->period?->taxType?->code }}</td><td><span class="badge bg-secondary">{{ $return->status }}</span></td>
                <td class="text-end">{{ $money($return->total_tax_due) }}</td><td class="text-end">{{ $money($return->total_payments) }}</td><td class="text-end">{{ $money($return->balance_due) }}</td>
                <td class="text-end">@if($return->status === \App\Models\TaxReturn::STATUS_PREPARED)<form method="POST" action="{{ route('admin.accounting.tax.returns.approve', $return) }}">@csrf<button class="btn btn-sm btn-success">Approve</button></form>@endif</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-3">No returns prepared yet.</td></tr>
        @endforelse
    </tbody></table></div>
</div>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Payments</h5></div>
    <div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Payment</th><th>Date</th><th class="text-end">Amount</th><th class="text-end">Unallocated</th><th>Allocate</th></tr></thead><tbody>
        @forelse($payments as $payment)
            <tr>
                <td>{{ $payment->payment_number }}</td><td>{{ $payment->payment_date?->format('d M Y') }}</td><td class="text-end">{{ $money($payment->amount) }}</td><td class="text-end">{{ $money($payment->unallocated_amount) }}</td>
                <td>
                    @if($payment->unallocated_amount > 0)
                        <form method="POST" action="{{ route('admin.accounting.tax.payments.allocate', $payment) }}" class="d-flex gap-1">
                            @csrf
                            <select name="tax_return_id" class="form-select form-select-sm">@foreach($returns as $return)<option value="{{ $return->id }}">{{ $return->return_number }}</option>@endforeach</select>
                            <input name="amount" type="number" step="0.01" min="0.01" max="{{ $payment->unallocated_amount }}" class="form-control form-control-sm" placeholder="Amount">
                            <button class="btn btn-sm btn-outline-primary">Allocate</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-3">No tax payments yet.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
@endsection
