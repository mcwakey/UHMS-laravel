@extends('layouts.app')
@section('title', 'Discharges Report')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Discharges Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Discharges</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.discharges', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
            <i class="ti ti-file-spreadsheet me-1"></i>Excel
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Total Discharges</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['total_discharges']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Average Length of Stay</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['avg_los'], 1) }} days</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Wards</p>
                <h4 class="fw-bold mb-0">{{ $stats['wards'] }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.discharges') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Ward</label>
                <select name="ward_id" class="form-select">
                    <option value="">All Wards</option>
                    @foreach($wards as $ward)
                    <option value="{{ $ward->id }}" {{ ($filters['ward_id'] ?? '') == $ward->id ? 'selected' : '' }}>{{ $ward->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.reports.discharges') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Admitted</th>
                    <th>Discharged</th>
                    <th>Patient</th>
                    <th>Ward</th>
                    <th>Doctor</th>
                    <th class="text-end">LOS (days)</th>
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
                <tr><td colspan="6"><x-empty-state message="No discharge records found." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($discharges->hasPages())
    <div class="card-footer">{{ $discharges->links() }}</div>
    @endif
</div>
@endsection
