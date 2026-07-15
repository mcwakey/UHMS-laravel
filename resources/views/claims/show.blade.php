@extends('layouts.app')
@section('title', __('claims.claim') . ' ' . $claim->claim_number)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            {{ __('claims.claim') }} {{ $claim->claim_number }}
            <x-status-badge :status="$claim->status" class="ms-2" />
        </h4>
    </div>
    <div class="d-flex gap-2">
        @if($claim->is_editable || $claim->status === \App\Enums\ClaimStatus::READY)
            @can('claims.create')
            @if($claim->is_editable)
            <form method="POST" action="{{ $workspaceRoutes->route('admin.claims.validate', $claim) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-info btn-md fs-13">
                    <i class="ti ti-shield-check me-1"></i>{{ __('claims.validate_claim') }}
                </button>
            </form>
            <form method="POST" action="{{ $workspaceRoutes->route('admin.claims.mark-ready', $claim) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-md fs-13">
                    <i class="ti ti-circle-check me-1"></i>{{ __('claims.mark_ready') }}
                </button>
            </form>
            @endif
            <form method="POST" action="{{ $workspaceRoutes->route('admin.claims.submit', $claim) }}" class="d-inline">
                @csrf
                <input type="hidden" name="submission_mode" value="{{ $claim->claim_workflow_code === 'NHIA' ? 'EXPORT' : 'MANUAL' }}">
                <button type="submit" class="btn btn-primary btn-md fs-13" onclick="return confirm(@json(__('claims.submit_confirm')))">
                    <i class="ti ti-send me-1"></i>{{ __('claims.mark_submitted') }}
                </button>
            </form>
            @endcan
        @endif

        @can('claims.export')
        <a href="{{ $workspaceRoutes->route('admin.claims.export-one', $claim) }}" class="btn btn-outline-success btn-md fs-13">
            <i class="ti ti-file-spreadsheet me-1"></i>{{ __('claims.export') }}
        </a>
        @endcan

        @if($claim->is_reviewable)
            @can('claims.approve')
            <a href="{{ $workspaceRoutes->route('admin.claims.review', $claim) }}" class="btn btn-warning btn-md fs-13">
                <i class="ti ti-checklist me-1"></i>{{ __('claims.review_claim') }}
            </a>
            @endcan
        @endif

        @if($claim->status === \App\Enums\ClaimStatus::APPROVED || $claim->status === \App\Enums\ClaimStatus::PARTIALLY_APPROVED)
            @can('claims.approve')
            <form method="POST" action="{{ $workspaceRoutes->route('admin.claims.mark-paid', $claim) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success btn-md fs-13" onclick="return confirm(@json(__('claims.mark_paid_confirm')))">
                    <i class="ti ti-cash me-1"></i>{{ __('claims.mark_paid') }}
                </button>
            </form>
            @endcan
        @endif

        @if($claim->status === \App\Enums\ClaimStatus::REJECTED || $claim->status === \App\Enums\ClaimStatus::PARTIALLY_APPROVED)
            @can('claims.create')
            <form method="POST" action="{{ $workspaceRoutes->route('admin.claims.appeal', $claim) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-warning btn-md fs-13" onclick="return confirm(@json(__('claims.appeal_confirm')))">
                    <i class="ti ti-refresh me-1"></i>{{ __('claims.appeal') }}
                </button>
            </form>
            @endcan
        @endif

        <a href="{{ $workspaceRoutes->route('admin.claims.index') }}" class="btn btn-outline-secondary btn-md fs-13">
            <i class="ti ti-arrow-left me-1"></i>{{ __('claims.back') }}
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(($validation ?? null) && (!$validation->valid || $validation->warnings))
<div class="alert {{ $validation->valid ? 'alert-warning' : 'alert-danger' }}">
    <div class="fw-semibold mb-1">
        <i class="ti ti-shield-check me-1"></i>
        {{ $validation->valid ? __('claims.validation_passed_warnings') : __('claims.validation_requires_attention') }}
    </div>
    @foreach($validation->errors as $error)
        <div>{{ $error }}</div>
    @endforeach
    @foreach($validation->warnings as $warning)
        <div class="text-muted">{{ $warning }}</div>
    @endforeach
