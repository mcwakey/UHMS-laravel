@extends('layouts.app')

@section('title', 'Stock Ledger')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="ti ti-list"></i> Stock Movement Ledger</h4>
        <a href="{{ route('admin.product-stock.balances') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-arrow-left"></i> Balances</a>
    </div>

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label small mb-1">Location</label>
                <select name="location_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($locations as $l)
                        <option value="{{ $l->id }}" @selected(($filters['location_id'] ?? null) == $l->id)>{{ $l->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Product</label>
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" @selected(($filters['product_id'] ?? null) == $p->id)>{{ $p->name }} ({{ $p->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Type</label>
                <select name="movement_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($movementTypes as $val => $label)
                        <option value="{{ $val }}" @selected(($filters['movement_type'] ?? null) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">From</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $filters['from_date'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">To</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $filters['to_date'] ?? '' }}">
            </div>
            <div class="col-12 mt-2 d-flex gap-2">
                <button class="btn btn-sm btn-primary">Filter</button>
                <a href="{{ route('admin.product-stock.ledger') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>When</th><th>Product</th><th>Location</th><th>Type</th>
                        <th class="text-end">Qty</th><th>Direction</th>
                        <th>Batch</th><th>Expiry</th><th>Performed By</th><th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($movements as $m)
                    <tr>
                        <td><small>{{ $m->movement_date->format('Y-m-d H:i') }}</small></td>
                        <td>{{ $m->product->name ?? '—' }} <br><small class="text-muted">{{ $m->product->code ?? '' }}</small></td>
                        <td>{{ $m->location->name ?? '—' }}</td>
                        <td><span class="badge bg-info text-dark">{{ $m->movement_type->label() }}</span></td>
                        <td class="text-end"><strong>{{ rtrim(rtrim(number_format((float)$m->quantity, 4, '.', ''), '0'), '.') }}</strong></td>
                        <td>
                            @if($m->direction->value === 'in')
                                <span class="badge bg-success">IN</span>
                            @else
                                <span class="badge bg-danger">OUT</span>
                            @endif
                        </td>
                        <td><small>{{ $m->batch_no ?? '—' }}</small></td>
                        <td><small>{{ $m->expiry_date?->format('Y-m-d') ?? '—' }}</small></td>
                        <td><small>{{ $m->performedBy->name ?? '—' }}</small></td>
                        <td><small class="text-muted">{{ \Illuminate\Support\Str::limit($m->notes, 50) }}</small></td>
                    </tr>
                @empty
                    <tr><td colspan="10"><x-empty-state message="No movements match the filters." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $movements->links() }}</div>
    </div>
</div>
@endsection
