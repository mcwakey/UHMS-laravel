@extends('layouts.app')
@section('title', __('blood_bank.dashboard'))

@section('content')
<x-page-header :title="__('blood_bank.dashboard')" :description="__('blood_bank.dashboard_description')" icon="ti-droplet-filled">
    <x-slot:actions>
        <a href="{{ route('admin.blood-bank.requests.index') }}" class="btn btn-primary btn-sm"><i class="ti ti-plus me-1"></i>{{ __('blood_bank.request_blood') }}</a>
        <a href="{{ route('admin.blood-bank.donors.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-users me-1"></i>{{ __('blood_bank.donors') }}</a>
        <a href="{{ route('admin.blood-bank.donations.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-droplet me-1"></i>{{ __('blood_bank.donations') }}</a>
        @can('blood_bank.settings.manage')
            <a href="{{ route('admin.blood-bank.storage.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-fridge me-1"></i>{{ __('blood_bank.storage') }}</a>
        @endcan
        <a href="{{ route('admin.blood-bank.reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-report-analytics me-1"></i>{{ __('blood_bank.reports') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-3">
    @foreach([
        [__('blood_bank.available_units'), $summary['available_units'] ?? 0, 'success'],
        [__('blood_bank.quarantined'), $summary['quarantined_units'] ?? 0, 'warning'],
        [__('blood_bank.pending_requests'), $summary['pending_requests'] ?? 0, 'primary'],
        [__('blood_bank.issued_today'), $summary['issued_today'] ?? 0, 'info'],
        [__('blood_bank.expiring_soon'), $summary['expiring_soon'] ?? 0, 'danger'],
    ] as [$label, $value, $color])
        <div class="col-6 col-xl">
            <div class="card border-start border-{{ $color }} border-3">
                <div class="card-body py-3">
                    <div class="small text-muted">{{ $label }}</div>
                    <div class="h4 mb-0">{{ $value }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ __('blood_bank.inventory_by_group') }}</h5>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.blood-bank.units.index') }}">{{ __('blood_bank.all_units') }}</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light"><tr><th>{{ __('blood_bank.group') }}</th><th>{{ __('blood_bank.component') }}</th><th>{{ __('common.status') }}</th><th class="text-end">{{ __('blood_bank.units') }}</th></tr></thead>
                        <tbody>
                            @forelse($inventoryByGroup as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row->blood_group }}</td>
                                    <td>{{ str_replace('_', ' ', $row->component_type) }}</td>
                                    <td><x-status-badge :status="$row->status" domain="blood_unit" /></td>
                                    <td class="text-end">{{ $row->total }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-empty-state icon="ti-droplet-off" :message="__('blood_bank.no_inventory_recorded')" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ __('blood_bank.pending_active_requests') }}</h5>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.blood-bank.requests.index') }}">{{ __('blood_bank.open_worklist') }}</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light"><tr><th>{{ __('blood_bank.request') }}</th><th>{{ __('common.patient') }}</th><th>{{ __('blood_bank.blood') }}</th><th>{{ __('common.status') }}</th></tr></thead>
                        <tbody>
                            @forelse($pendingRequests as $request)
                                <tr>
                                    <td>{{ $request->request_number }}</td>
                                    <td>{{ $request->patient->full_name ?? '—' }}</td>
                                    <td>{{ $request->blood_group }} {{ str_replace('_', ' ', $request->component_type) }} x{{ $request->units_requested }}</td>
                                    <td><x-status-badge :status="$request->status" domain="blood_request" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-empty-state icon="ti-droplet-off" :message="__('blood_bank.no_active_requests')" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-white"><h5 class="card-title mb-0 text-danger">{{ __('blood_bank.expiring_soon') }}</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light"><tr><th>{{ __('blood_bank.unit') }}</th><th>{{ __('blood_bank.group') }}</th><th>{{ __('common.location') }}</th><th>{{ __('blood_bank.expiry') }}</th></tr></thead>
                        <tbody>
                            @forelse($expiringUnits as $unit)
                                <tr>
                                    <td>{{ $unit->unit_number }}</td>
                                    <td>{{ $unit->blood_group }}</td>
                                    <td>{{ $unit->storageLocation->name ?? '—' }}</td>
                                    <td class="text-danger fw-semibold">{{ $unit->expiry_date?->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-empty-state icon="ti-clock-check" :message="__('blood_bank.no_expiring_units')" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
