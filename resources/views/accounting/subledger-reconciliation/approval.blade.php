@extends('layouts.app')
@section('title', __('accounting.approve_reconciliation_run'))
@section('content')
<x-page-header :title="__('accounting.approve_reconciliation_run')" :description="__('accounting.reconciliation_run').' #'.$run->id" icon="ti-check" />
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@php($unresolved = $run->items->where('resolution_status', 'open')->whereNotIn('classification', ['balanced','not_available']))
<div class="card"><div class="card-body">
    <dl class="row"><dt class="col-sm-4">{{ __('accounting.reconciliation_type') }}</dt><dd class="col-sm-8">{{ __('accounting.reconciliation_type_'.$run->reconciliation_type) }}</dd><dt class="col-sm-4">{{ __('accounting.difference_amount') }}</dt><dd class="col-sm-8">{{ number_format((float)$run->difference_amount, 2) }}</dd><dt class="col-sm-4">{{ __('accounting.unresolved_differences') }}</dt><dd class="col-sm-8">{{ $unresolved->count() }}</dd></dl>
    @if($unresolved->isNotEmpty())<div class="alert alert-warning">{{ __('accounting.unresolved_approval_warning') }}</div>@endif
    <form method="POST" action="{{ route('admin.accounting.subledger-reconciliation.approve', $run) }}">@csrf
        @if($unresolved->isNotEmpty())<label class="form-check mb-3"><input type="checkbox" class="form-check-input" name="approve_with_unresolved" value="1" required><span class="form-check-label">{{ __('accounting.confirm_unresolved_approval') }}</span></label>@endif
        <button class="btn btn-success"><i class="ti ti-check me-1"></i>{{ __('accounting.approve_reconciliation_run') }}</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.accounting.subledger-reconciliation.show', $run) }}">{{ __('common.cancel') }}</a>
    </form>
</div></div>
@endsection
