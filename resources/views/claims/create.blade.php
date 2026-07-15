@extends('layouts.app')
@section('title', __('claims.create_claim'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('claims.create_insurance_claim') }}</h4>
    </div>
    <div>
        <a href="{{ $workspaceRoutes->route('admin.claims.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>{{ __('claims.back') }}
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($invoice)
{{-- Create from Invoice --}}
@php
    $claimableItems = $invoice->items->filter(fn ($item) => $item->is_nhis_covered && (float) $item->nhis_approved_amount > 0)->values();
    $claimTotal = $claimableItems->sum(fn ($item) => (float) $item->nhis_approved_amount);
    $defaultProviderId = old('insurance_provider_id', $invoice->visit?->visitInsurance?->insurance_provider_id);
@endphp
<div class="alert alert-info">
    <i class="ti ti-info-circle me-1"></i>
    Creating insurance claim from Invoice <strong>{{ $invoice->invoice_number }}</strong> —
    Patient: <strong>{{ $invoice->patient->first_name }} {{ $invoice->patient->last_name }}</strong> —
    Claimable Amount: <strong>GH₵ {{ number_format($claimTotal, 2) }}</strong>
</div>

@if($claimableItems->isEmpty())
<div class="alert alert-warning">
    <i class="ti ti-alert-circle me-1"></i>
    {{ __('claims.invoice_no_claimable_items') }}
