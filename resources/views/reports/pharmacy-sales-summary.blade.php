@extends('layouts.app')
@section('title', 'Pharmacy Sales Summary')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Pharmacy Sales Summary</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Pharmacy Summary</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.pharmacy-sales-summary', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
            <i class="ti ti-file-spreadsheet me-1"></i>Excel
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.pharmacy-sales-summary') }}" class="row g-3 align-items-end">
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
                <a href="{{ route('admin.reports.pharmacy-sales-summary') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Drug Name</th>
                    <th>Generic Name</th>
                    <th class="text-end">Total Qty</th>
                    <th class="text-end">Total Revenue</th>
                    <th class="text-end">Patients</th>
                </tr>
            </thead>
            <tbody>
                @forelse($summary as $row)
                <tr>
                    <td class="fw-semibold">{{ $row->drug_name }}</td>
                    <td class="text-muted">{{ $row->generic_name ?? '—' }}</td>
                    <td class="text-end">{{ number_format($row->total_quantity) }}</td>
                    <td class="text-end fw-semibold text-success">₵{{ number_format($row->total_revenue, 2) }}</td>
                    <td class="text-end">{{ $row->patient_count }}</td>
                </tr>
                @empty
                <tr><td colspan="5"><x-empty-state message="No data found." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($summary->hasPages())
    <div class="card-footer">{{ $summary->links() }}</div>
    @endif
</div>
@endsection
