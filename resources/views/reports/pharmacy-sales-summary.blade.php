@extends('layouts.app')
@section('title', __('reports.pharmacy.summary_title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('reports.pharmacy.summary_title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('common.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('reports.pharmacy.summary_title') }}</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.pharmacy-sales-summary', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
            <i class="ti ti-file-spreadsheet me-1"></i>{{ __('reports.actions.excel') }}
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.pharmacy-sales-summary') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.filters.date_from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.filters.date_to') }}</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">{{ __('reports.filter') }}</button>
                <a href="{{ route('admin.reports.pharmacy-sales-summary') }}" class="btn btn-outline-secondary">{{ __('reports.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('reports.columns.drug_name') }}</th>
                    <th>{{ __('reports.columns.generic_name') }}</th>
                    <th class="text-end">{{ __('reports.pharmacy.total_qty') }}</th>
                    <th class="text-end">{{ __('reports.pharmacy.total_amount') }}</th>
                    <th class="text-end">{{ __('reports.columns.patients') }}</th>
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
                <tr><td colspan="5"><x-empty-state message="{{ __('reports.empty.no_dispensing') }}" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($summary->hasPages())
    <div class="card-footer">{{ $summary->links() }}</div>
    @endif
</div>
@endsection
