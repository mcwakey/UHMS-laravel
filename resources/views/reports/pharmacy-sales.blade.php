@extends('layouts.app')
@section('title', 'Pharmacy Sales Report')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Pharmacy Sales (Detailed)</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Pharmacy Sales</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.reports.pharmacy-sales', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
            <i class="ti ti-file-spreadsheet me-1"></i>Excel
        </a>
        <a href="{{ route('admin.reports.pharmacy-sales', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger btn-sm">
            <i class="ti ti-file-type-pdf me-1"></i>PDF
        </a>
    </div>
</div>

<!-- Stats -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Total Dispensed</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['total_dispensed']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Total Revenue</p>
                <h4 class="fw-bold mb-0 text-success">₵{{ number_format($stats['total_revenue'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Items Dispensed</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['items_dispensed']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Unique Patients</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['unique_patients']) }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.pharmacy-sales') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.reports.pharmacy-sales') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Drug</th>
                    <th>Batch #</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Total</th>
                    <th>Dispensed By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                @php $unitPrice = $record->prescriptionItem?->drug?->price ?? 0; @endphp
                <tr>
                    <td>{{ $record->dispensed_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $record->patient?->full_name ?? '—' }}</td>
                    <td>{{ $record->prescriptionItem?->drug_name ?? '—' }}</td>
                    <td><small>—</small></td>
                    <td class="text-end">{{ $record->quantity_dispensed }}</td>
                    <td class="text-end">₵{{ number_format($unitPrice, 2) }}</td>
                    <td class="text-end fw-semibold">₵{{ number_format($record->quantity_dispensed * $unitPrice, 2) }}</td>
                    <td>{{ $record->dispensedBy?->full_name ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="8"><x-empty-state message="No dispensing records found." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
    <div class="card-footer">{{ $records->links() }}</div>
    @endif
</div>
@endsection
