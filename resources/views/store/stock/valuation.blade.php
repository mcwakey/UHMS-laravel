@extends('layouts.app')
@section('title', 'Inventory Valuation')

@php $canCost = auth()->user()?->can('inventory.cost.view') ?? false; @endphp

@section('content')
<x-page-header title="Inventory Valuation" icon="ti-report-money" description="On-hand stock valued at weighted-average cost.">
    <x-slot:actions>
        <a href="{{ route('admin.store.stock.balances') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-list-numbers me-1"></i>Stock Balances</a>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><input type="text" name="search" class="form-control" placeholder="Product name/code" value="{{ request('search') }}"></div>
        <div class="col-md-3">
            <select name="location_id" class="form-select"><option value="">All Locations</option>
                @foreach($locations as $l)<option value="{{ $l->id }}" @selected((string)request('location_id')===(string)$l->id)>{{ $l->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="product_type" class="form-select"><option value="">All Types</option>
                @foreach($productTypes as $t)<option value="{{ $t->value }}" @selected(request('product_type')===$t->value)>{{ ucwords(str_replace('_',' ',$t->value)) }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
        @if(request()->hasAny(['search','location_id','product_type']))<div class="col-md-1"><a href="{{ route('admin.store.stock.valuation') }}" class="btn btn-outline-secondary w-100"><i class="ti ti-x"></i></a></div>@endif
    </form>
</div></div>

@if($canCost)
<div class="row g-2 mb-3">
    <div class="col-md-3"><div class="card border-primary"><div class="card-body py-2 text-center"><small class="text-muted d-block">Total Inventory Value</small><strong class="fs-5 text-primary">GHS {{ number_format($report['total_value'], 2) }}</strong><small class="text-muted d-block">{{ $report['count'] }} line(s)</small></div></div></div>
    @foreach(array_slice($report['by_location'], 0, 3, true) as $loc => $val)
        <div class="col-md-3"><div class="card h-100"><div class="card-body py-2 text-center"><small class="text-muted d-block text-truncate">{{ $loc }}</small><strong>GHS {{ number_format($val, 2) }}</strong></div></div></div>
    @endforeach
</div>
@endif

<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr>
            <th>Code</th><th>Product</th><th>Type</th><th>Location</th><th class="text-end">Qty on Hand</th>
            @if($canCost)<th class="text-end">Avg Cost</th><th class="text-end">Total Value</th><th>Inventory Account</th>@endif
            <th>Last Movement</th>
        </tr></thead>
        <tbody>
        @forelse($report['rows'] as $r)
            <tr>
                <td><small>{{ $r['product_code'] ?? '—' }}</small></td>
                <td class="fw-medium">{{ $r['product_name'] }}</td>
                <td><small>{{ ucwords(str_replace('_',' ',$r['product_type'])) }}</small></td>
                <td><small>{{ $r['location'] }}</small></td>
                <td class="text-end">{{ rtrim(rtrim(number_format($r['quantity_on_hand'],4,'.',''),'0'),'.') }}</td>
                @if($canCost)
                    <td class="text-end">{{ number_format($r['average_cost'], 4) }}</td>
                    <td class="text-end fw-semibold">{{ number_format($r['total_value'], 2) }}</td>
                    <td><small>{{ $r['inventory_account'] }}</small></td>
                @endif
                <td><small>{{ $r['last_movement_at'] ?? '—' }}</small></td>
            </tr>
        @empty
            <tr><td colspan="{{ $canCost ? 9 : 6 }}"><x-empty-state icon="ti-report-money" title="No stock on hand" message="No valued inventory matches your filters." /></td></tr>
        @endforelse
        </tbody>
        @if($canCost && !empty($report['rows']))
        <tfoot><tr class="table-light"><td colspan="6" class="text-end fw-bold">Total Inventory Value</td><td class="text-end fw-bold">{{ number_format($report['total_value'], 2) }}</td><td colspan="2"></td></tr></tfoot>
        @endif
    </table>
</div></div>
@endsection
