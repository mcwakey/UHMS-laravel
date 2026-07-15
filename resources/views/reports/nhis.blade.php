@extends('layouts.app')
@section('title', __('reports.claims.title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('reports.claims.title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('common.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('reports.claims.title') }}</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ $workspaceRoutes->route('admin.reports.insurance-claims', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger btn-sm">
            <i class="ti ti-file-type-pdf me-1"></i>{{ __('reports.export.label_pdf') }}
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.total_claims') }}</p>
                <h4 class="fw-bold mb-0">{{ $stats['total_claims'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.total_insurance_amount') }}</p>
                <h4 class="fw-bold mb-0 text-success">₵{{ number_format($stats['total_insurance_amount'] ?? 0, 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.approved_claims') }}</p>
                <h4 class="fw-bold mb-0">{{ $stats['approved_claims'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.pending_claims') }}</p>
                <h4 class="fw-bold mb-0">{{ $stats['pending_claims'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ $workspaceRoutes->route('admin.reports.insurance-claims') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.filters.date_from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.filters.date_to') }}</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.filters.status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('reports.filters.all_statuses') }}</option>
                    @foreach($invoiceStatuses as $status)
                        <option value="{{ $status->value }}" {{ ($filters['status'] ?? '') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i>{{ __('reports.actions.apply_filters') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ __('reports.claims.invoice_records') }}</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('reports.columns.invoice_number') }}</th>
                        <th>{{ __('reports.columns.patient') }}</th>
                        <th>{{ __('reports.columns.department') }}</th>
                        <th>{{ __('reports.columns.total_amount') }}</th>
                        <th>{{ __('reports.columns.insurance_amount') }}</th>
                        <th>{{ __('reports.columns.patient_pays') }}</th>
                        <th>{{ __('reports.columns.status') }}</th>
                        <th>{{ __('reports.columns.date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td>
                            <a href="{{ route('admin.billing.invoices.show', $invoice) }}" class="text-primary fw-medium">
                                {{ $invoice->invoice_number }}
                            </a>
                        </td>
                        <td>{{ $invoice->patient->full_name ?? '—' }}</td>
                        <td>{{ $invoice->visit?->department?->name ?? '—' }}</td>
                        <td>₵{{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="fw-bold text-success">₵{{ number_format($invoice->nhis_amount, 2) }}</td>
                        <td>₵{{ number_format($invoice->total_amount - $invoice->nhis_amount, 2) }}</td>
                        <td><x-status-badge :status="$invoice->status" /></td>
                        <td>{{ $invoice->created_at->format('d M Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8"><x-empty-state :message="__('reports.empty.no_claims')" /></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($invoices->hasPages())
    <div class="card-footer">
        {{ $invoices->links() }}
    </div>
    @endif
</div>
@endsection
