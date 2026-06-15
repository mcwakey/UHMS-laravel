@extends('layouts.app')
@section('title', __('accounting.preview_import'))

@section('content')
<x-page-header :title="__('accounting.preview_import')" icon="ti-file-search" />

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('accounting.statement_lines') }}</div><div class="h4 mb-0">{{ $preview['totals']['count'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('accounting.debit_amount') }}</div><div class="h4 mb-0">&#8373;{{ number_format($preview['totals']['debit'], 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('accounting.credit_amount') }}</div><div class="h4 mb-0">&#8373;{{ number_format($preview['totals']['credit'], 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('common.errors') ?? 'Errors' }}</div><div class="h4 mb-0 text-danger">{{ count($preview['errors']) }}</div></div></div></div>
</div>

@if($preview['duplicate_file'])
<div class="alert alert-danger"><i class="ti ti-alert-triangle me-1"></i>{{ __('accounting.duplicate_statement_file') }}</div>
@endif

@if(count($preview['errors']))
<div class="card mb-3"><div class="card-header"><h6 class="mb-0 text-danger">{{ __('common.errors') ?? 'Errors' }}</h6></div>
<div class="card-body"><ul class="mb-0">@foreach($preview['errors'] as $err)<li>{{ __('common.line') ?? 'Line' }} {{ $err['line'] }}: {{ $err['error'] }}</li>@endforeach</ul></div></div>
@endif

<div class="card">
    <div class="card-header"><h6 class="mb-0">{{ $bankAccount->name }} — {{ __('accounting.statement_lines') }}</h6></div>
    <div class="card-body">
        <div class="table-responsive" style="max-height:420px;overflow:auto;">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light"><tr>
                    <th>{{ __('accounting.transaction_date') }}</th><th>{{ __('accounting.reference') }}</th><th>{{ __('accounting.description') }}</th>
                    <th class="text-end">{{ __('accounting.debit_amount') }}</th><th class="text-end">{{ __('accounting.credit_amount') }}</th><th></th>
                </tr></thead>
                <tbody>
                    @foreach($preview['rows'] as $row)
                    <tr class="{{ !empty($row['is_duplicate']) ? 'table-warning' : '' }}">
                        <td>{{ $row['transaction_date'] }}</td>
                        <td>{{ $row['reference'] }}</td>
                        <td>{{ $row['description'] }}</td>
                        <td class="text-end">@if($row['debit_amount'] > 0)&#8373;{{ number_format($row['debit_amount'], 2) }}@endif</td>
                        <td class="text-end">@if($row['credit_amount'] > 0)&#8373;{{ number_format($row['credit_amount'], 2) }}@endif</td>
                        <td>@if(!empty($row['is_duplicate']))<span class="badge bg-warning">{{ __('accounting.duplicate_line') }}</span>@endif</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @can('accounting.bank_statements.import')
        <form method="POST" action="{{ route('admin.accounting.bank.imports.store') }}" class="mt-3 text-end">
            @csrf
            <input type="hidden" name="bank_account_id" value="{{ $bankAccount->id }}">
            <input type="hidden" name="file_contents" value="{{ $fileContents }}">
            <input type="hidden" name="original_filename" value="{{ $meta['original_filename'] }}">
            <input type="hidden" name="period_start" value="{{ $meta['period_start'] }}">
            <input type="hidden" name="period_end" value="{{ $meta['period_end'] }}">
            <input type="hidden" name="opening_balance" value="{{ $meta['opening_balance'] }}">
            <input type="hidden" name="closing_balance" value="{{ $meta['closing_balance'] }}">
            @foreach($mapping['columns'] as $k => $v)<input type="hidden" name="columns[{{ $k }}]" value="{{ $v }}">@endforeach
            <input type="hidden" name="date_format" value="{{ $mapping['date_format'] }}">
            <input type="hidden" name="amount_sign" value="{{ $mapping['amount_sign'] }}">
            <a href="{{ route('admin.accounting.bank.imports.create') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
            <button class="btn btn-success" type="submit" @disabled($preview['duplicate_file'] || $preview['totals']['count'] === 0)><i class="ti ti-check me-1"></i>{{ __('accounting.confirm_import') }}</button>
        </form>
        @endcan
    </div>
</div>
@endsection
