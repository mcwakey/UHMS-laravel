@extends('layouts.app')
@section('title', 'Purchase Return')

@section('content')
<div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-0">{{ $purchaseReturn->return_number }}</h4>
        <div class="text-muted small">{{ $purchaseReturn->supplier?->name }} - {{ $purchaseReturn->return_date?->format('d M Y') }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.store.purchase-returns.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>Back</a>
        @if($purchaseReturn->status === \App\Enums\PurchaseReturnStatus::DRAFT)
            <form method="POST" action="{{ route('admin.store.purchase-returns.approve', $purchaseReturn) }}">@csrf<button class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Approve</button></form>
            <x-confirm-form :action="route('admin.store.purchase-returns.cancel', $purchaseReturn)" method="POST" button-label="Cancel" button-class="btn btn-outline-danger btn-sm" icon="ti-x" confirm-title="Cancel this return?" confirm-text="The purchase return will be cancelled." confirm-button="Yes, cancel" />
        @elseif($purchaseReturn->status === \App\Enums\PurchaseReturnStatus::APPROVED)
            <x-confirm-form :action="route('admin.store.purchase-returns.post', $purchaseReturn)" method="POST" button-label="Post" button-class="btn btn-success btn-sm" icon="ti-upload" confirm-title="Post this return?" confirm-text="This posts the return to stock and the supplier ledger." confirm-button="Yes, post" />
            <x-confirm-form :action="route('admin.store.purchase-returns.cancel', $purchaseReturn)" method="POST" button-label="Cancel" button-class="btn btn-outline-danger btn-sm" icon="ti-x" confirm-title="Cancel this return?" confirm-text="The purchase return will be cancelled." confirm-button="Yes, cancel" />
        @endif
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">Status</small><x-status-badge :status="$purchaseReturn->status" /></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">Return From</small><strong>{{ $purchaseReturn->stockLocation?->name ?? '-' }}</strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">Value</small><strong>GHS {{ number_format((float) $purchaseReturn->total_amount, 2) }}</strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">Purchase Order</small><strong>{{ $purchaseReturn->purchaseOrder?->po_number ?? '-' }}</strong></div></div></div>
</div>

<div class="card">
    <div class="card-header"><h6 class="mb-0">Returned Items</h6></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Product</th><th>Location</th><th>Batch</th><th>Expiry</th><th class="text-end">Qty</th><th class="text-end">Unit Cost</th><th class="text-end">Total</th><th>Movement</th></tr>
            </thead>
            <tbody>
            @foreach($purchaseReturn->items as $item)
                <tr>
                    <td>{{ $item->product?->name ?? '-' }}<br><small class="text-muted">{{ $item->product?->code }}</small></td>
                    <td>{{ $item->stockLocation?->name ?? '-' }}</td>
                    <td>{{ $item->batch_no ?? '-' }}</td>
                    <td>{{ $item->expiry_date?->format('d M Y') ?? '-' }}</td>
                    <td class="text-end">{{ rtrim(rtrim(number_format((float) $item->quantity, 4, '.', ''), '0'), '.') }}</td>
                    <td class="text-end">{{ number_format((float) $item->unit_cost, 2) }}</td>
                    <td class="text-end fw-medium">{{ number_format((float) $item->total_cost, 2) }}</td>
                    <td>{{ $item->stock_movement_id ? '#'.$item->stock_movement_id : '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($purchaseReturn->reason || $purchaseReturn->notes)
<div class="card mt-3"><div class="card-body"><small class="text-muted d-block">Reason / Notes</small>{{ $purchaseReturn->reason }} @if($purchaseReturn->notes)<br>{{ $purchaseReturn->notes }}@endif</div></div>
@endif
@endsection
