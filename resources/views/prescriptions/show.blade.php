@extends('layouts.app')
@section('title', 'Prescription ' . $prescription->prescription_number)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Prescription {{ $prescription->prescription_number }}</h4>
        <small class="text-muted">Created {{ $prescription->created_at->format('d M Y, h:i A') }} by Dr. {{ $prescription->doctor->full_name }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.prescriptions.index') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>Back to Prescriptions
        </a>
        @if($prescription->status->value === 'pending')
        @can('prescriptions.create')
        <form method="POST" action="{{ route('admin.prescriptions.cancel', $prescription) }}" class="d-inline">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-danger btn-md" onclick="return confirm('Cancel this prescription?')">
                <i class="ti ti-x me-1"></i>Cancel Prescription
            </button>
        </form>
        @endcan
        @endif
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

<div class="row">
    <!-- Main Info -->
    <div class="col-lg-8">
        <!-- Status -->
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold fs-5">{{ $prescription->prescription_number }}</span>
                </div>
                <x-status-badge :status="$prescription->status" class="fs-14 px-3 py-2" />
            </div>
        </div>

        <!-- Items -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-pill me-1"></i>Prescription Items</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Drug Name</th>
                                <th>Dosage</th>
                                <th>Frequency</th>
                                <th>Duration</th>
                                <th>Route</th>
                                <th>Qty</th>
                                <th>Dispensed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prescription->items as $i => $item)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="fw-medium">{{ $item->drug_name }}</td>
                                <td>{{ $item->dosage }}</td>
                                <td>{{ $item->frequency }}</td>
                                <td>{{ $item->duration }}</td>
                                <td>{{ $item->route }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>
                                    @if($item->is_dispensed)
                                        <span class="badge bg-success"><i class="ti ti-check"></i> Yes</span>
                                    @else
                                        <span class="badge bg-warning">No</span>
                                    @endif
                                </td>
                            </tr>
                            @if($item->instructions)
                            <tr>
                                <td></td>
                                <td colspan="7"><small class="text-muted"><i class="ti ti-info-circle me-1"></i>{{ $item->instructions }}</small></td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pharmacy Billing (moved here from dispensing; dispense only after settlement) -->
        @can('pharmacy.dispensing.create')
        @php
            $formatQty = fn ($qty) => rtrim(rtrim(number_format((float) $qty, 4, '.', ''), '0'), '.') ?: '0';
            $billableItems = $prescription->items->filter(fn ($item) => ($item->remaining_prescribed_to_bill ?? 0) > 0 && $item->drug?->product_id);
        @endphp
        <div class="card mb-3">
            <div class="card-header  bg-primary-subtle d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="ti ti-receipt me-1"></i>Bill for Dispensing</h6>
                <span class="badge bg-light text-dark">Patient pays before the pharmacy dispenses</span>
            </div>
            <div class="card-body">
                @if(!empty($billingError))
                    <div class="alert alert-warning mb-0 py-2"><i class="ti ti-alert-triangle me-1"></i>{{ $billingError }}</div>
                @elseif($billableItems->isEmpty())
                    <p class="text-muted mb-0">All prescribed items have been billed. Once payment is settled, they can be dispensed at the pharmacy.</p>
                @else
                <form method="POST" action="{{ route('admin.prescriptions.bill', $prescription) }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:48px">Bill</th>
                                    <th>Drug</th>
                                    <th class="text-end">Prescribed</th>
                                    <th class="text-end">Billed</th>
                                    <th class="text-end">Remaining</th>
                                    <th class="text-end">Pharmacy Qty</th>
                                    <th style="width:130px">Selected Qty</th>
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
                                    @endphp
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input" name="items[{{ $item->id }}][selected]" value="1" {{ $defaultQty > 0 ? '' : 'disabled' }}></td>
                                        <td>
                                            <span class="fw-medium">{{ $item->drug_name }}</span>
                                            @if($item->drug)<br><small class="text-muted">{{ $item->drug->dosage_form }} {{ $item->drug->strength }}</small>@endif
                                        </td>
                                        <td class="text-end">{{ $formatQty($item->quantity) }}</td>
                                        <td class="text-end">{{ $formatQty($item->billed_quantity ?? 0) }}</td>
                                        <td class="text-end fw-semibold text-primary">{{ $formatQty($remainingToBill) }}</td>
                                        <td class="text-end">{{ $formatQty($pharmacyQty) }} <span class="badge bg-{{ $pharmacyStatus['class'] }} ms-1">{{ $pharmacyStatus['label'] }}</span></td>
                                        <td><input type="number" name="items[{{ $item->id }}][quantity]" class="form-control form-control-sm" value="{{ $formatQty($defaultQty) }}" min="0" max="{{ $formatQty($defaultQty) }}"></td>
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
                @endif
            </div>
        </div>
        @endcan

        <!-- Notes -->
        @if($prescription->notes)
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-notes me-1"></i>Notes</h6>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $prescription->notes }}</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar Info -->
    <div class="col-lg-4">
        <!-- Patient Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-user me-1"></i>Patient</h6>
            </div>
            <div class="card-body">
                <h6 class="fw-bold">{{ $prescription->patient->full_name }}</h6>
                <small class="text-muted d-block">{{ $prescription->patient->patient_number }}</small>
                <small class="text-muted d-block">{{ $prescription->patient->age }}y &middot; {{ $prescription->patient->gender->value }}</small>
                @if($prescription->patient->allergies)
                <div class="alert alert-danger py-1 mt-2 mb-0">
                    <small><strong>Allergies:</strong> {{ $prescription->patient->allergies }}</small>
                </div>
                @endif
            </div>
        </div>

        <!-- Visit Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-calendar-check me-1"></i>Visit</h6>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.visits.show', $prescription->visit) }}" class="fw-medium">{{ $prescription->visit->visit_number }}</a>
                <small class="text-muted d-block">{{ $prescription->visit->visit_date->format('d M Y') }}</small>
                <x-status-badge :status="$prescription->visit->status" />
            </div>
        </div>

        <!-- Doctor Card -->
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-stethoscope me-1"></i>Prescribing Doctor</h6>
            </div>
            <div class="card-body">
                <h6 class="fw-medium">Dr. {{ $prescription->doctor->full_name }}</h6>
                <small class="text-muted">{{ $prescription->doctor->department?->name ?? '—' }}</small>
            </div>
        </div>
    </div>
</div>
@endsection