</div>
@endif

@include('claims.partials.clinical-mirror', ['clinicalMirror' => $clinicalMirror ?? ['records' => []]])

<div class="row">
    <!-- Claim Info -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('claims.claim_information') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive"><table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%;">{{ __('claims.claim_number') }}</td>
                        <td class="fw-medium">{{ $claim->claim_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('claims.status') }}</td>
                        <td><x-status-badge :status="$claim->status" /></td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('claims.claim_type') }}</td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary">{{ $claim->claim_type_code ?: $claim->insuranceType?->code ?: 'GENERIC' }}</span>
                            <small class="text-muted d-block">{{ $claim->claim_workflow_code ?: $claim->insuranceProvider?->claimWorkflowCode() }}</small>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('claims.claim_date') }}</td>
                        <td>{{ $claim->claim_date->format('d M Y') }}</td>
                    </tr>
                    @if($claim->period_from)
                    <tr>
                        <td class="text-muted">{{ __('claims.period') }}</td>
                        <td>{{ $claim->period_from->format('d M Y') }} — {{ $claim->period_to?->format('d M Y') ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted">{{ __('claims.total_amount') }}</td>
                        <td class="fw-bold">GH₵ {{ number_format($claim->total_amount, 2) }}</td>
                    </tr>
                    @if($claim->approved_amount !== null)
                    <tr>
                        <td class="text-muted">{{ __('claims.approved') }}</td>
                        <td class="fw-bold text-success">GH₵ {{ number_format($claim->approved_amount, 2) }}</td>
                    </tr>
                    @endif
                    @if($claim->submitted_at)
                    <tr>
                        <td class="text-muted">{{ __('claims.submitted') }}</td>
                        <td>{{ $claim->submitted_at->format('d M Y H:i') }}</td>
                    </tr>
                    @endif
                    @if($claim->reviewed_at)
                    <tr>
                        <td class="text-muted">{{ __('claims.reviewed') }}</td>
                        <td>{{ $claim->reviewed_at->format('d M Y H:i') }}</td>
                    </tr>
                    @endif
                    @if($claim->reviewer_notes)
                    <tr>
                        <td class="text-muted">{{ __('claims.reviewer_notes') }}</td>
                        <td>{{ $claim->reviewer_notes }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted">{{ __('claims.created_by') }}</td>
                        <td>{{ $claim->createdByUser->name ?? 'N/A' }}</td>
                    </tr>
                    @if($claim->preparedBy)
                    <tr>
                        <td class="text-muted">{{ __('claims.prepared_by') }}</td>
                        <td>{{ $claim->preparedBy->name }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted">{{ __('claims.created') }}</td>
                        <td>{{ $claim->created_at->format('d M Y H:i') }}</td>
                    </tr>
                </table></div>
            </div>
        </div>

        <!-- Provider Info -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('claims.insurance_provider') }}</h5>
            </div>
            <div class="card-body">
                <h6 class="mb-1">{{ $claim->insuranceProvider->name }}</h6>
                <x-status-badge :status="$claim->insuranceProvider->type" class="mb-2" />
                @if($claim->insuranceProvider->insuranceType)
                    <span class="badge bg-primary-subtle text-primary mb-2">{{ $claim->insuranceProvider->insuranceType->code }}</span>
                    <div><small class="text-muted">{{ __('claims.workflow') }}: {{ $claim->insuranceProvider->insuranceType->claim_workflow ?: 'GENERIC' }}</small></div>
                @endif
                @if($claim->insuranceProvider->contact_phone)
                    <div><small class="text-muted"><i class="ti ti-phone me-1"></i>{{ $claim->insuranceProvider->contact_phone }}</small></div>
                @endif
                @if($claim->insuranceProvider->contact_email)
                    <div><small class="text-muted"><i class="ti ti-mail me-1"></i>{{ $claim->insuranceProvider->contact_email }}</small></div>
                @endif
                @php
                    $claimTier = $claim->visit?->visitInsurance?->insuranceTier;
                    $claimMemberType = $claim->visit?->visitInsurance?->member_type;
                @endphp
                @if($claimTier)
                <div class="mt-2 pt-2 border-top">
                    <small class="text-muted d-block mb-1">{{ __('claims.tier_used_for_visit') }}</small>
                    <span class="badge bg-primary bg-opacity-75">{{ $claimTier->name }}</span>
                    @if($claimMemberType)
                        <span class="badge {{ $claimMemberType->value === 'beneficiary' ? 'bg-warning text-dark' : 'bg-info' }} ms-1">
                            {{ $claimMemberType->translatedLabel() }}
                        </span>
                    @endif
                    @php
                        $memberVal = $claimMemberType?->value ?? 'holder';
                        $tierConstraints = $claimTier->effectiveConstraints($memberVal);
                    @endphp
                    @if($tierConstraints['coverage_percentage'])
                        <div class="small text-muted mt-1">{{ __('claims.coverage') }}: {{ $tierConstraints['coverage_percentage'] }}%</div>
                    @endif
                    @if($tierConstraints['annual_limit'])
                        <div class="small text-muted">{{ __('claims.annual_limit') }}: GH₵ {{ number_format($tierConstraints['annual_limit'], 2) }}</div>
                    @endif
                </div>
                @elseif($claim->insuranceProvider->tiers->isNotEmpty())
                <div class="mt-2 pt-2 border-top">
                    <small class="text-muted d-block mb-1">{{ __('claims.available_tiers') }}</small>
                    @foreach($claim->insuranceProvider->tiers as $provTier)
                        <span class="badge bg-light text-dark border me-1 mb-1">{{ $provTier->name }}</span>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <!-- Patient Info -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('claims.patient') }}</h5>
            </div>
            <div class="card-body">
                <h6 class="mb-1">{{ $claim->patient->first_name }} {{ $claim->patient->last_name }}</h6>
                <div><small class="text-muted">{{ $claim->patient->patient_number }}</small></div>
                @if($claim->assignedDoctor)
                <div class="mt-2"><small class="text-muted">{{ __('claims.assigned_doctor') }}: {{ $claim->assignedDoctor->name }}</small></div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('claims.membership_verification') }}</h5>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <small class="text-muted d-block">{{ __('claims.membership_number') }}</small>
                    <span class="fw-semibold">{{ $claim->membership_number ?: 'N/A' }}</span>
                </div>
                <form method="POST" action="{{ $workspaceRoutes->route('admin.claims.verification-code', $claim) }}">
                    @csrf
                    <label class="form-label">{{ $claim->insuranceProvider?->verificationCodeLabel() ?? __('claims.verification_code') }}</label>
                    <div class="input-group">
                        <input type="text" name="verification_code" class="form-control" value="{{ old('verification_code', $claim->verification_code) }}">
                        @can('claims.create')
                        <button type="submit" class="btn btn-outline-primary">{{ __('claims.update') }}</button>
                        @endcan
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Claim Items -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ __('claims.claim_items') }} ({{ $claim->items->count() }})</h5>
                @if($claim->is_editable)
                    @can('claims.create')
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="ti ti-plus me-1"></i>{{ __('claims.add_item') }}
                    </button>
                    @endcan
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('claims.service') }}</th>
                                <th>{{ __('claims.department') }}</th>
                                <th>{{ __('claims.service_type') }}</th>
                                <th class="text-center">{{ __('claims.qty') }}</th>
                                <th class="text-end">{{ __('claims.unit_price') }}</th>
                                <th class="text-end">{{ __('claims.total') }}</th>
                                <th class="text-end">{{ __('claims.approved') }}</th>
                                <th>{{ __('claims.status') }}</th>
                                @if($claim->is_editable)
                                <th class="text-end">{{ __('claims.actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($claim->items as $item)
                            <tr>
                                <td class="fw-medium">{{ $item->description ?: $item->service_name }}</td>
                                <td>{{ $item->department?->name ?? '-' }}</td>
                                <td><span class="badge bg-light text-dark">{{ $item->service_type->translatedLabel() }}</span></td>
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
                                <td><x-status-badge :status="$item->status" /></td>
                                @if($claim->is_editable)
                                <td class="text-end">
                                    <x-confirm-form :action="$workspaceRoutes->route('admin.claims.remove-item', $item)" method="DELETE"
                                        button-label="" button-class="btn btn-sm btn-outline-danger" icon="ti-trash"
                                        :confirm-title="__('claims.remove_item_title')" :confirm-text="__('claims.remove_item_text')" :confirm-button="__('claims.remove_item_confirm')" />
                                </td>
                                @endif
                            </tr>
                            @if($item->rejection_reason)
                            <tr>
                                <td colspan="{{ $claim->is_editable ? 9 : 8 }}" class="py-1 ps-4">
                                    <small class="text-danger"><i class="ti ti-alert-circle me-1"></i>{{ $item->rejection_reason }}</small>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="5" class="text-end">{{ __('claims.total') }}:</td>
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
                <h5 class="card-title mb-0">{{ __('claims.linked_invoice') }}</h5>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.billing.invoices.show', $claim->invoice) }}" class="fw-medium">
                    {{ $claim->invoice->invoice_number }}
                </a>
                <span class="ms-2 text-muted">GH₵ {{ number_format($claim->invoice->total_amount, 2) }}</span>
                <x-status-badge :status="$claim->invoice->status" class="ms-2" />
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('claims.claim_payments') }}</h5>
            </div>
            <div class="card-body">
                @if($claim->payments->isNotEmpty())
                    <div class="mb-3">
                        <div class="table-responsive"><table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('claims.date') }}</th>
                                    <th>{{ __('claims.reference') }}</th>
                                    <th>{{ __('claims.method') }}</th>
                                    <th class="text-end">{{ __('claims.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($claim->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                                    <td>{{ $payment->payment_reference ?: '-' }}</td>
                                    <td>{{ $payment->payment_method ?: '-' }}</td>
                                    <td class="text-end">GHS {{ number_format($payment->amount, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table></div>
                    </div>
                @else
                    <p class="text-muted mb-3">{{ __('claims.no_insurer_payments') }}</p>
                @endif
                @can('claims.approve')
                <form method="POST" action="{{ $workspaceRoutes->route('admin.claims.payments.store', $claim) }}" class="row g-2">
                    @csrf
                    <div class="col-md-3">
                        <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="amount" class="form-control" min="0.01" step="0.01" placeholder="{{ __('claims.amount') }}" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="payment_reference" class="form-control" placeholder="{{ __('claims.reference') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-outline-success w-100">{{ __('claims.record_payment') }}</button>
                    </div>
                </form>
                @endcan
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
@if($claim->is_editable)
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ $workspaceRoutes->route('admin.claims.add-item', $claim) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('claims.add_claim_item') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('claims.service_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="service_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('claims.service_type') }} <span class="text-danger">*</span></label>
                        <select name="service_type" class="form-select">
                            @foreach(\App\Enums\ServiceType::cases() as $st)
                                <option value="{{ $st->value }}">{{ $st->translatedLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('claims.qty') }} <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('claims.unit_price') }} <span class="text-danger">*</span></label>
                            <input type="number" name="unit_price" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('claims.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('claims.add_item') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
