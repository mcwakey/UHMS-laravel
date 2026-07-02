@extends('layouts.app')
@section('title', __('invoices.invoice') . ' ' . $invoice->invoice_number)

@section('content')
@php
    $org = \App\Models\Setting::getGroup('organization');
    $orgName = $org['name'] ?? config('app.name', 'UHMS');
    $orgLogo = !empty($org['logo']) ? asset('storage/'.$org['logo']) : URL::asset('build/img/logo.svg');
    $orgAddress = collect([$org['address'] ?? null, $org['city'] ?? null, $org['region'] ?? null])->filter()->implode(', ');
    $orgContact = collect([$org['phone'] ?? null, $org['email'] ?? null])->filter()->implode('  ·  ');
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
    $canReversePayments = $currentUser?->can('payments.refund') ?? false;
    $canReverseCreditNotes = ($currentUser?->can('credit_notes.create') ?? false)
        || ($currentUser?->can('billing.credit_note.reverse') ?? false);
    $canReverseWriteOffs = ($currentUser?->can('credit_notes.write_off') ?? false)
        || ($currentUser?->can('billing.write_off.reverse') ?? false);
    $canReverseSettlements = $canReversePayments || $canReverseCreditNotes || $canReverseWriteOffs;
    $showSettlementActions = $canViewAccountingPosting || $canReverseSettlements || $invoice->payments->isNotEmpty();
    $canIssueCreditNote = $currentUser?->can('credit_notes.create') ?? false;
    $canIssueWriteOff = $currentUser?->can('credit_notes.write_off') ?? false;
    $canIssueAdjustment = ($canIssueCreditNote || $canIssueWriteOff)
        && ! in_array($invoice->status, [\App\Enums\InvoiceStatus::CANCELLED, \App\Enums\InvoiceStatus::REFUNDED], true)
        && (float) $invoice->balance > 0;
    $creditableAmount = app(\App\Services\CreditNoteService::class)->availableToCredit($invoice);
    $canVoidInvoice = ($currentUser?->can('invoices.void') ?? false)
        && ! in_array($invoice->status, [\App\Enums\InvoiceStatus::PAID, \App\Enums\InvoiceStatus::CANCELLED], true);
    $hasQuickActions = $invoice->visit || $invoice->patient || $invoice->bloodRequest || $canVoidInvoice;
    $accountingStatusColors = [
        'pending' => 'secondary',
        'posted' => 'success',
        'failed' => 'danger',
        'reversed' => 'warning',
    ];
    $accountingStatusLabel = fn ($status) => __('statuses.default.' . ($status ?: 'pending'));
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
    $itemSettlement = app(\App\Services\Billing\InvoiceItemSettlementService::class);
    $cashierReceivables = $invoice->receivables
        ->reject(fn ($r) => $r->payer_type === \App\Models\InvoiceReceivable::PAYER_INSURANCE)
        ->values();
    $openReceivables = $cashierReceivables->filter(fn ($r) => (float) $r->balance > 0)->values();
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
            <a href="{{ route('admin.billing.invoices.index') }}"><i class="ti ti-chevron-left me-1 fs-14"></i>{{ __('invoices.title') }}</a>
        </h6>
    </div>
    <div class="d-flex gap-2">
        @can('claims.view')
        @if($invoiceClaim)
        <a href="{{ route('admin.claims.show', $invoiceClaim) }}" class="btn btn-outline-primary btn-md">
            <i class="ti ti-file-dollar me-1"></i>{{ __('billing.view_insurance_claim') }}
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
                        <i class="ti ti-file-plus me-1"></i>{{ __('billing.generate_insurance_claim') }}
                    </button>
                </form>
                @else
                <a href="{{ route('admin.claims.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-primary btn-md">
                    <i class="ti ti-file-plus me-1"></i>{{ __('billing.generate_insurance_claim') }}
                </a>
                @endif
            @endcan
        @endif
        <a data-no-inertia href="{{ route('admin.billing.invoices.print', $invoice) }}" target="_blank" class="btn btn-dark btn-md">
            <i class="ti ti-printer me-1"></i>{{ __('common.print') }}
        </a>
        <a data-no-inertia href="{{ route('admin.billing.invoices.pdf', $invoice) }}" class="btn btn-outline-danger btn-md">
            <i class="ti ti-file-type-pdf me-1"></i>{{ __('invoices.download_pdf') }}
        </a>
        @can('invoices.edit')
        @if(!in_array($invoice->status, [\App\Enums\InvoiceStatus::CANCELLED, \App\Enums\InvoiceStatus::REFUNDED, \App\Enums\InvoiceStatus::PAID], true))
        <a href="{{ route('admin.billing.invoices.edit', $invoice) }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-edit me-1"></i>{{ __('common.edit') }}
        </a>
        @endif
        @endcan
        @if($canIssueAdjustment)
        <button type="button" class="btn btn-outline-info btn-md" data-bs-toggle="modal" data-bs-target="#creditNoteModal">
            <i class="ti ti-receipt-refund me-1"></i>{{ __('invoices.credit_note') }}
        </button>
        @endif
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
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3 mb-4" style="border-bottom:2px solid var(--bs-primary);">
                    <div class="d-flex align-items-start gap-3">
                        <img src="{{ $orgLogo }}" alt="{{ $orgName }}" style="max-height:48px; max-width:170px;">
                        <div>
                            <div class="fw-bold fs-16 lh-sm">{{ $orgName }}</div>
                            @if($orgAddress)<div class="text-muted small">{{ $orgAddress }}</div>@endif
                            @if($orgContact)<div class="text-muted small">{{ $orgContact }}</div>@endif
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-uppercase fw-bold text-primary" style="font-size:22px; letter-spacing:1px;">{{ __('invoices.invoice_label') }}</div>
                        <div class="fw-semibold">{{ $invoice->invoice_number }}</div>
                        <span id="invoiceStatusBadge" class="badge bg-{{ $invoice->status->color() }} fs-13 px-3 py-2 mt-1">{{ $invoice->status->translatedLabel() }}</span>
                    </div>
                </div>

                <!-- Invoice Info Row -->
                <div class="row g-3 mb-4 pb-3 border-bottom">
                    <div class="col-md-4">
                        <div class="text-uppercase fw-semibold text-muted small mb-2" style="letter-spacing:.04em;">{{ __('invoices.invoice_details') }}</div>
                        <dl class="row mb-0 small gx-2">
                            <dt class="col-5 fw-normal text-muted">{{ __('invoices.invoice_date') }}</dt>
                            <dd class="col-7 mb-1 text-end">{{ $invoice->created_at->format('d M Y') }}</dd>
                            <dt class="col-5 fw-normal text-muted">{{ __('invoices.due_date') }}</dt>
                            <dd class="col-7 mb-1 text-end">{{ $invoice->due_date?->format('d M Y') ?? '—' }}</dd>
                            <dt class="col-5 fw-normal text-muted">{{ __('common.type') }}</dt>
                            <dd class="col-7 mb-0 text-end"><span class="badge bg-soft-{{ $invoice->billing_type->color() }}">{{ $invoice->billing_type->translatedLabel() }}</span></dd>
                        </dl>
                        @if($canViewAccountingPosting)
                            <div class="mt-2 small">
                                <span class="text-muted">{{ __('invoices.accounting') }}:</span>
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
                                        <button type="submit" class="btn btn-link btn-sm p-0 align-baseline">{{ __('invoices.retry') }}</button>
                                    </form>
                                @endif
                                @if($invoice->accounting_status === 'failed' && $canViewAccountingFailures && $invoice->accounting_error)
                                    <div class="text-danger mt-1">{{ $invoice->accounting_error }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <div class="text-uppercase fw-semibold text-muted small mb-2" style="letter-spacing:.04em;">{{ $invoice->patient ? __('common.patient') : __('invoices.recipient') }}</div>
                        @if($invoice->patient)
                            <div class="fw-semibold">{{ $invoice->patient->full_name }}</div>
                            <div class="text-muted small">{{ $invoice->patient->patient_number }}</div>
                            @if($invoice->patient->phone)<div class="text-muted small"><x-patient-protected-field field="phone" :value="$invoice->patient->phone" /></div>@endif
                        @else
                            <div class="fw-semibold">{{ $invoice->external_party_name ?? __('invoices.external_recipient') }}</div>
                            <div class="mt-1"><span class="badge bg-purple-lt">{{ __('billing.external_referral') }}</span></div>
                            @if($invoice->bloodRequest)<div class="text-muted small mt-1">{{ __('invoices.blood_requests') }} {{ $invoice->bloodRequest->request_number }}</div>@endif
                        @endif
                    </div>
                    <div class="col-md-4">
                        <div class="text-uppercase fw-semibold text-muted small mb-2" style="letter-spacing:.04em;">{{ __('invoices.visit') }}</div>
                        @if($invoice->visit)
                            <div class="fw-semibold">{{ $invoice->visit->visit_number }}</div>
                            <div id="invoiceVisitStatusLabel" class="text-muted small">{{ $invoice->visit->status->translatedLabel() }}</div>
                            <div class="text-muted small">{{ $invoice->visit->visit_date->format('d M Y') }}</div>
                        @else
                            <span id="invoiceVisitStatusLabel" class="text-muted">—</span>
                        @endif
                    </div>
                </div>

                <!-- Items Table -->
                <h6 class="fw-bold mb-3">{{ __('invoices.service_items') }}</h6>
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
                                <th>{{ __('invoices.description') }}</th>
                                <th>{{ __('invoices.pricing') }}</th>
                                <th class="text-end">{{ __('invoices.price') }}</th>
                                <th class="text-end">{{ __('invoices.covered') }}</th>
                                <!-- <th class="text-end">{{ __('invoices.discount') }}</th> -->
                                <th class="text-end">{{ __('invoices.patient_payable') }}</th>
                                <th class="text-end">{{ __('invoices.paid') }}</th>
                                <th class="text-end">{{ __('invoices.balance') }}</th>
                                <!-- <th class="text-center">{{ __('common.status') }}</th> -->
                                @if($canDiscountActions)
                                <th class="text-center" style="width:60px;">{{ __('common.actions') }}</th>
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
                                $meta      = $sourceLabels[$src] ?? [__('statuses.default.' . $src), 'light text-dark'];
                                $payer     = $item->payer_type ?? 'cash';
                                $lineTotalForCoverage = round($selectedPrice * max(1, (int) $item->quantity), 2);
                                $coverageAmount = (float) $item->insurance_covered;
                                $coveragePercent = $lineTotalForCoverage > 0 && $coverageAmount > 0
                                    ? round(($coverageAmount / $lineTotalForCoverage) * 100, 2)
                                    : null;
                                $rawSourceKey  = $item->source_type ?: ($item->service_catalog_id ? 'service_catalog' : 'other');
                                $sourceKey  = $sourceTypeGroups[$rawSourceKey] ?? $rawSourceKey;
                                $departmentKey = $item->department_id ? 'department_'.$item->department_id : 'department_none';
                                $groupKey  = $sourceKey.'|'.$departmentKey;
                                $groupLabel = $sourceTypeLabels[$sourceKey] ?? __('statuses.default.' . $sourceKey);
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
                                        <!-- <i class="ti ti-{{ $payer === 'insurance' ? 'shield-check' : 'cash' }} me-1"></i>{{ __('statuses.default.' . $payer) }} -->
                                        <i class="ti ti-{{ $payer === 'insurance' ? 'shield-check' : 'cash' }} me-1"></i>{{ ucfirst($payer) }}
                                    </div>
                                </td>
                                {{-- <td class="text-end fw-semibold">&#8373;{{ number_format($selectedPrice, 2) }}</td> --}}
                                <td class="text-end">
                                    @if($item->cash_price > $selectedPrice)
                                    <div class="small text-muted text-decoration-line-through">&#8373;{{ number_format($item->cash_price, 2) }}</div>
                                    @endif
                                    <span class="fw-semibold">&#8373;{{ number_format($selectedPrice, 2) }}</span>
                                    <!-- @if($item->insurance_price !== null)
                                    <div class="small text-muted">{{ __('invoices.selected_insurance_price') }}</div>
                                    @endif -->
                                </td>
                                <!-- <td class="text-end">
                                    @if($item->cash_price > $selectedPrice)
                                    <div class="small text-muted text-decoration-line-through">&#8373;{{ number_format($item->cash_price, 2) }}</div>
                                    @endif
                                    <span class="fw-semibold">&#8373;{{ number_format($selectedPrice, 2) }}</span>
                                    @if($item->insurance_price !== null)
                                    <div class="small text-muted">{{ __('invoices.selected_insurance_price') }}</div>
                                    @endif
                                </td> -->
                                {{-- <td class="text-end fw-medium">&#8373;{{ number_format($item->total_price, 2) }}</td> --}}
                                <td class="text-end">
                                    @if($item->cash_price > $selectedPrice)
                                    <div class="small text-warning text-decoration-line-through">&#8373;{{ number_format(($item->cash_price - $selectedPrice), 2) }}</div>
                                    @endif

                                    @if((float) $item->insurance_covered > 0)
                                    <span class="text-success">&#8373;{{ number_format($item->insurance_covered, 2) }}</span>
                                    @if($coveragePercent !== null)
                                    <div class="small text-muted">{{ __('invoices.coverage_percent', ['percent' => number_format($coveragePercent, 2)]) }}</div>
                                    @endif
                                    @else
                                    —
                                    @endif
                                </td>
                                <!-- <td class="text-end">
                                    @if((float) $item->discount_amount > 0)
                                    <span class="text-danger">-&#8373;{{ number_format($item->discount_amount, 2) }}</span>
                                    @else
                                    —
                                    @endif
                                </td> -->
                                <td class="text-end">
                                    @if((float) $item->discount_amount > 0)
                                    <span class="text-danger">-&#8373;{{ number_format($item->discount_amount, 2) }}</span>
                                    @endif
                                    &#8373;{{ number_format($item->patient_payable, 2) }}
                                </td>
                                <td class="text-end">&#8373;{{ number_format($item->paid_amount, 2) }}</td>
                                <td class="text-end {{ (float) $item->balance > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                    @if((float) $item->balance > 0)
                                    &#8373;{{ number_format($item->balance, 2) }}
                                    @else
                                    <span class="badge bg-{{ $payColor }} text-uppercase">{{ str_replace('_',' ', $payStatus) }}</span>
                                    @endif
                                </td>
                                <!-- <td class="text-center">
                                    <span class="badge bg-{{ $payColor }} text-uppercase">{{ str_replace('_',' ', $payStatus) }}</span>
                                </td> -->
                                @if($canDiscountActions)
                                <td class="text-center">
                                    @if(! in_array($payStatus, ['paid','cancelled','voided','waived']))
                                    @if($canApplyDiscount)
                                    <button type="button"
                                            class="btn btn-sm btn-outline-warning mb-1"
                                            title="{{ __('invoices.apply_discount') }}"
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
                                         button-label="" button-class="btn btn-sm btn-outline-danger" icon="ti-x"
                                         :confirm-title="__('invoices.remove_discount_title')"
                                         :confirm-text="__('invoices.remove_discount_text')"
                                         :confirm-button="__('invoices.remove_discount_btn')"
                                         :require-reason="true"
                                         :reason-placeholder="__('invoices.remove_discount_reason')" />
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
                            <h6 class="fw-bold mb-1">{{ __('invoices.notes') }}</h6>
                            <p class="text-muted">{{ $invoice->notes }}</p>
                        </div>
                        @endif
                        <p class="text-muted mb-1">{{ __('common.created_by') }}: <span class="text-dark">{{ $invoice->createdBy->name ?? '—' }}</span></p>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light rounded p-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('invoices.gross_total') }}</span>
                            <span class="fw-medium">&#8373;{{ number_format($invoiceBalanceSummary['gross_total'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('invoices.discounts') }}</span>
                            <span class="text-danger">-&#8373;{{ number_format($invoiceBalanceSummary['discounts'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('invoices.credit_notes') }}</span>
                            <span class="text-info">-&#8373;{{ number_format($invoiceBalanceSummary['credit_notes'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('invoices.write_offs') }}</span>
                            <span class="text-dark">-&#8373;{{ number_format($invoiceBalanceSummary['write_offs'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-success fw-medium">{{ __('invoices.payments') }}</span>
                            <span id="invoicePaidValue" class="text-success fw-medium" data-amount="{{ $invoice->amount_paid }}">-&#8373;{{ number_format($invoiceBalanceSummary['payments'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-danger fw-medium">{{ __('invoices.refunds_reversals') }}</span>
                            <span class="text-danger">+&#8373;{{ number_format($invoiceBalanceSummary['refunds'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2">
                            <span class="fw-bold text-danger">{{ __('invoices.balance_label') }}</span>
                            <span id="invoiceBalanceValue" class="fw-bold text-danger fs-5" data-amount="{{ $invoice->balance }}">&#8373;{{ number_format($invoiceBalanceSummary['outstanding_balance'], 2) }}</span>
                        </div>
                        @if(! $invoiceBalanceSummary['formula_matches_invoice'])
                        <div class="alert alert-warning py-2 mt-2 mb-0 small">
                            Formula balance is &#8373;{{ number_format($invoiceBalanceSummary['formula_balance'], 2) }}. Invoice balance is &#8373;{{ number_format($invoiceBalanceSummary['outstanding_balance'], 2) }}.
                        </div>
                        @endif
                        </div>
                    </div>
                </div>

                @if($canViewReceivables)
                <hr>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0"><i class="ti ti-users-group me-1"></i>{{ __('invoices.payer_responsibility') }}</h6>
                    @if($canReallocateReceivables && $openReceivables->isNotEmpty())
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#receivableReallocationModal">
                        <i class="ti ti-arrows-exchange me-1"></i>{{ __('invoices.reallocate') }}
                    </button>
                    @endif
                </div>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('invoices.payer') }}</th>
                                <th>{{ __('common.type') }}</th>
                                <th class="text-end">{{ __('invoices.allocated') }}</th>
                                <th class="text-end">{{ __('invoices.paid') }}</th>
                                <th class="text-end">{{ __('invoices.adjustments') }}</th>
                                <th class="text-end">{{ __('invoices.balance') }}</th>
                                <th>{{ __('invoices.due_aging') }}</th>
                                <th>{{ __('common.status') }}</th>
                                @if($canViewAccountingPosting)
                                <th>{{ __('invoices.journal_entry') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cashierReceivables as $receivable)
                            @php
                                $adjustments = (float) $receivable->credit_note_amount + (float) $receivable->write_off_amount;
                                $agingReference = $receivable->due_date ?: $receivable->aging_start_date;
                                $agingDays = $agingReference ? max(0, $agingReference->diffInDays(now(), false)) : 0;
                            @endphp
                            <tr>
                                <td class="fw-medium">{{ $receivable->payerName() }}</td>
                                <td><span class="badge bg-{{ $receivable->payerBadgeColor() }}">{{ __('statuses.default.' . $receivable->payer_type) }}</span></td>
                                <td class="text-end">&#8373;{{ number_format($receivable->allocated_amount, 2) }}</td>
                                <td class="text-end text-success">&#8373;{{ number_format($receivable->paid_amount, 2) }}</td>
                                <td class="text-end">&#8373;{{ number_format($adjustments, 2) }}</td>
                                <td class="text-end fw-semibold {{ (float) $receivable->balance > 0 ? 'text-danger' : 'text-muted' }}">&#8373;{{ number_format($receivable->balance, 2) }}</td>
                                <td>
                                    <div>{{ $receivable->due_date?->format('d M Y') ?? '—' }}</div>
                                    <small class="text-muted">{{ $agingDays }} {{ $agingDays === 1 ? 'day' : 'days' }}</small>
                                </td>
                                <td><span class="badge bg-{{ $receivableStatusColor($receivable->status) }}">{{ __('statuses.default.' . $receivable->status) }}</span></td>
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
                                <td colspan="{{ $canViewAccountingPosting ? 9 : 8 }}" class="text-center text-muted py-3">{{ __('invoices.no_payer_rows') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @endif

                <hr>
                <h6 class="fw-bold mb-3"><i class="ti ti-adjustments-dollar me-1"></i>{{ __('invoices.adjustments_settlements') }}</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('common.date') }}</th>
                                <!-- <th>{{ __('common.type') }}</th> -->
                                <th>{{ __('invoices.reference') }}</th>
                                <th>{{ __('common.details') }}</th>
                                <th class="text-end">{{ __('common.amount') }}</th>
                                <th>{{ __('common.reason') }}</th>
                                <!-- <th>{{ __('common.status') }}</th> -->
                                <th>{{ __('invoices.approved_by') }}</th>
                                @if($canViewAccountingPosting)
                                <th>{{ __('invoices.journal_entry') }}</th>
                                @endif
                                @if($showSettlementActions)
                                <th>{{ __('common.action') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adjustmentHistory as $history)
                            <tr>
                                <td>{{ $history['date']?->format('d M Y H:i') ?? '—' }}</td>
                                <!-- <td><span class="badge bg-{{ $history['badge'] }}">{{ $history['type'] }}</span></td> -->
                                <td>
                                    <span class="badge bg-{{ $history['badge'] }}">{{ $history['type'] }}</span>
                                    <span class="fw-medium">{{ $history['reference'] }}</span>
                                    @if($history['payment_reference'])
                                        <div class="small text-muted">{{ $history['payment_reference'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($history['payment_method'])
                                        <span class="badge bg-soft-primary">{{ $history['payment_method'] }}</span>
                                        @if($history['payer_type'])
                                            <div class="small mt-1">
                                                {{ __('statuses.default.' . $history['payer_type']) }}
                                                @if($history['payer_name']) - {{ $history['payer_name'] }} @endif
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">&#8373;{{ number_format($history['amount'], 2) }}</td>
                                <td>{{ $history['reason'] ?: '—' }}</td>
                                <!-- <td>{{ $history['status'] }}</td> -->
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
                                            {{ __('invoices.reversal') }}:
                                            <a href="{{ route('admin.accounting.journals.show', $history['reversal_journal']) }}">{{ $history['reversal_journal']->journal_number }}</a>
                                        </div>
                                    @endif
                                    @if($history['accounting_status'] === 'failed' && $canViewAccountingFailures && $history['accounting_error'])
                                        <div class="small text-danger">{{ $history['accounting_error'] }}</div>
                                    @endif
                                </td>
                                @endif
                                @if($showSettlementActions)
                                <td class="text-nowrap">
                                    @php
                                        $canReverseHistory = ($history['can_reverse'] ?? false)
                                            && match ($history['reversal_kind'] ?? null) {
                                                'payment' => $canReversePayments,
                                                'credit_note' => $canReverseCreditNotes,
                                                'write_off' => $canReverseWriteOffs,
                                                default => false,
                                            };
                                    @endphp
                                    @if($canReverseHistory)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#reverseSettlementModal"
                                                data-reverse-url="{{ $history['reverse_url'] }}"
                                                data-reverse-reference="{{ $history['reference'] }}"
                                                data-reverse-type="{{ $history['type'] }}">
                                            <i class="ti ti-arrow-back-up me-1"></i>
                                        </button>
                                    @endif
                                    @if($history['receipt_url'])
                                    <div class="dropdown mt-1">
                                        <a aria-label="Actions" title="Actions" href="javascript:void(0);" class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a data-no-inertia class="dropdown-item" href="{{ $history['receipt_url'] }}"><i class="ti ti-eye me-2"></i>{{ __('payments.view_receipt') }}</a></li>
                                            <li><a data-no-inertia class="dropdown-item" href="{{ $history['receipt_thermal_url'] }}"><i class="ti ti-printer me-2"></i>{{ __('payments.print_receipt_80mm') }}</a></li>
                                            <!-- <li><a class="dropdown-item" href="{{ $history['receipt_thermal_url'] }}"><i class="ti ti-edit me-2"></i>{{ __('common.edit') }}</a></li> -->
                                            <li><a data-no-inertia class="dropdown-item" href="{{ $history['receipt_pdf_url'] }}"><i class="ti ti-file-type-pdf me-2"></i>{{ __('payments.download_pdf') }}</a></li>
                                        </ul>
                                    </div>
                                        <!-- <a data-no-inertia href="{{ $history['receipt_url'] }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="{{ __('payments.view_receipt') }}">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a data-no-inertia href="{{ $history['receipt_thermal_url'] }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="{{ __('payments.print_receipt_80mm') }}">
                                            <i class="ti ti-printer"></i>
                                        </a>
                                        <a data-no-inertia href="{{ $history['receipt_pdf_url'] }}" class="btn btn-sm btn-outline-secondary" title="{{ __('payments.download_pdf') }}">
                                            <i class="ti ti-file-type-pdf"></i>
                                        </a> -->
                                    @endif
                                    @if($history['accounting_status'] === 'failed' && $canRetryAccountingPosting)
                                        <form method="POST" action="{{ route('admin.accounting.postings.retry') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="source_type" value="{{ $history['retry_source_type'] }}">
                                            <input type="hidden" name="source_id" value="{{ $history['retry_source_id'] }}">
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                <i class="ti ti-refresh me-1"></i>
                                            </button>
                                        </form>
                                    @elseif(! $canReverseHistory && ! $history['receipt_url'])
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ 8 + ($canViewAccountingPosting ? 1 : 0) + ($showSettlementActions ? 1 : 0) }}" class="text-center text-muted py-3">{{ __('invoices.no_adjustments') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($canViewDiscountHistory)
                <hr>
                <h6 class="fw-bold mb-3"><i class="ti ti-discount-2 me-1"></i>{{ __('invoices.discount_history') }}</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('common.date') }}</th>
                                <th>{{ __('invoices.item') }}</th>
                                <th class="text-end">{{ __('invoices.old_discount') }}</th>
                                <th class="text-end">{{ __('invoices.new_discount') }}</th>
                                <th>{{ __('invoices.risk') }}</th>
                                <th>{{ __('common.reason') }}</th>
                                <th>{{ __('invoices.user') }}</th>
                                @if($canViewAccountingPosting)
                                <th>{{ __('invoices.accounting') }}</th>
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
                                            <button type="submit" class="btn btn-link btn-sm p-0">{{ __('invoices.retry') }}</button>
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
                                <td colspan="{{ $canViewAccountingPosting ? 8 : 7 }}" class="text-center text-muted py-3">{{ __('invoices.no_discount_history') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @endif

            </div>
        </div>
    </div>

    <!-- Right Sidebar: Record Payment -->
    <div class="col-lg-4">

        {{-- Awaiting Mobile Money payment: pending gateway charges for this invoice,
             with a manual Recheck and a live status poll. Only shows while pending. --}}
        @php
            $gatewayModuleActive = app(\App\Services\ModuleService::class)->enabled('payment_gateway')
                && app(\App\Services\Integrations\Payment\PaymentProviderResolver::class)->activeProvider() !== null;
            $pendingMomoCharges = $gatewayModuleActive
                ? \App\Models\PaymentProviderTransaction::where('invoice_id', $invoice->id)
                    ->whereIn('status', ['initiated', 'pending', 'requires_customer_action'])
                    ->latest()->get()
                : collect();
        @endphp
        @if($pendingMomoCharges->isNotEmpty())
        <div class="card border-warning mb-3" id="pendingMomoCard"
            data-status-url="{{ route('admin.integrations.payments.transactions.status', $pendingMomoCharges->first()) }}">
            <div class="card-header bg-warning-subtle">
                <h6 class="fw-bold mb-0"><i class="ti ti-clock-hour-4 me-1 spinner-grow spinner-grow-sm"></i>{{ __('payments.gateway.awaiting_payment') }}</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">{{ __('payments.gateway.awaiting_instructions') }}</p>
                @foreach($pendingMomoCharges as $charge)
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="small"><code>{{ $charge->payment_reference }}</code></div>
                            <div class="fw-semibold">{{ $charge->currency }} {{ number_format((float) $charge->amount, 2) }}</div>
                            @if($charge->payer_phone)<div class="text-muted small">{{ $charge->payer_phone }}</div>@endif
                        </div>
                        <x-status-badge :status="$charge->status" domain="payment_transaction" size="sm" />
                    </div>
                    @can('integrations.payments.transactions.verify')
                    <form method="POST" action="{{ route('admin.integrations.payments.transactions.verify-inline', $charge) }}" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                            <i class="ti ti-refresh me-1"></i>{{ __('payments.gateway.recheck') }}
                        </button>
                    </form>
                    @endcan
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if(!in_array($invoice->status, [\App\Enums\InvoiceStatus::PAID, \App\Enums\InvoiceStatus::CANCELLED, \App\Enums\InvoiceStatus::REFUNDED]) && $openReceivables->isNotEmpty())
        <div class="card border-primary" id="recordPaymentCard">
            <div class="card-header bg-primary text-white">
                <h6 class="fw-bold mb-0"><i class="ti ti-cash me-1"></i>{{ __('invoices.record_payment') }}</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-warning py-2 mb-3" id="invoiceOutstandingAlert">
                    <small><strong>{{ __('invoices.outstanding_label') }}:</strong> <span id="invoiceOutstandingValue">&#8373;{{ number_format($invoice->balance, 2) }}</span></small>
                </div>

                @php
                    $paymentGatewayAvailable = app(\App\Services\ModuleService::class)->enabled('payment_gateway')
                        && app(\App\Services\Integrations\Payment\PaymentProviderResolver::class)->activeProvider() !== null
                        && (bool) auth()->user()?->can('integrations.payments.transactions.initiate');
                @endphp
                <form method="POST" action="{{ route('admin.billing.payments.store', $invoice) }}" id="paymentForm"
                    @if($paymentGatewayAvailable) data-gateway-charge="{{ route('admin.integrations.payments.invoices.charge', $invoice) }}" @endif>
                    @csrf
                    @if($openReceivables->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('invoices.paying_party') }} <span class="text-danger">*</span></label>
                        <select name="invoice_receivable_id" id="invoiceReceivableSelect" class="form-select @error('invoice_receivable_id') is-invalid @enderror" required>
                            @foreach($openReceivables as $receivable)
                            <option value="{{ $receivable->id }}"
                                    data-balance="{{ number_format((float) $receivable->balance, 2, '.', '') }}"
                                    data-payer-type="{{ $receivable->payer_type }}"
                                    {{ (int) $defaultPaymentReceivable?->id === (int) $receivable->id ? 'selected' : '' }}>
                                {{ __('statuses.default.' . $receivable->payer_type) }} - {{ $receivable->payerName() }} (&#8373;{{ number_format($receivable->balance, 2) }})
                            </option>
                            @endforeach
                        </select>
                        @error('invoice_receivable_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('invoices.amount_label') }} <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="paymentAmountInput" class="form-control @error('amount') is-invalid @enderror"
                            value="{{ old('amount', number_format($defaultPaymentAmount, 2, '.', '')) }}" step="0.01" min="0.01" max="{{ number_format($defaultPaymentAmount, 2, '.', '') }}" required>
                        @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('payments.payment_method') }} <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required id="paymentMethodSelect">
                            <option value="cash">{{ __('payments.cash') }}</option>
                            @if($paymentGatewayAvailable)
                            <option value="mobile_money">{{ __('payments.mobile_money') }}</option>
                            @endif
                            <option value="bank_transfer">{{ __('payments.bank_transfer') }}</option>
                            <option value="card">{{ __('payments.card') }}</option>
                            <option value="cheque">{{ __('payments.cheque') }}</option>
                        </select>
                        @error('payment_method')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($paymentGatewayAvailable)
                    {{-- Mobile Money: revealed when the method above is "Mobile Money".
                         On submit the form posts to the payment gateway (charge), using
                         the Amount entered above; the invoice is deducted after verification. --}}
                    <div class="mb-3" id="momoFields" style="display:none;">
                        <label class="form-label fw-medium">{{ __('payments.gateway.payer_phone') }} <span class="text-danger">*</span></label>
                        <input type="tel" name="payer_phone" id="momoPhone" class="form-control"
                            value="{{ old('payer_phone', $invoice->patient?->phone) }}" autocomplete="off">
                        <label class="form-label fw-medium mt-2">{{ __('payments.gateway.method') }}</label>
                        <select name="mobile_network" class="form-select">
                            <option value="mtn_momo">{{ __('payments.gateway.method_mtn_momo') }}</option>
                            <option value="vodafone_cash">{{ __('payments.gateway.method_telecel_cash') }}</option>
                            <option value="airteltigo_money">{{ __('payments.gateway.method_airteltigo_money') }}</option>
                        </select>
                        <div class="form-text">{{ __('payments.gateway.inline_hint') }}</div>
                    </div>
                    @endif

                    <div class="mb-3" id="referenceGroup" style="display:none;">
                        <label class="form-label fw-medium">{{ __('payments.payment_reference') }}</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="e.g. MoMo Transaction ID">
                    </div>

                    {{-- Per-line allocations: optional. If none ticked, payment auto-distributes oldest-first. --}}
                    @php
                        $unpaidItems = $invoice->items->filter(function($i) use ($itemSettlement) {
                            return !in_array($i->payment_status, ['paid','cancelled','voided'])
                                && $itemSettlement->outstandingBalance($i) > 0;
                        })->values();
                    @endphp
                    @if($unpaidItems->isNotEmpty())
                    <div class="mb-3">
                        <details>
                            <summary class="fw-medium text-primary" style="cursor:pointer;">
                                <i class="ti ti-list-check me-1"></i>{{ __('invoices.pay_specific_items') }}
                                <small class="text-muted">({{ __('invoices.auto_distribute') }})</small>
                            </summary>
                            <div class="mt-2 border rounded p-2" style="max-height:260px;overflow:auto;">
                                @foreach($unpaidItems as $uIdx => $uItem)
                                <div class="row g-1 align-items-center mb-2 py-1 border-bottom">
                                    <div class="col-auto">
                                        <input type="checkbox" class="form-check-input alloc-toggle" data-row="{{ $uIdx }}">
                                    </div>
                                    <div class="col">
                                        <div class="small fw-medium">{{ $uItem->description }}</div>
                                        @php $itemCashierBalance = $itemSettlement->outstandingBalance($uItem); @endphp
                                        <div class="small text-muted">{{ __('invoices.balance_label_item') }}: &#8373;{{ number_format($itemCashierBalance, 2) }}</div>
                                        <input type="hidden" name="allocations[{{ $uIdx }}][invoice_item_id]" value="{{ $uItem->id }}" disabled class="alloc-id">
                                    </div>
                                    <div class="col-4">
                                        <input type="number" step="0.01" min="0.01" max="{{ number_format($itemCashierBalance, 2, '.', '') }}"
                                            name="allocations[{{ $uIdx }}][amount]"
                                            value="{{ number_format($itemCashierBalance, 2, '.', '') }}"
                                            class="form-control form-control-sm alloc-amount" disabled>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </details>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('payments.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('common.optional') }}"></textarea>
                    </div>

                    @can('payments.create')
                    <button type="submit" class="btn btn-primary w-100" id="recordPaymentBtn">
                        <i class="ti ti-check me-1"></i>{{ __('invoices.record_payment') }}
                    </button>
                    @endcan
                </form>
            </div>
        </div>
        @elseif(!in_array($invoice->status, [\App\Enums\InvoiceStatus::PAID, \App\Enums\InvoiceStatus::CANCELLED, \App\Enums\InvoiceStatus::REFUNDED]))
        <div class="card">
            <div class="card-body text-center py-4">
                <i class="ti ti-shield-dollar text-primary fs-1 d-block mb-2"></i>
                <h5 class="mb-1">{{ __('invoices.no_cashier_collectable_balance') }}</h5>
                <p class="text-muted mb-0">{{ __('invoices.insurance_claims_settled_elsewhere') }}</p>
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body text-center py-4">
                @if($invoice->status === \App\Enums\InvoiceStatus::PAID)
                <i class="ti ti-circle-check text-success fs-1 d-block mb-2"></i>
                <h5 class="text-success">{{ __('invoices.fully_paid') }}</h5>
                @elseif($invoice->status === \App\Enums\InvoiceStatus::CANCELLED)
                <i class="ti ti-circle-x text-danger fs-1 d-block mb-2"></i>
                <h5 class="text-danger">{{ __('invoices.cancelled_label') }}</h5>
                @endif
            </div>
        </div>
        @endif

        <!-- Quick Actions -->
        @if($hasQuickActions)
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0">{{ __('invoices.quick_actions') }}</h6>
            </div>
            <div class="card-body d-grid gap-2">
                @if($invoice->visit)
                <a href="{{ route('admin.visits.show', $invoice->visit) }}" class="btn btn-outline-primary">
                    <i class="ti ti-calendar-check me-1"></i>{{ __('invoices.view_visit') }}
                </a>
                @endif
                @if($invoice->patient)
                <a href="{{ route('admin.patients.show', $invoice->patient) }}" class="btn btn-outline-info">
                    <i class="ti ti-user me-1"></i>{{ __('invoices.view_patient') }}
                </a>
                @elseif($invoice->bloodRequest)
                <a href="{{ route('admin.blood-bank.requests.index') }}" class="btn btn-outline-info">
                    <i class="ti ti-droplet me-1"></i>{{ __('invoices.blood_requests') }}
                </a>
                @endif
                @if(!in_array($invoice->status, [\App\Enums\InvoiceStatus::PAID, \App\Enums\InvoiceStatus::CANCELLED]))
                @can('invoices.void')
                <x-confirm-form :action="route('admin.billing.invoices.cancel', $invoice)" method="PATCH"
                    :button-label="__('billing.cancel_invoice')" button-class="btn btn-outline-danger w-100" icon="ti-x"
                    :confirm-title="__('billing.cancel_invoice_title')" :confirm-text="__('billing.cancel_invoice_text')" :confirm-button="__('billing.cancel_invoice_confirm')" />
                @endcan
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

@if($canReallocateReceivables && $openReceivables->isNotEmpty())
<div class="modal fade" id="receivableReallocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.billing.invoices.receivables.reallocate', $invoice) }}" id="receivableReallocationForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-arrows-exchange me-1"></i>{{ __('invoices.reallocate_payer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('invoices.move_from') }} <span class="text-danger">*</span></label>
                        <select name="from_receivable_id" class="form-select" required>
                            @foreach($openReceivables as $receivable)
                            <option value="{{ $receivable->id }}" data-balance="{{ number_format((float) $receivable->balance, 2, '.', '') }}">
                                {{ __('statuses.default.' . $receivable->payer_type) }} - {{ $receivable->payerName() }} ({{ __('invoices.balance_label') }} &#8373;{{ number_format($receivable->balance, 2) }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('invoices.move_to') }} <span class="text-danger">*</span></label>
                        <select name="target_payer_type" id="targetPayerTypeSelect" class="form-select" required>
                            <option value="patient">{{ __('common.patient') }}</option>
                            <option value="sponsor">{{ __('invoices.sponsor_label') ?? 'Sponsor' }}</option>
                            <option value="corporate">{{ __('invoices.corporate') }}</option>
                        </select>
                    </div>
                    <div class="mb-3 payer-target-select d-none" data-payer-target="sponsor">
                        <label class="form-label">{{ __('invoices.sponsor_label') ?? 'Sponsor' }}</label>
                        <select class="form-select target-payer-id" disabled>
                            <option value="">{{ __('billing.select_sponsor') }}</option>
                            @foreach(($receivablePayerOptions['sponsors'] ?? []) as $sponsor)
                            <option value="{{ $sponsor->id }}">{{ $sponsor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 payer-target-select d-none" data-payer-target="corporate">
                        <label class="form-label">{{ __('invoices.corporate') }}</label>
                        <select class="form-select target-payer-id" disabled>
                            <option value="">{{ __('billing.select_corporate_client') }}</option>
                            @foreach(($receivablePayerOptions['corporate'] ?? []) as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="target_payer_id" id="targetPayerIdInput">
                    <div class="mb-3">
                        <label class="form-label">{{ __('invoices.amount_label') }} <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="receivableReallocationAmount" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.reason') }} <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" maxlength="500" required></textarea>
                    </div>
                    <div class="alert alert-warning py-2 mb-0 small">
                        {{ __('invoices.reallocation_warning') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('invoices.reallocate') }}</button>
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
                    <h5 class="modal-title"><i class="ti ti-discount-2 me-1"></i>{{ __('invoices.apply_discount') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2"><strong>{{ __('invoices.item') }}:</strong> <span id="discountItemDesc">—</span></p>
                    <p class="mb-3 text-muted small">{{ __('invoices.subtotal') }}: &#8373;<span id="discountLineTotal">0.00</span></p>
                    <div class="mb-3">
                        <label class="form-label">{{ __('invoices.discount_amount_label') }} <span class="text-danger">*</span></label>
                        <input type="number" name="discount_amount" id="discountAmountInput"
                               class="form-control" step="0.01" min="0.01" required>
                        <div class="form-text">{{ __('invoices.discount_exceed_note') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.reason') }} <span class="text-danger">*</span></label>
                        <textarea name="reason" id="discountReasonInput" class="form-control" rows="3" maxlength="500" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-warning"><i class="ti ti-check me-1"></i>{{ __('invoices.apply_discount') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan

@if($canIssueAdjustment)
<!-- Credit Note / Write-Off Modal -->
<div class="modal fade" id="creditNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.billing.credit-notes.store') }}" id="creditNoteForm">
            @csrf
            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
            <input type="hidden" name="return" value="invoice">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-receipt-refund me-1"></i>{{ __('invoices.issue_credit_note_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center bg-light rounded p-2 mb-3 small">
                        <span class="text-muted">{{ __('invoices.invoice_number') }} <strong class="text-dark">{{ $invoice->invoice_number }}</strong></span>
                        <span class="text-muted">{{ __('invoices.available_to_credit') }}: <strong class="text-dark">&#8373;{{ number_format($creditableAmount, 2) }}</strong></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.type') }} <span class="text-danger">*</span></label>
                        <select name="type" id="creditNoteType" class="form-select" required>
                            @if($canIssueCreditNote)<option value="credit_note">{{ __('invoices.credit_note') }}</option>@endif
                            @if($canIssueWriteOff)<option value="write_off">{{ __('invoices.write_off') }}</option>@endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('invoices.amount_label') }} <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="creditNoteAmount" class="form-control"
                               step="0.01" min="0.01" max="{{ number_format($creditableAmount, 2, '.', '') }}"
                               value="{{ number_format($creditableAmount, 2, '.', '') }}" required>
                        <div class="form-text">{{ __('invoices.adjustment_exceed_note') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.reason') }} <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" maxlength="255" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">{{ __('invoices.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2" maxlength="1000" placeholder="{{ __('invoices.optional_notes') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-info"><i class="ti ti-check me-1"></i>{{ __('invoices.credit_note') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

@if($canReverseSettlements)
<div class="modal fade" id="reverseSettlementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="reverseSettlementForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ti ti-arrow-back-up me-1"></i>{{ __('invoices.reverse_entry') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">
                        <strong id="reverseSettlementType"></strong>
                        <span id="reverseSettlementReference"></span>
                    </p>
                    <div class="alert alert-warning py-2 small">
                        {{ __('invoices.reverse_entry_warning') }}
                    </div>
                    <label class="form-label">{{ __('payments.reversal_reason') }} <span class="text-danger">*</span></label>
                    <textarea name="reason" id="reverseSettlementReason" class="form-control" rows="3" maxlength="500" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-arrow-back-up me-1"></i>{{ __('invoices.confirm_reversal') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
$(function() {
    const reverseSettlementModal = document.getElementById('reverseSettlementModal');
    if (reverseSettlementModal) {
        reverseSettlementModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;

            document.getElementById('reverseSettlementForm').setAttribute('action', button.getAttribute('data-reverse-url'));
            document.getElementById('reverseSettlementType').textContent = button.getAttribute('data-reverse-type') || '';
            document.getElementById('reverseSettlementReference').textContent = button.getAttribute('data-reverse-reference') || '';
            document.getElementById('reverseSettlementReason').value = '';
        });
    }

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
    const paymentAmountInput = $('#paymentAmountInput');
    const invoiceReceivableSelect = $('#invoiceReceivableSelect');
    let paymentNavigationStarted = false;
    const originalButtonHtml = recordPaymentBtn.html();

    function showFeedback(type, html) {
        feedback.removeClass('d-none alert-success alert-danger').addClass('alert-' + type).html(html);
        $('html, body').animate({ scrollTop: 0 }, 200);
    }

    function refreshInvoice(url) {
        paymentNavigationStarted = true;
        const target = url || window.location.href;

        window.location.href = target;
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

    const momoFields = $('#momoFields');
    const momoPhone = $('#momoPhone');
    paymentMethodSelect.on('change', function() {
        let method = $(this).val();
        let isMomo = method === 'mobile_money';
        momoFields.toggle(isMomo);
        momoPhone.prop('required', isMomo);
        let needsRef = ['bank_transfer', 'card', 'cheque'].includes(method);
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
        // Mobile Money → initiate the gateway charge via a normal POST so the
        // provider redirect + flash work (the AJAX path below is for cash etc.).
        if (paymentMethodSelect.val() === 'mobile_money') {
            const gatewayUrl = paymentForm.attr('data-gateway-charge');
            if (gatewayUrl) {
                paymentForm.attr('action', gatewayUrl);
                return; // allow native submission to the gateway
            }
        }

        event.preventDefault();

        if (!window.confirm(@json(__('billing.record_payment_confirm')))) {
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

            refreshInvoice(payload.redirect_url);
        } catch (error) {
            showFeedback('danger', 'Network error while recording payment.');
        } finally {
            if (!paymentNavigationStarted) {
                recordPaymentBtn.prop('disabled', false).html(originalButtonHtml);
            }
        }
    });
});
</script>
<script>
// Live "awaiting payment" poll: while a mobile-money charge is pending, ask the
// gateway for its status; reload the invoice once it resolves (paid / failed).
(function () {
    var card = document.getElementById('pendingMomoCard');
    if (!card) { return; }
    var url = card.getAttribute('data-status-url');
    if (!url) { return; }
    var attempts = 0, max = 20;
    var timer = setInterval(function () {
        if (attempts++ >= max) { clearInterval(timer); return; }
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) { if (d && d.resolved) { clearInterval(timer); window.location.reload(); } })
            .catch(function () {});
    }, 6000);
})();
</script>
@endsection
