@extends('layouts.app')
@section('title', __('reports.discount_report'))

@php $money = fn ($value) => '₵'.number_format((float) ($value ?? 0), 2); @endphp

@section('content')
<x-page-header :title="__('reports.discount_report')" icon="ti-discount-2" />

<div class="row g-3 mb-4">
    <div class="col-md-3"><x-stat-card :title="__('reports.events')" :value="$report['summary']['total_events'] ?? 0" icon="ti-list" variant="primary" /></div>
    <div class="col-md-3"><x-stat-card :title="__('reports.discount_added')" value="{{ $money($report['summary']['total_discount_added'] ?? 0) }}" icon="ti-plus" variant="warning" /></div>
    <div class="col-md-3"><x-stat-card :title="__('reports.discount_removed')" value="{{ $money($report['summary']['total_discount_removed'] ?? 0) }}" icon="ti-minus" variant="info" /></div>
    <div class="col-md-3"><x-stat-card :title="__('reports.overrides')" :value="$report['summary']['override_events'] ?? 0" icon="ti-alert-triangle" variant="danger" /></div>
</div>

<x-filter-bar :action="route('admin.billing.reports.discounts')" :reset-url="route('admin.billing.reports.discounts')">
    <div class="col-md-3"><label class="form-label small">{{ __('common.from') }}</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></div>
    <div class="col-md-3"><label class="form-label small">{{ __('common.to') }}</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div>
    <div class="col-md-3"><label class="form-label small">{{ __('reports.override') }}</label><select name="override" class="form-select"><option value="">{{ __('billing.all') }}</option><option value="1" @selected(($filters['override'] ?? '') === '1')>{{ __('reports.overrides_only') }}</option><option value="0" @selected(($filters['override'] ?? '') === '0')>{{ __('reports.non_overrides_only') }}</option></select></div>
</x-filter-bar>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>{{ __('common.date') }}</th><th>{{ __('common.invoice') }}</th><th>{{ __('common.patient') }}</th><th>{{ __('reports.col_item') }}</th><th class="text-end">{{ __('reports.old') }}</th><th class="text-end">{{ __('reports.new') }}</th><th>{{ __('reports.risk') }}</th><th>{{ __('common.reason') }}</th><th>{{ __('reports.user_staff') }}</th></tr></thead>
        <tbody>
            @forelse($report['events'] ?? [] as $event)
                <tr><td>{{ $event['performed_at'] ?? '' }}</td><td>{{ $event['invoice_number'] ?? '' }}</td><td>{{ $event['patient_name'] ?? '' }}</td><td>{{ $event['item'] ?? '' }}</td><td class="text-end">{{ $money($event['old_discount_amount'] ?? 0) }}</td><td class="text-end">{{ $money($event['new_discount_amount'] ?? 0) }}</td><td><span class="badge bg-{{ !empty($event['is_override']) ? 'danger' : 'warning' }}">{{ !empty($event['is_override']) ? __('reports.override') : __('reports.manual') }}</span></td><td>{{ $event['reason'] ?? '' }}</td><td>{{ $event['performed_by'] ?? '' }}</td></tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">{{ __('reports.no_discount_events_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div></div>
@endsection
