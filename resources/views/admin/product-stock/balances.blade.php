@extends('layouts.app')

@section('title', 'Stock Balances')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="ti ti-database"></i> Stock Balances</h4>
        <div class="btn-group">
            <a href="{{ route('admin.product-stock.receive.form') }}" class="btn btn-sm btn-success"><i class="ti ti-arrow-down"></i> Receive</a>
            <a href="{{ route('admin.product-stock.transfer.form') }}" class="btn btn-sm btn-primary"><i class="ti ti-transfer"></i> Transfer</a>
            <a href="{{ route('admin.product-stock.adjust.form') }}" class="btn btn-sm btn-warning"><i class="ti ti-adjustments"></i> Adjust</a>
            <a href="{{ route('admin.product-stock.return.form') }}" class="btn btn-sm btn-secondary"><i class="ti ti-arrow-back-up"></i> Return</a>
            <a href="{{ route('admin.product-stock.ledger') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-list"></i> Ledger</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label small mb-1">Location</label>
                <select name="location_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($locations as $l)
                        <option value="{{ $l->id }}" @selected($locationId == $l->id)>
                            {{ $l->name }} @if($l->is_main) (Main) @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Search</label>
                <input name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Name or code">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Product Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($typeOptions as $val => $label)
                        <option value="{{ $val }}" @selected($type === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <label class="form-check me-2">
                    <input type="checkbox" class="form-check-input" name="low_only" value="1" @checked($lowOnly)>
                    <span class="form-check-label">Low only</span>
                </label>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover mb-0">
                <thead>
                    <tr>
                        <th>Code</th><th>Product</th><th>Type</th><th>Unit</th>
                        <th class="text-end">On Hand</th>
                        <th class="text-end">Reorder Level</th>
                        <th>Last Movement</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($balances as $b)
                    @php
                        $low = $b->product && $b->product->reorder_level !== null && $b->quantity_on_hand <= $b->product->reorder_level;
                    @endphp
                    <tr class="{{ $low ? 'table-warning' : '' }}">
                        <td><code>{{ $b->product->code ?? '—' }}</code></td>
                        <td>{{ $b->product->name ?? '—' }}</td>
                        <td><span class="badge bg-secondary">{{ $b->product?->type?->label() ?? '—' }}</span></td>
                        <td>{{ $b->product->unit ?? '' }}</td>
                        <td class="text-end"><strong>{{ rtrim(rtrim(number_format((float)$b->quantity_on_hand, 4, '.', ''), '0'), '.') }}</strong></td>
                        <td class="text-end">{{ $b->product->reorder_level ?? '—' }}</td>
                        <td>{{ optional($b->last_movement_at)->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No balances at this location yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $balances->links() }}</div>
    </div>
</div>
@endsection
