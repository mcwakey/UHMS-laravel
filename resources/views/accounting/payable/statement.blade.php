@extends('layouts.app')
@section('title', 'Supplier Statement — ' . $supplier->name)

@section('content')
<x-page-header :title="'Statement — ' . $supplier->name" icon="ti-file-text"
    :description="\Illuminate\Support\Carbon::parse($statement['from'])->format('d M Y') . ' to ' . \Illuminate\Support\Carbon::parse($statement['to'])->format('d M Y')">
    <x-slot:actions>
        <a href="{{ route('admin.accounts-payable.payables') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>Payables</a>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small mb-1">From</label><input type="date" name="from" class="form-control" value="{{ $statement['from'] }}"></div>
        <div class="col-md-3"><label class="form-label small mb-1">To</label><input type="date" name="to" class="form-control" value="{{ $statement['to'] }}"></div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
    </form>
</div></div>

<div class="row g-2 mb-3">
    <div class="col-md-2 col-6"><div class="card h-100"><div class="card-body py-2 text-center"><small class="text-muted d-block">Opening</small><strong>{{ number_format($statement['opening_balance'], 2) }}</strong></div></div></div>
    <div class="col-md-2 col-6"><div class="card h-100"><div class="card-body py-2 text-center"><small class="text-muted d-block">Goods/Invoices</small><strong class="text-danger">{{ number_format($statement['totals']['goods'], 2) }}</strong></div></div></div>
    <div class="col-md-2 col-6"><div class="card h-100"><div class="card-body py-2 text-center"><small class="text-muted d-block">Payments</small><strong class="text-success">{{ number_format($statement['totals']['payments'], 2) }}</strong></div></div></div>
    <div class="col-md-2 col-6"><div class="card h-100"><div class="card-body py-2 text-center"><small class="text-muted d-block">Returns</small><strong>{{ number_format($statement['totals']['returns'], 2) }}</strong></div></div></div>
    <div class="col-md-2 col-6"><div class="card h-100"><div class="card-body py-2 text-center"><small class="text-muted d-block">Credit Notes</small><strong>{{ number_format($statement['totals']['credit_notes'], 2) }}</strong></div></div></div>
    <div class="col-md-2 col-6"><div class="card h-100 border-primary"><div class="card-body py-2 text-center"><small class="text-muted d-block">Closing</small><strong class="text-primary">{{ number_format($statement['closing_balance'], 2) }}</strong></div></div></div>
</div>

<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead>
        <tbody>
            <tr class="table-light"><td colspan="5" class="fw-semibold">Opening Balance</td><td class="text-end fw-semibold">{{ number_format($statement['opening_balance'], 2) }}</td></tr>
        @forelse($statement['rows'] as $r)
            <tr>
                <td><small>{{ $r['date'] }}</small></td>
                <td><span class="badge bg-light text-dark border">{{ ucwords(str_replace('_',' ',strtolower($r['type']))) }}</span></td>
                <td>{{ $r['description'] }}</td>
                <td class="text-end">{{ $r['debit'] > 0 ? number_format($r['debit'], 2) : '' }}</td>
                <td class="text-end">{{ $r['credit'] > 0 ? number_format($r['credit'], 2) : '' }}</td>
                <td class="text-end fw-medium">{{ number_format($r['balance'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-3">No ledger movements in this period.</td></tr>
        @endforelse
            <tr class="table-light"><td colspan="5" class="fw-bold text-end">Closing Balance</td><td class="text-end fw-bold">{{ number_format($statement['closing_balance'], 2) }}</td></tr>
        </tbody>
    </table>
</div></div>
@endsection
