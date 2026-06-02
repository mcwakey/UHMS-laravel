@extends('layouts.app')
@section('title', 'Stock Transfers')

@section('content')
<x-page-header title="Stock Transfers" icon="ti-arrows-exchange">
    <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">Total: {{ $transfers->total() }}</span>
    <x-slot:actions>
        @can('store.transfer.create')
        <a href="{{ route('admin.store.transfers.create') }}" class="btn btn-primary btn-md fs-13">
            <i class="ti ti-plus me-1"></i>New Transfer
        </a>
        @endcan
    </x-slot:actions>
</x-page-header>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<!-- Stats Cards -->
<div class="row mb-3">
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-warning bg-opacity-10 rounded me-3">
                        <i class="ti ti-clock fs-4 text-warning"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $stats['pending'] ?? 0 }}</h4>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-info bg-opacity-10 rounded me-3">
                        <i class="ti ti-check fs-4 text-info"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $stats['approved'] ?? 0 }}</h4>
                        <small class="text-muted">Approved</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-success bg-opacity-10 rounded me-3">
                        <i class="ti ti-circle-check fs-4 text-success"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $stats['completed'] ?? 0 }}</h4>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary bg-opacity-10 rounded me-3">
                        <i class="ti ti-transfer fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $stats['this_month'] ?? 0 }}</h4>
                        <small class="text-muted">This Month</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.store.transfers.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search transfer #..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button>
            </div>
            @if(request()->hasAny(['search', 'status', 'date_from']))
            <div class="col-md-1">
                <a href="{{ route('admin.store.transfers.index') }}" class="btn btn-outline-secondary w-100"><i class="ti ti-x"></i></a>
            </div>
            @endif
        </form>
    </div>
</div>

<!-- Transfers Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transfer #</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Date</th>
                        <th class="text-center">Items</th>
                        <th>Transferred By</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $transfer)
                    <tr>
                        <td>
                            <a href="{{ route('admin.store.transfers.show', $transfer) }}" class="fw-medium text-primary">
                                {{ $transfer->transfer_number }}
                            </a>
                        </td>
                        <td><span class="badge bg-{{ $transfer->from_location->color() }}">{{ $transfer->from_location->label() }}</span></td>
                        <td><span class="badge bg-{{ $transfer->to_location->color() }}">{{ $transfer->to_location->label() }}</span></td>
                        <td>{{ $transfer->transfer_date->format('d M Y') }}</td>
                        <td class="text-center"><span class="badge bg-soft-info">{{ $transfer->items_count }}</span></td>
                        <td>{{ $transfer->transferredByUser->name ?? '-' }}</td>
                        <td><x-status-badge :status="$transfer->status" /></td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a href="{{ route('admin.store.transfers.show', $transfer) }}" class="dropdown-item">
                                            <i class="ti ti-eye me-1"></i>View
                                        </a>
                                    </li>
                                    @if($transfer->status === \App\Enums\StockTransferStatus::PENDING)
                                    @can('store.purchase.approve')
                                    <li>
                                        <form method="POST" action="{{ route('admin.store.transfers.approve', $transfer) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="ti ti-check me-1"></i>Approve
                                            </button>
                                        </form>
                                    </li>
                                    @endcan
                                    @endif
                                    @if($transfer->status === \App\Enums\StockTransferStatus::APPROVED)
                                    @can('store.transfer.create')
                                    <li>
                                        <form method="POST" action="{{ route('admin.store.transfers.complete', $transfer) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="ti ti-circle-check me-1"></i>Complete
                                            </button>
                                        </form>
                                    </li>
                                    @endcan
                                    @endif
                                    @if($transfer->is_editable || $transfer->status === \App\Enums\StockTransferStatus::APPROVED)
                                    <li>
                                        <form method="POST" action="{{ route('admin.store.transfers.cancel', $transfer) }}" onsubmit="return confirm('Cancel this transfer?')">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="ti ti-x me-1"></i>Cancel
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <x-empty-state icon="ti-transfer-off" title="No transfers found" message="No stock transfers match your filters." />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($transfers->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $transfers->withQueryString()->links() }}
</div>
@endif
@endsection
