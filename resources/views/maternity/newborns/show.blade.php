@extends('layouts.app')
@section('title', __('maternity.newborn_record'))
@section('content')
<x-page-header :title="__('maternity.newborn_record')" :subtitle="$record->mother?->full_name" icon="ti-baby-bottle">
    <x-slot:actions>
        <a href="{{ route('admin.maternity.deliveries.show', $record->deliveryRecord) }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
        @can('maternity.newborn.update')<a href="{{ route('admin.maternity.newborns.edit', $record) }}" class="btn btn-primary btn-md fs-13">{{ __('common.edit') }}</a>@endcan
    </x-slot:actions>
</x-page-header>

@if(! empty($riskAssessment['warnings']))
<div class="alert alert-warning"><strong>{{ __('maternity.risk_assessment') }}</strong><ul class="mb-0">@foreach($riskAssessment['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach</ul></div>
@endif

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between"><h5 class="card-title mb-0">{{ __('maternity.newborn_record') }}</h5><span class="badge bg-{{ $record->status?->color() ?? 'secondary' }}">{{ $record->status?->label() }}</span></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.patient') }}</small><strong>{{ $record->mother?->full_name }}</strong><div class="small text-muted">{{ $record->mother?->patient_number }}</div></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.birth_order') }}</small><strong>{{ $record->birth_order ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.sex') }}</small><strong>{{ $record->sex?->label() ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.birth_time') }}</small><strong>{{ $record->birth_time?->format('d M Y H:i') ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.newborn_outcome') }}</small><strong>{{ $record->outcome?->label() ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.neonatal_condition') }}</small><strong>{{ $record->neonatal_condition?->label() ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.feeding_status') }}</small><strong>{{ $record->feeding_status?->label() ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.breathing_status') }}</small><strong>{{ $record->breathing_status?->label() ?? __('common.none') }}</strong></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ __('maternity.apgar_1_min') }}</small><div class="fs-3 fw-bold">{{ $record->apgar_1_min ?? '—' }}</div></div></div></div>
            <div class="col-md-4"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ __('maternity.apgar_5_min') }}</small><div class="fs-3 fw-bold">{{ $record->apgar_5_min ?? '—' }}</div></div></div></div>
            <div class="col-md-4"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ __('maternity.apgar_10_min') }}</small><div class="fs-3 fw-bold">{{ $record->apgar_10_min ?? '—' }}</div></div></div></div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.birth_outcome') }}</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.birth_weight') }}</small><strong>{{ $record->birth_weight_kg ? $record->birth_weight_kg.' kg' : __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.length') }}</small><strong>{{ $record->length_cm ? $record->length_cm.' cm' : __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.head_circumference') }}</small><strong>{{ $record->head_circumference_cm ? $record->head_circumference_cm.' cm' : __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.temperature') }}</small><strong>{{ $record->temperature ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.cried_at_birth') }}</small><span class="badge bg-secondary">{{ $record->cried_at_birth === null ? __('common.none') : ($record->cried_at_birth ? __('common.yes') : __('common.no')) }}</span></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.resuscitation_required') }}</small><span class="badge bg-{{ $record->resuscitation_required ? 'warning' : 'success' }}">{{ $record->resuscitation_required ? __('common.yes') : __('common.no') }}</span></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.cord_status') }}</small><strong>{{ $record->cord_status?->label() ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.colour') }}</small><strong>{{ $record->colour ?: __('common.none') }}</strong></div>
                    <div class="col-12"><small class="text-muted d-block">{{ __('maternity.resuscitation_details') }}</small>{{ $record->resuscitation_details ?: __('common.none') }}</div>
                    <div class="col-12"><small class="text-muted d-block">{{ __('maternity.congenital_concerns') }}</small>{{ $record->congenital_concerns ?: __('common.none') }}</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.risk_flags') }} / {{ __('maternity.danger_signs') }}</h5></div>
            <div class="card-body">
                <div class="mb-2">@forelse($record->risk_flags ?? [] as $flag)<span class="badge bg-warning text-dark me-1">{{ __('maternity.newborn_risk_flags.'.$flag) }}</span>@empty<span class="text-muted">{{ __('common.none') }}</span>@endforelse</div>
                <div>@forelse($record->danger_signs ?? [] as $sign)<span class="badge bg-danger me-1">{{ __('maternity.newborn_danger_signs.'.$sign) }}</span>@empty<span class="text-muted">{{ __('common.none') }}</span>@endforelse</div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-heart-handshake me-1"></i>{{ __('maternity.postnatal_care') }}</h5></div>
            <div class="card-body">
                @if($postnatalOverview['case'])
                <div class="row g-3">
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.postnatal_case') }}</small><a href="{{ route('admin.maternity.postnatal.show', $postnatalOverview['case']) }}">{{ $postnatalOverview['case']->status?->label() }}</a></div>
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.latest_observation') }}</small><strong>{{ $postnatalOverview['latest_observation']?->observed_at?->format('d M Y H:i') ?? __('common.none') }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.newborn_ready') }}</small><span class="badge bg-{{ $postnatalOverview['ready'] ? 'success' : 'warning' }}">{{ $postnatalOverview['ready'] ? __('common.yes') : __('common.no') }}</span></div>
                </div>
                @if($postnatalOverview['observation_required'])
                <hr>
                @can('maternity.postnatal.newborn.record')<a href="{{ route('admin.maternity.postnatal.newborn-observations.create', [$postnatalOverview['case'], $record]) }}" class="btn btn-sm btn-primary">{{ __('maternity.record_newborn_observation') }}</a>@endcan
                @else
                <div class="text-muted">{{ __('maternity.postnatal_observation_not_required') }}</div>
                @endif
                @else
                <div class="text-muted">{{ __('maternity.no_postnatal_case_yet') }}</div>
                @endif
            </div>
        </div>
        <div class="alert alert-secondary"><i class="ti ti-file-description me-1"></i>{{ __('maternity.newborn_discharge_summary_placeholder') }}</div>
    </div>

    <div class="col-xl-4">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.link_newborn_patient') }}</h5></div>
            <div class="card-body">
                @if($record->newbornPatient)
                <a href="{{ route('admin.patients.show', $record->newbornPatient) }}" class="fw-semibold">{{ $record->newbornPatient->full_name }}</a>
                <div class="text-muted small">{{ $record->newbornPatient->patient_number }}</div>
                @else
                <p class="text-muted">{{ __('common.none') }}</p>
                @can('maternity.newborn.create_patient')
                <form method="POST" action="{{ route('admin.maternity.newborns.create-patient', $record) }}" class="mb-3">@csrf<button class="btn btn-primary w-100">{{ __('maternity.create_newborn_patient') }}</button></form>
                @endcan
                @can('maternity.newborn.link_patient')
                <form method="POST" action="{{ route('admin.maternity.newborns.link-patient', $record) }}">
                    @csrf
                    <label class="form-label">{{ __('maternity.link_newborn_patient') }}</label>
                    <select name="newborn_patient_id" class="form-select mb-2">@foreach($patientCandidates as $patient)<option value="{{ $patient->id }}">{{ $patient->patient_number }} - {{ $patient->full_name }}</option>@endforeach</select>
                    <button class="btn btn-outline-primary w-100">{{ __('common.save') }}</button>
                </form>
                @endcan
                @endif
            </div>
        </div>

        @can('maternity.birth_outcome.manage')
        <form method="POST" action="{{ route('admin.maternity.newborns.status', $record) }}" class="card mb-3">
            @csrf
            @method('PATCH')
            <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.birth_outcome') }}</h5></div>
            <div class="card-body">
                <div class="mb-3"><label class="form-label">{{ __('maternity.status') }}</label><select name="status" class="form-select">@foreach(\App\Enums\NewbornRecordStatus::cases() as $status)<option value="{{ $status->value }}" @selected($record->status?->value === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
                <label class="form-label">{{ __('maternity.newborn_outcome') }}</label><select name="outcome" class="form-select">@foreach(\App\Enums\NewbornOutcome::cases() as $outcome)<option value="{{ $outcome->value }}" @selected($record->outcome?->value === $outcome->value)>{{ $outcome->label() }}</option>@endforeach</select>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary">{{ __('common.update') }}</button></div>
        </form>
        @endcan

        @can('maternity.newborn.close')
        @if(! $record->status?->isClosed())
        <form method="POST" action="{{ route('admin.maternity.newborns.close', $record) }}" class="card">
            @csrf
            @method('PATCH')
            <div class="card-header"><h5 class="card-title mb-0">{{ __('common.close') }}</h5></div>
            <div class="card-body">
                <select name="status" class="form-select mb-3"><option value="closed">{{ __('maternity.newborn_statuses.closed') }}</option><option value="cancelled">{{ __('maternity.newborn_statuses.cancelled') }}</option><option value="discharged">{{ __('maternity.newborn_statuses.discharged') }}</option><option value="deceased">{{ __('maternity.newborn_statuses.deceased') }}</option><option value="transferred">{{ __('maternity.newborn_statuses.transferred') }}</option></select>
                <textarea name="reason" rows="3" class="form-control" placeholder="{{ __('common.reason') }}"></textarea>
            </div>
            <div class="card-footer text-end"><button class="btn btn-outline-danger">{{ __('common.close') }}</button></div>
        </form>
        @endif
        @endcan
    </div>
</div>
@endsection
