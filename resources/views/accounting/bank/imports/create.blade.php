@extends('layouts.app')
@section('title', __('accounting.import_statement'))

@section('content')
<x-page-header :title="__('accounting.import_statement')" icon="ti-file-import" />

@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.accounting.bank.imports.preview') }}" enctype="multipart/form-data">
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
                <div class="col-md-6">
                    <label class="form-label">{{ __('common.file') ?? 'CSV File' }} <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('accounting.statement_period') }} ({{ __('common.from') ?? 'from' }})</label>
                    <input type="date" name="period_start" class="form-control" value="{{ old('period_start') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('common.to') ?? 'to' }}</label>
                    <input type="date" name="period_end" class="form-control" value="{{ old('period_end') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('accounting.opening_statement_balance') }}</label>
                    <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ old('opening_balance') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('accounting.closing_statement_balance') }}</label>
                    <input type="number" step="0.01" name="closing_balance" class="form-control" value="{{ old('closing_balance') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('common.date_format') ?? 'Date format' }}</label>
                    <input type="text" name="date_format" class="form-control" value="{{ old('date_format', 'Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('accounting.debit_amount') }} / {{ __('accounting.credit_amount') }}</label>
                    <div class="form-text mb-0">{{ __('accounting.csv_no_amount') ? 'Columns: date, reference, description, debit, credit, balance' : '' }}</div>
                </div>
            </div>
            <div class="mt-3 text-end">
                <a href="{{ route('admin.accounting.bank.imports.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
                <button class="btn btn-primary" type="submit"><i class="ti ti-eye me-1"></i>{{ __('accounting.preview_import') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
