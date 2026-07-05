@extends('layouts.app')
@section('title', __('maternity.labor_observation'))
@section('content')
<x-page-header :title="__('maternity.labor_observation')" :subtitle="$observation->patient?->full_name" icon="ti-chart-line">
    <x-slot:actions>
        <a href="{{ route('admin.maternity.labor.show', $observation->laborEpisode) }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
        @can('maternity.labor.observation.update')<a href="{{ route('admin.maternity.labor.observations.edit', $observation) }}" class="btn btn-primary btn-md fs-13">{{ __('common.edit') }}</a>@endcan
    </x-slot:actions>
</x-page-header>
@if(! empty($riskAssessment['warnings']))<div class="alert alert-warning"><strong>{{ __('maternity.risk_assessment') }}</strong><ul class="mb-0">@foreach($riskAssessment['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach</ul></div>@endif
<div class="card">
    <div class="card-header d-flex justify-content-between"><h5 class="card-title mb-0">{{ __('maternity.labor_observation') }}</h5><span class="badge bg-{{ $observation->status?->color() ?? 'secondary' }}">{{ $observation->status?->label() }}</span></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.observed_at') }}</small><strong>{{ $observation->observed_at?->format('d M Y H:i') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.labor_stage') }}</small><strong>{{ $observation->labor_stage?->label() ?? __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.cervical_dilation') }}</small><strong>{{ $observation->cervical_dilation_cm !== null ? $observation->cervical_dilation_cm.' cm' : __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.fetal_heart_rate') }}</small><strong>{{ $observation->fetal_heart_rate ?? __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.blood_pressure') }}</small><strong>{{ $observation->blood_pressure_systolic && $observation->blood_pressure_diastolic ? $observation->blood_pressure_systolic.'/'.$observation->blood_pressure_diastolic : __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.temperature') }}</small><strong>{{ $observation->temperature ?? __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.liquor_colour') }}</small><strong>{{ $observation->liquor_colour?->label() ?? __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.pain_score') }}</small><strong>{{ $observation->pain_score ?? __('common.none') }}</strong></div>
            <div class="col-12"><small class="text-muted d-block">{{ __('maternity.danger_signs') }}</small>@forelse($observation->danger_signs ?? [] as $sign)<span class="badge bg-danger me-1">{{ __('maternity.labor_danger_signs.'.$sign) }}</span>@empty<span class="text-muted">{{ __('common.none') }}</span>@endforelse</div>
            <div class="col-12"><small class="text-muted d-block">{{ __('maternity.risk_flags') }}</small>@forelse($observation->risk_flags ?? [] as $flag)<span class="badge bg-warning text-dark me-1">{{ __('maternity.labor_risk_flags.'.$flag) }}</span>@empty<span class="text-muted">{{ __('common.none') }}</span>@endforelse</div>
            <div class="col-12"><small class="text-muted d-block">{{ __('maternity.notes') }}</small>{{ $observation->notes ?: __('common.none') }}</div>
        </div>
    </div>
</div>
@endsection
