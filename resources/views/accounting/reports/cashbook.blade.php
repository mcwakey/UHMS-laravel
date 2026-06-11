@extends('layouts.app')
@section('title', __('reports.accounting.cashbook'))

@php $money = fn ($n) => number_format((float) $n, 2); @endphp

@section('content')
<x-page-header title="{{ __('reports.accounting.cashbook') }}" icon="ti-cash" description="{{ __('reports.billing.income_description') }}" />

<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small mb-1">{{ __('reports.filters.account') }}</label>
            <select name="account_id" class="form-select"><option value="">{{ __('reports.filters.all_accounts') }}</option>@foreach($accounts as $a)<option value="{{ $a->id }}" @selected((string)request('account_id')===(string)$a->id)>{{ $a->code }} {{ $a->name }}</option>@endforeach</select>
        </div>
        <div class="col-md-3"><label class="form-label small mb-1">{{ __('common.from') }}</label><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
        <div class="col-md-3"><label class="form-label small mb-1">{{ __('common.to') }}</label><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
    </form>
</div></div>

<div class="row g-2 mb-3">
    <div class="col-md-3 col-6"><div class="card h-100"><div class="card-body py-2 text-center"><small class="text-muted d-block">{{ __('reports.accounting_labels.opening') }}</small><strong>{{ $money($report['opening_balance']) }}</strong></div></div></div>
    <div class="col-md-3 col-6"><div class="card h-100"><div class="card-body py-2 text-center"><small class="text-muted d-block">{{ __('reports.accounting_labels.money_in') }}</small><strong class="text-success">{{ $money($report['total_in']) }}</strong></div></div></div>
    <div class="col-md-3 col-6"><div class="card h-100"><div class="card-body py-2 text-center"><small class="text-muted d-block">{{ __('reports.accounting_labels.money_out') }}</small><strong class="text-danger">{{ $money($report['total_out']) }}</strong></div></div></div>
    <div class="col-md-3 col-6"><div class="card h-100 border-primary"><div class="card-body py-2 text-center"><small class="text-muted d-block">{{ __('reports.accounting_labels.closing') }}</small><strong class="text-primary">{{ $money($report['closing_balance']) }}</strong></div></div></div>
</div>

<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>{{ __('reports.col_date') }}</th><th>{{ __('reports.columns.journal_number') }}</th><th>{{ __('reports.columns.account') }}</th><th>{{ __('reports.columns.description') }}</th><th>{{ __('reports.filters.source') }}</th><th class="text-end">{{ __('reports.columns.in') }}</th><th class="text-end">{{ __('reports.columns.out') }}</th><th class="text-end">{{ __('reports.col_balance') }}</th></tr></thead>
        <tbody>
            <tr class="table-light"><td colspan="7" class="fw-semibold">{{ __('reports.columns.opening_balance') }}</td><td class="text-end fw-semibold">{{ $money($report['opening_balance']) }}</td></tr>
        @forelse($report['rows'] as $r)
            <tr>
                <td><small>{{ $r['date'] }}</small></td>
                <td><small>{{ $r['journal'] }}</small></td>
                <td><small>{{ $r['account'] }}</small></td>
                <td>{{ $r['description'] }}</td>
                <td><small>{{ $r['source'] }}</small></td>
                <td class="text-end">{{ $r['money_in'] > 0 ? $money($r['money_in']) : '' }}</td>
                <td class="text-end">{{ $r['money_out'] > 0 ? $money($r['money_out']) : '' }}</td>
                <td class="text-end fw-medium">{{ $money($r['balance']) }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-3">{{ __('reports.empty.no_cash') }}</td></tr>
        @endforelse
            <tr class="table-light"><td colspan="7" class="fw-bold text-end">{{ __('reports.columns.closing_balance') }}</td><td class="text-end fw-bold">{{ $money($report['closing_balance']) }}</td></tr>
        </tbody>
    </table>
</div></div>
@endsection
