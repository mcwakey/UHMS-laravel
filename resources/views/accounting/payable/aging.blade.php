@extends('layouts.app')
@section('title', 'AP Aging')

@section('content')
<x-page-header title="Accounts Payable Aging" icon="ti-clock-dollar" :description="'As of ' . \Illuminate\Support\Carbon::parse($report['as_of'])->format('d M Y')">
    <x-slot:actions>
        <a href="{{ route('admin.accounts-payable.payables') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-file-dollar me-1"></i>Payables</a>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small mb-1">As of</label><input type="date" name="as_of" class="form-control" value="{{ $report['as_of'] }}"></div>
        <div class="col-md-4"><label class="form-label small mb-1">Supplier</label>
            <select name="supplier_id" class="form-select"><option value="">All Suppliers</option>
                @foreach($suppliers as $s)<option value="{{ $s->id }}" @selected((string)request('supplier_id')===(string)$s->id)>{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
    </form>
</div></div>

<div class="row g-2 mb-3">
    @foreach($report['buckets'] as $b)
    <div class="col"><div class="card h-100"><div class="card-body py-2 text-center">
        <small class="text-muted d-block">{{ $b['label'] }}</small>
        <strong class="fs-5">{{ number_format($b['total'], 2) }}</strong>
        <small class="text-muted d-block">{{ $b['count'] }} item(s)</small>
    </div></div></div>
    @endforeach
    <div class="col"><div class="card h-100 border-primary"><div class="card-body py-2 text-center">
        <small class="text-muted d-block">Total Payable</small>
        <strong class="fs-5 text-primary">{{ number_format($report['grand_total'], 2) }}</strong>
        <small class="text-muted d-block">{{ $report['grand_count'] }} item(s)</small>
    </div></div></div>
</div>

<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr>
            <th>Supplier</th><th>Reference</th><th class="text-end">Original</th><th class="text-end">Paid</th>
            <th class="text-end">Returns/Credits</th><th class="text-end">Balance</th><th>Due</th><th class="text-end">Age (days)</th><th>Bucket</th>
        </tr></thead>
        <tbody>
        @forelse($report['rows'] as $r)
            <tr @class(['table-warning' => $r['bucket'] === 'b120_plus'])>
                <td><a href="{{ route('admin.accounts-payable.statement', $r['supplier_id']) }}" class="fw-medium text-primary">{{ $r['supplier_name'] }}</a></td>
                <td><small>{{ $r['grn_number'] ?? '—' }}@if($r['po_number']) · {{ $r['po_number'] }}@endif</small></td>
                <td class="text-end">{{ number_format($r['original_amount'], 2) }}</td>
                <td class="text-end">{{ number_format($r['paid_amount'], 2) }}</td>
                <td class="text-end">{{ number_format($r['adjustment_amount'], 2) }}</td>
                <td class="text-end fw-semibold">{{ number_format($r['balance'], 2) }}</td>
                <td><small>{{ $r['due_date'] ?? '—' }}</small></td>
                <td class="text-end">{{ $r['days_overdue'] }}</td>
                <td><small>{{ $report['buckets'][$r['bucket']]['label'] }}</small></td>
            </tr>
        @empty
            <tr><td colspan="9"><x-empty-state icon="ti-clock-dollar" title="No outstanding payables" message="Nothing in AP aging as of this date." /></td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection
