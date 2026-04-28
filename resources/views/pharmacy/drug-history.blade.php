@extends('layouts.app')
@section('title', 'Drug History — ' . $drug->name)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            <a href="{{ route('admin.pharmacy.drugs.index') }}" class="text-muted me-2"><i class="ti ti-arrow-left"></i></a>
            <i class="ti ti-pill me-1"></i>{{ $drug->name }}
            @if($drug->strength)
                <span class="text-muted fw-normal fs-5">— {{ $drug->strength }}</span>
            @endif
        </h4>
        <small class="text-muted">{{ $drug->category->name ?? 'Uncategorised' }} &middot; {{ $drug->dosage_form }}</small>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-{{ $drug->is_active ? 'success' : 'secondary' }} fs-14 px-3 py-2">
            {{ $drug->is_active ? 'Active' : 'Inactive' }}
        </span>
    </div>
</div>

<!-- Drug Info + Summary Stats -->
<div class="row g-3 mb-4">
    <!-- Drug Info -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti ti-info-circle me-1"></i>Drug Details</h6></div>
            <div class="card-body">
                <dl class="row mb-0 g-1" style="font-size:0.9rem">
                    <dt class="col-5 text-muted">Name</dt>
                    <dd class="col-7">{{ $drug->name }}</dd>

                    @if($drug->generic_name)
                    <dt class="col-5 text-muted">Generic</dt>
                    <dd class="col-7">{{ $drug->generic_name }}</dd>
                    @endif

                    @if($drug->brand_name)
                    <dt class="col-5 text-muted">Brand</dt>
                    <dd class="col-7">{{ $drug->brand_name }}</dd>
                    @endif

                    <dt class="col-5 text-muted">Category</dt>
                    <dd class="col-7">{{ $drug->category->name ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Form</dt>
                    <dd class="col-7">{{ $drug->dosage_form }}</dd>

                    <dt class="col-5 text-muted">Strength</dt>
                    <dd class="col-7">{{ $drug->strength ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Unit</dt>
                    <dd class="col-7">{{ $drug->unit }}</dd>

                    <dt class="col-5 text-muted">Price</dt>
                    <dd class="col-7 fw-medium text-primary">GHS {{ number_format($drug->price, 2) }}</dd>

                    <dt class="col-5 text-muted">Rx Required</dt>
                    <dd class="col-7">
                        <span class="badge bg-{{ $drug->requires_prescription ? 'warning' : 'secondary' }}">
                            {{ $drug->requires_prescription ? 'Yes' : 'No' }}
                        </span>
                    </dd>

                    @if($drug->description)
                    <dt class="col-5 text-muted">Notes</dt>
                    <dd class="col-7">{{ $drug->description }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="col-md-8">
        <div class="row g-3">
            <div class="col-6 col-lg-3">
                <div class="card text-center border-primary h-100">
                    <div class="card-body py-3">
                        <i class="ti ti-packages fs-2 text-primary mb-1"></i>
                        <h3 class="mb-0 fw-bold">{{ $stats['total_batches'] }}</h3>
                        <small class="text-muted">Batches</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card text-center border-success h-100">
                    <div class="card-body py-3">
                        <i class="ti ti-arrow-down-circle fs-2 text-success mb-1"></i>
                        <h3 class="mb-0 fw-bold">{{ number_format($stats['total_received']) }}</h3>
                        <small class="text-muted">Total Received</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card text-center border-warning h-100">
                    <div class="card-body py-3">
                        <i class="ti ti-arrow-up-circle fs-2 text-warning mb-1"></i>
                        <h3 class="mb-0 fw-bold">{{ number_format($stats['total_dispensed']) }}</h3>
                        <small class="text-muted">Total Dispensed</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card text-center border-info h-100">
                    <div class="card-body py-3">
                        <i class="ti ti-stack fs-2 text-info mb-1"></i>
                        <h3 class="mb-0 fw-bold">{{ number_format($stats['current_stock']) }}</h3>
                        <small class="text-muted">In Stock</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Card -->
        <div class="card mt-3">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <i class="ti ti-cash fs-2 text-success"></i>
                <div>
                    <div class="text-muted small">Total Revenue Generated</div>
                    <div class="fw-bold fs-5 text-success">GHS {{ number_format($stats['revenue'], 2) }}</div>
                </div>
                <div class="ms-auto text-end">
                    <div class="text-muted small">Dispensing Events</div>
                    <div class="fw-bold">{{ $dispensingRecords->count() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stock Batches -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="ti ti-packages me-1"></i>Stock Batches</h6>
        <span class="badge bg-soft-primary">{{ $drug->stocks->count() }} batches</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.88rem">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Batch No.</th>
                        <th>Supplier</th>
                        <th>Location</th>
                        <th>Received Date</th>
                        <th>Received By</th>
                        <th>Unit Cost</th>
                        <th>Sell Price</th>
                        <th>Expiry</th>
                        <th class="text-end">Received</th>
                        <th class="text-end">Dispensed</th>
                        <th class="text-end">Remaining</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drug->stocks->sortByDesc('received_date') as $index => $batch)
                    @php
                        $batchDispensed = $batch->dispensingRecords->sum('quantity_dispensed');
                        $batchReceived  = $batch->quantity + $batchDispensed;
                        $isExpired      = $batch->expiry_date && $batch->expiry_date->isPast();
                        $isExpiringSoon = !$isExpired && $batch->expiry_date && $batch->expiry_date->diffInDays(now()) <= 90;
                    @endphp
                    <tr class="{{ $isExpired ? 'table-danger' : ($isExpiringSoon ? 'table-warning' : '') }}">
                        <td class="text-muted">{{ $index + 1 }}</td>
                        <td><span class="badge bg-soft-secondary font-monospace">{{ $batch->batch_number ?? 'N/A' }}</span></td>
                        <td>{{ $batch->supplier ?? '—' }}</td>
                        <td>{{ $batch->location ?? '—' }}</td>
                        <td>{{ $batch->received_date?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $batch->receivedBy?->full_name ?? '—' }}</td>
                        <td>{{ $batch->unit_cost ? 'GHS '.number_format($batch->unit_cost, 2) : '—' }}</td>
                        <td>{{ $batch->selling_price ? 'GHS '.number_format($batch->selling_price, 2) : '—' }}</td>
                        <td>
                            @if($batch->expiry_date)
                                {{ $batch->expiry_date->format('d M Y') }}
                                @if($isExpired)
                                    <span class="badge bg-danger ms-1">Expired</span>
                                @elseif($isExpiringSoon)
                                    <span class="badge bg-warning ms-1">Soon</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end fw-medium text-success">{{ number_format($batchReceived) }}</td>
                        <td class="text-end text-warning">{{ number_format($batchDispensed) }}</td>
                        <td class="text-end fw-bold {{ $batch->quantity <= 0 ? 'text-danger' : 'text-primary' }}">
                            {{ number_format($batch->quantity) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">No stock batches recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($drug->stocks->count() > 0)
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="9" class="text-end">Totals</td>
                        <td class="text-end text-success">{{ number_format($stats['total_received']) }}</td>
                        <td class="text-end text-warning">{{ number_format($stats['total_dispensed']) }}</td>
                        <td class="text-end text-primary">{{ number_format($stats['current_stock']) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<!-- Dispensing History -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="ti ti-history me-1"></i>Dispensing History</h6>
        <span class="badge bg-soft-warning">{{ $dispensingRecords->count() }} records</span>
    </div>

    @if($dispensingRecords->count() > 0)
    <!-- Timeline grouped by date -->
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.88rem">
                <thead class="table-light">
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Patient</th>
                        <th>Prescription</th>
                        <th>Batch</th>
                        <th class="text-end">Qty Dispensed</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Line Total</th>
                        <th>Dispensed By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dispensingRecords as $record)
                    <tr>
                        <td>
                            <span class="fw-medium">{{ $record->dispensed_at->format('d M Y') }}</span>
                            <br><small class="text-muted">{{ $record->dispensed_at->format('H:i') }}</small>
                        </td>
                        <td>
                            @if($record->patient)
                                <a href="{{ route('admin.patients.show', $record->patient) }}" class="fw-medium text-primary">
                                    {{ $record->patient->full_name }}
                                </a>
                                <br><small class="text-muted">{{ $record->patient->patient_number }}</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($record->prescription)
                                <a href="{{ route('admin.pharmacy.dispensing.show', $record->prescription) }}" class="text-primary font-monospace">
                                    {{ $record->prescription->prescription_number }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($record->drugStock)
                                <span class="badge bg-soft-secondary font-monospace">{{ $record->drugStock->batch_number ?? 'N/A' }}</span>
                                @if($record->drugStock->expiry_date)
                                    <br><small class="text-muted">Exp: {{ $record->drugStock->expiry_date->format('d M Y') }}</small>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold">{{ number_format($record->quantity_dispensed) }}</td>
                        <td class="text-end">
                            {{ $record->drugStock?->selling_price ? 'GHS '.number_format($record->drugStock->selling_price, 2) : '—' }}
                        </td>
                        <td class="text-end text-success fw-medium">
                            @if($record->drugStock?->selling_price)
                                GHS {{ number_format($record->quantity_dispensed * $record->drugStock->selling_price, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $record->dispensedBy?->full_name ?? '—' }}</td>
                        <td><small class="text-muted">{{ $record->notes ?? '—' }}</small></td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="4" class="text-end">Total</td>
                        <td class="text-end">{{ number_format($stats['total_dispensed']) }}</td>
                        <td></td>
                        <td class="text-end text-success">GHS {{ number_format($stats['revenue'], 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @else
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-history fs-1 mb-2 d-block"></i>
        No dispensing records for this drug yet.
    </div>
    @endif
</div>
@endsection
