@extends('layouts.app')
@section('title', __('maternity.newborn_records'))
@section('content')
<x-page-header :title="__('maternity.newborn_records')" :subtitle="$delivery->patient?->full_name" icon="ti-baby-bottle">
    <x-slot:actions>
        <a href="{{ route('admin.maternity.deliveries.show', $delivery) }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
        @can('maternity.newborn.record')<a href="{{ route('admin.maternity.deliveries.newborns.create', $delivery) }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-plus me-1"></i>{{ __('maternity.create_newborn_record') }}</a>@endcan
    </x-slot:actions>
</x-page-header>
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">{{ __('maternity.expected_newborn_count') }}</small><div class="fs-3 fw-bold">{{ $newbornOverview['expected_count'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">{{ __('maternity.recorded_newborn_count') }}</small><div class="fs-3 fw-bold">{{ $newbornOverview['recorded_count'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">{{ __('maternity.resuscitation_required') }}</small><div class="fs-3 fw-bold">{{ $newbornOverview['resuscitation_count'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">{{ __('maternity.newborn_records_complete') }}</small><div><span class="badge bg-{{ $newbornOverview['complete'] ? 'success' : 'warning' }}">{{ $newbornOverview['complete'] ? __('common.yes') : __('common.no') }}</span></div></div></div></div>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>{{ __('maternity.birth_order') }}</th><th>{{ __('maternity.sex') }}</th><th>{{ __('maternity.birth_time') }}</th><th>{{ __('maternity.birth_weight') }}</th><th>{{ __('maternity.newborn_outcome') }}</th><th>{{ __('maternity.status') }}</th><th></th></tr></thead>
                <tbody>
                @forelse($newbornOverview['records'] as $record)
                    <tr>
                        <td>{{ $record->birth_order }}</td><td>{{ $record->sex?->label() ?? __('common.none') }}</td><td>{{ $record->birth_time?->format('d M Y H:i') ?? __('common.none') }}</td><td>{{ $record->birth_weight_kg ? $record->birth_weight_kg.' kg' : __('common.none') }}</td><td>{{ $record->outcome?->label() ?? __('common.none') }}</td><td><span class="badge bg-{{ $record->status?->color() ?? 'secondary' }}">{{ $record->status?->label() }}</span></td><td class="text-end"><a href="{{ route('admin.maternity.newborns.show', $record) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="ti-baby-bottle" :title="__('maternity.no_newborn_records_yet')" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
