@extends('layouts.app')
@section('title', __('accounting.reconciliation_run').' #'.$run->id)
@section('content')
<x-page-header :title="__('accounting.reconciliation_run').' #'.$run->id" :description="__('accounting.reconciliation_type_'.$run->reconciliation_type)" icon="ti-scale">
    <x-slot:actions>
        <a class="btn btn-outline-secondary" href="{{ route('admin.accounting.subledger-reconciliation.index') }}">{{ __('common.back') }}</a>
        @can('accounting.subledger_reconciliation.approve')
        @if($run->status === 'completed')<a class="btn btn-success" href="{{ route('admin.accounting.subledger-reconciliation.approval', $run) }}"><i class="ti ti-check me-1"></i>{{ __('accounting.approve_reconciliation_run') }}</a>@endif
        @endcan
    </x-slot:actions>
</x-page-header>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="row g-3 mb-4">
@foreach([
    [__('accounting.subledger_total'), $run->subledger_total, 'primary'],
    [__('accounting.gl_total'), $run->gl_total, 'dark'],
    [__('accounting.difference_amount'), $run->difference_amount, abs((float)$run->difference_amount) <= (float)$run->tolerance_amount ? 'success' : 'danger'],
] as [$label, $value, $colour])
<div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="fs-3 fw-bold text-{{ $colour }}">{{ number_format((float)$value, 2) }}</div></div></div></div>
@endforeach
<div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ __('accounting.availability') }}</div><div class="fs-5 fw-bold">{{ __('accounting.availability_'.$run->availability()) }}</div><div class="small text-muted">{{ data_get($run->summary_snapshot, 'availability_reason') }}</div></div></div></div>
</div>

<div class="card mb-4"><div class="card-body row g-3">
    <div class="col-md-3"><span class="text-muted">{{ __('common.status') }}</span><div>{{ __('accounting.reconciliation_status_'.$run->status) }}</div></div>
    <div class="col-md-3"><span class="text-muted">{{ __('accounting.period') }}</span><div>{{ $run->period_start->format('d M Y') }} - {{ $run->period_end->format('d M Y') }}</div></div>
    <div class="col-md-2"><span class="text-muted">{{ __('accounting.as_of_date') }}</span><div>{{ $run->as_of_date->format('d M Y') }}</div></div>
    <div class="col-md-2"><span class="text-muted">{{ __('accounting.difference_classification') }}</span><div>{{ __('accounting.classification_'.($run->difference_classification ?? 'balanced')) }}</div></div>
    <div class="col-md-2"><span class="text-muted">{{ __('accounting.started_by') }}</span><div>{{ $run->startedBy?->name ?? '-' }}</div></div>
</div></div>

<div class="card mb-4">
<div class="card-header"><h5 class="mb-0">{{ __('accounting.reconciliation_items') }}</h5></div>
<div class="table-responsive"><table class="table table-hover mb-0">
<thead class="table-light"><tr><th>{{ __('accounting.source_record') }}</th><th>{{ __('accounting.control_account') }}</th><th>{{ __('accounting.subledger_total') }}</th><th>{{ __('accounting.gl_total') }}</th><th>{{ __('accounting.difference_amount') }}</th><th>{{ __('accounting.classification') }}</th><th>{{ __('accounting.resolution_status') }}</th><th></th></tr></thead>
<tbody>@forelse($run->items as $item)<tr>
    <td><div class="fw-semibold">{{ $item->source_reference ?: $item->source_type }}</div><small class="text-muted">{{ Str::limit($item->source_description, 70) }}</small></td>
    <td>{{ $item->glAccount?->display_name ?? '-' }}</td>
    <td>{{ number_format((float)$item->subledger_amount, 2) }}</td><td>{{ number_format((float)$item->gl_amount, 2) }}</td><td class="{{ abs((float)$item->difference_amount) >= 0.01 ? 'text-danger fw-semibold' : '' }}">{{ number_format((float)$item->difference_amount, 2) }}</td>
    <td><span class="badge bg-{{ $item->classification === 'balanced' ? 'success' : ($item->classification === 'not_available' ? 'secondary' : 'warning') }}">{{ __('accounting.classification_'.$item->classification) }}</span></td>
    <td>{{ __('accounting.resolution_status_'.$item->resolution_status) }}</td>
    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.accounting.subledger-reconciliation.items.show', [$run, $item]) }}"><i class="ti ti-eye"></i></a></td>
</tr>@empty<tr><td colspan="8" class="text-center text-muted py-4">{{ __('accounting.no_reconciliation_items') }}</td></tr>@endforelse</tbody>
</table></div></div>

@can('accounting.subledger_reconciliation.cancel')
@if(in_array($run->status, ['draft','running','completed']))
<div class="card border-danger"><div class="card-body"><form method="POST" action="{{ route('admin.accounting.subledger-reconciliation.cancel', $run) }}" class="row g-2 align-items-end">@csrf<div class="col-md-9"><label class="form-label">{{ __('accounting.cancellation_reason') }}</label><input class="form-control" name="cancellation_reason" required></div><div class="col-md-3"><button class="btn btn-outline-danger w-100">{{ __('accounting.cancel_reconciliation_run') }}</button></div></form></div></div>
@endif
@endcan
@endsection
