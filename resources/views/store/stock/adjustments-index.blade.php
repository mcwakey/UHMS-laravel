@extends('layouts.app')
@section('title', __('stock.stock_adjustments'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('stock.stock_adjustments') }}
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">{{ __('stock.total') }}: {{ $batches->total() }}</span>
        </h4>
        <small class="text-muted">{{ __('stock.adjustments_description') }}</small>
    </div>
    <div>
        @can('store.purchase.create')
        <a href="{{ route('admin.store.stock.adjustments.create') }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-plus me-1"></i>{{ __('stock.new_adjustment') }}</a>
        @endcan
    </div>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.store.stock.adjustments.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <select name="location_id" class="form-select">
                    <option value="">{{ __('stock.all_locations') }}</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) request('location_id') === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
            <div class="col-md-3"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
            <div class="col-md-1"><button aria-label="Search" title="Search" class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
            @if(request()->hasAny(['location_id', 'date_from', 'date_to']))
                <div class="col-md-1"><a aria-label="Reset" title="Reset" href="{{ route('admin.store.stock.adjustments.index') }}" class="btn btn-outline-secondary w-100"><i class="ti ti-x"></i></a></div>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('stock.date') }}</th>
                    <th>{{ __('stock.batch_number') }}</th>
                    <th>{{ __('stock.location') }}</th>
                    <th class="text-center">{{ __('stock.items') }}</th>
                    <th>{{ __('stock.reason') }}</th>
                    <th>{{ __('stock.by') }}</th>
                    <th class="text-end">{{ __('stock.actions') ?? 'Actions' }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($batches as $batch)
                <tr role="button" onclick="window.location='{{ route('admin.store.stock.batches.show', $batch) }}'">
                    <td>{{ $batch->created_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td><a href="{{ route('admin.store.stock.batches.show', $batch) }}" class="fw-medium text-primary">{{ $batch->batch_number }}</a></td>
                    <td>{{ $batch->sourceLocation?->name ?? '—' }}</td>
                    <td class="text-center"><span class="badge bg-info-subtle text-info">{{ $batch->movements_count }}</span></td>
                    <td>{{ $batch->reason ?: '—' }}</td>
                    <td>{{ trim(($batch->createdBy?->first_name ?? '') . ' ' . ($batch->createdBy?->last_name ?? '')) ?: '—' }}</td>
                    <td class="text-end"><a aria-label="View" title="View" href="{{ route('admin.store.stock.batches.show', $batch) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty-state icon="ti-adjustments-off" title="{{ __('stock.no_adjustments') }}" message="{{ __('stock.no_adjustments_message') }}" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-end">{{ $batches->links() }}</div>
</div>
@endsection