</div>
@endif

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ __('claims.claim_from_invoice') }}</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ $workspaceRoutes->route('admin.claims.store-from-invoice') }}">
            @csrf
            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('claims.insurance_provider') }} <span class="text-danger">*</span></label>
                    <select name="insurance_provider_id" class="form-select select2" required>
                        <option value="">{{ __('claims.select_provider') }}</option>
                        @foreach($providers as $provider)
                            <option value="{{ $provider->id }}" {{ (string) $defaultProviderId === (string) $provider->id ? 'selected' : '' }}>
                                {{ $provider->name }} ({{ $provider->type->translatedLabel() }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('claims.assigned_doctor') }}</label>
                    <select name="assigned_doctor_id" class="form-select select2">
                        <option value="">{{ __('claims.select_doctor_optional') }}</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Invoice Items Preview -->
            <h6 class="mb-2">{{ __('claims.insurance_covered_items') }}</h6>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('claims.service') }}</th>
                            <th>{{ __('claims.qty') }}</th>
                            <th class="text-end">{{ __('claims.total') }}</th>
                            <th class="text-end">{{ __('claims.claim_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($claimableItems as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td class="text-end">GH₵ {{ number_format($item->total_price, 2) }}</td>
                            <td class="text-end fw-bold text-primary">GH₵ {{ number_format($item->nhis_approved_amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4"><x-empty-state :message="__('claims.no_insurance_items')" /></td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">{{ __('claims.claim_total') }}:</td>
                            <td class="text-end text-primary">GH₵ {{ number_format($claimTotal, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <button type="submit" class="btn btn-primary" {{ $claimableItems->isEmpty() ? 'disabled' : '' }}>
                <i class="ti ti-file-plus me-1"></i>{{ __('claims.create_from_invoice') }}
            </button>
        </form>
    </div>
</div>

@else
{{-- Manual Claim Creation --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ __('claims.claim_details') }}</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ $workspaceRoutes->route('admin.claims.store') }}" id="claimForm">
            @csrf

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">{{ __('claims.insurance_provider') }} <span class="text-danger">*</span></label>
                    <select name="insurance_provider_id" class="form-select select2" required>
                        <option value="">{{ __('claims.select_provider') }}</option>
                        @foreach($providers as $provider)
                            <option value="{{ $provider->id }}" {{ old('insurance_provider_id') == $provider->id ? 'selected' : '' }}>
                                {{ $provider->name }} ({{ $provider->type->translatedLabel() }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('claims.patient') }} <span class="text-danger">*</span></label>
                    <select name="patient_id" class="form-select select2" required>
                        <option value="">{{ __('claims.select_patient') }}</option>
                        @foreach($patients as $p)
                            <option value="{{ $p->id }}" {{ old('patient_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->first_name }} {{ $p->last_name }} ({{ $p->patient_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('claims.visit') }} <span class="text-danger">*</span></label>
                    <select name="visit_id" class="form-select select2" required>
                        <option value="">{{ __('claims.select_visit') }}</option>
                        @foreach($visits as $visit)
                            <option value="{{ $visit->id }}" {{ old('visit_id') == $visit->id ? 'selected' : '' }}>
                                {{ $visit->visit_number }} — {{ $visit->patient?->full_name ?? 'Unknown Patient' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('claims.assigned_doctor') }}</label>
                    <select name="assigned_doctor_id" class="form-select select2">
                        <option value="">{{ __('claims.select_doctor_optional') }}</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" {{ old('assigned_doctor_id') == $doctor->id ? 'selected' : '' }}>{{ $doctor->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('claims.claim_date') }} <span class="text-danger">*</span></label>
                    <input type="date" name="claim_date" class="form-control" value="{{ old('claim_date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('claims.period_from') }}</label>
                    <input type="date" name="period_from" class="form-control" value="{{ old('period_from', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('claims.period_to') }}</label>
                    <input type="date" name="period_to" class="form-control" value="{{ old('period_to', date('Y-m-d')) }}" required>
                </div>
            </div>

            <!-- Claim Items -->
            <h6 class="mb-2">{{ __('claims.claim_items') }} <span class="text-danger">*</span></h6>
            <div class="table-responsive mb-3">
                <table class="table table-bordered" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('claims.service_name') }}</th>
                            <th style="width: 150px;">{{ __('claims.service_type') }}</th>
                            <th style="width: 80px;">{{ __('claims.qty') }}</th>
                            <th style="width: 120px;">{{ __('claims.unit_price') }}</th>
                            <th style="width: 120px;">{{ __('claims.total') }}</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr class="item-row">
                            <td><input type="text" name="items[0][service_name]" class="form-control form-control-sm" required></td>
                            <td>
                                <select name="items[0][service_type]" class="form-select form-select-sm">
                                    @foreach($serviceTypes as $st)
                                        <option value="{{ $st->value }}">{{ $st->translatedLabel() }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm qty-input" value="1" min="1" required></td>
                            <td><input type="number" name="items[0][unit_price]" class="form-control form-control-sm price-input" step="0.01" min="0" required></td>
                            <td class="row-total text-end align-middle fw-medium">0.00</td>
                            <td class="text-center"><button aria-label="{{ __('claims.delete') }}" title="{{ __('claims.delete') }}" type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end fw-bold">{{ __('claims.grand_total') }}:</td>
                            <td class="text-end fw-bold" id="grandTotal">GH₵ 0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="addItemBtn">
                <i class="ti ti-plus me-1"></i>{{ __('claims.add_item') }}
            </button>

            <div>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-file-plus me-1"></i>{{ __('claims.create_claim') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let itemIndex = 1;

    // Add item row
    $('#addItemBtn').on('click', function() {
        const serviceOptions = `@foreach($serviceTypes as $st)<option value="{{ $st->value }}">{{ $st->translatedLabel() }}</option>@endforeach`;
        const row = `<tr class="item-row">
            <td><input type="text" name="items[${itemIndex}][service_name]" class="form-control form-control-sm" required></td>
            <td><select name="items[${itemIndex}][service_type]" class="form-select form-select-sm">${serviceOptions}</select></td>
            <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm qty-input" value="1" min="1" required></td>
            <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control form-control-sm price-input" step="0.01" min="0" required></td>
            <td class="row-total text-end align-middle fw-medium">0.00</td>
            <td class="text-center"><button aria-label="{{ __('claims.delete') }}" title="{{ __('claims.delete') }}" type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button></td>
        </tr>`;
        $('#itemsBody').append(row);
        itemIndex++;
    });

    // Remove item row
    $(document).on('click', '.remove-row', function() {
        if ($('.item-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotal();
        }
    });

    // Calculate totals
    $(document).on('input', '.qty-input, .price-input', function() {
        calculateTotal();
    });

    function calculateTotal() {
        let grandTotal = 0;
        $('.item-row').each(function() {
            const qty = parseFloat($(this).find('.qty-input').val()) || 0;
            const price = parseFloat($(this).find('.price-input').val()) || 0;
            const total = qty * price;
            $(this).find('.row-total').text(total.toFixed(2));
            grandTotal += total;
        });
        $('#grandTotal').text('GH₵ ' + grandTotal.toFixed(2));
    }
});
</script>
@endpush
