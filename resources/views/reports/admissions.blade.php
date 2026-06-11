@extends('layouts.app')
@section('title', __('reports.admissions.title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('reports.admissions.title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('common.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('reports.admissions.title') }}</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.admissions', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
            <i class="ti ti-file-spreadsheet me-1"></i>{{ __('reports.actions.excel') }}
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.admissions.total_admissions') }}</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['total_admissions']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.current_admissions') }}</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['currently_admitted']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.admissions.discharges') }}</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['discharged']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
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
        <form method="GET" action="{{ route('admin.reports.admissions') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.filters.date_from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.filters.date_to') }}</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('reports.filters.ward') }}</label>
                <select name="ward_id" class="form-select">
                    <option value="">{{ __('reports.filters.all_wards') }}</option>
                    @foreach($wards as $ward)
                    <option value="{{ $ward->id }}" {{ ($filters['ward_id'] ?? '') == $ward->id ? 'selected' : '' }}>{{ $ward->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('reports.status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach(\App\Enums\AdmissionStatus::cases() as $s)
                    <option value="{{ $s->value }}" {{ ($filters['status'] ?? '') == $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary">{{ __('reports.filter') }}</button>
                <a href="{{ route('admin.reports.admissions') }}" class="btn btn-outline-secondary">{{ __('reports.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('reports.admissions.admission_date') }}</th>
                    <th>{{ __('reports.col_patient') }}</th>
                    <th>{{ __('reports.admissions.ward') }}</th>
                    <th>{{ __('reports.admissions.bed') }}</th>
                    <th>{{ __('reports.col_doctor') }}</th>
                    <th>{{ __('reports.status') }}</th>
                    <th>{{ __('reports.col_discharged') }}</th>
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
                <tr><td colspan="7"><x-empty-state message="{{ __('reports.empty.no_admissions') }}" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($admissions->hasPages())
    <div class="card-footer">{{ $admissions->links() }}</div>
    @endif
</div>
@endsection
