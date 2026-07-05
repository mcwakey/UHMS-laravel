@extends('layouts.app')
@section('title', __('maternity.newborn_observation'))
@section('content')
<x-page-header :title="__('maternity.newborn_observation')" :subtitle="$observation->mother?->full_name" icon="ti-baby-bottle">
    <x-slot:actions>
        <a href="{{ route('admin.maternity.postnatal.show', $observation->postnatalCase) }}" class="btn btn-outline-secondary btn-md fs-13">{{ __('common.back') }}</a>
        @can('maternity.postnatal.newborn.update')<a href="{{ route('admin.maternity.postnatal.newborn-observations.edit', $observation) }}" class="btn btn-primary btn-md fs-13">{{ __('common.edit') }}</a>@endcan
    </x-slot:actions>
</x-page-header>
<div class="card">
    <div class="card-header d-flex justify-content-between"><h5 class="card-title mb-0">{{ __('maternity.newborn_observation') }}</h5><span class="badge bg-{{ $observation->status?->color() ?? 'secondary' }}">{{ $observation->status?->label() }}</span></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.birth_order') }}</small><strong>{{ $observation->newbornRecord?->birth_order }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.observed_at') }}</small><strong>{{ $observation->observed_at?->format('d M Y H:i') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.temperature') }}</small><strong>{{ $observation->temperature ?? __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.weight') }}</small><strong>{{ $observation->weight_kg ? $observation->weight_kg.' kg' : __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.feeding_status') }}</small><strong>{{ $observation->feeding_status?->label() ?? __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.breathing_status') }}</small><strong>{{ $observation->breathing_status?->label() ?? __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.cord_status') }}</small><strong>{{ $observation->cord_status?->label() ?? __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.jaundice_status') }}</small><strong>{{ $observation->jaundice_status?->label() ?? __('common.none') }}</strong></div>
            <div class="col-12"><small class="text-muted d-block">{{ __('maternity.newborn_danger_signs') }}</small>@forelse($observation->danger_signs ?? [] as $sign)<span class="badge bg-danger me-1">{{ __('maternity.postnatal_newborn_danger_signs.'.$sign) }}</span>@empty<span class="text-muted">{{ __('common.none') }}</span>@endforelse</div>
            <div class="col-12"><small class="text-muted d-block">{{ __('maternity.newborn_risk_flags') }}</small>@forelse($observation->risk_flags ?? [] as $flag)<span class="badge bg-warning text-dark me-1">{{ __('maternity.postnatal_newborn_risk_flags.'.$flag) }}</span>@empty<span class="text-muted">{{ __('common.none') }}</span>@endforelse</div>
            <div class="col-12"><small class="text-muted d-block">{{ __('maternity.immunisation_note') }}</small>{{ $observation->immunisation_note ?: __('common.none') }}</div>
            <div class="col-12"><small class="text-muted d-block">{{ __('maternity.assessment') }}</small>{{ $observation->assessment ?: __('common.none') }}</div>
            <div class="col-12"><small class="text-muted d-block">{{ __('maternity.plan') }}</small>{{ $observation->plan ?: __('common.none') }}</div>
            <div class="col-12"><small class="text-muted d-block">{{ __('maternity.counselling') }}</small>{{ $observation->counselling ?: __('common.none') }}</div>
        </div>
    </div>
</div>
@endsection
