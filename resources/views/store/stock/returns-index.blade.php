@extends('layouts.app')
@section('title', 'Stock Returns')

@php use App\Enums\StockMovementDirection; @endphp

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Stock Returns
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">Total: {{ $movements->total() }}</span>
        </h4>
        <small class="text-muted">Stock returned in (patient/ward) or out (to supplier). Click a line to see its full detail.</small>
    </div>
    <div>
        @can('store.purchase.create')
        <a href="{{ route('admin.store.stock.returns.create') }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-plus me-1"></i>New Return</a>
        @endcan
    </div>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.store.stock.returns.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <select name="location_id" class="form-select">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) request('location_id') === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="movement_type" class="form-select">
                    <option value="">All Types</option>
                    @foreach($types as $type)
                        <option value="{{ $type->value }}" @selected(request('movement_type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
            <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
            <div class="col-md-1"><button aria-label="Search" title="Search" class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
            @if(request()->hasAny(['location_id', 'movement_type', 'date_from', 'date_to']))
                <div class="col-md-1"><a aria-label="Reset" title="Reset" href="{{ route('admin.store.stock.returns.index') }}" class="btn btn-outline-secondary w-100"><i class="ti ti-x"></i></a></div>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Location</th>
                    <th>Type</th>
                    <th class="text-end">Quantity</th>
                    <th>By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($movements as $movement)
                @php $isIn = $movement->movement_type->direction() === StockMovementDirection::IN; @endphp
                <tr>
                    <td>{{ $movement->movement_date?->format('d M Y H:i') ?? '-' }}</td>
                    <td><a href="{{ route('admin.store.stock.movements.show', $movement) }}" class="fw-medium text-primary">{{ $movement->product?->name ?? $movement->drug?->name ?? '—' }}</a></td>
                    <td>{{ $movement->location?->name ?? '—' }}</td>
                    <td><span class="badge {{ $isIn ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ $movement->movement_type->label() }}</span></td>
                    <td class="text-end fw-medium {{ $isIn ? 'text-success' : 'text-danger' }}">{{ $isIn ? '+' : '−' }}{{ rtrim(rtrim(number_format((float) $movement->quantity, 4, '.', ''), '0'), '.') }}</td>
                    <td>{{ trim(($movement->performedBy?->first_name ?? '') . ' ' . ($movement->performedBy?->last_name ?? '')) ?: '—' }}</td>
                    <td class="text-end"><a aria-label="View" title="View" href="{{ route('admin.store.stock.movements.show', $movement) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty-state icon="ti-arrow-back-up" title="No returns" message="No stock returns match your filters." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-end">{{ $movements->links() }}</div>
</div>
@endsection
