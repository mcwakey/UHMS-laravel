@extends('layouts.app')
@section('title', 'Review Claim ' . $claim->claim_number)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            Review Claim {{ $claim->claim_number }}
            <span class="badge bg-{{ $claim->status->color() }} ms-2">{{ $claim->status->label() }}</span>
        </h4>
        <small class="text-muted">
            Patient: {{ $claim->patient->first_name }} {{ $claim->patient->last_name }} |
            Provider: {{ $claim->insuranceProvider->name }}
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.claims.show', $claim) }}" class="btn btn-outline-secondary btn-md fs-13">
            <i class="ti ti-arrow-left me-1"></i>Back to Claim
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<!-- Review Summary -->
@php
    $pendingCount = $claim->items->where('status', \App\Enums\ClaimItemStatus::PENDING)->count();
    $approvedCount = $claim->items->where('status', \App\Enums\ClaimItemStatus::APPROVED)->count();
    $rejectedCount = $claim->items->where('status', \App\Enums\ClaimItemStatus::REJECTED)->count();
    $totalItems = $claim->items->count();
@endphp

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-start border-4 border-warning">
            <div class="card-body py-2">
                <small class="text-muted">Pending</small>
                <h5 class="mb-0">{{ $pendingCount }} / {{ $totalItems }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-4 border-success">
            <div class="card-body py-2">
                <small class="text-muted">Approved</small>
                <h5 class="mb-0">{{ $approvedCount }} / {{ $totalItems }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-4 border-danger">
            <div class="card-body py-2">
                <small class="text-muted">Rejected</small>
                <h5 class="mb-0">{{ $rejectedCount }} / {{ $totalItems }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-4 border-primary">
            <div class="card-body py-2">
                <small class="text-muted">Claim Total</small>
                <h5 class="mb-0">GH₵ {{ number_format($claim->total_amount, 2) }}</h5>
            </div>
        </div>
    </div>
</div>

<!-- Items Review -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Review Items</h5>
    </div>
    <div class="card-body p-0">
        @foreach($claim->items as $item)
        <div class="border-bottom p-3 {{ $item->status !== \App\Enums\ClaimItemStatus::PENDING ? 'bg-light' : '' }}">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h6 class="mb-1">{{ $item->service_name }}</h6>
                    <small class="text-muted">
                        {{ $item->service_type->label() }} |
                        Qty: {{ $item->quantity }} × GH₵ {{ number_format($item->unit_price, 2) }} =
                        <strong>GH₵ {{ number_format($item->total_price, 2) }}</strong>
                    </small>
                </div>
                <div class="col-md-2 text-center">
                    <span class="badge bg-{{ $item->status->color() }} py-1 px-2">{{ $item->status->label() }}</span>
                    @if($item->approved_amount !== null)
                        <div class="mt-1"><small class="text-success fw-medium">Approved: GH₵ {{ number_format($item->approved_amount, 2) }}</small></div>
                    @endif
                    @if($item->rejection_reason)
                        <div class="mt-1"><small class="text-danger">{{ $item->rejection_reason }}</small></div>
                    @endif
                </div>
                <div class="col-md-6">
                    @if($item->status === \App\Enums\ClaimItemStatus::PENDING)
                    <div class="d-flex gap-2">
                        <!-- Approve Form -->
                        <form method="POST" action="{{ route('admin.claims.review-item', [$claim, $item]) }}" class="d-flex gap-1 flex-grow-1">
                            @csrf
                            <input type="hidden" name="action" value="approve">
                            <input type="number" name="approved_amount" class="form-control form-control-sm" style="width: 140px;"
                                placeholder="Approved amt" step="0.01" min="0" value="{{ $item->total_price }}">
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="ti ti-check me-1"></i>Approve
                            </button>
                        </form>
                        <!-- Reject Form -->
                        <form method="POST" action="{{ route('admin.claims.review-item', [$claim, $item]) }}" class="d-flex gap-1 flex-grow-1">
                            @csrf
                            <input type="hidden" name="action" value="reject">
                            <input type="text" name="rejection_reason" class="form-control form-control-sm"
                                placeholder="Reason for rejection...">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="ti ti-x me-1"></i>Reject
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Complete Review -->
@if($pendingCount === 0 && $totalItems > 0)
<div class="card">
    <div class="card-header bg-success bg-opacity-10">
        <h5 class="card-title mb-0 text-success"><i class="ti ti-check me-1"></i>All Items Reviewed — Complete Review</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.claims.complete-review', $claim) }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Reviewer Notes (Optional)</label>
                <textarea name="reviewer_notes" class="form-control" rows="3" placeholder="Add any notes about this review..."></textarea>
            </div>
            <button type="submit" class="btn btn-success" onclick="return confirm('Complete the review for this claim?')">
                <i class="ti ti-check-double me-1"></i>Complete Review
            </button>
        </form>
    </div>
</div>
@elseif($pendingCount > 0)
<div class="alert alert-warning">
    <i class="ti ti-alert-circle me-1"></i>
    {{ $pendingCount }} item(s) still pending review. Please review all items before completing the review.
</div>
@endif
@endsection
