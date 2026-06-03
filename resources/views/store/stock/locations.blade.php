@extends('layouts.app')
@section('title', 'Stock Locations')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1"><h4 class="fw-bold mb-0">Stock Locations</h4></div>
    @can('store.purchase.create')
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newLocationModal"><i class="ti ti-plus me-1"></i>New Location</button>
    @endcan
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locations as $loc)
                    <tr>
                        <td>{{ $loc->name }}</td>
                        <td><span class="badge bg-light text-dark">{{ ucfirst($loc->type) }}</span></td>
                        <td>{{ $loc->department?->name ?? '—' }}</td>
                        <td>
                            @if($loc->is_active)<span class="badge bg-success">Active</span>
                            @else<span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('store.purchase.create')
                            <button class="btn btn-sm btn-outline-secondary edit-loc"
                                data-bs-toggle="modal" data-bs-target="#editLocationModal"
                                data-id="{{ $loc->id }}"
                                data-name="{{ $loc->name }}"
                                data-type="{{ $loc->type }}"
                                data-department="{{ $loc->department_id }}"
                                data-active="{{ (int) $loc->is_active }}"
                                data-notes="{{ $loc->notes }}"
                                data-url="{{ route('admin.store.stock.locations.update', $loc) }}"
                             aria-label="Edit" title="Edit"><i class="ti ti-pencil"></i></button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5"><x-empty-state message="No locations yet." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($locations->hasPages())<div class="card-footer">{{ $locations->links() }}</div>@endif
</div>

@can('store.purchase.create')
<!-- New -->
<div class="modal fade" id="newLocationModal" tabindex="-1"><div class="modal-dialog">
    <form method="POST" action="{{ route('admin.store.stock.locations.store') }}">@csrf
    <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">New Stock Location</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Name *</label><input name="name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Type *</label>
                <select name="type" class="form-select" required>
                    @foreach(['store','pharmacy','ward','theater','laboratory','other'] as $t)
                    <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Department</label>
                <select name="department_id" class="form-select"><option value="">—</option>
                    @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                </select>
            </div>
            <div><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Create</button>
        </div>
    </div>
    </form>
</div></div>

<!-- Edit -->
<div class="modal fade" id="editLocationModal" tabindex="-1"><div class="modal-dialog">
    <form method="POST" id="editLocationForm">@csrf @method('PUT')
    <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Edit Stock Location</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Name *</label><input name="name" id="el_name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Type *</label>
                <select name="type" id="el_type" class="form-select" required>
                    @foreach(['store','pharmacy','ward','theater','laboratory','other'] as $t)
                    <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Department</label>
                <select name="department_id" id="el_department" class="form-select"><option value="">—</option>
                    @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                </select>
            </div>
            <div class="form-check mb-3"><input type="hidden" name="is_active" value="0"><input type="checkbox" id="el_active" name="is_active" value="1" class="form-check-input"><label for="el_active" class="form-check-label">Active</label></div>
            <div><label class="form-label">Notes</label><textarea name="notes" id="el_notes" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </div>
    </form>
</div></div>

@push('scripts')
<script>
document.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    if (!btn || !btn.classList.contains('edit-loc')) return;
    document.getElementById('editLocationForm').setAttribute('action', btn.dataset.url);
    document.getElementById('el_name').value = btn.dataset.name;
    document.getElementById('el_type').value = btn.dataset.type;
    document.getElementById('el_department').value = btn.dataset.department || '';
    document.getElementById('el_notes').value = btn.dataset.notes || '';
    document.getElementById('el_active').checked = btn.dataset.active === '1';
});
</script>
@endpush
@endcan
@endsection
