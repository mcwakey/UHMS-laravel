@extends('layouts.app')
@section('title', 'Manage Tiers — ' . $provider->name)

@section('content')

{{-- Page Header --}}
<div class="d-flex align-items-center gap-2 pb-3 mb-3 border-bottom">
    <a href="{{ route('admin.insurance-providers.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i>Providers
    </a>
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            {{ $provider->name }}
            <span class="badge bg-{{ $provider->type->color() }} ms-2">{{ $provider->type->label() }}</span>
            <span class="badge badge-soft-secondary ms-1 fs-12">Tiers ({{ $tiers->count() }})</span>
        </h4>
        <small class="text-muted">Coverage rules, limits, holder/beneficiary overrides and visit intervals</small>
    </div>
    @can('claims.create')
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTierModal">
        <i class="ti ti-plus me-1"></i>Add Tier
    </button>
    @endcan
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@forelse($tiers as $tier)
<div class="card mb-3 {{ $tier->is_default ? 'border-primary' : '' }}">
    <div class="card-header d-flex align-items-center py-2">
        <div class="flex-grow-1">
            <span class="fw-bold">{{ $tier->name }}</span>
            @if($tier->code)
                <span class="badge bg-light text-dark ms-2">{{ $tier->code }}</span>
            @endif
            @if($tier->is_default)
                <span class="badge bg-primary ms-1">Default</span>
            @endif
            @if(!$tier->is_active)
                <span class="badge bg-danger ms-1">Inactive</span>
            @endif
            @if($tier->description)
                <small class="text-muted ms-2">{{ $tier->description }}</small>
            @endif
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-soft-info text-info">
                {{ $tier->patientInsurances()->count() }} enrolled
            </span>
            @can('claims.create')
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editTierModal-{{ $tier->id }}">
                <i class="ti ti-edit me-1"></i>Edit
            </button>
            <form method="POST" action="{{ route('admin.insurance-tiers.destroy', $tier) }}" class="d-inline"
                  onsubmit="return confirm('Delete tier \'{{ $tier->name }}\'? This cannot be undone.')">
                @csrf @method('DELETE')
                <button aria-label="Delete" title="Delete" type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="ti ti-trash"></i>
                </button>
            </form>
            @endcan
        </div>
    </div>
    <div class="card-body py-3">

        {{-- Base constraints --}}
        <div class="row g-3 mb-3">
            <div class="col-12"><h6 class="text-muted text-uppercase fs-11 mb-1">Base Constraints (apply to all members unless overridden below)</h6></div>
            <div class="col-auto">
                <label class="text-muted small">Coverage</label>
                <div class="fw-bold">{{ $tier->coverage_percentage ?? 100 }}%</div>
            </div>
            <div class="col-auto">
                <label class="text-muted small">Per Visit Limit</label>
                <div class="fw-bold">{{ $tier->per_visit_limit ? '₵' . number_format($tier->per_visit_limit, 2) : '—' }}</div>
            </div>
            <div class="col-auto">
                <label class="text-muted small">Monthly Limit</label>
                <div class="fw-bold">{{ $tier->max_per_month ? '₵' . number_format($tier->max_per_month, 2) : '—' }}</div>
            </div>
            <div class="col-auto">
                <label class="text-muted small">Annual Limit</label>
                <div class="fw-bold">{{ $tier->annual_limit ? '₵' . number_format($tier->annual_limit, 2) : '—' }}</div>
            </div>
            <div class="col-auto">
                <label class="text-muted small">Max Visits/Month</label>
                <div class="fw-bold">{{ $tier->max_visits_per_month ?? '—' }}</div>
            </div>
            <div class="col-auto">
                <label class="text-muted small">Min Interval (days)</label>
                <div class="fw-bold">{{ $tier->min_visit_interval_days ?? '—' }}</div>
            </div>
            <div class="col-auto">
                <label class="text-muted small">Max Beneficiaries</label>
                <div class="fw-bold">{{ $tier->max_beneficiaries ?? 'Unlimited' }}</div>
            </div>
        </div>

        @if($tier->holder_per_visit_limit || $tier->holder_annual_limit || $tier->holder_max_per_month || $tier->holder_max_visits_per_month)
        <div class="row g-3 mb-2">
            <div class="col-12"><h6 class="text-primary text-uppercase fs-11 mb-1">Card Holder Overrides</h6></div>
            <div class="col-auto"><label class="text-muted small">Per Visit</label><div class="fw-bold text-primary">{{ $tier->holder_per_visit_limit ? '₵' . number_format($tier->holder_per_visit_limit, 2) : '(base)' }}</div></div>
            <div class="col-auto"><label class="text-muted small">Monthly</label><div class="fw-bold text-primary">{{ $tier->holder_max_per_month ? '₵' . number_format($tier->holder_max_per_month, 2) : '(base)' }}</div></div>
            <div class="col-auto"><label class="text-muted small">Annual</label><div class="fw-bold text-primary">{{ $tier->holder_annual_limit ? '₵' . number_format($tier->holder_annual_limit, 2) : '(base)' }}</div></div>
            <div class="col-auto"><label class="text-muted small">Max Visits/Month</label><div class="fw-bold text-primary">{{ $tier->holder_max_visits_per_month ?? '(base)' }}</div></div>
        </div>
        @endif

        @if($tier->beneficiary_per_visit_limit || $tier->beneficiary_annual_limit || $tier->beneficiary_max_per_month || $tier->beneficiary_max_visits_per_month)
        <div class="row g-3">
            <div class="col-12"><h6 class="text-info text-uppercase fs-11 mb-1">Beneficiary Overrides</h6></div>
            <div class="col-auto"><label class="text-muted small">Per Visit</label><div class="fw-bold text-info">{{ $tier->beneficiary_per_visit_limit ? '₵' . number_format($tier->beneficiary_per_visit_limit, 2) : '(base)' }}</div></div>
            <div class="col-auto"><label class="text-muted small">Monthly</label><div class="fw-bold text-info">{{ $tier->beneficiary_max_per_month ? '₵' . number_format($tier->beneficiary_max_per_month, 2) : '(base)' }}</div></div>
            <div class="col-auto"><label class="text-muted small">Annual</label><div class="fw-bold text-info">{{ $tier->beneficiary_annual_limit ? '₵' . number_format($tier->beneficiary_annual_limit, 2) : '(base)' }}</div></div>
            <div class="col-auto"><label class="text-muted small">Max Visits/Month</label><div class="fw-bold text-info">{{ $tier->beneficiary_max_visits_per_month ?? '(base)' }}</div></div>
        </div>
        @endif

    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editTierModal-{{ $tier->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.insurance-tiers.update', $tier) }}">
                @csrf @method('PUT')
                @include('insurance._tier_form', ['tier' => $tier, 'submitLabel' => 'Update Tier'])
            </form>
        </div>
    </div>
</div>
@empty
<div class="card"><div class="card-body text-center text-muted py-5">
    No tiers configured yet. <button class="btn btn-sm btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#addTierModal">Add First Tier</button>
</div></div>
@endforelse

{{-- Add Tier Modal --}}
<div class="modal fade" id="addTierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.insurance-providers.tiers.store', $provider) }}">
                @csrf
                @include('insurance._tier_form', ['tier' => null, 'submitLabel' => 'Create Tier'])
            </form>
        </div>
    </div>
</div>

@endsection
