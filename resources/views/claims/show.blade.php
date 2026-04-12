@extends('layouts.app')
@section('title', 'Claim ' . $claim->claim_number)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            Claim {{ $claim->claim_number }}
            <span class="badge bg-{{ $claim->status->color() }} ms-2">{{ $claim->status->label() }}</span>
        </h4>
    </div>
    <div class="d-flex gap-2">
        @if($claim->is_editable)
            @can('claims.create')
            <form method="POST" action="{{ route('admin.claims.submit', $claim) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-info btn-md fs-13" onclick="return confirm('Submit this claim for review?')">
                    <i class="ti ti-send me-1"></i>Submit for Review
                </button>
            </form>
            @endcan
        @endif

        @if($claim->is_reviewable)
            @can('claims.approve')
            <a href="{{ route('admin.claims.review', $claim) }}" class="btn btn-warning btn-md fs-13">
                <i class="ti ti-checklist me-1"></i>Review Claim
            </a>
            @endcan
        @endif

        @if($claim->status === \App\Enums\ClaimStatus::APPROVED || $claim->status === \App\Enums\ClaimStatus::PARTIALLY_APPROVED)
            @can('claims.approve')
            <form method="POST" action="{{ route('admin.claims.mark-paid', $claim) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success btn-md fs-13" onclick="return confirm('Mark this claim as paid?')">
                    <i class="ti ti-cash me-1"></i>Mark Paid
                </button>
            </form>
            @endcan
        @endif

        @if($claim->status === \App\Enums\ClaimStatus::REJECTED || $claim->status === \App\Enums\ClaimStatus::PARTIALLY_APPROVED)
            @can('claims.create')
            <form method="POST" action="{{ route('admin.claims.appeal', $claim) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-warning btn-md fs-13" onclick="return confirm('Appeal this claim?')">
                    <i class="ti ti-refresh me-1"></i>Appeal
                </button>
            </form>
            @endcan
        @endif

        <a href="{{ route('admin.claims.index') }}" class="btn btn-outline-secondary btn-md fs-13">
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
    <!-- Claim Info -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Claim Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%;">Claim #</td>
                        <td class="fw-medium">{{ $claim->claim_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td><span class="badge bg-{{ $claim->status->color() }}">{{ $claim->status->label() }}</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Claim Date</td>
                        <td>{{ $claim->claim_date->format('d M Y') }}</td>
                    </tr>
                    @if($claim->period_from)
                    <tr>
                        <td class="text-muted">Period</td>
                        <td>{{ $claim->period_from->format('d M Y') }} — {{ $claim->period_to?->format('d M Y') ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted">Total Amount</td>
                        <td class="fw-bold">GH₵ {{ number_format($claim->total_amount, 2) }}</td>
                    </tr>
                    @if($claim->approved_amount !== null)
                    <tr>
                        <td class="text-muted">Approved</td>
                        <td class="fw-bold text-success">GH₵ {{ number_format($claim->approved_amount, 2) }}</td>
                    </tr>
                    @endif
                    @if($claim->submitted_at)
                    <tr>
                        <td class="text-muted">Submitted</td>
                        <td>{{ $claim->submitted_at->format('d M Y H:i') }}</td>
                    </tr>
                    @endif
                    @if($claim->reviewed_at)
                    <tr>
                        <td class="text-muted">Reviewed</td>
                        <td>{{ $claim->reviewed_at->format('d M Y H:i') }}</td>
                    </tr>
                    @endif
                    @if($claim->reviewer_notes)
                    <tr>
                        <td class="text-muted">Reviewer Notes</td>
                        <td>{{ $claim->reviewer_notes }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted">Created By</td>
                        <td>{{ $claim->createdByUser->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Created</td>
                        <td>{{ $claim->created_at->format('d M Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Provider Info -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Insurance Provider</h5>
            </div>
            <div class="card-body">
                <h6 class="mb-1">{{ $claim->insuranceProvider->name }}</h6>
                <span class="badge bg-{{ $claim->insuranceProvider->type->color() }} mb-2">{{ $claim->insuranceProvider->type->label() }}</span>
                @if($claim->insuranceProvider->contact_phone)
                    <div><small class="text-muted"><i class="ti ti-phone me-1"></i>{{ $claim->insuranceProvider->contact_phone }}</small></div>
                @endif
                @if($claim->insuranceProvider->contact_email)
                    <div><small class="text-muted"><i class="ti ti-mail me-1"></i>{{ $claim->insuranceProvider->contact_email }}</small></div>
                @endif
            </div>
        </div>

        <!-- Patient Info -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Patient</h5>
            </div>
            <div class="card-body">
                <h6 class="mb-1">{{ $claim->patient->first_name }} {{ $claim->patient->last_name }}</h6>
                <div><small class="text-muted">{{ $claim->patient->patient_number }}</small></div>
                @if($claim->patient->nhis_number)
                <div><small class="text-muted">NHIS: {{ $claim->patient->nhis_number }}</small></div>
                @endif
                @if($claim->assignedDoctor)
                <div class="mt-2"><small class="text-muted">Doctor: {{ $claim->assignedDoctor->name }}</small></div>
                @endif
            </div>
        </div>
    </div>

    <!-- Claim Items -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Claim Items ({{ $claim->items->count() }})</h5>
                @if($claim->is_editable)
                    @can('claims.create')
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="ti ti-plus me-1"></i>Add Item
                    </button>
                    @endcan
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Service</th>
                                <th>Type</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Approved</th>
                                <th>Status</th>
                                @if($claim->is_editable)
                                <th class="text-end">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($claim->items as $item)
                            <tr>
                                <td class="fw-medium">{{ $item->service_name }}</td>
                                <td><span class="badge bg-light text-dark">{{ $item->service_type->label() }}</span></td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">GH₵ {{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end">GH₵ {{ number_format($item->total_price, 2) }}</td>
                                <td class="text-end">
                                    @if($item->approved_amount !== null)
                                        GH₵ {{ number_format($item->approved_amount, 2) }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-{{ $item->status->color() }}">{{ $item->status->label() }}</span></td>
                                @if($claim->is_editable)
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.claims.remove-item', $item) }}" class="d-inline" onsubmit="return confirm('Remove this item?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </td>
                                @endif
                            </tr>
                            @if($item->rejection_reason)
                            <tr>
                                <td colspan="{{ $claim->is_editable ? 8 : 7 }}" class="py-1 ps-4">
                                    <small class="text-danger"><i class="ti ti-alert-circle me-1"></i>{{ $item->rejection_reason }}</small>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="4" class="text-end">Total:</td>
                                <td class="text-end">GH₵ {{ number_format($claim->total_amount, 2) }}</td>
                                <td class="text-end">
                                    @if($claim->approved_amount !== null)
                                        GH₵ {{ number_format($claim->approved_amount, 2) }}
                                    @endif
                                </td>
                                <td colspan="{{ $claim->is_editable ? 2 : 1 }}"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        @if($claim->invoice)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Linked Invoice</h5>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.billing.invoices.show', $claim->invoice) }}" class="fw-medium">
                    {{ $claim->invoice->invoice_number }}
                </a>
                <span class="ms-2 text-muted">GH₵ {{ number_format($claim->invoice->total_amount, 2) }}</span>
                <span class="badge bg-{{ $claim->invoice->status->color() }} ms-2">{{ $claim->invoice->status->label() }}</span>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Add Item Modal -->
@if($claim->is_editable)
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.claims.add-item', $claim) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Claim Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="service_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Service Type <span class="text-danger">*</span></label>
                        <select name="service_type" class="form-select">
                            @foreach(\App\Enums\ServiceType::cases() as $st)
                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                            <input type="number" name="unit_price" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
