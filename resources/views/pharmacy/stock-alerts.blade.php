@extends('layouts.app')
@section('title', 'Stock Alerts')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-alert-triangle me-2 text-warning"></i>Stock Alerts</h4>
    </div>
    <div>
        <a href="{{ route('admin.pharmacy.stock.index') }}" class="btn btn-outline-primary btn-md">
            <i class="ti ti-arrow-left me-1"></i>Back to Stock
        </a>
    </div>
</div>

<!-- Expired Stock -->
@if(count($alerts['expired']) > 0)
<div class="card mb-3 border-danger">
    <div class="card-header bg-danger-subtle">
        <h6 class="fw-bold mb-0 text-danger">
            <i class="ti ti-alert-octagon me-1"></i>Expired Stock
            <span class="badge bg-danger ms-1">{{ count($alerts['expired']) }}</span>
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Drug</th>
                        <th>Batch #</th>
                        <th>Qty Remaining</th>
                        <th>Expiry Date</th>
                        <th>Supplier</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($alerts['expired'] as $entry)
                    <tr>
                        <td>
                            <span class="fw-medium">{{ $entry->drug->name ?? '-' }}</span>
                            @if($entry->drug)
                                <br><small class="text-muted">{{ $entry->drug->dosage_form }} {{ $entry->drug->strength }}</small>
                            @endif
                        </td>
                        <td><code>{{ $entry->batch_number }}</code></td>
                        <td><span class="badge bg-danger">{{ $entry->quantity }}</span></td>
                        <td class="text-danger fw-medium">{{ $entry->expiry_date?->format('d M Y') }}</td>
                        <td>{{ $entry->supplier ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Low Stock -->
@if(count($alerts['low_stock']) > 0)
<div class="card mb-3 border-warning">
    <div class="card-header bg-warning-subtle">
        <h6 class="fw-bold mb-0 text-warning">
            <i class="ti ti-trending-down me-1"></i>Low Stock
            <span class="badge bg-warning ms-1">{{ count($alerts['low_stock']) }}</span>
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Drug</th>
                        <th>Batch #</th>
                        <th>Qty Remaining</th>
                        <th>Reorder Level</th>
                        <th>Expiry Date</th>
                        <th>Supplier</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($alerts['low_stock'] as $entry)
                    <tr>
                        <td>
                            <span class="fw-medium">{{ $entry->drug->name ?? '-' }}</span>
                            @if($entry->drug)
                                <br><small class="text-muted">{{ $entry->drug->dosage_form }} {{ $entry->drug->strength }}</small>
                            @endif
                        </td>
                        <td><code>{{ $entry->batch_number }}</code></td>
                        <td><span class="badge bg-warning">{{ $entry->quantity }}</span></td>
                        <td>{{ $entry->reorder_level ?? '-' }}</td>
                        <td>{{ $entry->expiry_date?->format('d M Y') }}</td>
                        <td>{{ $entry->supplier ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Expiring Soon -->
@if(count($alerts['expiring_soon']) > 0)
<div class="card mb-3 border-info">
    <div class="card-header bg-info-subtle">
        <h6 class="fw-bold mb-0 text-info">
            <i class="ti ti-clock me-1"></i>Expiring Soon (within 90 days)
            <span class="badge bg-info ms-1">{{ count($alerts['expiring_soon']) }}</span>
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Drug</th>
                        <th>Batch #</th>
                        <th>Qty Remaining</th>
                        <th>Expiry Date</th>
                        <th>Days Left</th>
                        <th>Supplier</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($alerts['expiring_soon'] as $entry)
                    <tr>
                        <td>
                            <span class="fw-medium">{{ $entry->drug->name ?? '-' }}</span>
                            @if($entry->drug)
                                <br><small class="text-muted">{{ $entry->drug->dosage_form }} {{ $entry->drug->strength }}</small>
                            @endif
                        </td>
                        <td><code>{{ $entry->batch_number }}</code></td>
                        <td><span class="badge bg-info">{{ $entry->quantity }}</span></td>
                        <td>{{ $entry->expiry_date?->format('d M Y') }}</td>
                        <td>
                            @php $daysLeft = now()->diffInDays($entry->expiry_date, false); @endphp
                            <span class="badge bg-{{ $daysLeft < 30 ? 'danger' : ($daysLeft < 60 ? 'warning' : 'info') }}">
                                {{ (int)$daysLeft }} days
                            </span>
                        </td>
                        <td>{{ $entry->supplier ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@if(count($alerts['expired']) === 0 && count($alerts['low_stock']) === 0 && count($alerts['expiring_soon']) === 0)
<div class="card">
    <div class="card-body text-center py-5">
        <i class="ti ti-check fs-1 text-success d-block mb-2"></i>
        <h5 class="text-success">All Clear!</h5>
        <p class="text-muted mb-0">No stock alerts at this time.</p>
    </div>
</div>
@endif
@endsection
