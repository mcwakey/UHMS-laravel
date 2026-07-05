@extends('layouts.app')
@section('title', __('maternity.maternity_case'))

@section('content')
<x-page-header :title="__('maternity.maternity_case')" :subtitle="$case->patient?->full_name" icon="ti-folder-open">
    <x-slot:actions>
        @if($case->pregnancyProfile)
        <a href="{{ route('admin.maternity.pregnancies.show', $case->pregnancyProfile) }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('maternity.pregnancy_profile') }}</a>
        @else
        <a href="{{ route('admin.maternity.dashboard') }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ti ti-notes me-1"></i>{{ __('maternity.case_summary') }}</h5>
                <span class="badge bg-{{ $case->status?->color() ?? 'secondary' }}">{{ $case->status?->label() }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.patient') }}</small><strong>{{ $case->patient?->full_name }}</strong><div class="text-muted small">{{ $case->patient?->patient_number }}</div></div>
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.case_type') }}</small><strong>{{ $case->case_type?->label() ?? __('common.not_available') }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.risk_level') }}</small><span class="badge badge-soft-{{ $case->risk_level?->color() ?? 'secondary' }}">{{ $case->risk_level?->label() ?? __('common.not_available') }}</span></div>
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.priority') }}</small><strong>{{ $case->priority ?? __('common.none') }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.linked_visit') }}</small><strong>{{ $case->visit?->visit_number ?? __('common.none') }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.linked_admission') }}</small><strong>{{ $case->admission?->admission_number ?? __('common.none') }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.department') }}</small><strong>{{ $case->department?->name ?? __('common.none') }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.opened_at') }}</small><strong>{{ $case->opened_at?->format('d M Y H:i') ?? __('common.not_available') }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('common.created_by') }}</small><strong>{{ $case->openedBy?->name ?? __('common.not_available') }}</strong></div>
                    <div class="col-12"><small class="text-muted d-block">{{ __('maternity.clinical_summary') }}</small>{{ $case->clinical_summary ?: __('common.none') }}</div>
                    @if($case->reason)
                    <div class="col-12"><small class="text-muted d-block">{{ __('common.reason') }}</small>{{ $case->reason }}</div>
                    @endif
                </div>
            </div>
        </div>

        @can('maternity.case.update')
        <form method="POST" action="{{ route('admin.maternity.cases.update', $case) }}" class="card">
            @csrf
            @method('PATCH')
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-edit me-1"></i>{{ __('maternity.update_case') }}</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">{{ __('maternity.case_status') }}</label><select name="status" class="form-select"><option value="open" @selected($case->status?->value === 'open')>{{ __('maternity.case_statuses.open') }}</option><option value="under_observation" @selected($case->status?->value === 'under_observation')>{{ __('maternity.case_statuses.under_observation') }}</option><option value="admitted" @selected($case->status?->value === 'admitted')>{{ __('maternity.case_statuses.admitted') }}</option><option value="referred" @selected($case->status?->value === 'referred')>{{ __('maternity.case_statuses.referred') }}</option><option value="transferred" @selected($case->status?->value === 'transferred')>{{ __('maternity.case_statuses.transferred') }}</option></select></div>
                    <div class="col-md-4"><label class="form-label">{{ __('maternity.risk_level') }}</label><select name="risk_level" class="form-select"><option value="low" @selected($case->risk_level?->value === 'low')>{{ __('maternity.risk_levels.low') }}</option><option value="moderate" @selected($case->risk_level?->value === 'moderate')>{{ __('maternity.risk_levels.moderate') }}</option><option value="high" @selected($case->risk_level?->value === 'high')>{{ __('maternity.risk_levels.high') }}</option><option value="emergency" @selected($case->risk_level?->value === 'emergency')>{{ __('maternity.risk_levels.emergency') }}</option></select></div>
                    <div class="col-md-4"><label class="form-label">{{ __('maternity.priority') }}</label><input name="priority" class="form-control" value="{{ old('priority', $case->priority) }}"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('maternity.department') }}</label><select name="department_id" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected($case->department_id === $department->id)>{{ $department->name }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label">{{ __('maternity.clinical_summary') }}</label><textarea name="clinical_summary" rows="4" class="form-control">{{ old('clinical_summary', $case->clinical_summary) }}</textarea></div>
                </div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('common.save_changes') }}</button></div>
        </form>
        @endcan
    </div>

    <div class="col-xl-4">
        @can('maternity.admission.request')
        @if(! $case->status?->isClosed())
        <form method="POST" action="{{ route('admin.maternity.cases.admission-request', $case) }}" class="card mb-3">
            @csrf
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-bed me-1"></i>{{ __('maternity.create_admission_request') }}</h5></div>
            <div class="card-body">
                <div class="mb-3"><label class="form-label">{{ __('maternity.requested_ward') }}</label><select name="requested_ward_id" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($wards as $ward)<option value="{{ $ward->id }}">{{ $ward->name }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label">{{ __('maternity.priority') }}</label><input name="priority" class="form-control" value="{{ old('priority', $case->priority) }}"></div>
                <div class="mb-3"><label class="form-label">{{ __('maternity.provisional_diagnosis') }}</label><input name="provisional_diagnosis" class="form-control" value="{{ old('provisional_diagnosis', __('maternity.default_admission_diagnosis')) }}"></div>
                <div class="mb-0"><label class="form-label">{{ __('maternity.clinical_summary') }}</label><textarea name="clinical_summary" rows="3" class="form-control">{{ old('clinical_summary', $case->clinical_summary) }}</textarea></div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary"><i class="ti ti-send me-1"></i>{{ __('maternity.request_admission') }}</button></div>
        </form>
        @endif
        @endcan

        @can('maternity.case.close')
        @if(! $case->status?->isClosed())
        <form method="POST" action="{{ route('admin.maternity.cases.close', $case) }}" class="card mb-3">
            @csrf
            @method('PATCH')
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-circle-x me-1"></i>{{ __('maternity.close_case') }}</h5></div>
            <div class="card-body">
                <div class="mb-3"><label class="form-label">{{ __('common.status') }}</label><select name="status" class="form-select"><option value="closed">{{ __('maternity.case_statuses.closed') }}</option><option value="cancelled">{{ __('maternity.case_statuses.cancelled') }}</option></select></div>
                <textarea name="reason" rows="3" class="form-control" placeholder="{{ __('common.reason') }}"></textarea>
            </div>
            <div class="card-footer text-end"><button class="btn btn-outline-danger">{{ __('maternity.close_case') }}</button></div>
        </form>
        @endif
        @endcan

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-clock me-1"></i>{{ __('maternity.future_workflows') }}</h5></div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @if($case->pregnancyProfile)
                    <a class="list-group-item px-0 d-flex justify-content-between align-items-center" href="{{ route('admin.maternity.pregnancies.labor.create', $case->pregnancyProfile) }}"><span>{{ __('maternity.start_labor_episode') }}</span><i class="ti ti-chevron-right"></i></a>
                    <a class="list-group-item px-0 d-flex justify-content-between align-items-center" href="{{ route('admin.maternity.labor.index') }}"><span>{{ __('maternity.labor_and_delivery') }}</span><i class="ti ti-chevron-right"></i></a>
                    @endif
                    <div class="list-group-item px-0">{{ __('maternity.future_newborn_placeholder') }}</div>
                    <div class="list-group-item px-0">{{ __('maternity.future_postnatal_placeholder') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
