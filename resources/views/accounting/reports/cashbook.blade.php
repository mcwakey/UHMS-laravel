@extends('layouts.app')
@section('title', 'Cashbook')

@php $money = fn ($n) => number_format((float) $n, 2); @endphp

@section('content')
<x-page-header title="Cashbook / Cash &amp; Bank" icon="ti-cash" description="Cash, bank and mobile-money movements from posted journals." />

<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small mb-1">Account</label>
            <select name="account_id" class="form-select"><option value="">All Cash/Bank</option>@foreach($accounts as $a)<option value="{{ $a->id }}" @selected((string)request('account_id')===(string)$a->id)>{{ $a->code }} {{ $a->name }}</option>@endforeach</select>
        </div>
        <div class="col-md-3"><label class="form-label small mb-1">From</label><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
        <div class="col-md-3"><label class="form-label small mb-1">To</label><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
    </form>
</div></div>

<div class="row g-2 mb-3">
    <div class="col-md-3 col-6"><div class="card h-100"><div class="card-body py-2 text-center"><small class="text-muted d-block">Opening</small><strong>{{ $money($report['opening_balance']) }}</strong></div></div></div>
    <div class="col-md-3 col-6"><div class="card h-100"><div class="card-body py-2 text-center"><small class="text-muted d-block">Money In</small><strong class="text-success">{{ $money($report['total_in']) }}</strong></div></div></div>
    <div class="col-md-3 col-6"><div class="card h-100"><div class="card-body py-2 text-center"><small class="text-muted d-block">Money Out</small><strong class="text-danger">{{ $money($report['total_out']) }}</strong></div></div></div>
    <div class="col-md-3 col-6"><div class="card h-100 border-primary"><div class="card-body py-2 text-center"><small class="text-muted d-block">Closing</small><strong class="text-primary">{{ $money($report['closing_balance']) }}</strong></div></div></div>
</div>

<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Date</th><th>Journal</th><th>Account</th><th>Description</th><th>Source</th><th class="text-end">In</th><th class="text-end">Out</th><th class="text-end">Balance</th></tr></thead>
        <tbody>
            <tr class="table-light"><td colspan="7" class="fw-semibold">Opening Balance</td><td class="text-end fw-semibold">{{ $money($report['opening_balance']) }}</td></tr>
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
            <tr><td colspan="8" class="text-center text-muted py-3">No cash movements for this period.</td></tr>
        @endforelse
            <tr class="table-light"><td colspan="7" class="fw-bold text-end">Closing Balance</td><td class="text-end fw-bold">{{ $money($report['closing_balance']) }}</td></tr>
        </tbody>
    </table>
</div></div>
@endsection
