@extends('layouts.app')
@section('title', __('maternity.pregnancy_profile'))

@section('content')
<x-page-header :title="__('maternity.pregnancy_profile')" :subtitle="$profile->patient?->full_name" icon="ti-heartbeat">
    <x-slot:actions>
        <a href="{{ route('admin.maternity.pregnancies.index') }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
        @can('maternity.pregnancy.update')
        <a href="{{ route('admin.maternity.pregnancies.edit', $profile) }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-edit me-1"></i>{{ __('common.edit') }}</a>
        @endcan
    </x-slot:actions>
</x-page-header>

@if(! empty($overview['warnings']))
<div class="alert alert-warning">
    <div class="fw-semibold mb-1"><i class="ti ti-alert-triangle me-1"></i>{{ __('maternity.profile_warnings') }}</div>
    <ul class="mb-0">
        @foreach($overview['warnings'] as $warning)
        <li>{{ $warning }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ti ti-notes me-1"></i>{{ __('maternity.profile_summary') }}</h5>
                <span class="badge bg-{{ $profile->profile_status?->color() ?? 'secondary' }}">{{ $profile->profile_status?->label() }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.patient') }}</small><strong>{{ $profile->patient?->full_name }}</strong><div class="text-muted small">{{ $profile->patient?->patient_number }}</div></div>
                    <div class="col-md-2"><small class="text-muted d-block">{{ __('maternity.gravida') }}</small><strong>{{ $profile->gravida ?? __('common.not_available') }}</strong></div>
                    <div class="col-md-2"><small class="text-muted d-block">{{ __('maternity.para') }}</small><strong>{{ $profile->para ?? __('common.not_available') }}</strong></div>
                    <div class="col-md-2"><small class="text-muted d-block">{{ __('maternity.abortions') }}</small><strong>{{ $profile->abortions ?? __('common.not_available') }}</strong></div>
                    <div class="col-md-2"><small class="text-muted d-block">{{ __('maternity.living_children') }}</small><strong>{{ $profile->living_children ?? __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.lmp') }}</small><strong>{{ $profile->last_menstrual_period?->format('d M Y') ?? __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.edd') }}</small><strong>{{ $profile->estimated_due_date?->format('d M Y') ?? __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.gestational_age') }}</small><strong>{{ $profile->gestational_age_weeks !== null ? $profile->gestational_age_weeks.'w '.$profile->gestational_age_days.'d' : __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.blood_group') }}</small><strong>{{ trim(($profile->blood_group ?? '').' '.($profile->rhesus_status ?? '')) ?: __('common.not_available') }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.linked_visit') }}</small><strong>{{ $profile->visit?->visit_number ?? __('common.none') }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.linked_admission') }}</small><strong>{{ $profile->admission?->admission_number ?? $overview['admission']?->admission_number ?? __('common.none') }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.department') }}</small><strong>{{ $profile->department?->name ?? __('common.none') }}</strong></div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-alert-circle me-1"></i>{{ __('maternity.risk_snapshot') }}</h5></div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @forelse($overview['risk_summary']['flags'] as $flag)
                    <span class="badge bg-danger-subtle text-danger">{{ __('maternity.'.$flag) }}</span>
                    @empty
                    <span class="text-muted">{{ __('maternity.no_risk_flags') }}</span>
                    @endforelse
                </div>
                @if($overview['risk_summary']['known_risks']->isNotEmpty())
                <ul class="mb-0">
                    @foreach($overview['risk_summary']['known_risks'] as $risk)
                    <li>{{ $risk }}</li>
                    @endforeach
                </ul>
                @endif
                @if($profile->allergies_snapshot)
                <hr>
                <div><small class="text-muted d-block">{{ __('maternity.allergies_snapshot') }}</small>{{ $profile->allergies_snapshot }}</div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ti ti-stethoscope me-1"></i>{{ __('maternity.antenatal_care') }}</h5>
                <div class="d-flex gap-2">
                    @can('maternity.anc.record')
                    <a href="{{ route('admin.maternity.pregnancies.antenatal.create', $profile) }}" class="btn btn-sm btn-primary"><i class="ti ti-plus me-1"></i>{{ __('maternity.record_anc_visit') }}</a>
                    @endcan
                    @can('maternity.anc.view')
                    <a href="{{ route('admin.maternity.pregnancies.antenatal.index', $profile) }}" class="btn btn-sm btn-outline-primary">{{ __('maternity.anc_history') }}</a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                @php($anc = $overview['anc'])
                <div class="row g-3">
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.anc_visit_count') }}</small><strong>{{ $anc['visit_count'] }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.latest_anc_visit') }}</small><strong>{{ $anc['latest_visit']?->visit_date?->format('d M Y') ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.next_visit_date') }}</small><strong>{{ $anc['next_visit_date']?->format('d M Y') ?? __('common.none') }}</strong>@if($anc['missed_visit']) <span class="badge bg-danger ms-1">{{ __('maternity.missed_anc_visit') }}</span>@endif</div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.high_risk_anc') }}</small><span class="badge bg-{{ $anc['high_risk'] ? 'danger' : 'success' }}">{{ $anc['high_risk'] ? __('common.yes') : __('common.no') }}</span></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.blood_pressure') }}</small><strong>{{ $anc['latest_visit']?->blood_pressure_systolic && $anc['latest_visit']?->blood_pressure_diastolic ? $anc['latest_visit']->blood_pressure_systolic.'/'.$anc['latest_visit']->blood_pressure_diastolic : __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.fetal_heart_rate') }}</small><strong>{{ $anc['latest_visit']?->fetal_heart_rate ?? __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.fundal_height') }}</small><strong>{{ $anc['latest_visit']?->fundal_height_cm ? $anc['latest_visit']->fundal_height_cm.' cm' : __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.referral') }}</small><span class="badge bg-{{ $anc['pending_referral'] ? 'warning' : 'secondary' }}">{{ $anc['pending_referral'] ? __('maternity.referrals_pending') : __('common.none') }}</span></div>
                </div>
                @if($anc['danger_signs']->isNotEmpty() || $anc['risk_flags']->isNotEmpty())
                <hr>
                <div class="mb-2">@foreach($anc['danger_signs'] as $sign)<span class="badge bg-danger me-1">{{ __('maternity.anc_danger_signs.'.$sign) }}</span>@endforeach</div>
                <div>@foreach($anc['risk_flags'] as $flag)<span class="badge bg-warning text-dark me-1">{{ __('maternity.anc_risk_flags.'.$flag) }}</span>@endforeach</div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ti ti-baby-carriage me-1"></i>{{ __('maternity.labor_and_delivery') }}</h5>
                <div class="d-flex gap-2">
                    @can('maternity.labor.start')
                    <a href="{{ route('admin.maternity.pregnancies.labor.create', $profile) }}" class="btn btn-sm btn-primary"><i class="ti ti-plus me-1"></i>{{ __('maternity.start_labor_episode') }}</a>
                    @endcan
                    @can('maternity.labor.view')
                    <a href="{{ route('admin.maternity.labor.index') }}" class="btn btn-sm btn-outline-primary">{{ __('maternity.labor_episodes') }}</a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                @php($labor = $overview['labor'])
                <div class="row g-3">
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.active_labor_episode') }}</small>
                        @if($labor['active_episode'])
                        <a href="{{ route('admin.maternity.labor.show', $labor['active_episode']) }}">{{ $labor['active_episode']->status?->label() }}</a>
                        @else
                        <strong>{{ __('common.none') }}</strong>
                        @endif
                    </div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.labor_history') }}</small><strong>{{ $labor['episode_count'] }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.latest_labor_stage') }}</small><strong>{{ $labor['latest_episode']?->labor_stage?->label() ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.delivery_record_status') }}</small><strong>{{ $labor['latest_delivery_record']?->status?->label() ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.latest_observation') }}</small><strong>{{ $labor['latest_observation']?->observed_at?->format('d M Y H:i') ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.fetal_heart_rate') }}</small><strong>{{ $labor['latest_observation']?->fetal_heart_rate ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.blood_pressure') }}</small><strong>{{ $labor['latest_observation']?->blood_pressure_systolic && $labor['latest_observation']?->blood_pressure_diastolic ? $labor['latest_observation']->blood_pressure_systolic.'/'.$labor['latest_observation']->blood_pressure_diastolic : __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.newborn_records_pending') }}</small><span class="badge bg-{{ $labor['newborn_records_pending'] ? 'warning' : 'secondary' }}">{{ $labor['newborn_records_pending'] ? __('common.yes') : __('common.no') }}</span></div>
                </div>
                @if($labor['warnings'])
                <hr>
                @foreach($labor['warnings'] as $warning)<span class="badge bg-warning text-dark me-1">{{ $warning }}</span>@endforeach
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-folders me-1"></i>{{ __('maternity.maternity_cases') }}</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light"><tr><th>{{ __('maternity.case_type') }}</th><th>{{ __('maternity.risk_level') }}</th><th>{{ __('maternity.case_status') }}</th><th>{{ __('common.created_by') }}</th><th></th></tr></thead>
                        <tbody>
                            @forelse($profile->maternityCases as $case)
                            <tr>
                                <td>{{ $case->case_type?->label() ?? __('common.not_available') }}</td>
                                <td><span class="badge badge-soft-{{ $case->risk_level?->color() ?? 'secondary' }}">{{ $case->risk_level?->label() ?? __('common.not_available') }}</span></td>
                                <td><span class="badge bg-{{ $case->status?->color() ?? 'secondary' }}">{{ $case->status?->label() }}</span></td>
                                <td>{{ $case->openedBy?->name ?? __('common.not_available') }}</td>
                                <td class="text-end"><a href="{{ route('admin.maternity.cases.show', $case) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></td>
                            </tr>
                            @empty
                            <tr><td colspan="5"><x-empty-state icon="ti-folder-open" :title="__('maternity.no_maternity_cases')" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        @can('maternity.case.create')
        <form method="POST" action="{{ route('admin.maternity.cases.store') }}" class="card mb-3">
            @csrf
            <input type="hidden" name="pregnancy_profile_id" value="{{ $profile->id }}">
            <input type="hidden" name="patient_id" value="{{ $profile->patient_id }}">
            <input type="hidden" name="visit_id" value="{{ $profile->visit_id }}">
            <input type="hidden" name="admission_id" value="{{ $profile->admission_id }}">
            <input type="hidden" name="department_id" value="{{ $profile->department_id }}">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-plus me-1"></i>{{ __('maternity.open_maternity_case') }}</h5></div>
            <div class="card-body">
                <div class="mb-3"><label class="form-label">{{ __('maternity.case_type') }}</label><select name="case_type" class="form-select"><option value="pregnancy_profile">{{ __('maternity.case_types.pregnancy_profile') }}</option><option value="antenatal">{{ __('maternity.case_types.antenatal') }}</option><option value="maternity_admission">{{ __('maternity.case_types.maternity_admission') }}</option><option value="labor_observation">{{ __('maternity.case_types.labor_observation') }}</option></select></div>
                <div class="mb-3"><label class="form-label">{{ __('maternity.risk_level') }}</label><select name="risk_level" class="form-select"><option value="low">{{ __('maternity.risk_levels.low') }}</option><option value="moderate">{{ __('maternity.risk_levels.moderate') }}</option><option value="high" @selected($profile->profile_status?->value === 'high_risk')>{{ __('maternity.risk_levels.high') }}</option><option value="emergency">{{ __('maternity.risk_levels.emergency') }}</option></select></div>
                <div class="mb-0"><label class="form-label">{{ __('maternity.clinical_summary') }}</label><textarea name="clinical_summary" rows="3" class="form-control"></textarea></div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary"><i class="ti ti-folder-plus me-1"></i>{{ __('maternity.open_case') }}</button></div>
        </form>
        @endcan

        @can('maternity.pregnancy.risk.manage')
        @if($profile->profile_status?->value !== 'high_risk')
        <form method="POST" action="{{ route('admin.maternity.pregnancies.status', $profile) }}" class="card mb-3">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="high_risk">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-alert-triangle me-1"></i>{{ __('maternity.mark_high_risk') }}</h5></div>
            <div class="card-body"><textarea name="reason" rows="3" class="form-control" placeholder="{{ __('maternity.high_risk_reason') }}"></textarea></div>
            <div class="card-footer text-end"><button class="btn btn-warning">{{ __('maternity.mark_high_risk') }}</button></div>
        </form>
        @endif
        @endcan

        @can('maternity.pregnancy.close')
        @if($overview['active_profile'])
        <form method="POST" action="{{ route('admin.maternity.pregnancies.status', $profile) }}" class="card mb-3">
            @csrf
            @method('PATCH')
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-circle-check me-1"></i>{{ __('maternity.close_pregnancy_profile') }}</h5></div>
            <div class="card-body">
                <div class="mb-3"><label class="form-label">{{ __('common.status') }}</label><select name="status" class="form-select"><option value="delivered">{{ __('maternity.profile_statuses.delivered') }}</option><option value="transferred">{{ __('maternity.profile_statuses.transferred') }}</option><option value="closed">{{ __('maternity.profile_statuses.closed') }}</option></select></div>
                <textarea name="reason" rows="3" class="form-control" placeholder="{{ __('common.reason') }}"></textarea>
            </div>
            <div class="card-footer text-end"><button class="btn btn-outline-danger">{{ __('maternity.close_profile') }}</button></div>
        </form>
        @endif
        @endcan

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-clock me-1"></i>{{ __('maternity.future_workflows') }}</h5></div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @foreach($overview['future_panels'] as $panel)
                    <div class="list-group-item px-0">{{ $panel }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
