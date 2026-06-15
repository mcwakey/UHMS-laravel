@extends('layouts.app')
@section('title', __('accounting.run_reconciliation'))
@section('content')
<x-page-header :title="__('accounting.run_reconciliation')" :description="__('accounting.run_reconciliation_description')" icon="ti-player-play" />
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('admin.accounting.subledger-reconciliation.store') }}" class="row g-3">
    @csrf
    <div class="col-md-6"><label class="form-label">{{ __('accounting.reconciliation_type') }}</label><select name="reconciliation_type" class="form-select" required>@foreach($types as $type)<option value="{{ $type }}" @selected(old('reconciliation_type') === $type)>{{ __('accounting.reconciliation_type_'.$type) }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label">{{ __('accounting.period_start') }}</label><input type="date" name="period_start" class="form-control" value="{{ old('period_start', now()->startOfMonth()->toDateString()) }}" required></div>
    <div class="col-md-2"><label class="form-label">{{ __('accounting.period_end') }}</label><input type="date" name="period_end" class="form-control" value="{{ old('period_end', now()->endOfMonth()->toDateString()) }}" required></div>
    <div class="col-md-2"><label class="form-label">{{ __('accounting.as_of_date') }}</label><input type="date" name="as_of_date" class="form-control" value="{{ old('as_of_date', now()->toDateString()) }}" required></div>
    <div class="col-md-3"><label class="form-label">{{ __('accounting.tolerance_amount') }}</label><input type="number" step="0.01" min="0" name="tolerance_amount" class="form-control" value="{{ old('tolerance_amount', '0.01') }}" required></div>
    <div class="col-md-9"><label class="form-label">{{ __('accounting.notes') }}</label><textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea></div>
    <div class="col-12"><div class="alert alert-info mb-0"><i class="ti ti-info-circle me-1"></i>{{ __('accounting.reconciliation_non_destructive_notice') }}</div></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-primary"><i class="ti ti-player-play me-1"></i>{{ __('accounting.run_reconciliation') }}</button><a class="btn btn-outline-secondary" href="{{ route('admin.accounting.subledger-reconciliation.index') }}">{{ __('common.cancel') }}</a></div>
</form>
</div></div>
@endsection
