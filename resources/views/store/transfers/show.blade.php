@extends('layouts.app')
@section('title', 'Transfer ' . $transfer->transfer_number)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            {{ $transfer->transfer_number }}
            <x-status-badge :status="$transfer->status" class="ms-2" />
        </h4>
    </div>
    <div class="d-flex gap-2">
        @if($transfer->status === \App\Enums\StockTransferStatus::PENDING)
            @can('store.purchase.approve')
            <form method="POST" action="{{ route('admin.store.transfers.approve', $transfer) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success btn-md fs-13" onclick="return confirm('Approve this transfer?')">
                    <i class="ti ti-check me-1"></i>Approve
                </button>
            </form>
            @endcan
        @endif

        @if($transfer->status === \App\Enums\StockTransferStatus::APPROVED)
            @can('store.transfer.create')
            <form method="POST" action="{{ route('admin.store.transfers.complete', $transfer) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary btn-md fs-13" onclick="return confirm('Complete this transfer? Stock will be moved.')">
                    <i class="ti ti-circle-check me-1"></i>Complete Transfer
                </button>
            </form>
            @endcan
        @endif

        @if($transfer->status->canTransitionTo(\App\Enums\StockTransferStatus::CANCELLED))
            <form method="POST" action="{{ route('admin.store.transfers.cancel', $transfer) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-md fs-13" onclick="return confirm('Cancel this transfer?')">
                    <i class="ti ti-x me-1"></i>Cancel
                </button>
            </form>
        @endif

        <a href="{{ route('admin.store.transfers.index') }}" class="btn btn-outline-secondary btn-md fs-13">
            <i class="ti ti-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row">
    <!-- Transfer Info -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Transfer Information</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive"><table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%;">Transfer #</td>
                        <td class="fw-medium">{{ $transfer->transfer_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td><x-status-badge :status="$transfer->status" /></td>
                    </tr>
                    <tr>
                        <td class="text-muted">From</td>
                        <td><x-status-badge :status="$transfer->from_location" /></td>
                    </tr>
                    <tr>
                        <td class="text-muted">To</td>
                        <td><x-status-badge :status="$transfer->to_location" /></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Date</td>
                        <td>{{ $transfer->transfer_date->format('d M Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Transferred By</td>
                        <td>{{ $transfer->transferredByUser->name ?? 'N/A' }}</td>
                    </tr>
                    @if($transfer->approvedByUser)
                    <tr>
                        <td class="text-muted">Approved By</td>
                        <td>{{ $transfer->approvedByUser->name }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted">Created</td>
                        <td>{{ $transfer->created_at->format('d M Y H:i') }}</td>
                    </tr>
                </table></div>
            </div>
        </div>

        @if($transfer->notes)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Notes</h5>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $transfer->notes }}</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Transfer Items -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Transfer Items ({{ $transfer->items->count() }})</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Drug</th>
                                <th class="text-center">Quantity</th>
                                <th>Batch #</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transfer->items as $idx => $item)
                            <tr>
                                <td class="text-muted">{{ $idx + 1 }}</td>
                                <td class="fw-medium">{{ $item->drug->name }}</td>
                                <td class="text-center"><span class="badge bg-soft-primary">{{ $item->quantity }}</span></td>
                                <td>{{ $item->batch_number ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="2" class="text-end">Total Items:</td>
                                <td class="text-center">{{ $transfer->items->sum('quantity') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
