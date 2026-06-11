@extends('layouts.app')
@section('title', __('pharmacy.dispensing_queue'))

@section('content')
<x-page-header :title="__('pharmacy.dispensing_queue')" icon="ti-pill">
    <x-slot:actions>
        @can('invoices.create')
        <a href="{{ route('admin.billing.counter-sale.create') }}" class="btn btn-primary btn-md">
            <i class="ti ti-cash-register me-1"></i>{{ __('pharmacy.counter_sale') }}
        </a>
        @endcan
        <a href="{{ route('admin.pharmacy.history') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-history me-1"></i>{{ __('pharmacy.dispensing_history') }}
        </a>
    </x-slot:actions>
</x-page-header>

<!-- Stats Cards -->
<div class="row g-3 mb-3">
    <div class="col-md-2">
        <div class="card border-warning">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-warning">{{ $stats['pending_prescriptions'] }}</h3>
                <small class="text-muted">{{ __('pharmacy.pending') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-info">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-info">{{ $stats['partially_dispensed'] }}</h3>
                <small class="text-muted">{{ __('pharmacy.partial') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-success">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-success">{{ $stats['dispensed_today'] }}</h3>
                <small class="text-muted">{{ __('pharmacy.dispensed_today') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-danger">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-danger">{{ $stats['low_stock_count'] }}</h3>
                <small class="text-muted">{{ __('pharmacy.low_stock') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-orange">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-warning">{{ $stats['expiring_soon_count'] }}</h3>
                <small class="text-muted">{{ __('pharmacy.expiring_soon') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-primary">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-primary">{{ $stats['total_drugs'] }}</h3>
                <small class="text-muted">{{ __('pharmacy.active_drugs') }}</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="{{ __('pharmacy.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-md"><i class="ti ti-search me-1"></i>{{ __('common.search') }}</button>
                <a href="{{ route('admin.pharmacy.dispensing.index') }}" class="btn btn-outline-secondary btn-md">{{ __('common.clear') }}</a>
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
                        <th>{{ __('pharmacy.rx_number_short') }}</th>
                        <th>{{ __('common.patient') }}</th>
                        <th>{{ __('common.doctor') }}</th>
                        <th>{{ __('pharmacy.items') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('common.date') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
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
                            <span class="badge bg-soft-primary">{{ __('pharmacy.items_count', ['count' => $rx->items->count()]) }}</span>
                            @php
                                $dispensed = $rx->items->where('is_dispensed', true)->count();
                            @endphp
                            @if($dispensed > 0)
                                <span class="badge bg-soft-success">{{ __('pharmacy.dispensed_count', ['count' => $dispensed]) }}</span>
                            @endif
                        </td>
                        <td>
                            <x-status-badge :status="$rx->status" />
                        </td>
                        <td>
                            <small>{{ $rx->created_at->translatedFormat('d M Y') }}</small><br>
                            <small class="text-muted">{{ $rx->created_at->format('H:i') }}</small>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.pharmacy.dispensing.show', $rx) }}" class="btn btn-sm btn-primary">
                                <i class="ti ti-pill me-1"></i>{{ __('pharmacy.dispense') }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <x-empty-state icon="ti-pill" :title="__('pharmacy.nothing_to_dispense')" :message="__('pharmacy.no_pending_prescriptions')" />
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
