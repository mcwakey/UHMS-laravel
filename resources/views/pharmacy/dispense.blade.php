@extends('layouts.app')
@section('title', 'Dispense - ' . $prescription->prescription_number)

@section('content')
@php
    $allItems = $prescription->items ?? collect();
    $dispensableItems = $prescription->dispensableItems ?? collect();
    $billableItems = $allItems->filter(fn ($item) => ($item->remaining_prescribed_to_bill ?? 0) > 0 && $item->drug?->product_id);
    $formatQty = fn ($qty) => rtrim(rtrim(number_format((float) $qty, 4, '.', ''), '0'), '.') ?: '0';
@endphp

<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            <a href="{{ route('admin.pharmacy.dispensing.index') }}" class="text-muted me-2"><i class="ti ti-arrow-left"></i></a>
            Dispense: {{ $prescription->prescription_number }}
        </h4>
    </div>
    <div>
        <span class="badge bg-{{ $prescription->status->color() }} px-3 py-2 fs-14">{{ $prescription->status->label() }}</span>
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
                <h6 class="fw-bold mb-0"><i class="ti ti-info-circle me-1"></i>Prescription Details</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Patient</small>
                        <span class="fw-medium">{{ $prescription->patient->full_name }}</span>
                        <small class="text-muted d-block">{{ $prescription->patient->patient_number }} &middot; {{ $prescription->patient->age }}y &middot; {{ $prescription->patient->gender->value }}</small>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Prescribing Doctor</small>
                        <span>{{ $prescription->doctor->name ?? '-' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Date</small>
                        <span>{{ $prescription->created_at->format('d M Y H:i') }}</span>
                    </div>
                    @if($prescription->notes)
                    <div class="col-12">
                        <small class="text-muted d-block">Notes</small>
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
                <h6 class="fw-bold mb-0"><i class="ti ti-chart-bar me-1"></i>Progress</h6>
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
                <small class="text-muted">{{ $formatQty($billed) }} billed, {{ $formatQty($dispensed) }} dispensed of {{ $formatQty($total) }}</small>
            </div>
        </div>
    </div>
</div>

<!-- Billing Selection Form -->
@if($billableItems->count() > 0)
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="ti ti-receipt me-1"></i>Bill Selected Items</h6>
        <span class="badge bg-light text-dark">Stock is not deducted here</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.pharmacy.dispensing.bill-selected', $prescription) }}">
            @csrf
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 48px;">Bill</th>
                            <th>Drug</th>
                            <th class="text-end">Prescribed</th>
                            <th class="text-end">Already Billed</th>
                            <th class="text-end">Remaining</th>
                            <th class="text-end">Pharmacy Qty</th>
                            <th class="text-end">Main Store Qty</th>
                            <th style="width: 140px;">Selected Qty</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($billableItems as $item)
                            @php
                                $remainingToBill = (float) ($item->remaining_prescribed_to_bill ?? 0);
                                $pharmacyQty = (float) ($item->pharmacy_available_quantity ?? 0);
                                $defaultQty = min($remainingToBill, $pharmacyQty);
                                $pharmacyStatus = $item->pharmacy_stock_status ?? ['label' => 'OUT', 'class' => 'danger'];
                                $mainStatus = $item->main_stock_status ?? ['label' => 'OUT', 'class' => 'danger'];
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input" name="items[{{ $item->id }}][selected]" value="1" {{ $defaultQty > 0 ? '' : 'disabled' }}>
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $item->drug_name }}</span>
                                    @if($item->drug)
                                        <br><small class="text-muted">{{ $item->drug->dosage_form }} {{ $item->drug->strength }}</small>
                                    @endif
                                </td>
                                <td class="text-end">{{ $formatQty($item->quantity) }}</td>
                                <td class="text-end">{{ $formatQty($item->billed_quantity ?? 0) }}</td>
                                <td class="text-end fw-semibold text-primary">{{ $formatQty($remainingToBill) }}</td>
                                <td class="text-end">
                                    {{ $formatQty($pharmacyQty) }}
                                    <span class="badge bg-{{ $pharmacyStatus['class'] }} ms-1">{{ $pharmacyStatus['label'] }}</span>
                                </td>
                                <td class="text-end">
                                    {{ $formatQty($item->main_store_quantity ?? 0) }}
                                    <span class="badge bg-{{ $mainStatus['class'] }} ms-1">{{ $mainStatus['label'] }}</span>
                                </td>
                                <td>
                                    <input type="number" name="items[{{ $item->id }}][quantity]" class="form-control form-control-sm"
                                        value="{{ $formatQty($defaultQty) }}" min="0" max="{{ $formatQty(min($remainingToBill, $pharmacyQty)) }}">
                                </td>
                                <td><input type="text" name="items[{{ $item->id }}][notes]" class="form-control form-control-sm" placeholder="Optional"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary"><i class="ti ti-receipt me-1"></i>Bill Selected</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Batch Dispense Form -->
