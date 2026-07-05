@extends('layouts.app')
@section('title', __('maternity.postnatal_case'))
@section('content')
<x-page-header :title="__('maternity.postnatal_case')" :subtitle="$case->mother?->full_name" icon="ti-heart-handshake">
    <x-slot:actions>
        <a href="{{ route('admin.maternity.deliveries.show', $case->deliveryRecord) }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
        <a href="{{ route('admin.maternity.postnatal.index') }}" class="btn btn-outline-primary btn-md fs-13">{{ __('maternity.postnatal_care') }}</a>
    </x-slot:actions>
</x-page-header>

@if($overview['danger_signs_count'] > 0)
<div class="alert alert-warning"><i class="ti ti-alert-triangle me-1"></i>{{ __('maternity.postnatal_danger_signs_flagged') }}</div>
@endif

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between"><h5 class="card-title mb-0">{{ __('maternity.postnatal_case') }}</h5><span class="badge bg-{{ $case->status?->color() ?? 'secondary' }}">{{ $case->status?->label() }}</span></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.patient') }}</small><strong>{{ $case->mother?->full_name }}</strong><div class="small text-muted">{{ $case->mother?->patient_number }}</div></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.delivery_at') }}</small><strong>{{ $case->deliveryRecord?->delivery_at?->format('d M Y H:i') ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.linked_admission') }}</small><strong>{{ $case->admission?->admission_number ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.risk_level') }}</small><span class="badge badge-soft-{{ $case->risk_level?->color() ?? 'secondary' }}">{{ $case->risk_level?->label() }}</span></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.mother_ready') }}</small><span class="badge bg-{{ $overview['mother_ready'] ? 'success' : 'warning' }}">{{ $overview['mother_ready'] ? __('common.yes') : __('common.no') }}</span></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.newborn_ready') }}</small><span class="badge bg-{{ $overview['newborn_ready'] ? 'success' : 'warning' }}">{{ $overview['newborn_ready'] ? __('common.yes') : __('common.no') }}</span></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.ready_for_discharge') }}</small><span class="badge bg-{{ $overview['ready_for_discharge'] ? 'success' : 'warning' }}">{{ $overview['ready_for_discharge'] ? __('common.yes') : __('common.no') }}</span></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.referral_required') }}</small><span class="badge bg-{{ $case->referral_required ? 'warning' : 'secondary' }}">{{ $case->referral_required ? __('common.yes') : __('common.no') }}</span></div>
                    <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.follow_up_date') }}</small><strong>{{ $case->follow_up_date?->format('d M Y') ?? __('common.none') }}</strong></div>
                    <div class="col-md-8"><small class="text-muted d-block">{{ __('maternity.follow_up_instructions') }}</small>{{ $case->follow_up_instructions ?: __('common.none') }}</div>
                    <div class="col-12"><small class="text-muted d-block">{{ __('maternity.notes') }}</small>{{ $case->notes ?: __('common.none') }}</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ __('maternity.mother_observations') }}</h5>
                @can('maternity.postnatal.mother.record')<a href="{{ route('admin.maternity.postnatal.mother-observations.create', $case) }}" class="btn btn-sm btn-primary"><i class="ti ti-plus me-1"></i>{{ __('maternity.record_mother_observation') }}</a>@endcan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="bg-light"><tr><th>{{ __('maternity.observed_at') }}</th><th>{{ __('maternity.blood_pressure') }}</th><th>{{ __('maternity.bleeding_status') }}</th><th>{{ __('maternity.status') }}</th><th></th></tr></thead>
                        <tbody>
                            @forelse($case->motherObservations as $observation)
                            <tr>
                                <td>{{ $observation->observed_at?->format('d M Y H:i') }}</td>
                                <td>{{ $observation->blood_pressure_systolic && $observation->blood_pressure_diastolic ? $observation->blood_pressure_systolic.'/'.$observation->blood_pressure_diastolic : __('common.none') }}</td>
                                <td>{{ $observation->bleeding_status?->label() ?? __('common.none') }}</td>
                                <td><span class="badge bg-{{ $observation->status?->color() ?? 'secondary' }}">{{ $observation->status?->label() }}</span></td>
                                <td class="text-end"><a href="{{ route('admin.maternity.postnatal.mother-observations.show', $observation) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></td>
                            </tr>
                            @empty
                            <tr><td colspan="5"><x-empty-state icon="ti-stethoscope" :title="__('maternity.no_mother_observations_yet')" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.newborn_observations') }}</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="bg-light"><tr><th>{{ __('maternity.birth_order') }}</th><th>{{ __('maternity.latest_observation') }}</th><th>{{ __('maternity.feeding_status') }}</th><th>{{ __('maternity.jaundice_status') }}</th><th></th></tr></thead>
                        <tbody>
                            @forelse($overview['newborn_records'] as $newborn)
                            @php($latest = $newborn->latestPostnatalObservation)
                            <tr>
                                <td>{{ $newborn->birth_order }} · {{ $newborn->outcome?->label() }}</td>
                                <td>{{ $latest?->observed_at?->format('d M Y H:i') ?? __('common.none') }}</td>
                                <td>{{ $latest?->feeding_status?->label() ?? __('common.none') }}</td>
                                <td>{{ $latest?->jaundice_status?->label() ?? __('common.none') }}</td>
                                <td class="text-end">
                                    @if($newborn->outcome === \App\Enums\NewbornOutcome::LIVE_BIRTH)
                                    @can('maternity.postnatal.newborn.record')<a href="{{ route('admin.maternity.postnatal.newborn-observations.create', [$case, $newborn]) }}" class="btn btn-sm btn-outline-primary">{{ __('maternity.record_newborn_observation') }}</a>@endcan
                                    @else
                                    <span class="text-muted">{{ __('maternity.postnatal_observation_not_required') }}</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5"><x-empty-state icon="ti-baby-bottle" :title="__('maternity.no_newborn_records_yet')" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @include('maternity.partials.billing-preview')
    </div>

    <div class="col-xl-4">
        @can('maternity.postnatal.update')
        <form method="POST" action="{{ route('admin.maternity.postnatal.update', $case) }}" class="card mb-3">
            @csrf @method('PATCH')
            <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.follow_up_referral') }}</h5></div>
            <div class="card-body">
                <div class="mb-3"><label class="form-label">{{ __('maternity.risk_level') }}</label><select name="risk_level" class="form-select">@foreach($riskLevels as $risk)<option value="{{ $risk->value }}" @selected($case->risk_level?->value === $risk->value)>{{ $risk->label() }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label">{{ __('maternity.follow_up_date') }}</label><input type="date" name="follow_up_date" class="form-control" value="{{ old('follow_up_date', $case->follow_up_date?->format('Y-m-d')) }}"></div>
                <div class="mb-3"><label class="form-label">{{ __('maternity.follow_up_instructions') }}</label><textarea name="follow_up_instructions" rows="3" class="form-control">{{ old('follow_up_instructions', $case->follow_up_instructions) }}</textarea></div>
                <div class="mb-3"><label class="form-label">{{ __('maternity.referral_reason') }}</label><textarea name="referral_reason" rows="3" class="form-control">{{ old('referral_reason', $case->referral_reason) }}</textarea></div>
                <div class="form-check"><input type="checkbox" class="form-check-input" name="referral_required" value="1" id="referral_required" @checked($case->referral_required)><label class="form-check-label" for="referral_required">{{ __('maternity.referral_required') }}</label></div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary">{{ __('common.save') }}</button></div>
        </form>
        @endcan

        @can('maternity.postnatal.discharge.manage')
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.discharge_readiness') }}</h5></div>
            <div class="card-body d-grid gap-2">
                <form method="POST" action="{{ route('admin.maternity.postnatal.status', $case) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="mother_ready"><button class="btn btn-outline-success w-100">{{ __('maternity.mark_mother_ready') }}</button></form>
                <form method="POST" action="{{ route('admin.maternity.postnatal.status', $case) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="newborn_ready"><button class="btn btn-outline-success w-100">{{ __('maternity.mark_newborn_ready') }}</button></form>
                <form method="POST" action="{{ route('admin.maternity.postnatal.status', $case) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="ready_for_discharge"><button class="btn btn-success w-100">{{ __('maternity.mark_ready_for_discharge') }}</button></form>
            </div>
        </div>
        @endcan

        @can('maternity.postnatal.referral.manage')
        <form method="POST" action="{{ route('admin.maternity.postnatal.status', $case) }}" class="card mb-3">
            @csrf @method('PATCH')
            <input type="hidden" name="action" value="referral_required">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.referral_required') }}</h5></div>
            <div class="card-body"><textarea name="referral_reason" rows="3" class="form-control" placeholder="{{ __('maternity.referral_reason') }}">{{ old('referral_reason', $case->referral_reason) }}</textarea></div>
            <div class="card-footer text-end"><button class="btn btn-warning">{{ __('maternity.mark_referral_required') }}</button></div>
        </form>
        @endcan

        @can('maternity.postnatal.close')
        @if(! $case->status?->isClosed())
        <form method="POST" action="{{ route('admin.maternity.postnatal.close', $case) }}" class="card">
            @csrf @method('PATCH')
            <div class="card-header"><h5 class="card-title mb-0">{{ __('common.close') }}</h5></div>
            <div class="card-body"><textarea name="reason" rows="3" class="form-control" placeholder="{{ __('common.reason') }}"></textarea></div>
            <div class="card-footer text-end"><button class="btn btn-outline-danger">{{ __('common.close') }}</button></div>
        </form>
        @endif
        @endcan
    </div>
</div>
@endsection
