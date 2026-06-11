@extends('layouts.app')
@section('title', __('reports.billing.income_title'))

@section('content')
<div class="uhms-page-header d-flex align-items-sm-center justify-content-between flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0">{{ __('reports.billing.income_title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('common.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('reports.billing.income_title') }}</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.income', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger btn-sm">
            <i class="ti ti-file-type-pdf me-1"></i>{{ __('reports.actions.export_pdf') }}
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.total_income') }}</p>
                <h4 class="fw-bold mb-0 text-success">₵{{ number_format($stats['total_income'] ?? 0, 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.consultation_fees') }}</p>
                <h4 class="fw-bold mb-0">₵{{ number_format($stats['consultation_fees'] ?? 0, 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.lab_revenue') }}</p>
                <h4 class="fw-bold mb-0">₵{{ number_format($stats['lab_revenue'] ?? 0, 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.insurance_revenue') }}</p>
                <h4 class="fw-bold mb-0">₵{{ number_format($stats['insurance_revenue'] ?? 0, 2) }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.income') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.filters.date_from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.filters.date_to') }}</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.payment_method') }}</label>
                <select name="payment_method" class="form-select">
                    <option value="">{{ __('reports.all_methods') }}</option>
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method->value }}" {{ ($filters['payment_method'] ?? '') === $method->value ? 'selected' : '' }}>
                            {{ $method->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i>{{ __('reports.filter') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ __('reports.billing.invoice_register') }}</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('reports.billing.receipt_no') }}</th>
                        <th>{{ __('reports.col_patient') }}</th>
                        <th>{{ __('reports.columns.invoice_number') }}</th>
                        <th>{{ __('reports.billing.method') }}</th>
                        <th class="text-end">{{ __('reports.billing.amount') }}</th>
                        <th>{{ __('reports.billing.received_by') }}</th>
                        <th>{{ __('reports.date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td class="fw-medium">{{ $payment->payment_number }}</td>
                        <td>{{ $payment->patient->full_name ?? '—' }}</td>
                        <td>
                            @if($payment->invoice)
                                <a href="{{ route('admin.billing.invoices.show', $payment->invoice) }}" class="text-primary">
                                    {{ $payment->invoice->invoice_number }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td><span class="badge bg-light text-dark">{{ $payment->payment_method->label() }}</span></td>
                        <td class="text-end fw-bold text-success">₵{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ $payment->receivedBy->full_name ?? '—' }}</td>
                        <td>{{ $payment->paid_at?->format('d M Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7"><x-empty-state message="{{ __('reports.empty.no_records') }}" /></td>
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
