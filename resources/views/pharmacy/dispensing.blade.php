@extends('layouts.app')
@section('title', 'Dispensing Queue')

@section('content')
<x-page-header title="Dispensing Queue" icon="ti-pill">
    <x-slot:actions>
        <a href="{{ route('admin.pharmacy.history') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-history me-1"></i>Dispensing History
        </a>
    </x-slot:actions>
</x-page-header>

<!-- Stats Cards -->
<div class="row g-3 mb-3">
    <div class="col-md-2">
        <div class="card border-warning">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-warning">{{ $stats['pending_prescriptions'] }}</h3>
                <small class="text-muted">Pending</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-info">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-info">{{ $stats['partially_dispensed'] }}</h3>
                <small class="text-muted">Partial</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-success">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-success">{{ $stats['dispensed_today'] }}</h3>
                <small class="text-muted">Dispensed Today</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-danger">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-danger">{{ $stats['low_stock_count'] }}</h3>
                <small class="text-muted">Low Stock</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-orange">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-warning">{{ $stats['expiring_soon_count'] }}</h3>
                <small class="text-muted">Expiring Soon</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-primary">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-primary">{{ $stats['total_drugs'] }}</h3>
                <small class="text-muted">Active Drugs</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search patient, Rx #..." value="{{ request('search') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-md"><i class="ti ti-search me-1"></i>Search</button>
                <a href="{{ route('admin.pharmacy.dispensing.index') }}" class="btn btn-outline-secondary btn-md">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Prescriptions Queue -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Rx #</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prescriptions as $rx)
                    <tr>
                        <td>
                            <a href="{{ route('admin.pharmacy.dispensing.show', $rx) }}" class="fw-medium text-primary">
                                {{ $rx->prescription_number }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-medium">{{ $rx->patient->full_name }}</div>
                            <small class="text-muted">{{ $rx->patient->patient_number }}</small>
                        </td>
                        <td>{{ $rx->doctor->name ?? '-' }}</td>
                        <td>
                            <span class="badge bg-soft-primary">{{ $rx->items->count() }} item(s)</span>
                            @php
                                $dispensed = $rx->items->where('is_dispensed', true)->count();
                            @endphp
                            @if($dispensed > 0)
                                <span class="badge bg-soft-success">{{ $dispensed }} dispensed</span>
                            @endif
                        </td>
                        <td>
                            <x-status-badge :status="$rx->status" />
                        </td>
                        <td>
                            <small>{{ $rx->created_at->format('d M Y') }}</small><br>
                            <small class="text-muted">{{ $rx->created_at->format('H:i') }}</small>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.pharmacy.dispensing.show', $rx) }}" class="btn btn-sm btn-primary">
                                <i class="ti ti-pill me-1"></i>Dispense
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <x-empty-state icon="ti-pill" title="Nothing to dispense" message="No pending prescriptions to dispense." />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($prescriptions->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $prescriptions->links() }}
</div>
@endif
@endsection
