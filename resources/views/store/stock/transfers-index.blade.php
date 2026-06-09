@extends('layouts.app')
@section('title', 'Stock Transfers')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Stock Transfers
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">Total: {{ $batches->total() }}</span>
        </h4>
        <small class="text-muted">One row per transfer batch (Main Store → managed location). Click a row to reveal its line items.</small>
    </div>
    <div>
        @can('store.purchase.create')
        <a href="{{ route('admin.store.stock.transfers.create') }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-plus me-1"></i>New Transfer</a>
        @endcan
    </div>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.store.stock.transfers.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <select name="location_id" class="form-select">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) request('location_id') === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
            <div class="col-md-3"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
            <div class="col-md-1"><button aria-label="Search" title="Search" class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
            @if(request()->hasAny(['location_id', 'date_from', 'date_to']))
                <div class="col-md-1"><a aria-label="Reset" title="Reset" href="{{ route('admin.store.stock.transfers.index') }}" class="btn btn-outline-secondary w-100"><i class="ti ti-x"></i></a></div>
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
                    <th>Batch #</th>
                    <th>From → To</th>
                    <th class="text-center">Items</th>
                    <th>Reason</th>
                    <th>By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($batches as $batch)
                <tr role="button" onclick="window.location='{{ route('admin.store.stock.batches.show', $batch) }}'">
                    <td>{{ $batch->created_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td><a href="{{ route('admin.store.stock.batches.show', $batch) }}" class="fw-medium text-primary">{{ $batch->batch_number }}</a></td>
                    <td>{{ $batch->sourceLocation?->name ?? '—' }} <i class="ti ti-arrow-right text-muted"></i> {{ $batch->destLocation?->name ?? '—' }}</td>
                    <td class="text-center"><span class="badge bg-info-subtle text-info">{{ intdiv($batch->movements_count, 2) }}</span></td>
                    <td>{{ $batch->reason ?: '—' }}</td>
                    <td>{{ trim(($batch->createdBy?->first_name ?? '') . ' ' . ($batch->createdBy?->last_name ?? '')) ?: '—' }}</td>
                    <td class="text-end"><a aria-label="View" title="View" href="{{ route('admin.store.stock.batches.show', $batch) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty-state icon="ti-transfer" title="No transfers" message="No stock transfer batches match your filters." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-end">{{ $batches->links() }}</div>
</div>
@endsection
