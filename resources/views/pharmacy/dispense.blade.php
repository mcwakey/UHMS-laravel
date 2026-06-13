@extends('layouts.app')
@section('title', __('pharmacy.dispense_title', ['number' => $prescription->prescription_number]))

@section('content')
@php
    $allItems = $prescription->items ?? collect();
    $dispensableItems = $prescription->dispensableItems ?? collect();
    $settledDispensable = $dispensableItems->filter(fn ($item) => $item->is_settled ?? false);
    $billableItems = $allItems->filter(fn ($item) => ($item->remaining_prescribed_to_bill ?? 0) > 0 && $item->drug?->product_id);
    $formatQty = fn ($qty) => rtrim(rtrim(number_format((float) $qty, 4, '.', ''), '0'), '.') ?: '0';
@endphp

<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            <a aria-label="{{ __('pharmacy.back') }}" title="{{ __('pharmacy.back') }}" href="{{ route('admin.pharmacy.dispensing.index') }}" class="text-muted me-2"><i class="ti ti-arrow-left"></i></a>
            {{ __('pharmacy.dispense') }}: {{ $prescription->prescription_number }}
        </h4>
    </div>
    <div>
        <x-status-badge :status="$prescription->status" class="px-3 py-2 fs-14" />
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ti ti-check me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ti ti-alert-circle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Prescription Info -->
<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-info-circle me-1"></i>{{ __('pharmacy.prescription_details') }}</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">{{ __('common.patient') }}</small>
                        <span class="fw-medium">{{ $prescription->patient->full_name }}</span>
                        <small class="text-muted d-block">{{ $prescription->patient->patient_number }} &middot; {{ $prescription->patient->age }}y &middot; {{ $prescription->patient->gender->value }}</small>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">{{ __('pharmacy.prescribing_doctor') }}</small>
                        <span>{{ $prescription->doctor->name ?? '-' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">{{ __('common.date') }}</small>
                        <span>{{ $prescription->created_at->format('d M Y H:i') }}</span>
                    </div>
                    @if($prescription->notes)
                    <div class="col-12">
                        <small class="text-muted d-block">{{ __('common.notes') }}</small>
                        <p class="mb-0">{{ $prescription->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-chart-bar me-1"></i>{{ __('pharmacy.progress') }}</h6>
            </div>
            <div class="card-body text-center">
                @php
                    $total = (float) $allItems->sum(fn ($item) => (float) ($item->quantity ?? 0));
                    $billed = (float) $allItems->sum(fn ($item) => (float) ($item->billed_quantity ?? 0));
                    $dispensed = (float) $allItems->sum(fn ($item) => (float) ($item->dispensed_billed_quantity ?? 0));
                    $pct = $total > 0 ? round(($dispensed / $total) * 100) : 0;
                @endphp
                <h1 class="mb-1 {{ $pct === 100 ? 'text-success' : 'text-primary' }}">{{ $pct }}%</h1>
                <div class="progress mb-2" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
                </div>
                <small class="text-muted">{{ __('pharmacy.billed_dispensed_of', ['billed' => $formatQty($billed), 'dispensed' => $formatQty($dispensed), 'total' => $formatQty($total)]) }}</small>
            </div>
        </div>
    </div>
</div>

<!-- Billing moved to the prescription page -->
@if($billableItems->count() > 0)
<div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div><i class="ti ti-receipt me-1"></i><strong>{{ __('pharmacy.items_count', ['count' => $billableItems->count()]) }}</strong> {{ __('pharmacy.still_need_billing') }}</div>
    <a href="{{ route('admin.prescriptions.show', $prescription) }}" class="btn btn-sm btn-primary"><i class="ti ti-external-link me-1"></i>{{ __('pharmacy.bill_on_prescription') }}</a>
</div>
@endif

<!-- Batch Dispense Form -->
@if($dispensableItems->count() > 0)
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="ti ti-pill me-1"></i>{{ __('pharmacy.dispense_items') }}</h6>
        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#batchDispenseForm">
            <i class="ti ti-edit me-1"></i>{{ __('pharmacy.batch_dispense') }}
        </button>
    </div>
    <div class="card-body">
        <div class="collapse mb-3" id="batchDispenseForm">
            <div class="card card-body bg-light">
                <form method="POST" action="{{ route('admin.pharmacy.dispensing.batch', $prescription) }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('common.drug') }}</th>
                                    <th>{{ __('pharmacy.dosage') }}</th>
                                    <th>{{ __('pharmacy.billed_qty') }}</th>
                                    <th>{{ __('pharmacy.already_dispensed') }}</th>
                                    <th>{{ __('pharmacy.remaining_billed') }}</th>
                                    <th>{{ __('pharmacy.pharmacy_main_stock') }}</th>
                                    <th>{{ __('pharmacy.qty_to_dispense') }}</th>
                                    <th>{{ __('common.notes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($settledDispensable as $item)
                                <tr>
                                    <td class="fw-medium">
                                        {{ $item->drug_name }}
                                        @if($item->drug)
                                            <br><small class="text-muted">{{ $item->drug->dosage_form }} {{ $item->drug->strength }}</small>
                                        @endif
                                    </td>
                                    <td><small>{{ $item->dosage }} &middot; {{ $item->frequency }}</small></td>
                                    <td>{{ $formatQty($item->billed_quantity ?? 0) }}</td>
                                    <td>{{ $formatQty($item->dispensed_billed_quantity ?? 0) }}</td>
                                    <td><span class="fw-medium text-primary">{{ $formatQty($item->remaining_billed_to_dispense ?? 0) }}</span></td>
                                    <td>
                                        @php
                                            $pharmacyStatus = $item->pharmacy_stock_status ?? ['label' => 'OUT', 'class' => 'danger'];
                                            $mainStatus = $item->main_stock_status ?? ['label' => 'OUT', 'class' => 'danger'];
                                        @endphp
                                        <small class="d-block">{{ __('pharmacy.pharmacy_label') }}: {{ $formatQty($item->pharmacy_available_quantity ?? 0) }} <span class="badge bg-{{ $pharmacyStatus['class'] }}">{{ $pharmacyStatus['label'] }}</span></small>
                                        <small class="d-block text-muted">{{ __('pharmacy.main_label') }}: {{ $formatQty($item->main_store_quantity ?? 0) }} <span class="badge bg-{{ $mainStatus['class'] }}">{{ $mainStatus['label'] }}</span></small>
                                    </td>
                                    <td style="width: 100px;">
                                        <input type="number" name="items[{{ $item->id }}][quantity]"
                                            class="form-control form-control-sm"
                                            value="{{ $formatQty(min((float) ($item->remaining_billed_to_dispense ?? 0), (float) ($item->pharmacy_available_quantity ?? 0))) }}"
                                            min="0" max="{{ $formatQty(min((float) ($item->remaining_billed_to_dispense ?? 0), (float) ($item->pharmacy_available_quantity ?? 0))) }}">
                                    </td>
                                    <td style="width: 150px;">
                                        <input type="text" name="items[{{ $item->id }}][notes]"
                                            class="form-control form-control-sm" placeholder="{{ __('common.optional') }}">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>{{ __('pharmacy.dispense_selected') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Items List with individual dispense buttons -->
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('common.drug') }}</th>
                        <th>{{ __('pharmacy.dosage') }}</th>
                        <th>{{ __('pharmacy.frequency') }}</th>
                        <th>{{ __('pharmacy.duration') }}</th>
                        <th>{{ __('pharmacy.route') }}</th>
                        <th>{{ __('pharmacy.billed_qty') }}</th>
                        <th>{{ __('pharmacy.dispensed') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dispensableItems as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <span class="fw-medium">{{ $item->drug_name }}</span>
                            @if($item->drug)
                                <br><small class="text-muted">{{ $item->drug->dosage_form }} {{ $item->drug->strength }}</small>
                            @endif
                            @if($item->instructions)
                                <br><small class="text-info"><i class="ti ti-info-circle me-1"></i>{{ $item->instructions }}</small>
                            @endif
                        </td>
                        <td>{{ $item->dosage }}</td>
                        <td>{{ $item->frequency }}</td>
                        <td>{{ $item->duration }}</td>
                        <td>{{ $item->route }}</td>
                        <td>{{ $formatQty($item->billed_quantity ?? 0) }}</td>
                        <td>
                            {{ $formatQty($item->dispensed_billed_quantity ?? 0) }}
                            @if($item->dispensingRecords->count() > 0)
                                <br>
                                @foreach($item->dispensingRecords as $dr)
                                <small class="text-muted d-block">
                                    {{ __('pharmacy.qty_dispensed', ['qty' => $dr->quantity_dispensed]) }}
                                    ({{ $dr->dispensed_at->format('d M H:i') }})
                                </small>
                                @endforeach
                            @endif
                        </td>
                        <td>
                            @if($item->is_settled ?? false)
                                <span class="badge bg-primary">{{ __('pharmacy.ready') }}</span>
                                <small class="d-block text-muted">{{ __('pharmacy.remaining_qty', ['qty' => $formatQty($item->remaining_billed_to_dispense ?? 0)]) }}</small>
                            @else
                                <span class="badge bg-warning text-dark"><i class="ti ti-clock-dollar me-1"></i>{{ __('pharmacy.awaiting_payment') }}</span>
                                <small class="d-block text-muted">{{ __('pharmacy.dispense_after_settled') }}</small>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($item->drug && ($item->is_settled ?? false))
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#dispenseModal-{{ $item->id }}">
                                <i class="ti ti-pill me-1"></i>{{ __('pharmacy.dispense') }}
                            </button>
                            @else
                            <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
<div class="card">
    <div class="card-body text-center py-5">
        <i class="ti ti-receipt-off fs-1 text-muted d-block mb-2"></i>
        <h5 class="text-muted">{{ __('pharmacy.no_billed_items_ready') }}</h5>
        <p class="text-muted mb-0">{{ __('pharmacy.bill_items_first') }}</p>
    </div>
</div>
@endif

{{-- Individual Dispense Modals --}}
@foreach($dispensableItems as $item)
@if($item->drug)
<div class="modal fade" id="dispenseModal-{{ $item->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.pharmacy.dispensing.dispense-item', $item) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('pharmacy.dispense') }}: {{ $item->drug_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <small>
                            <strong>{{ __('pharmacy.billed') }}:</strong> {{ $formatQty($item->billed_quantity ?? 0) }} {{ $item->drug->unit ?? '' }} &middot;
                            <strong>{{ __('pharmacy.already_dispensed_label') }}:</strong> {{ $formatQty($item->dispensed_billed_quantity ?? 0) }} &middot;
                            <strong>{{ __('pharmacy.remaining_billed_label') }}:</strong> {{ $formatQty($item->remaining_billed_to_dispense ?? 0) }} &middot;
                            <strong>{{ __('pharmacy.pharmacy_stock') }}:</strong> {{ $formatQty($item->pharmacy_available_quantity ?? 0) }}
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('pharmacy.quantity_to_dispense') }} <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control"
                            value="{{ $formatQty(min((float) ($item->remaining_billed_to_dispense ?? 0), (float) ($item->pharmacy_available_quantity ?? 0))) }}"
                            min="1" max="{{ $formatQty(min((float) ($item->remaining_billed_to_dispense ?? 0), (float) ($item->pharmacy_available_quantity ?? 0))) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('pharmacy.optional_dispensing_notes') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('pharmacy.dispense') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach
@endsection
