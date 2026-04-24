@extends('layouts.app')
@section('title', 'Permissions - ' . $role->name)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Manage Permissions: <span class="text-primary">{{ $role->name }}</span></h4>
    </div>
    <div>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>Back to Roles
        </a>
    </div>
</div>

<form method="POST" action="{{ route('admin.roles.permissions.update', $role) }}">
    @csrf
    @method('PUT')

    <div class="row">
        @foreach($permissions as $module => $modulePermissions)
        <div class="col-xl-4 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between py-2">
                    <h6 class="card-title mb-0 text-capitalize">{{ str_replace('_', ' ', $module) }}</h6>
                    <div class="form-check">
                        <input class="form-check-input module-check-all" type="checkbox" data-module="{{ $module }}"
                            {{ $modulePermissions->every(fn($p) => in_array($p->name, $rolePermissions)) ? 'checked' : '' }}>
                        <label class="form-check-label fs-12">All</label>
                    </div>
                </div>
                <div class="card-body py-2">
                    @foreach($modulePermissions as $permission)
                    <div class="form-check mb-2">
                        <input class="form-check-input perm-{{ $module }}" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm-{{ $permission->id }}"
                            {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}>
                        <label class="form-check-label" for="perm-{{ $permission->id }}">
                            {{ ucfirst(str_replace($module . '.', '', $permission->name)) }}
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3 mb-3">
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-check me-1"></i>Save Permissions
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    // Toggle all permissions in a module
    document.querySelectorAll('.module-check-all').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const module = this.dataset.module;
            document.querySelectorAll('.perm-' + module).forEach(function(perm) {
                perm.checked = checkbox.checked;
            });
        });
    });
</script>
@endpush
