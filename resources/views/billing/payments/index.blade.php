@extends('layouts.app')
@section('title', 'Payments')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-cash me-2"></i>Payments</h4>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-soft-success rounded me-3">
                        <i class="ti ti-cash fs-4 text-success"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">&#8373;{{ number_format($totalToday, 2) }}</h3>
                        <p class="text-muted mb-0">Today's Collections</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-soft-primary rounded me-3">
                        <i class="ti ti-calendar-stats fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">&#8373;{{ number_format($totalMonth, 2) }}</h3>
                        <p class="text-muted mb-0">This Month</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-soft-info rounded me-3">
                        <i class="ti ti-receipt fs-4 text-info"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">{{ $payments->total() }}</h3>
                        <p class="text-muted mb-0">Total Payments</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.billing.payments.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search payment #, patient..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">All Methods</option>
                    @foreach($paymentMethods as $method)
                    <option value="{{ $method->value }}" {{ request('payment_method') === $method->value ? 'selected' : '' }}>
                        {{ $method->label() }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="From">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="To">
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                <a href="{{ route('admin.billing.payments.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Payments Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-nowrap mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Payment #</th>
                        <th>Patient</th>
                        <th>Invoice</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th class="text-end">Amount</th>
                        <th>Received By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td class="fw-medium">{{ $payment->payment_number }}</td>
                        <td>
                            <div class="fw-medium">{{ $payment->patient->full_name }}</div>
                            <small class="text-muted">{{ $payment->patient->patient_number }}</small>
                        </td>
                        <td>
                            <a href="{{ route('admin.billing.invoices.show', $payment->invoice) }}" class="text-primary">
                                {{ $payment->invoice->invoice_number }}
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-soft-primary">{{ $payment->payment_method->label() }}</span>
                        </td>
                        <td>{{ $payment->reference_number ?? '—' }}</td>
                        <td class="text-end fw-bold text-success">&#8373;{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ $payment->receivedBy->name ?? '—' }}</td>
                        <td>{{ $payment->paid_at->format('d M Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <i class="ti ti-cash fs-1 d-block mb-2"></i>
                                No payments found.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($payments->hasPages())
    <div class="card-footer">
        {{ $payments->links() }}
    </div>
    @endif
</div>
@endsection
