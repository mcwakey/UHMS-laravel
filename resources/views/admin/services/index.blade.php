@extends('layouts.app')
@section('title', 'Service Catalog')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-list-details me-2"></i>Service Catalog</h4>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-md" data-bs-toggle="modal" data-bs-target="#addServiceModal">
            <i class="ti ti-plus me-1"></i>Add Service
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.services.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search service name or code..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                <a href="{{ route('admin.services.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Services Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-nowrap mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Service Name</th>
                        <th>Category</th>
                        <th>Departments</th>
                        <th class="text-end">Base Price (&#8373;)</th>
                        <th class="text-center">Insurance Prices</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                    @php
                        $deptIds = collect();
                        if ($service->department_id) $deptIds->push($service->department_id);
                        foreach ($service->specialties as $spec) {
                            if ($spec->department_id) $deptIds->push($spec->department_id);
                        }
                        $deptIds = $deptIds->unique();
                        $deptNames = $departments->whereIn('id', $deptIds)->pluck('name');
                        $typeDefaults = $service->prices->whereNull('insurance_provider_id')->keyBy('insurance_type');
                        $providerPrices = $service->prices->whereNotNull('insurance_provider_id');
                    @endphp
                    <tr>
                        <td class="fw-medium">{{ $service->code }}</td>
                        <td>
                            {{ $service->name }}
                            @if($service->specialties->isNotEmpty())
                            <div class="small text-muted mt-1">
                                @foreach($service->specialties as $spec)
                                <span class="badge bg-soft-secondary me-1">{{ $spec->name }}</span>
                                @endforeach
                            </div>
                            @endif
                        </td>
                        <td><span class="badge bg-soft-info">{{ ucfirst($service->category) }}</span></td>
                        <td>
                            @forelse($deptNames as $dname)
                            <span class="badge bg-soft-primary me-1">{{ $dname }}</span>
                            @empty
                            <span class="text-muted small">—</span>
                            @endforelse
                        </td>
                        <td class="text-end fw-medium">&#8373;{{ number_format($service->price, 2) }}</td>
                        <td class="text-center">
                            @if($typeDefaults->isNotEmpty() || $providerPrices->isNotEmpty())
                                <button class="btn btn-sm btn-soft-info border" data-bs-toggle="modal" data-bs-target="#pricesModal-{{ $service->id }}">
                                    <i class="ti ti-tag me-1"></i>{{ $typeDefaults->count() }} types
                                    @if($providerPrices->isNotEmpty())+ {{ $providerPrices->count() }}@endif
                                </button>
                            @else
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#pricesModal-{{ $service->id }}">
                                    <i class="ti ti-plus me-1"></i>Set Prices
                                </button>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $service->is_active ? 'success' : 'danger' }}">{{ $service->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editServiceModal-{{ $service->id }}">
                                            <i class="ti ti-edit me-1"></i>Edit
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#pricesModal-{{ $service->id }}">
                                            <i class="ti ti-tag me-1"></i>Manage Prices
                                        </button>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('admin.services.toggle', $service) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="dropdown-item {{ $service->is_active ? 'text-danger' : 'text-success' }}">
                                                <i class="ti ti-{{ $service->is_active ? 'x' : 'check' }} me-1"></i>
                                                {{ $service->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editServiceModal-{{ $service->id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.services.update', $service) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Edit Service</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-8 mb-3">
                                                <label class="form-label fw-medium">Name <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control" value="{{ $service->name }}" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-medium">Code <span class="text-danger">*</span></label>
                                                <input type="text" name="code" class="form-control" value="{{ $service->code }}" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-medium">Category <span class="text-danger">*</span></label>
                                                <select name="category" class="form-select" required>
                                                    @foreach($categories as $cat)
                                                    <option value="{{ $cat }}" {{ $service->category === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-medium">Department (primary)</label>
                                                <select name="department_id" class="form-select">
                                                    <option value="">— None —</option>
                                                    @foreach($departments as $dept)
                                                    <option value="{{ $dept->id }}" {{ $service->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-medium">Specialties <small class="text-muted">(adds service to those departments)</small></label>
                                            <div class="border rounded p-2" style="max-height:150px;overflow-y:auto">
                                                @foreach($specialties as $spec)
                                                <div class="form-check">
                                                    <input type="checkbox" name="specialties[]" value="{{ $spec->id }}" class="form-check-input" id="editSpec{{ $service->id }}_{{ $spec->id }}" {{ $service->specialties->contains($spec->id) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="editSpec{{ $service->id }}_{{ $spec->id }}">
                                                        {{ $spec->name }}
                                                        @if($spec->department)
                                                        <small class="text-muted">({{ $spec->department->name }})</small>
                                                        @endif
                                                    </label>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-medium">Base Price (&#8373;) <span class="text-danger">*</span></label>
                                                <input type="number" name="price" class="form-control" value="{{ $service->price }}" step="0.01" min="0" required>
                                                <small class="text-muted">Fallback when no insurance price configured</small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-medium">NHIS Price (&#8373;) <small class="text-muted">legacy</small></label>
                                                <input type="number" name="nhis_price" class="form-control" value="{{ $service->nhis_price }}" step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" name="is_nhis_covered" class="form-check-input" value="1" id="editNhis{{ $service->id }}" {{ $service->is_nhis_covered ? 'checked' : '' }}>
                                            <label class="form-check-label" for="editNhis{{ $service->id }}">NHIS Covered</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Update Service</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Insurance Prices Modal -->
                    <div class="modal fade" id="pricesModal-{{ $service->id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold"><i class="ti ti-tag me-1"></i>Insurance Prices — {{ $service->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('admin.services.prices.store', $service) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <!-- Default prices per type -->
                                        <h6 class="fw-bold mb-1">Default Prices by Insurance Type</h6>
                                        <p class="text-muted small mb-3">Applied to all patients with that insurance type (overrides base price). Leave blank to use base price (&#8373;{{ number_format($service->price, 2) }}).</p>
                                        <div class="row g-3 mb-4">
                                            @foreach($insuranceTypes as $type)
                                            @php $existing = $typeDefaults[$type->value] ?? null; @endphp
                                            <div class="col-md-3">
                                                <label class="form-label fw-medium">
                                                    <span class="badge bg-{{ $type->color() }}">{{ $type->label() }}</span>
                                                </label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">&#8373;</span>
                                                    <input type="number" name="type_prices[{{ $type->value }}]"
                                                        class="form-control"
                                                        value="{{ $existing ? number_format($existing->price, 2, '.', '') : '' }}"
                                                        placeholder="{{ number_format($service->price, 2) }}"
                                                        step="0.01" min="0">
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>

                                        <!-- Provider-specific overrides -->
                                        <h6 class="fw-bold mb-1">Provider-Specific Overrides</h6>
                                        <p class="text-muted small mb-3">Negotiated rates for specific insurance companies. These override the type default above.</p>
                                        <div id="providerPrices-{{ $service->id }}">
                                            @foreach($providerPrices as $pp)
                                            <div class="row g-2 align-items-end mb-2 provider-price-row">
                                                <div class="col-md-4">
                                                    <label class="form-label small">Type</label>
                                                    <select name="provider_prices[][insurance_type]" class="form-select form-select-sm" required>
                                                        @foreach($insuranceTypes as $type)
                                                        <option value="{{ $type->value }}" {{ $pp->insurance_type === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">Provider</label>
                                                    <select name="provider_prices[][insurance_provider_id]" class="form-select form-select-sm" required>
                                                        @foreach($insuranceProviders as $prov)
                                                        <option value="{{ $prov->id }}" {{ $pp->insurance_provider_id == $prov->id ? 'selected' : '' }}>{{ $prov->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small">Price (&#8373;)</label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">&#8373;</span>
                                                        <input type="number" name="provider_prices[][price]" class="form-control" value="{{ number_format($pp->price, 2, '.', '') }}" step="0.01" min="0" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-1 d-flex align-items-end pb-1">
                                                    <a href="{{ route('admin.services.prices.delete', [$service, $pp]) }}"
                                                        onclick="return confirm('Remove this price?')"
                                                        class="btn btn-sm btn-outline-danger">
                                                        <i class="ti ti-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1 add-provider-row"
                                            data-target="providerPrices-{{ $service->id }}"
                                            data-types='@json(collect($insuranceTypes)->map(fn($t)=>["value"=>$t->value,"label"=>$t->label()]))'
                                            data-providers='@json($insuranceProviders->map(fn($p)=>["id"=>$p->id,"name"=>$p->name]))'>
                                            <i class="ti ti-plus me-1"></i>Add Provider Override
                                        </button>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Save Prices</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <i class="ti ti-list-details fs-1 d-block mb-2"></i>
                                No services found. Add your first service.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($services->hasPages())
    <div class="card-footer">
        {{ $services->links() }}
    </div>
    @endif
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.services.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add New Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-medium">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. General Consultation" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-medium">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="e.g. CONSULT-001" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="">Select category</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Department (primary)</label>
                            <select name="department_id" class="form-select">
                                <option value="">— None —</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Specialties <small class="text-muted">(adds service to those departments)</small></label>
                        <div class="border rounded p-2" style="max-height:150px;overflow-y:auto">
                            @foreach($specialties as $spec)
                            <div class="form-check">
                                <input type="checkbox" name="specialties[]" value="{{ $spec->id }}" class="form-check-input" id="addSpec{{ $spec->id }}">
                                <label class="form-check-label" for="addSpec{{ $spec->id }}">
                                    {{ $spec->name }}
                                    @if($spec->department)
                                    <small class="text-muted">({{ $spec->department->name }})</small>
                                    @endif
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Base Price (&#8373;) <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                            <small class="text-muted">Fallback when no insurance price configured</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">NHIS Price (&#8373;) <small class="text-muted">legacy</small></label>
                            <input type="number" name="nhis_price" class="form-control" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_nhis_covered" class="form-check-input" value="1" id="addNhisCovered">
                        <label class="form-check-label" for="addNhisCovered">NHIS Covered</label>
                    </div>
                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="ti ti-info-circle me-1"></i>After creating, use <strong>Manage Prices</strong> to set insurance-specific rates.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Service</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.add-provider-row').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = this.dataset.target;
            const container = document.getElementById(targetId);
            const types = JSON.parse(this.dataset.types);
            const providers = JSON.parse(this.dataset.providers);

            const typeOptions = types.map(t => `<option value="${escH(t.value)}">${escH(t.label)}</option>`).join('');
            const provOptions = providers.map(p => `<option value="${p.id}">${escH(p.name)}</option>`).join('');

            const row = document.createElement('div');
            row.className = 'row g-2 align-items-end mb-2 provider-price-row';
            row.innerHTML = `
                <div class="col-md-4">
                    <label class="form-label small">Type</label>
                    <select name="provider_prices[][insurance_type]" class="form-select form-select-sm" required>
                        ${typeOptions}
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Provider</label>
                    <select name="provider_prices[][insurance_provider_id]" class="form-select form-select-sm" required>
                        ${provOptions}
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Price (&#8373;)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">&#8373;</span>
                        <input type="number" name="provider_prices[][price]" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end pb-1">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button>
                </div>`;
            container.appendChild(row);
            row.querySelector('.remove-row').addEventListener('click', () => row.remove());
        });
    });

    function escH(text) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(String(text)));
        return d.innerHTML;
    }
});
</script>
@endpush
