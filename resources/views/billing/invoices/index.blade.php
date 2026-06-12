@extends('layouts.app')
@section('title', __('billing.invoices'))

@section('content')
<x-page-header :title="__('billing.invoices')" icon="ti-file-invoice">
    <x-slot:actions>
        @can('invoices.create')
        <a href="{{ route('admin.billing.counter-sale.create') }}" class="btn btn-outline-primary btn-md">
            <i class="ti ti-cash-register me-1"></i>{{ __('billing.counter_sale') }}
        </a>
        <a href="{{ route('admin.billing.invoices.create') }}" class="btn btn-primary btn-md">
            <i class="ti ti-plus me-1"></i>{{ __('billing.new_invoice') }}
        </a>
        @endcan
    </x-slot:actions>
</x-page-header>

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
                        <p class="text-muted mb-0">{{ __('billing.total_invoices') }}</p>
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
                        <p class="text-muted mb-0">{{ __('billing.pending') }}</p>
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
                        <p class="text-muted mb-0">{{ __('billing.todays_revenue') }}</p>
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
                        <p class="text-muted mb-0">{{ __('billing.outstanding') }}</p>
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
                <label class="form-label small">{{ __('common.search') }}</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('billing.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">{{ __('common.status') }}</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ __('billing.all_statuses') }}</option>
                    @foreach(\App\Enums\InvoiceStatus::cases() as $status)
                    <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                        {{ $status->translatedLabel() }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">{{ __('billing.billing_type') }}</label>
                <select name="billing_type" class="form-select form-select-sm">
                    <option value="">{{ __('billing.all_billing_types') }}</option>
                    @foreach(\App\Enums\BillingType::cases() as $type)
                    <option value="{{ $type->value }}" {{ request('billing_type') === $type->value ? 'selected' : '' }}>
                        {{ $type->translatedLabel() }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter me-1"></i>{{ __('common.filter') }}</button>
                <a aria-label="Close" title="Close" href="{{ route('admin.billing.invoices.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-x"></i></a>
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
                        <th>{{ __('billing.invoice_number_short') }}</th>
                        <th>{{ __('common.patient') }}</th>
                        <th>{{ __('billing.billing_type') }}</th>
                        <th class="text-end">{{ __('common.total') }}</th>
                        <th class="text-end">{{ __('billing.paid') }}</th>
                        <th class="text-end">{{ __('common.balance') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('common.date') }}</th>
                        <th class="text-center">{{ __('common.actions') }}</th>
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
                            <div class="fw-medium">{{ $invoice->patient?->full_name ?? $invoice->external_party_name ?? '—' }}</div>
                            <small class="text-muted">{{ $invoice->patient?->patient_number ?? __('billing.external_referral') }}</small>
                        </td>
                        <td>
                            <span class="badge bg-soft-{{ $invoice->billing_type->color() }}">{{ $invoice->billing_type->translatedLabel() }}</span>
                        </td>
                        <td class="text-end fw-medium">&#8373;{{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="text-end text-success">&#8373;{{ number_format($invoice->amount_paid, 2) }}</td>
                        <td class="text-end {{ $invoice->balance > 0 ? 'text-danger fw-bold' : '' }}">&#8373;{{ number_format($invoice->balance, 2) }}</td>
                        <td>
                            <x-status-badge :status="$invoice->status" />
                        </td>
                        <td>{{ $invoice->created_at->translatedFormat('d M Y') }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button aria-label="Actions" title="Actions" type="button" class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.billing.invoices.show', $invoice) }}">
                                            <i class="ti ti-eye me-1"></i>{{ __('common.view') }}
                                        </a>
                                    </li>
                                    @can('claims.view')
                                    @if($invoiceClaim)
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.claims.show', $invoiceClaim) }}">
                                            <i class="ti ti-file-dollar me-1"></i>{{ __('billing.view_insurance_claim') }}
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
                                                <i class="ti ti-file-plus me-1"></i>{{ __('billing.generate_insurance_claim') }}
                                            </button>
                                        </form>
                                        @else
                                        <a class="dropdown-item" href="{{ route('admin.claims.create', ['invoice_id' => $invoice->id]) }}">
                                            <i class="ti ti-file-plus me-1"></i>{{ __('billing.generate_insurance_claim') }}
                                        </a>
                                        @endif
                                    </li>
                                    @endcan
                                    @endif
                                    @if(!in_array($invoice->status, [\App\Enums\InvoiceStatus::PAID, \App\Enums\InvoiceStatus::CANCELLED]))
                                    @can('invoices.void')
                                    <li>
                                        <x-confirm-form :action="route('admin.billing.invoices.cancel', $invoice)" method="PATCH"
                                            :button-label="__('common.cancel')" button-class="dropdown-item text-danger" icon="ti-x"
                                            :confirm-title="__('billing.cancel_invoice_title')" :confirm-text="__('billing.cancel_invoice_text')" :confirm-button="__('billing.cancel_invoice_confirm')" />
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
                                {{ __('billing.no_invoices_found') }}
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
