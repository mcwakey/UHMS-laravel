@extends('layouts.app')
@section('title', 'Insurance Claims Report')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Insurance Claims Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Insurance Claims Report</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.insurance-claims', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger btn-sm">
            <i class="ti ti-file-type-pdf me-1"></i>Export PDF
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Total Claims</p>
                <h4 class="fw-bold mb-0">{{ $stats['total_claims'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Total Insurance Amount</p>
                <h4 class="fw-bold mb-0 text-success">₵{{ number_format($stats['total_insurance_amount'] ?? 0, 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Approved Claims</p>
                <h4 class="fw-bold mb-0">{{ $stats['approved_claims'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Pending Claims</p>
                <h4 class="fw-bold mb-0">{{ $stats['pending_claims'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.insurance-claims') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach($invoiceStatuses as $status)
                        <option value="{{ $status->value }}" {{ ($filters['status'] ?? '') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Insurance Invoice Records</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Invoice #</th>
                        <th>Patient</th>
                        <th>Department</th>
                        <th>Total Amount</th>
                        <th>Insurance Amount</th>
                        <th>Patient Pays</th>
                        <th>Status</th>
                        <th>Date</th>
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
                        <td colspan="8"><x-empty-state message="No insurance claims found" /></td>
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
