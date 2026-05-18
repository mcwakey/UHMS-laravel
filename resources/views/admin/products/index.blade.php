@extends('layouts.app')
@section('title', 'Products')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-package me-2"></i>Products</h4>
        <small class="text-muted">{{ $products->total() }} products</small>
    </div>
    <div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="ti ti-plus me-1"></i>Add Product
        </button>
    </div>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(isset($errors) && $errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div>
@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.products.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search name or code" value="{{ $filters['search'] ?? '' }}"></div>
            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All types</option>
                    @foreach($types as $t)
                        <option value="{{ $t->value }}" @selected(($filters['type'] ?? '') === $t->value)>{{ $t->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All departments</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" @selected((string)($filters['department_id'] ?? '') === (string) $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm w-100" type="submit">Filter</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Name</th><th>Code</th><th>Type</th><th>Unit</th><th>Departments</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>@if($product->code)<code>{{ $product->code }}</code>@else <span class="text-muted">—</span> @endif</td>
                        <td><span class="badge bg-light text-dark">{{ $product->product_type?->label() ?? '—' }}</span></td>
                        <td>{{ $product->unit ?? '—' }}</td>
                        <td>
                            @foreach($product->departments as $d)
                                <span class="badge bg-info-subtle text-info">{{ $d->name }}</span>
                            @endforeach
                        </td>
                        <td>
                            @if($product->is_active)<span class="badge bg-success-subtle text-success">Active</span>
                            @else<span class="badge bg-secondary-subtle text-secondary">Inactive</span>@endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.products.show', $product) }}" class="btn btn-sm btn-outline-info" title="View / Stock"><i class="ti ti-eye"></i></a>
                            <button class="btn btn-sm btn-soft-info border" title="Insurance Prices"
                                data-bs-toggle="modal" data-bs-target="#productPricesModal-{{ $product->id }}">
                                <i class="ti ti-tag"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProductModal-{{ $product->id }}"><i class="ti ti-edit"></i></button>
                            <form method="POST" action="{{ route('admin.products.toggle', $product) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-secondary"><i class="ti ti-power"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No products found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $products->links() }}</div>
</div>

{{-- Edit Modals --}}
@foreach($products as $product)
    @include('admin.products._edit_modal', ['product' => $product, 'departments' => $departments, 'types' => $types])
    @include('admin.products._prices_modal', [
        'product'            => $product,
        'insuranceTypes'     => $insuranceTypes,
        'insuranceProviders' => $insuranceProviders,
    ])
@endforeach

{{-- Add Modal --}}
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" action="{{ route('admin.products.store') }}">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Add Product</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @include('admin.products._form_fields', ['product' => null, 'departments' => $departments, 'types' => $types])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.add-provider-row').forEach(function (btn) {
        const targetIdInit = btn.dataset.target;
        const containerInit = document.getElementById(targetIdInit);
        let rowCounter = containerInit ? containerInit.querySelectorAll('.provider-price-row').length + 1000 : 1000;

        btn.addEventListener('click', function () {
            const targetId  = this.dataset.target;
            const container = document.getElementById(targetId);
            const types     = JSON.parse(this.dataset.types);
            const providers = JSON.parse(this.dataset.providers);
            const idx       = rowCounter++;

            const typeOptions = types.map(t => `<option value="${escH(t.value)}">${escH(t.label)}</option>`).join('');
            const provOptions = providers.map(p => `<option value="${p.id}">${escH(p.name)}</option>`).join('');

            const row = document.createElement('div');
            row.className = 'row g-2 align-items-end mb-2 provider-price-row';
            row.innerHTML = `
                <div class="col-md-4">
                    <label class="form-label small">Type</label>
                    <select name="provider_prices[${idx}][insurance_type]" class="form-select form-select-sm" required>${typeOptions}</select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Provider</label>
                    <select name="provider_prices[${idx}][insurance_provider_id]" class="form-select form-select-sm" required>${provOptions}</select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Price (&#8373;)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">&#8373;</span>
                        <input type="number" name="provider_prices[${idx}][price]" class="form-control" step="0.01" min="0" required>
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
