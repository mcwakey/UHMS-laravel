@extends('layouts.app')
@section('title', 'Stock Movement Ledger')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Stock Movement Ledger</h4>
        <small class="text-muted">Source of truth — every IN and OUT movement.</small>
    </div>
    <div>
        <a href="{{ route('admin.store.stock.balances') }}" class="btn btn-outline-secondary"><i class="ti ti-list-numbers me-1"></i>Balances</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Drug ID</label>
                <input type="number" name="drug_id" value="{{ request('drug_id') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Location</label>
                <select name="location_id" class="form-select">
                    <option value="">All</option>
                    @foreach($locations as $loc)
                    <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="movement_type" class="form-select">
                    <option value="">All</option>
                    @foreach($types as $t)
                    <option value="{{ $t->value }}" {{ request('movement_type') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">Dir.</label>
                <select name="direction" class="form-select">
                    <option value="">All</option>
                    <option value="in"  {{ request('direction')==='in'  ? 'selected' : '' }}>IN</option>
                    <option value="out" {{ request('direction')==='out' ? 'selected' : '' }}>OUT</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            <div class="col-12 text-end">
                <button class="btn btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                <a href="{{ route('admin.store.stock.ledger') }}" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Drug</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th>Dir.</th>
                        <th class="text-end">Qty</th>
                        <th>Batch / Expiry</th>
                        <th>Source</th>
                        <th>By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                    <tr>
                        <td class="small">{{ $m->movement_date?->format('d M Y H:i') }}</td>
                        <td>{{ $m->drug?->name }}</td>
                        <td>{{ $m->location?->name }}</td>
                        <td><span class="badge bg-light text-dark">{{ $m->movement_type->label() }}</span></td>
                        <td>
                            @if($m->direction->value === 'in')
                            <span class="badge bg-success">IN</span>
                            @else
                            <span class="badge bg-danger">OUT</span>
                            @endif
                        </td>
                        <td class="text-end fw-semibold">{{ rtrim(rtrim(number_format((float)$m->quantity, 4), '0'), '.') }}</td>
                        <td class="small">
                            @if($m->batch_no)<div>{{ $m->batch_no }}</div>@endif
                            @if($m->expiry_date)<div class="text-muted">exp {{ $m->expiry_date->format('d M Y') }}</div>@endif
                            @if(!$m->batch_no && !$m->expiry_date)<span class="text-muted">—</span>@endif
                        </td>
                        <td class="small text-muted">
                            @if($m->source_type){{ class_basename($m->source_type) }}#{{ $m->source_id }}@else —@endif
                        </td>
                        <td class="small">{{ $m->performedBy?->full_name ?? '—' }}</td>
                        <td class="small">{{ $m->notes }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="10"><x-empty-state message="No movements found." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($movements->hasPages())
    <div class="card-footer">{{ $movements->links() }}</div>
    @endif
</div>
@endsection
