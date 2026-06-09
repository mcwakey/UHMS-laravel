@extends('layouts.app')
@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
@php
    $invoiceClaim = $invoice->claim;
    $invoiceInsuranceProviderId = $invoice->visit?->visitInsurance?->insurance_provider_id;
    $canCreateInsuranceClaim = (float) $invoice->nhis_amount > 0 && ! $invoiceClaim;
    $currentUser = auth()->user();
    $canApplyDiscount = $currentUser?->can('billing.discount.apply') ?? false;
    $canRemoveDiscount = ($currentUser?->can('billing.discount.remove') ?? false)
        || ($currentUser?->can('billing.discount.reverse') ?? false);
    $canViewDiscountHistory = $currentUser?->can('billing.discount.view') ?? false;
    $canDiscountActions = $canApplyDiscount || $canRemoveDiscount;
    $canViewReceivables = ($currentUser?->can('receivables.view') ?? false) || ($currentUser?->can('invoices.view') ?? false);
    $canReallocateReceivables = $currentUser?->can('receivables.reallocate') ?? false;
    $canViewAccountingPosting = $currentUser?->can('accounting.posting.view') ?? false;
    $canViewAccountingFailures = $currentUser?->can('accounting.posting.failure.view') ?? false;
    $canRetryAccountingPosting = $currentUser?->can('accounting.posting.retry') ?? false;
    $accountingStatusColors = [
        'pending' => 'secondary',
        'posted' => 'success',
        'failed' => 'danger',
        'reversed' => 'warning',
    ];
    $accountingStatusLabel = fn ($status) => ucfirst(str_replace('_', ' ', $status ?: 'pending'));
    $accountingStatusColor = fn ($status) => $accountingStatusColors[$status ?: 'pending'] ?? 'secondary';
    $receivableStatusColors = [
        'pending' => 'warning',
        'partially_paid' => 'info',
        'paid' => 'success',
        'overdue' => 'danger',
        'written_off' => 'dark',
        'cancelled' => 'secondary',
    ];
    $receivableStatusColor = fn ($status) => $receivableStatusColors[$status ?: 'pending'] ?? 'secondary';
    $openReceivables = $invoice->receivables->filter(fn ($r) => (float) $r->balance > 0)->values();
    $selectedReceivableId = old('invoice_receivable_id');
    $defaultPaymentReceivable = $openReceivables->firstWhere('id', (int) $selectedReceivableId) ?: $openReceivables->first();
    $defaultPaymentAmount = $defaultPaymentReceivable
        ? min((float) $invoice->balance, (float) $defaultPaymentReceivable->balance)
        : (float) $invoice->balance;
@endphp
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3">
    <div class="flex-grow-1">
        <h6 class="fw-bold mb-0 d-flex align-items-center">
            <a href="{{ route('admin.billing.invoices.index') }}"><i class="ti ti-chevron-left me-1 fs-14"></i>Invoices</a>
        </h6>
    </div>
    <div class="d-flex gap-2">
        @can('claims.view')
        @if($invoiceClaim)
        <a href="{{ route('admin.claims.show', $invoiceClaim) }}" class="btn btn-outline-primary btn-md">
            <i class="ti ti-file-dollar me-1"></i>View Insurance Claim
        </a>
        @endif
        @endcan
        @if($canCreateInsuranceClaim)
            @can('claims.create')
                @if($invoiceInsuranceProviderId)
                <form method="POST" action="{{ route('admin.claims.store-from-invoice') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                    <input type="hidden" name="insurance_provider_id" value="{{ $invoiceInsuranceProviderId }}">
                    <button type="submit" class="btn btn-primary btn-md">
                        <i class="ti ti-file-plus me-1"></i>Generate Insurance Claim
                    </button>
                </form>
                @else
                <a href="{{ route('admin.claims.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-primary btn-md">
                    <i class="ti ti-file-plus me-1"></i>Generate Insurance Claim
                </a>
                @endif
            @endcan
        @endif
        <a data-no-inertia href="{{ route('admin.billing.invoices.print', $invoice) }}" target="_blank" class="btn btn-dark btn-md">
            <i class="ti ti-printer me-1"></i>Print
        </a>
        <a data-no-inertia href="{{ route('admin.billing.invoices.pdf', $invoice) }}" class="btn btn-outline-danger btn-md">
            <i class="ti ti-file-type-pdf me-1"></i>Download PDF
        </a>
        @can('invoices.edit')
        @if(!in_array($invoice->status, [\App\Enums\InvoiceStatus::CANCELLED, \App\Enums\InvoiceStatus::REFUNDED, \App\Enums\InvoiceStatus::PAID], true))
        <a href="{{ route('admin.billing.invoices.edit', $invoice) }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-edit me-1"></i>Edit
        </a>
        @endif
        @endcan
        @can('credit_notes.create')
        @if(!in_array($invoice->status, [\App\Enums\InvoiceStatus::CANCELLED, \App\Enums\InvoiceStatus::REFUNDED], true) && $invoice->balance > 0)
        <a href="{{ route('admin.billing.credit-notes.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-outline-info btn-md">
            <i class="ti ti-receipt-refund me-1"></i>Issue Credit Note
        </a>
        @endif
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

<div id="paymentFormFeedback" class="alert d-none" role="alert"></div>

