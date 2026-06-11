@extends('layouts.app')
@section('title', __('emergency.board'))

@push('styles')
<style>
    .er-triage-strip { width: 6px; border-radius: 8px 0 0 8px; }
    .er-triage-RED { background: #dc3545; }
    .er-triage-ORANGE { background: #fd7e14; }
    .er-triage-YELLOW { background: #ffc107; }
    .er-triage-GREEN { background: #198754; }
    .er-triage-BLACK { background: #212529; }
    .er-alert-pill { min-width: 34px; }
</style>
@endpush

@section('content')
<x-page-header :title="__('emergency.board')" :description="__('emergency.board_description')" icon="ti-ambulance">
    <x-slot:actions>
        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="(window.UhmsInertia ? window.UhmsInertia.reload({ preserveScroll: true }) : location.reload())">
            <i class="ti ti-refresh me-1"></i>{{ __('emergency.refresh') }}
        </button>
        @can('emergency.case.create')
            <a href="{{ route('admin.emergency.cases.create') }}" class="btn btn-primary btn-sm">
                <i class="ti ti-plus me-1"></i>{{ __('emergency.new_case') }}
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-2"><div class="card border-0 bg-light"><div class="card-body py-3"><div class="text-muted small">{{ __('emergency.active') }}</div><div class="h4 mb-0">{{ $counts['active'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 bg-warning-subtle"><div class="card-body py-3"><div class="text-warning small">{{ __('emergency.waiting_triage') }}</div><div class="h4 mb-0">{{ $counts['waiting_triage'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 bg-danger text-white"><div class="card-body py-3"><div class="small opacity-75">{{ __('emergency.red_critical') }}</div><div class="h4 mb-0">{{ $counts['red'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 bg-info-subtle"><div class="card-body py-3"><div class="text-info small">{{ __('emergency.under_care') }}</div><div class="h4 mb-0">{{ $counts['under_care'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 bg-primary-subtle"><div class="card-body py-3"><div class="text-primary small">{{ __('emergency.observation') }}</div><div class="h4 mb-0">{{ $counts['observation'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 bg-success-subtle"><div class="card-body py-3"><div class="text-success small">{{ __('emergency.ready_disposition') }}</div><div class="h4 mb-0">{{ $counts['ready_for_disposition'] ?? 0 }}</div></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET" action="{{ route('admin.emergency.board') }}">
            <div class="col-md-3">
                <label class="form-label">{{ __('emergency.search') }}</label>
                <input type="text" class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('emergency.search_placeholder') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('emergency.triage') }}</label>
                <select class="form-select" name="triage_category">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach(['RED','ORANGE','YELLOW','GREEN','BLACK'] as $category)
                        <option value="{{ $category }}" @selected(($filters['triage_category'] ?? '') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('emergency.status') }}</label>
                <select class="form-select" name="status">
                    <option value="">{{ __('emergency.all_active') }}</option>
                    @foreach(['WAITING_TRIAGE','TRIAGED','UNDER_EMERGENCY_CARE','OBSERVATION','READY_FOR_DISPOSITION'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('emergency.bay') }}</label>
                <select class="form-select" name="bay_id">
                    <option value="">{{ __('emergency.all_bays') }}</option>
                    @foreach($bays as $bay)
                        <option value="{{ $bay->id }}" @selected((string)($filters['bay_id'] ?? '') === (string)$bay->id)>{{ $bay->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100" type="submit">{{ __('common.filter') }}</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.emergency.board') }}">{{ __('common.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>{{ __('emergency.case') }}</th>
                        <th>{{ __('emergency.patient') }}</th>
                        <th>{{ __('emergency.triage') }}</th>
                        <th>{{ __('emergency.arrival_wait') }}</th>
                        <th>{{ __('emergency.bay') }}</th>
                        <th>{{ __('emergency.team') }}</th>
                        <th>{{ __('emergency.status') }}</th>
                        <th>{{ __('emergency.alerts') }}</th>
                        <th class="text-end">{{ __('emergency.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cases as $case)
                        @php $triage = $case->triage_category ?? 'UNTRIAGED'; @endphp
                        <tr>
                            <td class="position-relative">
                                <span class="position-absolute top-0 bottom-0 start-0 er-triage-strip er-triage-{{ $case->triage_category ?? 'GREEN' }}"></span>
                                <div class="ps-2 fw-semibold">{{ $case->emergency_number }}</div>
                                <small class="ps-2 text-muted">{{ $case->visit->visit_number ?? __('emergency.no_visit_number') }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $case->patient->full_name ?? __('emergency.unknown_patient') }}</div>
                                <small class="text-muted">{{ $case->patient->patient_number ?? '' }}</small>
                                @if($case->patient?->is_temporary)
                                    <span class="badge bg-warning-subtle text-warning ms-1">{{ __('emergency.temporary') }}</span>
                                @endif
                                @if($case->chief_complaint)
                                    <div class="small text-muted text-truncate" style="max-width: 220px;">{{ $case->chief_complaint }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $case->triage_badge_class }}">{{ $triage }}</span>
                                @if($case->triage_score !== null)
                                    <div class="small text-muted">{{ __('emergency.score') }} {{ $case->triage_score }}</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $case->arrival_time?->format('d M H:i') }}</div>
                                <small class="text-muted">{{ $case->waiting_minutes }} {{ __('emergency.min') }}</small>
                            </td>
                            <td>{{ $case->bay->name ?? __('emergency.unassigned') }}</td>
                            <td>
                                <div class="small">{{ __('emergency.dr_prefix') }}: {{ $case->assignedDoctor->name ?? __('emergency.unassigned') }}</div>
                                <div class="small text-muted">{{ __('emergency.nurse_prefix') }}: {{ $case->assignedNurse->name ?? __('emergency.unassigned') }}</div>
                            </td>
                            <td><x-status-badge :status="$case->emergency_status" domain="emergency" /></td>
                            <td>
                                <span class="badge rounded-pill bg-info er-alert-pill" title="{{ __('emergency.alert_tasks') }}">{{ $case->due_tasks_count }}</span>
                                <span class="badge rounded-pill bg-danger er-alert-pill" title="{{ __('emergency.alert_medications') }}">{{ $case->active_medication_tasks_count }}</span>
                                <span class="badge rounded-pill bg-warning text-dark er-alert-pill" title="{{ __('emergency.alert_investigations') }}">{{ $case->pending_investigations_count }}</span>
                                <span class="badge rounded-pill bg-secondary er-alert-pill" title="{{ __('emergency.alert_procedures') }}">{{ $case->pending_procedures_count }}</span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex flex-wrap justify-content-end gap-1">
                                    <a href="{{ route('admin.emergency.cases.show', $case) }}" class="btn btn-sm btn-outline-primary">{{ __('emergency.open') }}</a>
                                    @if($case->visit)
                                        <a href="{{ route('admin.emergency.mar-chart', $case->visit) }}" class="btn btn-sm btn-outline-danger">{{ __('emergency.mar') }}</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><x-empty-state icon="ti-ambulance" :title="__('emergency.no_active_cases')" :message="__('emergency.no_cases_message')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($cases->hasPages())
        <div class="card-footer">{{ $cases->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
setTimeout(function () {
    if (window.UhmsInertia) {
        window.UhmsInertia.reload({ preserveScroll: true, preserveState: true });
    }
}, 60000);
</script>
@endpush
