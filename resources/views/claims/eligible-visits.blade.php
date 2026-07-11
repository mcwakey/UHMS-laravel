@extends('layouts.app')
@section('title', $selectedTypeCode === 'NHIA' ? __('claims.nhia_eligible_visits') : __('claims.eligible_claim_visits'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            {{ $selectedTypeCode === 'NHIA' ? __('claims.nhia_eligible_visits') : __('claims.eligible_claim_visits') }}
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">{{ __('claims.total') }}: {{ $visits->total() }}</span>
        </h4>
        <small class="text-muted">{{ __('claims.eligible_visits_help') }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.claims.index') }}" class="btn btn-outline-secondary btn-md fs-13">
            <i class="ti ti-arrow-left me-1"></i>{{ __('claims.claims') }}
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.claims.eligible-visits') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-1">{{ __('claims.insurance_type') }}</label>
                <select name="type" class="form-select">
                    <option value="">{{ __('claims.all_claim_workflows') }}</option>
                    @foreach($insuranceTypes as $type)
                        <option value="{{ $type->code }}" {{ $selectedTypeCode === $type->code ? 'selected' : '' }}>
                            {{ $type->name }} ({{ $type->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="ti ti-filter me-1"></i>{{ __('claims.filter') }}</button>
            </div>
            @if($selectedTypeCode)
            <div class="col-md-2">
                <a href="{{ route('admin.claims.eligible-visits') }}" class="btn btn-outline-secondary w-100">{{ __('claims.clear') }}</a>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('claims.visit') }}</th>
                        <th>{{ __('claims.patient') }}</th>
                        <th>{{ __('claims.provider') }}</th>
                        <th>{{ __('claims.claim_type') }}</th>
                        <th>{{ __('claims.invoice') }}</th>
                        <th class="text-end">{{ __('claims.claimable') }}</th>
                        <th class="text-end">{{ __('claims.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visits as $visit)
                        @php
                            $invoice = $visit->latestInvoice;
                            $claimable = $invoice?->items?->reject(fn ($item) => in_array($item->payment_status, ['cancelled', 'voided'], true))->sum(function ($item) {
                                $legacy = ($item->is_nhis_covered && $item->nhis_approved_amount) ? (float) $item->nhis_approved_amount : 0;
                                $covered = max($legacy, (float) ($item->insurance_covered ?? 0));
                                if ($covered > 0) {
                                    return $covered;
                                }
                                $quantity = max(1, (int) ($item->quantity ?? 1));
                                return (float) ($item->selected_price ?: $item->insurance_price ?: $item->total_price ?: $item->unit_price) * $quantity;
                            }) ?? 0;
                            $claimInsurance = $visit->claimEligibleInsurance ?? $visit->visitInsurance;
                            $provider = $claimInsurance?->insuranceProvider;
                            $type = $provider?->insuranceType;
                        @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.visits.show', $visit) }}" class="fw-medium text-primary">{{ $visit->visit_number }}</a>
                            <small class="text-muted d-block">{{ $visit->visit_date?->format('d M Y') }}</small>
                        </td>
                        <td>{{ $visit->patient?->full_name ?? trim(($visit->patient?->first_name ?? '').' '.($visit->patient?->last_name ?? '')) }}</td>
                        <td>{{ $provider?->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary">{{ $type?->code ?? 'N/A' }}</span>
                            <small class="text-muted d-block">{{ $type?->claim_workflow ?? 'GENERIC' }}</small>
                        </td>
                        <td>
                            @if($invoice)
                                <a href="{{ route('admin.billing.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                            @else
                                <span class="text-muted">{{ __('claims.no_invoice') }}</span>
                            @endif
                        </td>
                        <td class="text-end fw-semibold">GHS {{ number_format($claimable, 2) }}</td>
                        <td class="text-end">
                            @can('claims.create')
                            <form method="POST" action="{{ $type?->code === 'NHIA' ? route('admin.claims.nhia.prepare-from-visit', $visit) : route('admin.claims.prepare-from-visit', $visit) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="ti ti-file-plus me-1"></i>{{ __('claims.prepare_claim') }}
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="ti ti-file-off fs-2 d-block mb-2"></i>
                            {{ __('claims.no_eligible_visits') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($visits->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $visits->withQueryString()->links() }}
</div>
@endif
@endsection