@if($dispensableItems->count() > 0)
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="ti ti-pill me-1"></i>Dispense Items</h6>
        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#batchDispenseForm">
            <i class="ti ti-edit me-1"></i>Batch Dispense
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
                                    <th>Drug</th>
                                    <th>Dosage</th>
                                    <th>Billed Qty</th>
                                    <th>Already Dispensed</th>
                                    <th>Remaining Billed</th>
                                    <th>Pharmacy / Main Stock</th>
                                    <th>Qty to Dispense</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dispensableItems as $item)
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
                                        <small class="d-block">Pharmacy: {{ $formatQty($item->pharmacy_available_quantity ?? 0) }} <span class="badge bg-{{ $pharmacyStatus['class'] }}">{{ $pharmacyStatus['label'] }}</span></small>
                                        <small class="d-block text-muted">Main: {{ $formatQty($item->main_store_quantity ?? 0) }} <span class="badge bg-{{ $mainStatus['class'] }}">{{ $mainStatus['label'] }}</span></small>
                                    </td>
                                    <td style="width: 100px;">
                                        <input type="number" name="items[{{ $item->id }}][quantity]"
                                            class="form-control form-control-sm"
                                            value="{{ $formatQty(min((float) ($item->remaining_billed_to_dispense ?? 0), (float) ($item->pharmacy_available_quantity ?? 0))) }}"
                                            min="0" max="{{ $formatQty(min((float) ($item->remaining_billed_to_dispense ?? 0), (float) ($item->pharmacy_available_quantity ?? 0))) }}">
                                    </td>
                                    <td style="width: 150px;">
                                        <input type="text" name="items[{{ $item->id }}][notes]"
                                            class="form-control form-control-sm" placeholder="Optional">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Dispense Selected</button>
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
                        <th>Drug</th>
                        <th>Dosage</th>
                        <th>Frequency</th>
                        <th>Duration</th>
                        <th>Route</th>
                        <th>Billed Qty</th>
                        <th>Dispensed</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
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
                                    {{ $dr->quantity_dispensed }} dispensed
                                    ({{ $dr->dispensed_at->format('d M H:i') }})
                                </small>
                                @endforeach
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-primary">Ready</span>
                            <small class="d-block text-muted">Remaining {{ $formatQty($item->remaining_billed_to_dispense ?? 0) }}</small>
                        </td>
                        <td class="text-end">
                            @if($item->drug)
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#dispenseModal-{{ $item->id }}">
                                <i class="ti ti-pill me-1"></i>Dispense
                            </button>
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
        <h5 class="text-muted">No billed items are ready to dispense.</h5>
        <p class="text-muted mb-0">Bill selected prescription items first, then return here to dispense the billed quantities.</p>
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
                    <h5 class="modal-title">Dispense: {{ $item->drug_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <small>
                            <strong>Billed:</strong> {{ $formatQty($item->billed_quantity ?? 0) }} {{ $item->drug->unit ?? '' }} &middot;
                            <strong>Already dispensed:</strong> {{ $formatQty($item->dispensed_billed_quantity ?? 0) }} &middot;
                            <strong>Remaining billed:</strong> {{ $formatQty($item->remaining_billed_to_dispense ?? 0) }} &middot;
                            <strong>Pharmacy stock:</strong> {{ $formatQty($item->pharmacy_available_quantity ?? 0) }}
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity to Dispense <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control"
                            value="{{ $formatQty(min((float) ($item->remaining_billed_to_dispense ?? 0), (float) ($item->pharmacy_available_quantity ?? 0))) }}"
                            min="1" max="{{ $formatQty(min((float) ($item->remaining_billed_to_dispense ?? 0), (float) ($item->pharmacy_available_quantity ?? 0))) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional dispensing notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Dispense</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach
@endsection
