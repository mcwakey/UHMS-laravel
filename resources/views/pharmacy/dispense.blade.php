@extends('layouts.app')
@section('title', 'Dispense - ' . $prescription->prescription_number)

@section('content')
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
                    $total = $prescription->items->count();
                    $dispensed = $prescription->items->where('is_dispensed', true)->count();
                    $pct = $total > 0 ? round(($dispensed / $total) * 100) : 0;
                @endphp
                <h1 class="mb-1 {{ $pct === 100 ? 'text-success' : 'text-primary' }}">{{ $pct }}%</h1>
                <div class="progress mb-2" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
                </div>
                <small class="text-muted">{{ $dispensed }} of {{ $total }} items dispensed</small>
            </div>
        </div>
    </div>
</div>

<!-- Batch Dispense Form -->
@if($prescription->items->where('is_dispensed', false)->count() > 0)
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
                                    <th>Prescribed Qty</th>
                                    <th>Already Dispensed</th>
                                    <th>Remaining</th>
                                    <th>Stock Available</th>
                                    <th>Qty to Dispense</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($prescription->items as $item)
                                @if(!$item->is_dispensed)
                                <tr>
                                    <td class="fw-medium">
                                        {{ $item->drug_name }}
                                        @if($item->drug)
                                            <br><small class="text-muted">{{ $item->drug->dosage_form }} {{ $item->drug->strength }}</small>
                                        @endif
                                    </td>
                                    <td><small>{{ $item->dosage }} &middot; {{ $item->frequency }}</small></td>
                                    <td>{{ $item->quantity ?? '-' }}</td>
                                    <td>{{ $item->total_dispensed }}</td>
                                    <td><span class="fw-medium text-primary">{{ $item->remaining_quantity }}</span></td>
                                    <td>
                                        @if($item->drug)
                                            @php $available = $item->drug->total_stock; @endphp
                                            <span class="badge bg-{{ $available > 0 ? ($available <= 10 ? 'warning' : 'success') : 'danger' }}">
                                                {{ $available }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">N/A</span>
                                        @endif
                                    </td>
                                    <td style="width: 100px;">
                                        <input type="number" name="items[{{ $item->id }}][quantity]"
                                            class="form-control form-control-sm"
                                            value="{{ $item->remaining_quantity }}"
                                            min="0" max="{{ $item->drug ? $item->drug->total_stock : 0 }}">
                                    </td>
                                    <td style="width: 150px;">
                                        <input type="text" name="items[{{ $item->id }}][notes]"
                                            class="form-control form-control-sm" placeholder="Optional">
                                    </td>
                                </tr>
                                @endif
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
                        <th>Qty</th>
                        <th>Dispensed</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prescription->items as $index => $item)
                    <tr class="{{ $item->is_dispensed ? 'table-success' : '' }}">
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
                        <td>{{ $item->quantity ?? '-' }}</td>
                        <td>
                            {{ $item->total_dispensed }}
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
                            @if($item->is_dispensed)
                                <span class="badge bg-success"><i class="ti ti-check me-1"></i>Dispensed</span>
                            @else
                                <span class="badge bg-warning">Pending</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if(!$item->is_dispensed && $item->drug)
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#dispenseModal-{{ $item->id }}">
                                <i class="ti ti-pill me-1"></i>Dispense
                            </button>
                            @elseif(!$item->drug)
                            <span class="badge bg-secondary">No drug linked</span>
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
        <i class="ti ti-check-circle fs-1 text-success d-block mb-2"></i>
        <h5 class="text-success">All items have been dispensed!</h5>
    </div>
</div>
@endif

{{-- Individual Dispense Modals --}}
@foreach($prescription->items as $item)
@if(!$item->is_dispensed && $item->drug)
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
                            <strong>Prescribed:</strong> {{ $item->quantity ?? '-' }} {{ $item->drug->unit ?? '' }} &middot;
                            <strong>Already dispensed:</strong> {{ $item->total_dispensed }} &middot;
                            <strong>Remaining:</strong> {{ $item->remaining_quantity }} &middot;
                            <strong>In stock:</strong> {{ $item->drug->total_stock }}
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity to Dispense <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control"
                            value="{{ min($item->remaining_quantity, $item->drug->total_stock) }}"
                            min="1" max="{{ min($item->remaining_quantity, $item->drug->total_stock) }}" required>
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
