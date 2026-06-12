@extends('layouts.app')
@section('title', __('stock.inventory_valuation'))

@php $canCost = auth()->user()?->can('inventory.cost.view') ?? false; @endphp

@section('content')
<x-page-header title="{{ __('stock.inventory_valuation') }}" icon="ti-report-money" description="{{ __('stock.valuation_description') }}">
    <x-slot:actions>
        <a href="{{ route('admin.store.stock.balances') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-list-numbers me-1"></i>{{ __('stock.product_stock_balances') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><input type="text" name="search" class="form-control" placeholder="{{ __('stock.product_name_code') }}" value="{{ request('search') }}"></div>
        <div class="col-md-3">
            <select name="location_id" class="form-select"><option value="">{{ __('stock.all_locations') }}</option>
                @foreach($locations as $l)<option value="{{ $l->id }}" @selected((string)request('location_id')===(string)$l->id)>{{ $l->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="product_type" class="form-select"><option value="">{{ __('stock.all_types') }}</option>
                @foreach($productTypes as $t)<option value="{{ $t->value }}" @selected(request('product_type')===$t->value)>{{ ucwords(str_replace('_',' ',$t->value)) }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
        @if(request()->hasAny(['search','location_id','product_type']))<div class="col-md-1"><a href="{{ route('admin.store.stock.valuation') }}" class="btn btn-outline-secondary w-100"><i class="ti ti-x"></i></a></div>@endif
    </form>
</div></div>

@if($canCost)
<div class="row g-2 mb-3">
    <div class="col-md-3"><div class="card border-primary"><div class="card-body py-2 text-center"><small class="text-muted d-block">{{ __('stock.total_inventory_value') }}</small><strong class="fs-5 text-primary">GHS {{ number_format($report['total_value'], 2) }}</strong><small class="text-muted d-block">{{ $report['count'] }} line(s)</small></div></div></div>
    @foreach(array_slice($report['by_location'], 0, 3, true) as $loc => $val)
        <div class="col-md-3"><div class="card h-100"><div class="card-body py-2 text-center"><small class="text-muted d-block text-truncate">{{ $loc }}</small><strong>GHS {{ number_format($val, 2) }}</strong></div></div></div>
    @endforeach
</div>
@endif

<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr>
            <th>{{ __('stock.code') }}</th><th>{{ __('stock.product') }}</th><th>{{ __('stock.type') }}</th><th>{{ __('stock.location') }}</th><th class="text-end">{{ __('stock.qty_on_hand') }}</th>
            @if($canCost)<th class="text-end">{{ __('stock.avg_cost') }}</th><th class="text-end">{{ __('stock.total_value') }}</th><th>{{ __('stock.inventory_account') }}</th>@endif
            <th>{{ __('stock.last_movement') }}</th>
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
            <tr><td colspan="{{ $canCost ? 9 : 6 }}"><x-empty-state icon="ti-report-money" title="{{ __('stock.no_stock_on_hand') }}" message="{{ __('stock.no_valued_inventory') }}" /></td></tr>
        @endforelse
        </tbody>
        @if($canCost && !empty($report['rows']))
        <tfoot><tr class="table-light"><td colspan="6" class="text-end fw-bold">{{ __('stock.total_inventory_footer') }}</td><td class="text-end fw-bold">{{ number_format($report['total_value'], 2) }}</td><td colspan="2"></td></tr></tfoot>
        @endif
    </table>
</div></div>
@endsection
