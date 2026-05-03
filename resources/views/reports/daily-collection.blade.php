@extends('layouts.app')
@section('title', 'Daily Collection Report')

@section('content')
<div class="uhms-page-header d-flex align-items-sm-center justify-content-between flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0">Daily Collection Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Daily Collection</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.daily-collection', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger btn-sm">
            <i class="ti ti-file-type-pdf me-1"></i>PDF
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Total Collected</p>
                <h4 class="fw-bold mb-0 text-success">₵{{ number_format($stats['total_collected'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Transactions</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['total_transactions']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Payment Methods</p>
                <h4 class="fw-bold mb-0">{{ $stats['methods'] }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Collection by Payment Method -->
@if($byMethod->count())
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Collection by Payment Method</h6></div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr><th>Method</th><th class="text-end">Transactions</th><th class="text-end">Amount</th></tr>
            </thead>
            <tbody>
                @foreach($byMethod as $method)
                <tr>
                    <td><span class="badge bg-light text-dark">{{ $method->payment_method_label }}</span></td>
                    <td class="text-end">{{ number_format($method->count) }}</td>
                    <td class="text-end fw-semibold">₵{{ number_format($method->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.daily-collection') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="{{ $filters['date'] ?? now()->toDateString() }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Payment Method</label>
                <select name="payment_method" class="form-select">
                    <option value="">All Methods</option>
                    @foreach(\App\Enums\PaymentMethod::cases() as $pm)
                    <option value="{{ $pm->value }}" {{ ($filters['payment_method'] ?? '') == $pm->value ? 'selected' : '' }}>{{ $pm->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                <a href="{{ route('admin.reports.daily-collection') }}" class="btn btn-outline-secondary"><i class="ti ti-x me-1"></i>Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Receipt #</th>
                    <th>Time</th>
                    <th>Patient</th>
                    <th>Invoice</th>
                    <th>Method</th>
                    <th>Received By</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td><code>{{ $payment->payment_number ?? '-' }}</code></td>
                    <td>{{ ($payment->paid_at ?? $payment->created_at)->format('H:i') }}</td>
                    <td>{{ $payment->invoice?->patient?->full_name ?? '—' }}</td>
                    <td><code>{{ $payment->invoice?->invoice_number ?? '—' }}</code></td>
                    <td><span class="badge bg-light text-dark">{{ $payment->payment_method?->label() ?? $payment->payment_method }}</span></td>
                    <td>{{ $payment->receivedBy?->name ?? '—' }}</td>
                    <td class="text-end fw-semibold">₵{{ number_format($payment->amount, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No payments collected on this date.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($payments->hasPages())
    <div class="card-footer">{{ $payments->links() }}</div>
    @endif
</div>
@endsection
