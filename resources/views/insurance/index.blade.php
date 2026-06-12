@extends('layouts.app')
@section('title', 'Insurance Providers')

@section('content')
<x-page-header title="Insurance Providers" icon="ti-shield-check">
    <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">Total: {{ $providers->total() }}</span>
    <x-slot:actions>
        @can('claims.create')
        <button class="btn btn-primary btn-md fs-13" data-bs-toggle="modal" data-bs-target="#addProviderModal">
            <i class="ti ti-plus me-1"></i>Add Provider
        </button>
        @endcan
    </x-slot:actions>
</x-page-header>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.insurance-providers.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search name, short name..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    @foreach(\App\Enums\InsuranceType::cases() as $type)
                        <option value="{{ $type->value }}" {{ request('type') == $type->value ? 'selected' : '' }}>{{ $type->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="ti ti-search me-1"></i>Filter</button>
            </div>
            @if(request()->hasAny(['search', 'type']))
            <div class="col-md-2">
                <a href="{{ route('admin.insurance-providers.index') }}" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
            @endif
        </form>
    </div>
</div>

<!-- Providers Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Short Name</th>
                        <th>Type</th>
                        <th>Claim Type</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Contract #</th>
                        <th>Verification</th>
                        <th>Claims</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($providers as $provider)
                    <tr>
                        <td class="fw-medium">{{ $provider->name }}</td>
                        <td><span class="badge bg-light text-dark">{{ $provider->short_name ?? '-' }}</span></td>
                        <td><x-status-badge :status="$provider->type" /></td>
                        <td>
                            @if($provider->insuranceType)
                                <span class="badge bg-primary-subtle text-primary">{{ $provider->insuranceType->code }}</span>
                                <small class="text-muted d-block">{{ $provider->insuranceType->claim_workflow ?: 'GENERIC' }}</small>
                            @else
                                <span class="badge bg-light text-dark">Not set</span>
                            @endif
                        </td>
                        <td>{{ $provider->contact_phone ?? '-' }}</td>
                        <td>{{ $provider->contact_email ?? '-' }}</td>
                        <td>{{ $provider->contract_number ?? '-' }}</td>
                        <td>
                            @if($provider->verification_driver)
                                <span class="badge bg-info" title="{{ $provider->verification_method ?? '' }}{{ $provider->verification_channel ? ' • '.$provider->verification_channel : '' }}">
                                    <i class="ti ti-shield-check me-1"></i>{{ __('statuses.default.' . $provider->verification_driver) }}
                                </span>
                            @else
                                <span class="badge bg-light text-dark">Not required</span>
                            @endif
                        </td>
                        <td><span class="badge bg-soft-info">{{ $provider->claims_count ?? 0 }}</span></td>
                        <td>
                            <span class="badge bg-{{ $provider->is_active ? 'success' : 'danger' }}">
                                {{ $provider->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('claims.create')
                            <x-action-menu>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editProviderModal-{{ $provider->id }}">
                                    <i class="ti ti-edit me-2"></i>Edit
                                </button>
                                <a class="dropdown-item" href="{{ route('admin.insurance-providers.tiers.index', $provider) }}">
                                    <i class="ti ti-layers me-2"></i>Manage Tiers
                                </a>
                                <div class="dropdown-divider"></div>
                                <x-confirm-form
                                    :action="route('admin.insurance-providers.toggle', $provider)"
                                    method="PATCH"
                                    :button-label="$provider->is_active ? 'Deactivate' : 'Activate'"
                                    button-class="dropdown-item"
                                    :icon="$provider->is_active ? 'ti-eye-off' : 'ti-eye'"
                                    :confirm-title="($provider->is_active ? 'Deactivate' : 'Activate').' this provider?'"
                                    :confirm-text="$provider->is_active ? 'It will be hidden from new claims and verification.' : 'It will be available for new claims and verification.'"
                                    :confirm-button="$provider->is_active ? 'Yes, deactivate' : 'Yes, activate'" />
                            </x-action-menu>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11"><x-empty-state message="No insurance providers found" /></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($providers->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $providers->withQueryString()->links() }}
</div>
@endif

{{-- Edit Provider Modals (must live OUTSIDE the <table> -- modals inside <tbody> are invalid HTML
     and the browser will hoist the <div> out, breaking the <form> wrapper around the submit button). --}}
@foreach($providers as $provider)
<div class="modal fade" id="editProviderModal-{{ $provider->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.insurance-providers.update', $provider) }}">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Provider</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $provider->name }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Short Name</label>
                            <input type="text" name="short_name" class="form-control" value="{{ $provider->short_name }}" maxlength="20">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" value="{{ $provider->code }}" maxlength="60">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                        <label class="form-label">Pricing Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            @foreach(\App\Enums\InsuranceType::cases() as $type)
                                <option value="{{ $type->value }}" {{ $provider->type === $type ? 'selected' : '' }}>{{ $type->translatedLabel() }}</option>
                            @endforeach
                        </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Claim Workflow Type</label>
                            <select name="insurance_type_id" class="form-select">
                                <option value="">Use pricing type default</option>
                                @foreach($claimTypes as $claimType)
                                    <option value="{{ $claimType->id }}" {{ $provider->insurance_type_id == $claimType->id ? 'selected' : '' }}>
                                        {{ $claimType->name }} ({{ $claimType->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="contact_phone" class="form-control" value="{{ $provider->contact_phone }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="contact_email" class="form-control" value="{{ $provider->contact_email }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2">{{ $provider->address }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contract Number</label>
                        <input type="text" name="contract_number" class="form-control" value="{{ $provider->contract_number }}">
                    </div>
                    <div class="alert alert-info py-2 mb-3 small">
                        <i class="ti ti-info-circle me-1"></i>
                        Coverage rules (limits, coverage %, member types) are managed per <strong>Tier</strong>.
                        <a href="{{ route('admin.insurance-providers.tiers.index', $provider) }}" class="alert-link">Manage Tiers &rarr;</a>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_default" class="form-check-input" value="1" id="editDefault{{ $provider->id }}" {{ $provider->is_default ? 'checked' : '' }}>
                        <label class="form-check-label" for="editDefault{{ $provider->id }}">Default Provider</label>
                    </div>

                    @include('insurance._verification_fields', ['provider' => $provider, 'idSuffix' => 'edit_'.$provider->id])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- Add Provider Modal -->
<div class="modal fade" id="addProviderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.insurance-providers.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Insurance Provider</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Acme Health Plan" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Short Name</label>
                            <input type="text" name="short_name" class="form-control" placeholder="e.g. ACME" maxlength="20">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" placeholder="e.g. NHIS" maxlength="60">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                        <label class="form-label">Pricing Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            @foreach(\App\Enums\InsuranceType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->translatedLabel() }}</option>
                            @endforeach
                        </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Claim Workflow Type</label>
                            <select name="insurance_type_id" class="form-select">
                                <option value="">Use pricing type default</option>
                                @foreach($claimTypes as $claimType)
                                    <option value="{{ $claimType->id }}">{{ $claimType->name }} ({{ $claimType->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="contact_phone" class="form-control" placeholder="+233...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="contact_email" class="form-control" placeholder="email@provider.com">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Provider address..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contract Number</label>
                        <input type="text" name="contract_number" class="form-control" placeholder="Contract/Agreement #">
                    </div>
                    <div class="alert alert-info py-2 mb-3 small">
                        <i class="ti ti-info-circle me-1"></i>
                        Coverage rules are managed per <strong>Tier</strong> after the provider is created.
                        A <em>Standard</em> tier is auto-created. You can rename/add tiers from the provider dropdown.
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_default" class="form-check-input" value="1" id="addDefault">
                        <label class="form-check-label" for="addDefault">Default Provider</label>
                    </div>

                    @include('insurance._verification_fields', ['provider' => null, 'idSuffix' => 'add'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Provider</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Toggle credentials key + JSON config rows based on the chosen verification driver.
    function applyDriverVisibility(select) {
        const suffix = select.dataset.suffix;
        const driver = select.value;
        const apiRow    = document.querySelector('.verification-api-row[data-suffix="' + suffix + '"]');
        const configRow = document.querySelector('.verification-config-row[data-suffix="' + suffix + '"]');
        if (apiRow)    apiRow.style.display    = (driver === 'api') ? '' : 'none';
        if (configRow) configRow.style.display = (driver === 'api' || driver === 'code') ? '' : 'none';
    }
    document.querySelectorAll('.verification-driver-select').forEach(function (sel) {
        applyDriverVisibility(sel);
        sel.addEventListener('change', function () { applyDriverVisibility(sel); });
    });
})();
</script>
@endpush