<div class="row">
    <!-- Invoice Details -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <!-- Header -->
                <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                    <div>
                        <img src="{{ URL::asset('build/img/logo.svg') }}" alt="UHMS" style="height:40px;">
                    </div>
                    <div class="text-end">
                        <span id="invoiceStatusBadge" class="badge bg-{{ $invoice->status->color() }} fs-13 px-3 py-2">{{ $invoice->status->label() }}</span>
                    </div>
                </div>

                <!-- Invoice Info Row -->
                <div class="row mb-4 pb-3 border-bottom">
                    <div class="col-md-4">
                        <h6 class="fw-bold mb-2">Invoice Details</h6>
                        <p class="mb-1 text-muted">Invoice #: <span class="text-dark fw-medium">{{ $invoice->invoice_number }}</span></p>
                        <p class="mb-1 text-muted">Date: <span class="text-dark">{{ $invoice->created_at->format('d M Y') }}</span></p>
                        <p class="mb-1 text-muted">Due Date: <span class="text-dark">{{ $invoice->due_date?->format('d M Y') ?? '—' }}</span></p>
                        <p class="mb-0 text-muted">Type: <span class="badge bg-soft-{{ $invoice->billing_type->color() }}">{{ $invoice->billing_type->label() }}</span></p>
                        @if($canViewAccountingPosting)
                            <div class="mt-2 small">
                                <span class="text-muted">Accounting:</span>
                                <span class="badge bg-{{ $accountingStatusColor($invoice->accounting_status) }}">
                                    {{ $accountingStatusLabel($invoice->accounting_status) }}
                                </span>
                                @if($invoice->journalEntry)
                                    <a href="{{ route('admin.accounting.journals.show', $invoice->journalEntry) }}" class="ms-1">{{ $invoice->journalEntry->journal_number }}</a>
                                @endif
                                @if($invoice->accounting_status === 'failed' && $canRetryAccountingPosting)
                                    <form method="POST" action="{{ route('admin.accounting.postings.retry') }}" class="d-inline ms-1">
                                        @csrf
                                        <input type="hidden" name="source_type" value="invoice">
                                        <input type="hidden" name="source_id" value="{{ $invoice->id }}">
                                        <button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Retry</button>
                                    </form>
                                @endif
                                @if($invoice->accounting_status === 'failed' && $canViewAccountingFailures && $invoice->accounting_error)
                                    <div class="text-danger mt-1">{{ $invoice->accounting_error }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <h6 class="fw-bold mb-2">{{ $invoice->patient ? 'Patient' : 'Recipient' }}</h6>
                        @if($invoice->patient)
                            <p class="fw-medium mb-1">{{ $invoice->patient->full_name }}</p>
                            <p class="text-muted mb-1">{{ $invoice->patient->patient_number }}</p>
                            <p class="text-muted mb-1">{{ $invoice->patient->phone }}</p>
                        @else
                            <p class="fw-medium mb-1">{{ $invoice->external_party_name ?? 'External recipient' }}</p>
                            <p class="text-muted mb-1"><span class="badge bg-purple-lt">External / referral</span></p>
                            @if($invoice->bloodRequest)<p class="text-muted mb-1">Blood request {{ $invoice->bloodRequest->request_number }}</p>@endif
                        @endif
                    </div>
                    <div class="col-md-4 text-md-end">
                        <h6 class="fw-bold mb-2">Visit</h6>
                        @if($invoice->visit)
                            <p class="text-muted mb-1">{{ $invoice->visit->visit_number }}</p>
                            <p id="invoiceVisitStatusLabel" class="text-muted mb-1">{{ $invoice->visit->status->label() }}</p>
                            <p class="text-muted mb-0">{{ $invoice->visit->visit_date->format('d M Y') }}</p>
                        @else
                            <p class="text-muted mb-0">—</p>
                        @endif
                    </div>
                </div>

                <!-- Items Table -->
                <h6 class="fw-bold mb-3">Service Items</h6>
                @php
                    $sourceLabels = [
                        'cash_and_carry'         => ['Cash & Carry',    'secondary'],
                        'cash_price'             => ['Cash & Carry',    'secondary'],
                        'provider_specific'      => ['Provider Rate',   'success'],
                        'payer_specific_price'   => ['Provider Rate',   'success'],
                        'insurance_type'         => ['Insurance Type',  'info'],
                        'insurance_type_default' => ['Insurance Type',  'info'],
                        'base_price'             => ['Base Price',      'light text-dark'],
                    ];
                    $sourceTypeGroups = [
                        'visit_service'                     => 'consultation_visit_services',
                        'service_catalog'                   => 'consultation_visit_services',
                        'consultation_service'              => 'consultation_visit_services',
                        'visit_consultation_route_service'  => 'consultation_visit_services',
                    ];
                    $sourceTypeLabels = [
                        'consultation_visit_services'              => 'Consultation / Visit Services',
                        'visit_service'                            => 'Consultation / Visit Services',
                        'service_catalog'                          => 'Consultation / Visit Services',
                        'consultation_service'                     => 'Consultation / Visit Services',
                        'visit_consultation_route_service'         => 'Consultation / Visit Services',
                        'lab_request_item'                         => 'Investigations',
                        'investigation_service'                    => 'Investigations',
                        'investigation_consumable'                 => 'Investigation Consumables',
                        'prescription_item'                        => 'Pharmacy',
                        'pharmacy_product'                         => 'Pharmacy',
                        'pharmacy_billing_selection'               => 'Pharmacy',
                        'ward_charge'                              => 'Ward / Admission',
                        'ward_consumable'                          => 'Ward / Admission',
                        'admission_fee'                            => 'Ward / Admission',
                        'admission_bed_charge'                     => 'Ward / Admission',
                        'admission_daily_consumable_charge'        => 'Ward / Admission',
                        'scan_request_item'                        => 'Scans',
                        'xray_request_item'                        => 'X-Ray',
                        'procedure'                                => 'Procedures',
                        'procedure_service'                        => 'Procedures',
                        'procedure_consumable'                     => 'Procedure Consumables',
                        'emergency_consumable'                     => 'Emergency',
                        'emergency_bed_charge'                     => 'Emergency',
                        'emergency_daily_consumable_charge'        => 'Emergency',
                    ];
                    $statusBadge = [
                        'paid'           => 'success',
                        'partially_paid' => 'warning',
                        'unpaid'         => 'danger',
                        'waived'         => 'info',
                        'cancelled'      => 'secondary',
                        'voided'         => 'secondary',
                    ];
                    $invoiceSourceKey = fn ($item) => $sourceTypeGroups[
                        $item->source_type ?: ($item->service_catalog_id ? 'service_catalog' : 'other')
                    ] ?? ($item->source_type ?: ($item->service_catalog_id ? 'service_catalog' : 'other'));
                    $groupedItems = $invoice->items->sortBy(fn ($item) => implode('|', [
                        $invoiceSourceKey($item),
                        $item->department?->name ?? 'zz_unassigned',
                        str_pad((string) $item->id, 10, '0', STR_PAD_LEFT),
                    ]));
                    $currentGroup = null;
                @endphp
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Description</th>
                                <th>Pricing</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Covered</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Patient Payable</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th class="text-center">Status</th>
                                @if($canDiscountActions)
                                <th class="text-center" style="width:60px;">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groupedItems as $idx => $item)
                            @php
                                $selectedPrice = $item->selected_price !== null
                                    ? (float) $item->selected_price
                                    : (float) ($item->unit_price ?? 0);
                                $src       = $item->pricing_source ?? 'cash_and_carry';
                                $meta      = $sourceLabels[$src] ?? [ucfirst(str_replace('_',' ',$src)), 'light text-dark'];
                                $payer     = $item->payer_type ?? 'cash';
                                $rawSourceKey  = $item->source_type ?: ($item->service_catalog_id ? 'service_catalog' : 'other');
                                $sourceKey  = $sourceTypeGroups[$rawSourceKey] ?? $rawSourceKey;
                                $departmentKey = $item->department_id ? 'department_'.$item->department_id : 'department_none';
                                $groupKey  = $sourceKey.'|'.$departmentKey;
                                $groupLabel = $sourceTypeLabels[$sourceKey] ?? ucfirst(str_replace('_',' ',$sourceKey));
                                $departmentLabel = $item->department?->name ?? 'Unassigned Department';
                                $payStatus  = $item->payment_status ?: 'unpaid';
                                $payColor   = $statusBadge[$payStatus] ?? 'secondary';
                            @endphp
                            @if($currentGroup !== $groupKey)
                            <tr class="table-secondary">
                                <th colspan="{{ $canDiscountActions ? 11 : 10 }}" class="small text-uppercase">
                                    <i class="ti ti-folder me-1"></i>{{ $groupLabel }}
                                    <span class="badge bg-light text-dark ms-2">{{ $departmentLabel }}</span>
                                </th>
                            </tr>
                            @php $currentGroup = $groupKey; @endphp
                            @endif
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    {{ $item->description }}
                                    @if($item->serviceCatalog)
                                    <br><small class="text-muted">{{ $item->serviceCatalog->code }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $meta[1] }}">{{ $meta[0] }}</span>
                                    <div class="small text-muted mt-1">
                                        <i class="ti ti-{{ $payer === 'insurance' ? 'shield-check' : 'cash' }} me-1"></i>{{ ucfirst($payer) }}
                                    </div>
                                </td>
                                {{-- <td class="text-end fw-semibold">&#8373;{{ number_format($selectedPrice, 2) }}</td> --}}
                                <td class="text-end">
                                    @if($item->cash_price > $selectedPrice)
                                    <div class="small text-muted text-decoration-line-through">&#8373;{{ number_format($item->cash_price, 2) }}</div>
                                    @endif
                                    <span class="fw-semibold">&#8373;{{ number_format($selectedPrice, 2) }}</span>
                                </td>
                                {{-- <td class="text-end fw-medium">&#8373;{{ number_format($item->total_price, 2) }}</td> --}}
                                <td class="text-end">
                                    @if((float) $item->insurance_covered > 0)
                                    <span class="text-success">&#8373;{{ number_format($item->insurance_covered, 2) }}</span>
                                    @else
                                    —
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if((float) $item->discount_amount > 0)
                                    <span class="text-danger">-&#8373;{{ number_format($item->discount_amount, 2) }}</span>
                                    @else
                                    —
                                    @endif
                                </td>
                                <td class="text-end">&#8373;{{ number_format($item->patient_payable, 2) }}</td>
                                <td class="text-end">&#8373;{{ number_format($item->paid_amount, 2) }}</td>
                                <td class="text-end {{ (float) $item->balance > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                    &#8373;{{ number_format($item->balance, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $payColor }} text-uppercase">{{ str_replace('_',' ', $payStatus) }}</span>
                                </td>
                                @if($canDiscountActions)
                                <td class="text-center">
                                    @if(! in_array($payStatus, ['paid','cancelled','voided','waived']))
                                    @if($canApplyDiscount)
                                    <button type="button"
                                            class="btn btn-sm btn-outline-warning"
                                            title="Apply Discount"
                                            data-bs-toggle="modal"
                                            data-bs-target="#discountModal"
                                            data-item-id="{{ $item->id }}"
                                            data-item-desc="{{ $item->description }}"
                                            data-line-total="{{ number_format(((float)$item->selected_price) * ((float)$item->quantity), 2, '.', '') }}"
                                            data-current-discount="{{ number_format((float)$item->discount_amount, 2, '.', '') }}"
                                            data-action-url="{{ route('admin.billing.invoices.items.discount', [$invoice, $item]) }}">
                                        <i class="ti ti-discount-2"></i>
                                    </button>
                                    @endif
                                    @if($canRemoveDiscount && (float) $item->discount_amount > 0)
                                     <x-confirm-form :action="route('admin.billing.invoices.items.discount.remove', [$invoice, $item])" method="DELETE"
                                         button-label="" button-class="btn btn-sm btn-outline-danger ms-1" icon="ti-x"
                                         confirm-title="Remove this discount?"
                                         confirm-text="This will reverse the manual discount on this invoice item."
                                         confirm-button="Yes, remove discount"
                                         :require-reason="true"
                                         reason-placeholder="Reason for removing this discount" />
                                     @endif
                                    @endif
                                 </td>
                                 @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="row">
                    <div class="col-md-6">
                        @if($invoice->notes)
                        <div>
                            <h6 class="fw-bold mb-1">Notes</h6>
                            <p class="text-muted">{{ $invoice->notes }}</p>
                        </div>
                        @endif
                        <p class="text-muted mb-1">Created by: <span class="text-dark">{{ $invoice->createdBy->name ?? '—' }}</span></p>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Gross Total</span>
                            <span class="fw-medium">&#8373;{{ number_format($invoiceBalanceSummary['gross_total'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Discounts</span>
                            <span class="text-danger">-&#8373;{{ number_format($invoiceBalanceSummary['discounts'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Credit Notes</span>
                            <span class="text-info">-&#8373;{{ number_format($invoiceBalanceSummary['credit_notes'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Write-offs</span>
                            <span class="text-dark">-&#8373;{{ number_format($invoiceBalanceSummary['write_offs'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-success fw-medium">Payments</span>
                            <span id="invoicePaidValue" class="text-success fw-medium" data-amount="{{ $invoice->amount_paid }}">-&#8373;{{ number_format($invoiceBalanceSummary['payments'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-danger fw-medium">Refunds / Reversals</span>
                            <span class="text-danger">+&#8373;{{ number_format($invoiceBalanceSummary['refunds'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2">
                            <span class="fw-bold text-danger">Balance</span>
                            <span id="invoiceBalanceValue" class="fw-bold text-danger fs-5" data-amount="{{ $invoice->balance }}">&#8373;{{ number_format($invoiceBalanceSummary['outstanding_balance'], 2) }}</span>
                        </div>
                        @if($canViewAccountingPosting)
                        <div class="d-flex justify-content-between mt-2">
                            <span class="text-muted">Accounting Status</span>
                            <span class="badge bg-{{ $accountingStatusColor($invoiceBalanceSummary['accounting_status']) }}">{{ $accountingStatusLabel($invoiceBalanceSummary['accounting_status']) }}</span>
                        </div>
                        @endif
                        @if(! $invoiceBalanceSummary['formula_matches_invoice'])
                        <div class="alert alert-warning py-2 mt-2 mb-0 small">
                            Formula balance is &#8373;{{ number_format($invoiceBalanceSummary['formula_balance'], 2) }}. Invoice balance is &#8373;{{ number_format($invoiceBalanceSummary['outstanding_balance'], 2) }}.
                        </div>
                        @endif
                    </div>
                </div>

                @if($canViewReceivables)
                <hr>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0"><i class="ti ti-users-group me-1"></i>Payer Responsibility / Receivables</h6>
                    @if($canReallocateReceivables && $openReceivables->isNotEmpty())
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#receivableReallocationModal">
                        <i class="ti ti-arrows-exchange me-1"></i>Reallocate
                    </button>
                    @endif
                </div>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Payer</th>
                                <th>Type</th>
                                <th class="text-end">Allocated</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Adjustments</th>
                                <th class="text-end">Balance</th>
                                <th>Due / Aging</th>
                                <th>Status</th>
                                @if($canViewAccountingPosting)
                                <th>Journal</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->receivables as $receivable)
                            @php
                                $adjustments = (float) $receivable->credit_note_amount + (float) $receivable->write_off_amount;
                                $agingReference = $receivable->due_date ?: $receivable->aging_start_date;
                                $agingDays = $agingReference ? max(0, $agingReference->diffInDays(now(), false)) : 0;
                            @endphp
                            <tr>
                                <td class="fw-medium">{{ $receivable->payerName() }}</td>
                                <td><span class="badge bg-{{ $receivable->payerBadgeColor() }}">{{ ucfirst($receivable->payer_type) }}</span></td>
                                <td class="text-end">&#8373;{{ number_format($receivable->allocated_amount, 2) }}</td>
                                <td class="text-end text-success">&#8373;{{ number_format($receivable->paid_amount, 2) }}</td>
                                <td class="text-end">&#8373;{{ number_format($adjustments, 2) }}</td>
                                <td class="text-end fw-semibold {{ (float) $receivable->balance > 0 ? 'text-danger' : 'text-muted' }}">&#8373;{{ number_format($receivable->balance, 2) }}</td>
                                <td>
                                    <div>{{ $receivable->due_date?->format('d M Y') ?? 'No due date' }}</div>
                                    <small class="text-muted">{{ $agingDays }} day{{ $agingDays === 1 ? '' : 's' }}</small>
                                </td>
                                <td><span class="badge bg-{{ $receivableStatusColor($receivable->status) }}">{{ ucfirst(str_replace('_', ' ', $receivable->status)) }}</span></td>
                                @if($canViewAccountingPosting)
                                <td>
                                    @if($receivable->journalEntry)
                                        <a href="{{ route('admin.accounting.journals.show', $receivable->journalEntry) }}">{{ $receivable->journalEntry->journal_number }}</a>
                                    @else
                                        <span class="badge bg-{{ $accountingStatusColor($receivable->accounting_status) }}">{{ $accountingStatusLabel($receivable->accounting_status) }}</span>
                                    @endif
                                    @if($receivable->accounting_status === 'failed' && $canViewAccountingFailures && $receivable->accounting_error)
                                        <div class="small text-danger">{{ $receivable->accounting_error }}</div>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $canViewAccountingPosting ? 9 : 8 }}" class="text-center text-muted py-3">No payer responsibility rows are available yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @endif

                <hr>
                <h6 class="fw-bold mb-3"><i class="ti ti-adjustments-dollar me-1"></i>Adjustments &amp; Settlements</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Reference</th>
                                <th class="text-end">Amount</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Approved By</th>
                                @if($canViewAccountingPosting)
                                <th>Journal Entry</th>
                                <th>Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adjustmentHistory as $history)
                            <tr>
                                <td>{{ $history['date']?->format('d M Y H:i') ?? '—' }}</td>
                                <td><span class="badge bg-{{ $history['badge'] }}">{{ $history['type'] }}</span></td>
                                <td class="fw-medium">{{ $history['reference'] }}</td>
                                <td class="text-end">&#8373;{{ number_format($history['amount'], 2) }}</td>
                                <td>{{ $history['reason'] ?: '—' }}</td>
                                <td>{{ $history['status'] }}</td>
                                <td>{{ $history['actor'] ?: '—' }}</td>
                                @if($canViewAccountingPosting)
                                <td>
                                    @if($history['journal'])
                                        <a href="{{ route('admin.accounting.journals.show', $history['journal']) }}">{{ $history['journal']->journal_number }}</a>
                                    @else
                                        <span class="badge bg-{{ $accountingStatusColor($history['accounting_status']) }}">{{ $accountingStatusLabel($history['accounting_status']) }}</span>
                                    @endif
                                    @if($history['reversal_journal'])
                                        <div class="small">
                                            Reversal:
                                            <a href="{{ route('admin.accounting.journals.show', $history['reversal_journal']) }}">{{ $history['reversal_journal']->journal_number }}</a>
                                        </div>
                                    @endif
                                    @if($history['accounting_status'] === 'failed' && $canViewAccountingFailures && $history['accounting_error'])
                                        <div class="small text-danger">{{ $history['accounting_error'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($history['accounting_status'] === 'failed' && $canRetryAccountingPosting)
                                        <form method="POST" action="{{ route('admin.accounting.postings.retry') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="source_type" value="{{ $history['retry_source_type'] }}">
                                            <input type="hidden" name="source_id" value="{{ $history['retry_source_id'] }}">
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                <i class="ti ti-refresh me-1"></i>Retry
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $canViewAccountingPosting ? 9 : 7 }}" class="text-center text-muted py-3">No payments or adjustments recorded.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($canViewDiscountHistory)
                <hr>
                <h6 class="fw-bold mb-3"><i class="ti ti-discount-2 me-1"></i>Discount History</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Item</th>
                                <th class="text-end">Old Discount</th>
                                <th class="text-end">New Discount</th>
                                <th>Risk</th>
                                <th>Reason</th>
                                <th>User</th>
                                @if($canViewAccountingPosting)
                                <th>Accounting</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->discountEvents->sortByDesc('performed_at') as $event)
                            <tr>
                                <td>{{ $event->performed_at?->format('d M Y H:i') }}</td>
                                <td>{{ $event->invoiceItem->description ?? 'Invoice item' }}</td>
                                <td class="text-end">&#8373;{{ number_format($event->old_discount_amount, 2) }}</td>
                                <td class="text-end">&#8373;{{ number_format($event->new_discount_amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $event->is_override ? 'danger' : 'warning' }}">
                                        {{ $event->is_override ? 'Override' : 'Manual' }}
                                    </span>
                                </td>
                                <td>{{ $event->reason }}</td>
                                <td>{{ $event->performedBy->name ?? 'System' }}</td>
                                @if($canViewAccountingPosting)
                                <td>
                                    <span class="badge bg-{{ $accountingStatusColor($event->accounting_status) }}">
                                        {{ $accountingStatusLabel($event->accounting_status) }}
                                    </span>
                                    @if($event->journalEntry)
                                        <a href="{{ route('admin.accounting.journals.show', $event->journalEntry) }}" class="d-block small">{{ $event->journalEntry->journal_number }}</a>
                                    @elseif($event->accounting_status === 'failed' && $canRetryAccountingPosting)
                                        <form method="POST" action="{{ route('admin.accounting.postings.retry') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="source_type" value="discount">
                                            <input type="hidden" name="source_id" value="{{ $event->id }}">
                                            <button type="submit" class="btn btn-link btn-sm p-0">Retry</button>
                                        </form>
                                    @endif
                                    @if($event->accounting_status === 'failed' && $canViewAccountingFailures && $event->accounting_error)
                                        <div class="small text-danger">{{ $event->accounting_error }}</div>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $canViewAccountingPosting ? 8 : 7 }}" class="text-center text-muted py-3">No discount history.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @endif

                <!-- Payment History -->
                @if($invoice->payments->count() > 0)
                <hr>
                <h6 class="fw-bold mb-3"><i class="ti ti-cash me-1"></i>Payment History</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Payment #</th>
                                <th>Date</th>
                                <th>Payer</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th class="text-end">Amount</th>
                                <th>Received By</th>
                                @if($canViewAccountingPosting)
                                <th>Accounting</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->payments as $payment)
                            <tr>
                                <td class="fw-medium">{{ $payment->payment_number }}</td>
                                <td>{{ $payment->paid_at->format('d M Y H:i') }}</td>
                                <td>
                                    @if($payment->receivable)
                                        <span class="badge bg-{{ $payment->receivable->payerBadgeColor() }}">{{ ucfirst($payment->receivable->payer_type) }}</span>
                                        <div class="small text-muted">{{ $payment->receivable->payerName() }}</div>
                                    @else
                                        <span class="badge bg-light text-dark">{{ ucfirst($payment->payer_type ?: 'patient') }}</span>
                                    @endif
                                </td>
                                <td>{{ $payment->payment_method->label() }}</td>
                                <td>{{ $payment->reference_number ?? '—' }}</td>
                                <td class="text-end fw-medium text-success">&#8373;{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->receivedBy->name ?? '—' }}</td>
                                @if($canViewAccountingPosting)
                                <td>
                                    <span class="badge bg-{{ $accountingStatusColor($payment->accounting_status) }}">
                                        {{ $accountingStatusLabel($payment->accounting_status) }}
                                    </span>
                                    @if($payment->journalEntry)
                                        <a href="{{ route('admin.accounting.journals.show', $payment->journalEntry) }}" class="d-block small">{{ $payment->journalEntry->journal_number }}</a>
                                    @elseif($payment->accounting_status === 'failed' && $canRetryAccountingPosting)
                                        <form method="POST" action="{{ route('admin.accounting.postings.retry') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="source_type" value="payment">
                                            <input type="hidden" name="source_id" value="{{ $payment->id }}">
                                            <button type="submit" class="btn btn-link btn-sm p-0">Retry</button>
                                        </form>
                                    @endif
                                    @if($payment->accounting_status === 'failed' && $canViewAccountingFailures && $payment->accounting_error)
                                        <div class="small text-danger">{{ $payment->accounting_error }}</div>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Sidebar: Record Payment -->
    <div class="col-lg-4">
        @if(!in_array($invoice->status, [\App\Enums\InvoiceStatus::PAID, \App\Enums\InvoiceStatus::CANCELLED, \App\Enums\InvoiceStatus::REFUNDED]))
        <div class="card border-primary" id="recordPaymentCard">
            <div class="card-header bg-primary text-white">
                <h6 class="fw-bold mb-0"><i class="ti ti-cash me-1"></i>Record Payment</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-warning py-2 mb-3" id="invoiceOutstandingAlert">
                    <small><strong>Outstanding:</strong> <span id="invoiceOutstandingValue">&#8373;{{ number_format($invoice->balance, 2) }}</span></small>
                </div>

                <form method="POST" action="{{ route('admin.billing.payments.store', $invoice) }}" id="paymentForm">
                    @csrf
                    @if($openReceivables->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label fw-medium">Paying Party <span class="text-danger">*</span></label>
                        <select name="invoice_receivable_id" id="invoiceReceivableSelect" class="form-select @error('invoice_receivable_id') is-invalid @enderror" required>
                            @foreach($openReceivables as $receivable)
                            <option value="{{ $receivable->id }}"
                                    data-balance="{{ number_format((float) $receivable->balance, 2, '.', '') }}"
                                    data-payer-type="{{ $receivable->payer_type }}"
                                    {{ (int) $defaultPaymentReceivable?->id === (int) $receivable->id ? 'selected' : '' }}>
                                {{ ucfirst($receivable->payer_type) }} - {{ $receivable->payerName() }} (&#8373;{{ number_format($receivable->balance, 2) }})
                            </option>
                            @endforeach
                        </select>
                        @error('invoice_receivable_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-medium">Amount (&#8373;) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="paymentAmountInput" class="form-control @error('amount') is-invalid @enderror"
                            value="{{ old('amount', number_format($defaultPaymentAmount, 2, '.', '')) }}" step="0.01" min="0.01" max="{{ number_format($defaultPaymentAmount, 2, '.', '') }}" required>
                        @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required id="paymentMethodSelect">
                            @foreach(\App\Enums\PaymentMethod::cases() as $method)
                            <option value="{{ $method->value }}">{{ $method->label() }}</option>
                            @endforeach
                        </select>
                        @error('payment_method')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="referenceGroup" style="display:none;">
                        <label class="form-label fw-medium">Reference / Transaction ID</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="e.g. MoMo Transaction ID">
                    </div>

                    {{-- Per-line allocations: optional. If none ticked, payment auto-distributes oldest-first. --}}
                    @php
                        $unpaidItems = $invoice->items->filter(function($i){
                            return !in_array($i->payment_status, ['paid','cancelled','voided'])
                                && (float) $i->balance > 0;
                        })->values();
                    @endphp
                    @if($unpaidItems->isNotEmpty())
                    <div class="mb-3">
                        <details>
                            <summary class="fw-medium text-primary" style="cursor:pointer;">
                                <i class="ti ti-list-check me-1"></i>Pay specific items
                                <small class="text-muted">(optional — leave unchecked to auto-distribute)</small>
                            </summary>
                            <div class="mt-2 border rounded p-2" style="max-height:260px;overflow:auto;">
                                @foreach($unpaidItems as $uIdx => $uItem)
                                <div class="row g-1 align-items-center mb-2 py-1 border-bottom">
                                    <div class="col-auto">
                                        <input type="checkbox" class="form-check-input alloc-toggle" data-row="{{ $uIdx }}">
                                    </div>
                                    <div class="col">
                                        <div class="small fw-medium">{{ $uItem->description }}</div>
                                        <div class="small text-muted">Balance: &#8373;{{ number_format($uItem->balance, 2) }}</div>
                                        <input type="hidden" name="allocations[{{ $uIdx }}][invoice_item_id]" value="{{ $uItem->id }}" disabled class="alloc-id">
                                    </div>
                                    <div class="col-4">
                                        <input type="number" step="0.01" min="0.01" max="{{ $uItem->balance }}"
                                            name="allocations[{{ $uIdx }}][amount]"
                                            value="{{ number_format($uItem->balance, 2, '.', '') }}"
                                            class="form-control form-control-sm alloc-amount" disabled>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </details>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-medium">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional"></textarea>
                    </div>

                    @can('payments.create')
                    <button type="submit" class="btn btn-primary w-100" id="recordPaymentBtn">
                        <i class="ti ti-check me-1"></i>Record Payment
                    </button>
                    @endcan
                </form>
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body text-center py-4">
                @if($invoice->status === \App\Enums\InvoiceStatus::PAID)
                <i class="ti ti-circle-check text-success fs-1 d-block mb-2"></i>
                <h5 class="text-success">Fully Paid</h5>
                @elseif($invoice->status === \App\Enums\InvoiceStatus::CANCELLED)
                <i class="ti ti-circle-x text-danger fs-1 d-block mb-2"></i>
                <h5 class="text-danger">Cancelled</h5>
                @endif
            </div>
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0">Quick Actions</h6>
            </div>
            <div class="card-body d-grid gap-2">
                @can('claims.view')
                @if($invoiceClaim)
                <a href="{{ route('admin.claims.show', $invoiceClaim) }}" class="btn btn-outline-primary">
                    <i class="ti ti-file-dollar me-1"></i>View Insurance Claim
                </a>
                @endif
                @endcan
                @if($canCreateInsuranceClaim)
                    @can('claims.create')
                        @if($invoiceInsuranceProviderId)
                        <form method="POST" action="{{ route('admin.claims.store-from-invoice') }}">
                            @csrf
                            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                            <input type="hidden" name="insurance_provider_id" value="{{ $invoiceInsuranceProviderId }}">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="ti ti-file-plus me-1"></i>Generate Insurance Claim
                            </button>
                        </form>
                        @else
                        <a href="{{ route('admin.claims.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-outline-primary">
                            <i class="ti ti-file-plus me-1"></i>Generate Insurance Claim
                        </a>
                        @endif
                    @endcan
                @endif
                <a data-no-inertia href="{{ route('admin.billing.invoices.print', $invoice) }}" target="_blank" class="btn btn-outline-dark">
                    <i class="ti ti-printer me-1"></i>Print Invoice
                </a>
                @if(!in_array($invoice->status, [\App\Enums\InvoiceStatus::PAID, \App\Enums\InvoiceStatus::CANCELLED]))
                @can('invoices.void')
                <x-confirm-form :action="route('admin.billing.invoices.cancel', $invoice)" method="PATCH"
                    button-label="Cancel Invoice" button-class="btn btn-outline-danger w-100" icon="ti-x"
                    confirm-title="Cancel this invoice?" confirm-text="The invoice will be marked cancelled." confirm-button="Yes, cancel invoice" />
                @endcan
                @endif
                @if($invoice->visit)
                <a href="{{ route('admin.visits.show', $invoice->visit) }}" class="btn btn-outline-primary">
                    <i class="ti ti-calendar-check me-1"></i>View Visit
                </a>
                @endif
                @if($invoice->patient)
                <a href="{{ route('admin.patients.show', $invoice->patient) }}" class="btn btn-outline-info">
                    <i class="ti ti-user me-1"></i>View Patient
                </a>
                @elseif($invoice->bloodRequest)
                <a href="{{ route('admin.blood-bank.requests.index') }}" class="btn btn-outline-info">
                    <i class="ti ti-droplet me-1"></i>Blood Requests
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

@if($canReallocateReceivables && $openReceivables->isNotEmpty())
<div class="modal fade" id="receivableReallocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.billing.invoices.receivables.reallocate', $invoice) }}" id="receivableReallocationForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-arrows-exchange me-1"></i>Reallocate Payer Responsibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Move From <span class="text-danger">*</span></label>
                        <select name="from_receivable_id" class="form-select" required>
                            @foreach($openReceivables as $receivable)
                            <option value="{{ $receivable->id }}" data-balance="{{ number_format((float) $receivable->balance, 2, '.', '') }}">
                                {{ ucfirst($receivable->payer_type) }} - {{ $receivable->payerName() }} (Balance &#8373;{{ number_format($receivable->balance, 2) }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Move To <span class="text-danger">*</span></label>
                        <select name="target_payer_type" id="targetPayerTypeSelect" class="form-select" required>
                            <option value="patient">Patient</option>
                            <option value="insurance">Insurance Provider</option>
                            <option value="sponsor">Sponsor</option>
                            <option value="corporate">Corporate Client</option>
                        </select>
                    </div>
                    <div class="mb-3 payer-target-select d-none" data-payer-target="insurance">
                        <label class="form-label">Insurance Provider</label>
                        <select class="form-select target-payer-id" disabled>
                            <option value="">Select provider</option>
                            @foreach(($receivablePayerOptions['insurance'] ?? []) as $provider)
                            <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 payer-target-select d-none" data-payer-target="sponsor">
                        <label class="form-label">Sponsor</label>
                        <select class="form-select target-payer-id" disabled>
                            <option value="">Select sponsor</option>
                            @foreach(($receivablePayerOptions['sponsors'] ?? []) as $sponsor)
                            <option value="{{ $sponsor->id }}">{{ $sponsor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 payer-target-select d-none" data-payer-target="corporate">
                        <label class="form-label">Corporate Client</label>
                        <select class="form-select target-payer-id" disabled>
                            <option value="">Select corporate client</option>
                            @foreach(($receivablePayerOptions['corporate'] ?? []) as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="target_payer_id" id="targetPayerIdInput">
                    <div class="mb-3">
                        <label class="form-label">Amount (GH&#8373;) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="receivableReallocationAmount" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" maxlength="500" required></textarea>
                    </div>
                    <div class="alert alert-warning py-2 mb-0 small">
                        Reallocation changes the payer responsible for collection. It does not discount, waive, or cancel the invoice.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Reallocate</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

@can('billing.discount.apply')
<!-- Apply Discount Modal -->
<div class="modal fade" id="discountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="" id="discountForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-discount-2 me-1"></i>Apply Discount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2"><strong>Item:</strong> <span id="discountItemDesc">—</span></p>
                    <p class="mb-3 text-muted small">Line Total: &#8373;<span id="discountLineTotal">0.00</span></p>
                    <div class="mb-3">
                        <label class="form-label">Discount Amount (GH&#8373;) <span class="text-danger">*</span></label>
                        <input type="number" name="discount_amount" id="discountAmountInput"
                               class="form-control" step="0.01" min="0.01" required>
                        <div class="form-text">Must not exceed the line total. Larger discounts require override permission.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" id="discountReasonInput" class="form-control" rows="3" maxlength="500" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="ti ti-check me-1"></i>Apply Discount</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan

@endsection

@section('scripts')
<script>
$(function() {
    // Discount modal wiring
    const discountModal = document.getElementById('discountModal');
    if (discountModal) {
        discountModal.addEventListener('show.bs.modal', function(event) {
            const btn = event.relatedTarget;
            if (!btn) return;
            const url = btn.getAttribute('data-action-url');
            document.getElementById('discountForm').setAttribute('action', url);
            document.getElementById('discountItemDesc').textContent = btn.getAttribute('data-item-desc') || '—';
            document.getElementById('discountLineTotal').textContent = btn.getAttribute('data-line-total') || '0.00';
            const current = btn.getAttribute('data-current-discount') || '0';
            const input = document.getElementById('discountAmountInput');
            input.value = current;
            input.setAttribute('max', btn.getAttribute('data-line-total') || '');
            const reason = document.getElementById('discountReasonInput');
            if (reason) reason.value = '';
            setTimeout(() => input.focus(), 200);
        });
    }
});
</script>
<script>
$(function() {
    const feedback = $('#paymentFormFeedback');
    const paymentForm = $('#paymentForm');
    const recordPaymentBtn = $('#recordPaymentBtn');
    const paymentMethodSelect = $('#paymentMethodSelect');
    const referenceGroup = $('#referenceGroup');
    const invoiceStatusBadge = $('#invoiceStatusBadge');
    const invoiceVisitStatusLabel = $('#invoiceVisitStatusLabel');
    const invoicePaidValue = $('#invoicePaidValue');
    const invoiceBalanceValue = $('#invoiceBalanceValue');
    const invoiceOutstandingAlert = $('#invoiceOutstandingAlert');
    const invoiceOutstandingValue = $('#invoiceOutstandingValue');
    const paymentAmountInput = $('#paymentAmountInput');
    const invoiceReceivableSelect = $('#invoiceReceivableSelect');
    let invoiceMarkedPaid = false;
    const invoiceStatusColors = {
        draft: 'secondary',
        pending: 'warning',
        partially_paid: 'info',
        paid: 'success',
        cancelled: 'danger',
        refunded: 'dark'
    };
    const originalButtonHtml = recordPaymentBtn.html();

    function formatMoney(amount) {
        return '&#8373;' + Number(amount || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function showFeedback(type, html) {
        feedback.removeClass('d-none alert-success alert-danger').addClass('alert-' + type).html(html);
        $('html, body').animate({ scrollTop: 0 }, 200);
    }

    function updateInvoiceState(payload) {
        const paymentAmount = parseFloat(payload.amount || paymentAmountInput.val() || 0);
        const currentPaid = parseFloat(invoicePaidValue.data('amount') || 0);
        const currentBalance = parseFloat(invoiceBalanceValue.data('amount') || paymentAmountInput.attr('max') || 0);
        const nextPaid = Math.max(0, currentPaid + paymentAmount);
        const nextBalance = Math.max(0, currentBalance - paymentAmount);
        const statusColor = invoiceStatusColors[payload.invoice_status] || 'warning';

        if (payload.invoice_status_label && invoiceStatusBadge.length) {
            invoiceStatusBadge.removeClass('bg-secondary bg-warning bg-info bg-success bg-danger bg-dark')
                .addClass('bg-' + statusColor)
                .text(payload.invoice_status_label);
        }

        if (payload.visit_status_label && invoiceVisitStatusLabel.length) {
            invoiceVisitStatusLabel.text(payload.visit_status_label);
        }

        if (invoicePaidValue.length) {
            invoicePaidValue.data('amount', nextPaid);
            invoicePaidValue.html('-' + formatMoney(nextPaid));
        }

        if (invoiceBalanceValue.length) {
            invoiceBalanceValue.data('amount', nextBalance);
            invoiceBalanceValue.html(formatMoney(nextBalance));
        }

        if (invoiceOutstandingValue.length) {
            invoiceOutstandingValue.html(formatMoney(nextBalance));
        }

        if (payload.invoice_status === 'paid') {
            invoiceOutstandingAlert.removeClass('alert-warning').addClass('alert-success');
            paymentForm.find(':input').prop('disabled', true);
            recordPaymentBtn.prop('disabled', true).html('<i class="ti ti-circle-check me-1"></i>Paid');
            invoiceMarkedPaid = true;
            return;
        }

        paymentAmountInput.attr('max', nextBalance.toFixed(2)).val(nextBalance.toFixed(2));
    }

    function syncReceivablePaymentLimit() {
        if (!invoiceReceivableSelect.length || paymentAmountInput.prop('readonly')) {
            return;
        }

        const selected = invoiceReceivableSelect.find(':selected');
        const balance = parseFloat(selected.data('balance') || paymentAmountInput.attr('max') || 0);
        if (balance > 0) {
            paymentAmountInput.attr('max', balance.toFixed(2)).val(balance.toFixed(2));
        }
    }

    const targetPayerTypeSelect = $('#targetPayerTypeSelect');
    const targetPayerIdInput = $('#targetPayerIdInput');
    const reallocationAmount = $('#receivableReallocationAmount');
    const reallocationSource = $('#receivableReallocationForm select[name="from_receivable_id"]');

    function syncReallocationTarget() {
        const payerType = targetPayerTypeSelect.val();
        targetPayerIdInput.val('');
        $('.payer-target-select').addClass('d-none');
        $('.payer-target-select .target-payer-id').prop('disabled', true).removeAttr('name');

        const group = $('.payer-target-select[data-payer-target="' + payerType + '"]');
        if (group.length) {
            group.removeClass('d-none');
            const select = group.find('.target-payer-id');
            select.prop('disabled', false).attr('name', 'target_payer_id');
            targetPayerIdInput.prop('disabled', true);
        } else {
            targetPayerIdInput.prop('disabled', false).val('');
        }
    }

    function syncReallocationAmountLimit() {
        if (!reallocationSource.length) {
            return;
        }
        const balance = parseFloat(reallocationSource.find(':selected').data('balance') || 0);
        if (balance > 0) {
            reallocationAmount.attr('max', balance.toFixed(2)).val(balance.toFixed(2));
        }
    }

    function clearValidationErrors() {
        paymentForm.find('.is-invalid').removeClass('is-invalid');
        paymentForm.find('.dynamic-invalid-feedback').remove();
    }

    function applyValidationErrors(errors) {
        Object.entries(errors).forEach(function(entry) {
            const field = entry[0];
            const message = Array.isArray(entry[1]) ? entry[1][0] : entry[1];
            const input = paymentForm.find('[name="' + field + '"]');

            if (input.length === 0) {
                return;
            }

            input.addClass('is-invalid');
            $('<div class="invalid-feedback d-block dynamic-invalid-feedback"></div>')
                .text(message)
                .insertAfter(input);
        });

        if (feedback.hasClass('d-none')) {
            showFeedback('danger', 'Please correct the highlighted payment fields and try again.');
        }
    }

    paymentMethodSelect.on('change', function() {
        let method = $(this).val();
        let needsRef = ['mtn_momo', 'vodafone_cash', 'airteltigo_money', 'bank_transfer', 'card', 'cheque'].includes(method);
        referenceGroup.toggle(needsRef);
    }).trigger('change');
    invoiceReceivableSelect.on('change', syncReceivablePaymentLimit);
    targetPayerTypeSelect.on('change', syncReallocationTarget).trigger('change');
    reallocationSource.on('change', syncReallocationAmountLimit).trigger('change');

    // Line-allocation toggle: enable amount + hidden id when checkbox ticked,
    // and sum allocations into the main amount field.
    function syncAllocations() {
        const $rows = $('.alloc-toggle');
        if (!$rows.length) return;
        let any = false, sum = 0;
        $rows.each(function() {
            const $row    = $(this).closest('.row');
            const $idHid  = $row.find('.alloc-id');
            const $amount = $row.find('.alloc-amount');
            const on = $(this).is(':checked');
            $idHid.prop('disabled', !on);
            $amount.prop('disabled', !on);
            if (on) {
                any = true;
                sum += parseFloat($amount.val() || 0);
            }
        });
        if (any) {
            $('#paymentAmountInput').val(sum.toFixed(2)).prop('readonly', true);
        } else {
            $('#paymentAmountInput').prop('readonly', false);
        }
    }
    $(document).on('change', '.alloc-toggle, .alloc-amount', syncAllocations);

    paymentForm.on('submit', async function(event) {
        event.preventDefault();

        if (!window.confirm('Record this payment?')) {
            return;
        }

        clearValidationErrors();
        recordPaymentBtn.prop('disabled', true).html('<i class="ti ti-loader me-1"></i>Recording...');

        try {
            const response = await fetch(paymentForm.attr('action'), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(paymentForm[0])
            });

            const payload = (response.headers.get('content-type') || '').includes('application/json')
                ? await response.json()
                : {};

            if (response.status === 422 && payload.errors) {
                applyValidationErrors(payload.errors);
                return;
            }

            if (!response.ok) {
                showFeedback('danger', payload.message || 'Unable to record payment right now.');
                return;
            }

            updateInvoiceState(payload);

            showFeedback(
                'success',
                '<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">'
                    + '<div><strong>' + (payload.message || 'Payment recorded successfully.') + '</strong><div class="small text-muted">Invoice: ' + (payload.invoice_status_label || '') + (payload.visit_status_label ? ' | Visit: ' + payload.visit_status_label : '') + '</div></div>'
                    + '<div class="d-flex gap-2"><a href="' + (payload.receipt_url || '#') + '" class="btn btn-sm btn-success">Receipt</a>' + (payload.redirect_url ? '<a href="' + payload.redirect_url + '" class="btn btn-sm btn-outline-success">Open Invoice</a>' : '') + '</div>'
                    + '</div>'
            );
        } catch (error) {
            showFeedback('danger', 'Network error while recording payment.');
        } finally {
            if (!invoiceMarkedPaid) {
                recordPaymentBtn.prop('disabled', false).html(originalButtonHtml);
            }
        }
    });
});
</script>
@endsection
