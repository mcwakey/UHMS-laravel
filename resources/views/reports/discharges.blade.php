@extends('layouts.app')
@section('title', __('reports.admissions.discharge_title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('reports.admissions.discharge_title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('common.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('reports.admissions.discharge_title') }}</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.discharges', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
            <i class="ti ti-file-spreadsheet me-1"></i>{{ __('reports.actions.excel') }}
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.total_discharges') }}</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['total_discharges']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.avg_los') }}</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['avg_los'], 1) }} {{ __('reports.kpi.days') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.wards') }}</p>
                <h4 class="fw-bold mb-0">{{ $stats['wards'] }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.discharges') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.filters.date_from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.filters.date_to') }}</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.filters.ward') }}</label>
                <select name="ward_id" class="form-select">
                    <option value="">{{ __('reports.filters.all_wards') }}</option>
                    @foreach($wards as $ward)
                    <option value="{{ $ward->id }}" {{ ($filters['ward_id'] ?? '') == $ward->id ? 'selected' : '' }}>{{ $ward->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">{{ __('reports.filter') }}</button>
                <a href="{{ route('admin.reports.discharges') }}" class="btn btn-outline-secondary">{{ __('reports.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('reports.columns.admitted') }}</th>
                    <th>{{ __('reports.columns.discharged') }}</th>
                    <th>{{ __('reports.col_patient') }}</th>
                    <th>{{ __('reports.admissions.ward') }}</th>
                    <th>{{ __('reports.col_doctor') }}</th>
                    <th class="text-end">{{ __('reports.admissions.los_days') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($discharges as $d)
                <tr>
                    <td>{{ $d->admission_date?->format('d/m/Y') ?? $d->created_at->format('d/m/Y') }}</td>
                    <td>{{ $d->actual_discharge_date?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $d->patient?->full_name ?? '—' }}</td>
                    <td>{{ $d->ward?->name ?? '—' }}</td>
                    <td>{{ $d->doctor?->name ?? '—' }}</td>
                    <td class="text-end fw-semibold">
                        @if($d->admission_date && $d->actual_discharge_date)
                            {{ $d->admission_date->diffInDays($d->actual_discharge_date) }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6"><x-empty-state message="{{ __('reports.empty.no_discharges') }}" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($discharges->hasPages())
    <div class="card-footer">{{ $discharges->links() }}</div>
    @endif
</div>
@endsection
