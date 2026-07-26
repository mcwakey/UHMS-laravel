@extends('layouts.app')
@section('title', __('maternity.delivery_record'))
@section('content')
<x-page-header :title="__('maternity.delivery_record')" :subtitle="$record->patient?->full_name" icon="ti-confetti">
    <x-slot:actions>
        <a href="{{ route('admin.maternity.labor.show', $record->laborEpisode) }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
        @can('maternity.delivery.update')<a href="{{ route('admin.maternity.deliveries.edit', $record) }}" class="btn btn-primary btn-md fs-13">{{ __('common.edit') }}</a>@endcan
    </x-slot:actions>
</x-page-header>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between"><h5 class="card-title mb-0">{{ __('maternity.delivery_record') }}</h5><span class="badge bg-{{ $record->status?->color() ?? 'secondary' }}">{{ $record->status?->label() }}</span></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.delivery_at') }}</small><strong>{{ $record->delivery_at?->format('d M Y H:i') ?? __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.delivery_mode') }}</small><strong>{{ $record->delivery_mode?->label() ?? __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.delivery_outcome') }}</small><strong>{{ $record->delivery_outcome?->label() ?? __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.newborn_records_pending') }}</small><span class="badge bg-{{ $record->newborn_records_pending ? 'warning' : 'success' }}">{{ $record->newborn_records_pending ? __('common.yes') : __('common.no') }}</span></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.placenta_status') }}</small><strong>{{ $record->placenta_status?->label() ?? __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.estimated_blood_loss') }}</small><strong>{{ $record->estimated_blood_loss_ml !== null ? $record->estimated_blood_loss_ml.' ml' : __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.maternal_condition') }}</small><strong>{{ $record->maternal_condition?->label() ?? __('common.none') }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.newborn_count') }}</small><strong>{{ $record->newborn_count ?? __('common.none') }}</strong></div>
            <div class="col-12"><small class="text-muted d-block">{{ __('maternity.notes') }}</small>{{ $record->notes ?: __('common.none') }}</div>
        </div>
    </div>
    @can('maternity.delivery.complete')
    @if($record->status !== \App\Enums\DeliveryRecordStatus::COMPLETED)
    <div class="card-footer text-end">
        <form method="POST" action="{{ route('admin.maternity.deliveries.complete', $record) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-success">{{ __('maternity.complete_delivery_record') }}</button></form>
    </div>
    @endif
    @endcan
</div>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="ti ti-baby-bottle me-1"></i>{{ __('maternity.newborn_records') }}</h5>
        <div class="d-flex gap-2">
            @can('maternity.newborn.record')
            <form method="POST" action="{{ route('admin.maternity.deliveries.newborns.bulk-create', $record) }}">@csrf<button class="btn btn-sm btn-outline-primary">{{ __('maternity.bulk_create_newborn_records') }}</button></form>
            <a href="{{ route('admin.maternity.deliveries.newborns.create', $record) }}" class="btn btn-sm btn-primary"><i class="ti ti-plus me-1"></i>{{ __('maternity.create_newborn_record') }}</a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.expected_newborn_count') }}</small><strong>{{ $newbornOverview['expected_count'] }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.recorded_newborn_count') }}</small><strong>{{ $newbornOverview['recorded_count'] }}</strong></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.newborn_records_pending') }}</small><span class="badge bg-{{ $record->newborn_records_pending ? 'warning' : 'success' }}">{{ $record->newborn_records_pending ? __('common.yes') : __('common.no') }}</span></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.newborn_records_complete') }}</small><span class="badge bg-{{ $newbornOverview['complete'] ? 'success' : 'warning' }}">{{ $newbornOverview['complete'] ? __('common.yes') : __('common.no') }}</span></div>
        </div>
        @if($newbornOverview['missing_count'] > 0)<div class="alert alert-warning py-2">{{ __('maternity.missing_newborn_records_warning') }}</div>@endif
        @if($newbornOverview['extra_count'] > 0)<div class="alert alert-info py-2">{{ __('maternity.extra_newborn_records_warning') }}</div>@endif
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>{{ __('maternity.birth_order') }}</th><th>{{ __('maternity.sex') }}</th><th>{{ __('maternity.birth_weight') }}</th><th>{{ __('maternity.apgar_5_min') }}</th><th>{{ __('maternity.newborn_outcome') }}</th><th>{{ __('maternity.status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($newbornOverview['records'] as $newborn)
                    <tr>
                        <td>{{ $newborn->birth_order }}</td><td>{{ $newborn->sex?->label() ?? __('common.none') }}</td><td>{{ $newborn->birth_weight_kg ? $newborn->birth_weight_kg.' kg' : __('common.none') }}</td><td>{{ $newborn->apgar_5_min ?? '—' }}</td><td>{{ $newborn->outcome?->label() ?? __('common.none') }}</td><td><span class="badge bg-{{ $newborn->status?->color() ?? 'secondary' }}">{{ $newborn->status?->label() }}</span></td><td class="text-end"><a href="{{ route('admin.maternity.newborns.show', $newborn) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="7"><x-empty-state icon="ti-baby-bottle" :title="__('maternity.no_newborn_records_yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <a href="{{ route('admin.maternity.deliveries.newborns.index', $record) }}" class="btn btn-outline-primary">{{ __('maternity.newborn_records') }}</a>
        @if($postnatalOverview['case'])
        <a href="{{ route('admin.maternity.postnatal.show', $postnatalOverview['case']) }}" class="btn btn-outline-success">{{ __('maternity.postnatal_case') }}</a>
        @else
        @can('maternity.postnatal.open')
        <form method="POST" action="{{ route('admin.maternity.deliveries.postnatal.store', $record) }}">@csrf<button class="btn btn-success">{{ __('maternity.open_postnatal_care') }}</button></form>
        @else
        <span class="text-muted">{{ __('maternity.no_postnatal_case_yet') }}</span>
        @endcan
        @endif
    </div>
</div>
<div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-heart-handshake me-1"></i>{{ __('maternity.postnatal_care') }}</h5></div>
    <div class="card-body">
        @if($postnatalOverview['case'])
        @php($postnatalCase = $postnatalOverview['case'])
        <div class="row g-3">
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.status') }}</small><span class="badge bg-{{ $postnatalCase->status?->color() ?? 'secondary' }}">{{ $postnatalCase->status?->label() }}</span></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.mother_ready') }}</small><span class="badge bg-{{ $postnatalOverview['mother_ready'] ? 'success' : 'warning' }}">{{ $postnatalOverview['mother_ready'] ? __('common.yes') : __('common.no') }}</span></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.newborn_ready') }}</small><span class="badge bg-{{ $postnatalOverview['newborn_ready'] ? 'success' : 'warning' }}">{{ $postnatalOverview['newborn_ready'] ? __('common.yes') : __('common.no') }}</span></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.latest_mother_observation') }}</small><strong>{{ $postnatalOverview['latest_mother_observation']?->observed_at?->format('d M Y H:i') ?? __('common.none') }}</strong></div>
        </div>
        @else
        <x-empty-state icon="ti-heart-handshake" :title="__('maternity.no_postnatal_case_yet')" />
        @endif
    </div>
</div>
@include('maternity.partials.billing-preview')

{{-- Phase 14R.5.1 — explicit Emergency escalation (dark by default). --}}
@include('maternity.partials.emergency-handoff-panel', [
    'actions' => $emergencyHandoffActions ?? [],
])

@endsection
