@extends('layouts.app')
@section('title', 'Permissions - ' . $role->name)

@section('content')
@php
    $canManageCriticalPermissions = auth()->user()?->can('permissions.assign_critical') ?? false;
@endphp
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
                    @php
                        $riskMeta  = $riskLevels[$permission->meta_risk] ?? ['label' => $permission->meta_risk, 'color' => 'secondary'];
                        $criticalLocked = $permission->meta_risk === 'CRITICAL' && ! $canManageCriticalPermissions;
                        $isAssigned = in_array($permission->name, $rolePermissions);
                    @endphp
                    <div class="form-check mb-2 d-flex align-items-start gap-2">
                        @if($criticalLocked && $isAssigned)
                            <input type="hidden" name="permissions[]" value="{{ $permission->name }}">
                        @endif
                        <input class="form-check-input perm-{{ $module }} mt-1" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm-{{ $permission->id }}"
                            {{ $isAssigned ? 'checked' : '' }}
                            @disabled($criticalLocked)>
                        <label class="form-check-label flex-grow-1" for="perm-{{ $permission->id }}"
                            data-bs-toggle="tooltip" data-bs-placement="top"
                            title="{{ $permission->meta_description }} ({{ $permission->name }})">
                            <span class="d-flex align-items-center flex-wrap gap-1">
                                <span>{{ ucfirst(str_replace($module . '.', '', $permission->name)) }}</span>
                                <span class="badge bg-{{ $riskMeta['color'] }} fs-10">{{ $riskMeta['label'] }}</span>
                                @if($criticalLocked)
                                    <span class="badge bg-dark fs-10">locked</span>
                                @endif
                            </span>
                            <span class="d-block text-muted fs-12 mt-1">{{ $permission->meta_description }}</span>
                            <code class="d-block fs-11 mt-1">{{ $permission->name }}</code>
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

    // Activate Bootstrap tooltips for permission descriptions
    if (window.bootstrap && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
            new bootstrap.Tooltip(el);
        });
    }
</script>
@endpush
