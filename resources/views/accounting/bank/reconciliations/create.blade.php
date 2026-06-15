@extends('layouts.app')
@section('title', __('accounting.prepare_reconciliation'))

@section('content')
<x-page-header :title="__('accounting.prepare_reconciliation')" icon="ti-arrows-diff" />

@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.accounting.bank.reconciliations.prepare') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('accounting.bank_account') }} <span class="text-danger">*</span></label>
                    <select name="bank_account_id" class="form-select" required>
                        <option value="">{{ __('accounting.select') }}</option>
                        @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}" @selected(old('bank_account_id', $selectedBankAccountId) == $acc->id)>{{ $acc->name }} ({{ $acc->bank_name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('accounting.statement_period') }} ({{ __('common.from') ?? 'from' }}) <span class="text-danger">*</span></label>
                    <input type="date" name="period_start" class="form-control" value="{{ old('period_start') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('common.to') ?? 'to' }} <span class="text-danger">*</span></label>
                    <input type="date" name="period_end" class="form-control" value="{{ old('period_end') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('accounting.opening_statement_balance') }}</label>
                    <input type="number" step="0.01" name="statement_opening_balance" class="form-control" value="{{ old('statement_opening_balance') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('accounting.closing_statement_balance') }}</label>
                    <input type="number" step="0.01" name="statement_closing_balance" class="form-control" value="{{ old('statement_closing_balance') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('common.notes') ?? 'Notes' }}</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="mt-3 text-end">
                <a href="{{ route('admin.accounting.bank.reconciliations.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
                <button class="btn btn-primary" type="submit"><i class="ti ti-check me-1"></i>{{ __('accounting.prepare_reconciliation') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
