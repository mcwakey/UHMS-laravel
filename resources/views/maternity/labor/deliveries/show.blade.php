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
<div class="alert alert-info"><i class="ti ti-baby-bottle me-1"></i>{{ __('maternity.newborn_records_placeholder') }}</div>
@endsection
