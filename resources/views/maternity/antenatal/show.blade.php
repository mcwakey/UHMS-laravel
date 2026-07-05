@extends('layouts.app')
@section('title', __('maternity.anc_visit'))

@section('content')
<x-page-header :title="__('maternity.anc_visit')" :subtitle="$ancVisit->patient?->full_name" icon="ti-stethoscope">
    <x-slot:actions>
        <a href="{{ route('admin.maternity.pregnancies.antenatal.index', $ancVisit->pregnancyProfile) }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('maternity.anc_history') }}</a>
        @can('maternity.anc.update')
        <a href="{{ route('admin.maternity.antenatal.edit', $ancVisit) }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-edit me-1"></i>{{ __('common.edit') }}</a>
        @endcan
    </x-slot:actions>
</x-page-header>

@if(! empty($riskAssessment['warnings']))
<div class="alert alert-warning"><strong><i class="ti ti-alert-triangle me-1"></i>{{ __('maternity.risk_assessment') }}</strong><ul class="mb-0 mt-1">@foreach($riskAssessment['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach</ul></div>
@endif

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center"><h5 class="card-title mb-0"><i class="ti ti-notes me-1"></i>{{ __('maternity.anc_observations') }}</h5><span class="badge bg-{{ $ancVisit->status?->color() ?? 'secondary' }}">{{ $ancVisit->status?->label() }}</span></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.patient') }}</small><strong>{{ $ancVisit->patient?->full_name }}</strong><div class="small text-muted">{{ $ancVisit->patient?->patient_number }}</div></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.visit_date') }}</small><strong>{{ $ancVisit->visit_date?->format('d M Y H:i') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.gestational_age') }}</small><strong>{{ $ancVisit->gestational_age_weeks !== null ? $ancVisit->gestational_age_weeks.'w '.($ancVisit->gestational_age_days ?? 0).'d' : __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.recorded_by') }}</small><strong>{{ $ancVisit->recordedBy?->name ?? __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.blood_pressure') }}</small><strong>{{ $ancVisit->blood_pressure_systolic && $ancVisit->blood_pressure_diastolic ? $ancVisit->blood_pressure_systolic.'/'.$ancVisit->blood_pressure_diastolic : __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.weight') }}</small><strong>{{ $ancVisit->weight_kg ? $ancVisit->weight_kg.' kg' : __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.fundal_height') }}</small><strong>{{ $ancVisit->fundal_height_cm ? $ancVisit->fundal_height_cm.' cm' : __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.fetal_heart_rate') }}</small><strong>{{ $ancVisit->fetal_heart_rate ?: __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.presentation') }}</small><strong>{{ $ancVisit->presentation?->label() ?? __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.urine_protein') }}</small><strong>{{ $ancVisit->urine_protein?->label() ?? __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.urine_glucose') }}</small><strong>{{ $ancVisit->urine_glucose?->label() ?? __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.haemoglobin') }}</small><strong>{{ $ancVisit->haemoglobin ?: __('common.not_available') }}</strong></div>
                    <div class="col-12"><small class="text-muted d-block">{{ __('maternity.assessment') }}</small>{{ $ancVisit->assessment ?: __('common.none') }}</div>
                    <div class="col-12"><small class="text-muted d-block">{{ __('maternity.plan') }}</small>{{ $ancVisit->plan ?: __('common.none') }}</div>
                    <div class="col-12"><small class="text-muted d-block">{{ __('maternity.counselling') }}</small>{{ $ancVisit->counselling ?: __('common.none') }}</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-alert-circle me-1"></i>{{ __('maternity.danger_signs') }} / {{ __('maternity.risk_flags') }}</h5></div>
            <div class="card-body">
                <div class="mb-2">@forelse($ancVisit->danger_signs ?? [] as $sign)<span class="badge bg-danger me-1">{{ __('maternity.anc_danger_signs.'.$sign) }}</span>@empty<span class="text-muted">{{ __('common.none') }}</span>@endforelse</div>
                <div>@forelse($ancVisit->risk_flags ?? [] as $flag)<span class="badge bg-warning text-dark me-1">{{ __('maternity.anc_risk_flags.'.$flag) }}</span>@empty<span class="text-muted">{{ __('maternity.no_risk_flags') }}</span>@endforelse</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-test-pipe me-1"></i>{{ __('maternity.investigation_ultrasound') }}</h5></div>
            <div class="card-body text-muted">{{ __('maternity.investigation_ultrasound_deferred') }}</div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-calendar me-1"></i>{{ __('maternity.next_visit_plan') }}</h5></div>
            <div class="card-body">
                <small class="text-muted d-block">{{ __('maternity.next_visit_date') }}</small><strong>{{ $ancVisit->next_visit_date?->format('d M Y') ?? __('common.none') }}</strong>
                <hr>
                <small class="text-muted d-block">{{ __('maternity.related_case') }}</small>
                @if($ancVisit->maternityCase)<a href="{{ route('admin.maternity.cases.show', $ancVisit->maternityCase) }}">{{ $ancVisit->maternityCase->case_type?->label() }} #{{ $ancVisit->maternityCase->id }}</a>@else{{ __('common.none') }}@endif
            </div>
        </div>

        @can('maternity.labor.start')
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-baby-carriage me-1"></i>{{ __('maternity.labor_and_delivery') }}</h5></div>
            <div class="card-body text-muted">{{ __('maternity.start_labor_episode') }}</div>
            <div class="card-footer text-end"><a href="{{ route('admin.maternity.pregnancies.antenatal.labor.create', [$ancVisit->pregnancyProfile, $ancVisit]) }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>{{ __('maternity.start_labor_episode') }}</a></div>
        </div>
        @endcan

        @can('maternity.anc.referral.create')
        <form method="POST" action="{{ route('admin.maternity.antenatal.referral', $ancVisit) }}" class="card mb-3">
            @csrf
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-share me-1"></i>{{ __('maternity.referral') }}</h5></div>
            <div class="card-body">
                <div class="mb-3"><label class="form-label">{{ __('maternity.referral_type') }}</label><select name="referral_type" class="form-select">@foreach(\App\Enums\AntenatalReferralType::cases() as $type)<option value="{{ $type->value }}" @selected($ancVisit->referral_type?->value === $type->value)>{{ $type->label() }}</option>@endforeach</select></div>
                <label class="form-label">{{ __('maternity.referral_reason') }}</label><textarea name="referral_reason" rows="3" class="form-control">{{ old('referral_reason', $ancVisit->referral_reason) }}</textarea>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary">{{ __('maternity.record_referral') }}</button></div>
        </form>
        @endcan

        @can('maternity.anc.admission.request')
        <form method="POST" action="{{ route('admin.maternity.antenatal.admission-request', $ancVisit) }}" class="card mb-3">
            @csrf
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-bed me-1"></i>{{ __('maternity.create_admission_request') }}</h5></div>
            <div class="card-body">
                <div class="mb-3"><label class="form-label">{{ __('maternity.requested_ward') }}</label><select name="requested_ward_id" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($wards as $ward)<option value="{{ $ward->id }}">{{ $ward->name }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label">{{ __('maternity.priority') }}</label><input name="priority" class="form-control" value="{{ old('priority', 'urgent') }}"></div>
                <div class="mb-3"><label class="form-label">{{ __('maternity.provisional_diagnosis') }}</label><input name="provisional_diagnosis" class="form-control" value="{{ old('provisional_diagnosis', __('maternity.anc_default_admission_diagnosis')) }}"></div>
                <label class="form-label">{{ __('maternity.clinical_summary') }}</label><textarea name="clinical_summary" rows="3" class="form-control">{{ old('clinical_summary', $ancVisit->assessment) }}</textarea>
            </div>
            <div class="card-footer text-end"><button class="btn btn-warning">{{ __('maternity.request_admission') }}</button></div>
        </form>
        @endcan

        @can('maternity.anc.cancel')
        @if(! $ancVisit->status?->isClosed())
        <form method="POST" action="{{ route('admin.maternity.antenatal.cancel', $ancVisit) }}" class="card">
            @csrf
            @method('PATCH')
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-circle-x me-1"></i>{{ __('maternity.cancel_anc_visit') }}</h5></div>
            <div class="card-body"><textarea name="reason" rows="3" class="form-control" placeholder="{{ __('common.reason') }}"></textarea></div>
            <div class="card-footer text-end"><button class="btn btn-outline-danger">{{ __('maternity.cancel_anc_visit') }}</button></div>
        </form>
        @endif
        @endcan
    </div>
</div>
@endsection
