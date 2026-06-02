@extends('layouts.app')
@section('title', 'Admissions Report')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Admissions Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Admissions</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.admissions', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
            <i class="ti ti-file-spreadsheet me-1"></i>Excel
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Total Admissions</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['total_admissions']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Currently Admitted</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['currently_admitted']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Discharged</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['discharged']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
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
        <form method="GET" action="{{ route('admin.reports.admissions') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Ward</label>
                <select name="ward_id" class="form-select">
                    <option value="">All Wards</option>
                    @foreach($wards as $ward)
                    <option value="{{ $ward->id }}" {{ ($filters['ward_id'] ?? '') == $ward->id ? 'selected' : '' }}>{{ $ward->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(\App\Enums\AdmissionStatus::cases() as $s)
                    <option value="{{ $s->value }}" {{ ($filters['status'] ?? '') == $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.reports.admissions') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Ward</th>
                    <th>Bed</th>
                    <th>Doctor</th>
                    <th>Status</th>
                    <th>Discharged</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admissions as $adm)
                <tr>
                    <td>{{ $adm->admission_date?->format('d/m/Y') ?? $adm->created_at->format('d/m/Y') }}</td>
                    <td>{{ $adm->patient?->full_name ?? '—' }}</td>
                    <td>{{ $adm->ward?->name ?? '—' }}</td>
                    <td>{{ $adm->bed?->bed_number ?? '—' }}</td>
                    <td>{{ $adm->doctor?->name ?? '—' }}</td>
                    <td>
                        @php
                            $colors = ['admitted' => 'warning', 'discharged' => 'success', 'transferred' => 'info'];
                            $status = $adm->status instanceof \App\Enums\AdmissionStatus ? $adm->status->value : $adm->status;
                        @endphp
                        <span class="badge bg-{{ $colors[$status] ?? 'secondary' }}">{{ $adm->status instanceof \App\Enums\AdmissionStatus ? $adm->status->label() : ucfirst($status) }}</span>
                    </td>
                    <td>{{ $adm->actual_discharge_date?->format('d/m/Y') ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="7"><x-empty-state message="No admissions found." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($admissions->hasPages())
    <div class="card-footer">{{ $admissions->links() }}</div>
    @endif
</div>
@endsection
