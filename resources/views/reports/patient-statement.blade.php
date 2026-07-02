@extends('layouts.app')
@section('title', __('reports.statement.title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('reports.statement.title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('common.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.reports.statement-search') }}">{{ __('reports.statement.search_title') }}</a></li>
                <li class="breadcrumb-item active">{{ $patient->full_name }}</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.patient-statement', array_merge(['patient' => $patient->id], request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger btn-sm">
            <i class="ti ti-file-type-pdf me-1"></i>{{ __('reports.actions.download_pdf') }}
        </a>
    </div>
</div>

<!-- Patient Info -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3"><strong>{{ __('reports.statement.patient_name') }}:</strong> {{ $patient->full_name }}</div>
            <div class="col-md-3"><strong>{{ __('reports.statement.patient_id') }}:</strong> {{ $patient->patient_number }}</div>
            <div class="col-md-3"><strong>{{ __('reports.patients.phone') }}:</strong> <x-patient-protected-field field="phone" :value="$patient->phone" /></div>
            <div class="col-md-3"><strong>{{ __('reports.date') }}:</strong> {{ now()->format('d/m/Y') }}</div>
        </div>
    </div>
</div>

<!-- Summary -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-start border-danger border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.statement.total_charges') }}</p>
                <h4 class="fw-bold mb-0 text-danger">₵{{ number_format($summary['total_charges'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.statement.total_payments') }}</p>
                <h4 class="fw-bold mb-0 text-success">₵{{ number_format($summary['total_payments'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.statement.balance_due') }}</p>
                <h4 class="fw-bold mb-0 text-warning">₵{{ number_format($summary['balance_due'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.statement.transaction_ledger') }}</p>
                <h4 class="fw-bold mb-0">{{ $summary['invoice_count'] }} / {{ $summary['payment_count'] }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.patient-statement', $patient) }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">{{ __('reports.filters.date_from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('reports.filters.date_to') }}</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary">{{ __('reports.filter') }}</button>
                <a href="{{ route('admin.reports.patient-statement', $patient) }}" class="btn btn-outline-secondary">{{ __('reports.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<!-- Ledger -->
<div class="card">
    <div class="card-header"><h6 class="mb-0">{{ __('reports.statement.transaction_ledger') }}</h6></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('reports.col_date') }}</th>
                    <th>{{ __('reports.statement.col_description') }}</th>
                    <th class="text-end">{{ __('reports.statement.col_charges') }}</th>
                    <th class="text-end">{{ __('reports.statement.col_payments') }}</th>
                    <th class="text-end">{{ __('reports.statement.col_balance') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ledger as $entry)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($entry['date'])->format('d/m/Y') }}</td>
                    <td>{{ $entry['description'] }}</td>
                    <td class="text-end {{ $entry['type'] === 'charge' ? 'text-danger fw-semibold' : '' }}">
                        {{ $entry['type'] === 'charge' ? '₵' . number_format($entry['amount'], 2) : '' }}
                    </td>
                    <td class="text-end {{ $entry['type'] === 'payment' ? 'text-success fw-semibold' : '' }}">
                        {{ $entry['type'] === 'payment' ? '₵' . number_format($entry['amount'], 2) : '' }}
                    </td>
                    <td class="text-end fw-bold">₵{{ number_format($entry['balance'], 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="5"><x-empty-state message="{{ __('reports.statement.no_transactions') }}" /></td></tr>
                @endforelse
            </tbody>
            @if(count($ledger))
            <tfoot class="table-light">
                <tr class="fw-bold">
                    <td colspan="2">{{ __('reports.columns.totals') }}</td>
                    <td class="text-end text-danger">₵{{ number_format($summary['total_charges'], 2) }}</td>
                    <td class="text-end text-success">₵{{ number_format($summary['total_payments'], 2) }}</td>
                    <td class="text-end">₵{{ number_format($summary['balance_due'], 2) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
