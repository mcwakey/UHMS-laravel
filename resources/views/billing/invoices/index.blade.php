@extends('layouts.app')
@section('title', 'Invoices')

@section('content')
<!-- Page Header -->
<div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-file-invoice me-2"></i>Invoices</h4>
    </div>
    <div class="d-flex gap-2">
        @can('invoices.create')
        <a href="{{ route('admin.billing.invoices.create') }}" class="btn btn-primary btn-md">
            <i class="ti ti-plus me-1"></i>New Invoice
        </a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card uhms-stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-soft-primary rounded me-3">
                        <i class="ti ti-file-invoice fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">{{ number_format($stats['total_invoices']) }}</h3>
                        <p class="text-muted mb-0">Total Invoices</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card uhms-stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-soft-warning rounded me-3">
                        <i class="ti ti-clock fs-4 text-warning"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">{{ number_format($stats['pending_invoices']) }}</h3>
                        <p class="text-muted mb-0">Pending</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card uhms-stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-soft-success rounded me-3">
                        <i class="ti ti-currency-dollar fs-4 text-success"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">&#8373;{{ number_format($stats['today_revenue'], 2) }}</h3>
                        <p class="text-muted mb-0">Today's Revenue</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card uhms-stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-lg bg-soft-danger rounded me-3">
                        <i class="ti ti-alert-triangle fs-4 text-danger"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0">&#8373;{{ number_format($stats['outstanding_balance'], 2) }}</h3>
                        <p class="text-muted mb-0">Outstanding</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.billing.invoices.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search invoice #, patient..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach(\App\Enums\InvoiceStatus::cases() as $status)
                    <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                        {{ $status->label() }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Billing Type</label>
                <select name="billing_type" class="form-select form-select-sm">
                    <option value="">All Billing Types</option>
                    @foreach(\App\Enums\BillingType::cases() as $type)
                    <option value="{{ $type->value }}" {{ request('billing_type') === $type->value ? 'selected' : '' }}>
                        {{ $type->label() }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                <a href="{{ route('admin.billing.invoices.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Invoice Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-nowrap mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Invoice #</th>
                        <th>Patient</th>
                        <th>Billing Type</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    @php
                        $invoiceClaim = $invoice->claim;
                        $invoiceInsuranceProviderId = $invoice->visit?->visitInsurance?->insurance_provider_id;
                        $canCreateInsuranceClaim = (float) $invoice->nhis_amount > 0 && ! $invoiceClaim;
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.billing.invoices.show', $invoice) }}" class="fw-medium text-primary">
                                {{ $invoice->invoice_number }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-medium">{{ $invoice->patient->full_name }}</div>
                            <small class="text-muted">{{ $invoice->patient->patient_number }}</small>
                        </td>
                        <td>
                            <span class="badge bg-soft-{{ $invoice->billing_type->color() }}">{{ $invoice->billing_type->label() }}</span>
                        </td>
                        <td class="text-end fw-medium">&#8373;{{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="text-end text-success">&#8373;{{ number_format($invoice->amount_paid, 2) }}</td>
                        <td class="text-end {{ $invoice->balance > 0 ? 'text-danger fw-bold' : '' }}">&#8373;{{ number_format($invoice->balance, 2) }}</td>
                        <td>
                            <span class="badge bg-{{ $invoice->status->color() }}">{{ $invoice->status->label() }}</span>
                        </td>
                        <td>{{ $invoice->created_at->format('d M Y') }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.billing.invoices.show', $invoice) }}">
                                            <i class="ti ti-eye me-1"></i>View
                                        </a>
                                    </li>
                                    @can('claims.view')
                                    @if($invoiceClaim)
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.claims.show', $invoiceClaim) }}">
                                            <i class="ti ti-file-dollar me-1"></i>View Insurance Claim
                                        </a>
                                    </li>
                                    @endif
                                    @endcan
                                    @if($canCreateInsuranceClaim)
                                    @can('claims.create')
                                    <li>
                                        @if($invoiceInsuranceProviderId)
                                        <form method="POST" action="{{ route('admin.claims.store-from-invoice') }}">
                                            @csrf
                                            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                                            <input type="hidden" name="insurance_provider_id" value="{{ $invoiceInsuranceProviderId }}">
                                            <button type="submit" class="dropdown-item">
                                                <i class="ti ti-file-plus me-1"></i>Generate Insurance Claim
                                            </button>
                                        </form>
                                        @else
                                        <a class="dropdown-item" href="{{ route('admin.claims.create', ['invoice_id' => $invoice->id]) }}">
                                            <i class="ti ti-file-plus me-1"></i>Generate Insurance Claim
                                        </a>
                                        @endif
                                    </li>
                                    @endcan
                                    @endif
                                    @if(!in_array($invoice->status, [\App\Enums\InvoiceStatus::PAID, \App\Enums\InvoiceStatus::CANCELLED]))
                                    @can('invoices.edit')
                                    <li>
                                        <form method="POST" action="{{ route('admin.billing.invoices.cancel', $invoice) }}" onsubmit="return confirm('Cancel this invoice?')">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="ti ti-x me-1"></i>Cancel
                                            </button>
                                        </form>
                                    </li>
                                    @endcan
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <div class="text-muted">
                                <i class="ti ti-file-invoice fs-1 d-block mb-2"></i>
                                No invoices found.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($invoices->hasPages())
    <div class="card-footer">
        {{ $invoices->links() }}
    </div>
    @endif
</div>
@endsection
