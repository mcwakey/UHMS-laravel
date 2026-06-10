@extends('layouts.app')
@section('title', 'Profit & Loss')

@php $money = fn ($n) => number_format((float) $n, 2); @endphp

@section('content')
<x-page-header title="Profit &amp; Loss" icon="ti-chart-bar" description="Income statement from posted journal entries.">
    <x-slot:actions>
        <a href="{{ route('admin.accounting.reports.balance-sheet') }}" class="btn btn-outline-secondary btn-sm">Balance Sheet</a>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small mb-1">From</label><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
        <div class="col-md-3"><label class="form-label small mb-1">To</label><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
        <div class="col-md-3"><label class="form-label small mb-1">Department</label>
            <select name="department_id" class="form-select"><option value="">All</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected((string)request('department_id')===(string)$d->id)>{{ $d->name }}</option>@endforeach</select>
        </div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
    </form>
</div></div>

<div class="card"><div class="card-body">
    <div class="table-responsive">
    <table class="table table-sm mb-0">
        <tbody>
        @foreach(['revenue'=>'Revenue'] as $key=>$lbl)
            <tr class="table-light"><th colspan="2">{{ $report['sections'][$key]['label'] }}</th></tr>
            @foreach($report['sections'][$key]['rows'] as $r)<tr><td class="ps-4">{{ $r['code'] }} — {{ $r['name'] }}</td><td class="text-end">{{ $money($r['amount']) }}</td></tr>@endforeach
            <tr><th class="ps-4">Total Revenue</th><th class="text-end">{{ $money($report['revenue_total']) }}</th></tr>
        @endforeach

        <tr class="table-light"><th colspan="2">Cost of Goods Sold</th></tr>
        @foreach($report['sections']['cogs']['rows'] as $r)<tr><td class="ps-4">{{ $r['code'] }} — {{ $r['name'] }}</td><td class="text-end">({{ $money($r['amount']) }})</td></tr>@endforeach
        <tr><th class="ps-4">Total COGS</th><th class="text-end">({{ $money($report['cogs_total']) }})</th></tr>

        <tr class="table-primary"><th>Gross Profit</th><th class="text-end">{{ $money($report['gross_profit']) }}</th></tr>

        @foreach(['operating','admin','finance'] as $key)
            @if(!empty($report['sections'][$key]['rows']))
            <tr class="table-light"><th colspan="2">{{ $report['sections'][$key]['label'] }}</th></tr>
            @foreach($report['sections'][$key]['rows'] as $r)<tr><td class="ps-4">{{ $r['code'] }} — {{ $r['name'] }}</td><td class="text-end">({{ $money($r['amount']) }})</td></tr>@endforeach
            <tr><th class="ps-4">Total {{ $report['sections'][$key]['label'] }}</th><th class="text-end">({{ $money($report['sections'][$key]['total']) }})</th></tr>
            @endif
        @endforeach

        <tr><th>Total Expenses</th><th class="text-end">({{ $money($report['total_expenses']) }})</th></tr>
        <tr class="{{ $report['net_profit'] >= 0 ? 'table-success' : 'table-danger' }}"><th class="fs-6">Net {{ $report['net_profit'] >= 0 ? 'Profit' : 'Loss' }}</th><th class="text-end fs-6">{{ $money($report['net_profit']) }}</th></tr>
        </tbody>
    </table>
    </div>
</div></div>
@endsection
