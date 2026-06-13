@extends('layouts.app')

@section('title', __('stock.stock_ledger'))

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="ti ti-list"></i> {{ __('stock.stock_movement_ledger') }}</h4>
        <a href="{{ route('admin.product-stock.balances') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-arrow-left"></i> {{ __('stock.balances') }}</a>
    </div>

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label small mb-1">{{ __('stock.location') }}</label>
                <select name="location_id" class="form-select form-select-sm">
                    <option value="">{{ __('stock.all') }}</option>
                    @foreach($locations as $l)
                        <option value="{{ $l->id }}" @selected(($filters['location_id'] ?? null) == $l->id)>{{ $l->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">{{ __('stock.product') }}</label>
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">{{ __('stock.all') }}</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" @selected(($filters['product_id'] ?? null) == $p->id)>{{ $p->name }} ({{ $p->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">{{ __('stock.type') }}</label>
                <select name="movement_type" class="form-select form-select-sm">
                    <option value="">{{ __('stock.all') }}</option>
                    @foreach($movementTypes as $val => $label)
                        <option value="{{ $val }}" @selected(($filters['movement_type'] ?? null) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">{{ __('stock.from') }}</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $filters['from_date'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">{{ __('stock.to') }}</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $filters['to_date'] ?? '' }}">
            </div>
            <div class="col-12 mt-2 d-flex gap-2">
                <button class="btn btn-sm btn-primary">{{ __('stock.filter') }}</button>
                <a href="{{ route('admin.product-stock.ledger') }}" class="btn btn-sm btn-outline-secondary">{{ __('stock.reset') }}</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('stock.when') }}</th><th>{{ __('stock.product') }}</th><th>{{ __('stock.location') }}</th><th>{{ __('stock.type') }}</th>
                        <th class="text-end">{{ __('stock.qty') }}</th><th>{{ __('stock.direction_label') }}</th>
                        <th>{{ __('stock.batch') }}</th><th>{{ __('stock.expiry') }}</th><th>{{ __('stock.performed_by') }}</th><th>{{ __('stock.notes') }}</th>
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
                    <tr><td colspan="10"><x-empty-state :message="__('stock.no_movements_match')" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $movements->links() }}</div>
    </div>
</div>
@endsection
