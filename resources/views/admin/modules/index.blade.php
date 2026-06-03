@extends('layouts.app')

@section('title', 'Modules Management')

@section('content')
<div class="page-wrapper">
    <div class="content">

        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-1">Modules Management</h4>
                <p class="text-muted mb-0">Enable or disable optional system modules. Core modules cannot be disabled.</p>
            </div>
            <form method="POST" action="{{ route('admin.modules.flush') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-md fs-13">
                    <i class="ti ti-refresh me-1"></i>Flush Cache
                </button>
            </form>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;"></th>
                                <th>Module</th>
                                <th>Slug</th>
                                <th>Type</th>
                                <th>Depends On</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($modules as $module)
                            <tr>
                                <td>
                                    <i class="ti {{ $module->icon ?: 'ti-puzzle' }} fs-5 text-muted"></i>
                                </td>
                                <td>
                                    <strong>{{ $module->name }}</strong>
                                    @if($module->description)
                                        <div class="small text-muted">{{ $module->description }}</div>
                                    @endif
                                    @if(!empty($dependents[$module->slug] ?? []))
                                        <div class="small text-muted mt-1">
                                            <i class="ti ti-link"></i> Used by:
                                            {{ implode(', ', $dependents[$module->slug]) }}
                                        </div>
                                    @endif
                                </td>
                                <td><code>{{ $module->slug }}</code></td>
                                <td>
                                    @if($module->is_core)
                                        <span class="badge bg-primary">Core</span>
                                    @else
                                        <span class="badge bg-secondary">Optional</span>
                                    @endif
                                </td>
                                <td>
                                    @if($module->depends_on)
                                        <code>{{ $module->depends_on }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($module->is_enabled)
                                        <span class="badge bg-success-transparent text-success">
                                            <i class="ti ti-check"></i> Enabled
                                        </span>
                                    @else
                                        <span class="badge bg-danger-transparent text-danger">
                                            <i class="ti ti-x"></i> Disabled
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($module->is_core)
                                        <button class="btn btn-sm btn-light" disabled title="Core module — cannot be disabled">
                                            <i class="ti ti-lock"></i> Locked
                                        </button>
                                    @else
                                        <x-confirm-form :action="route('admin.modules.toggle', $module)" method="POST"
                                            :button-label="$module->is_enabled ? 'Disable' : 'Enable'"
                                            :button-class="$module->is_enabled ? 'btn btn-sm btn-outline-danger' : 'btn btn-sm btn-outline-success'"
                                            :icon="$module->is_enabled ? 'ti-toggle-right' : 'ti-toggle-left'"
                                            :confirm-title="($module->is_enabled ? 'Disable' : 'Enable').' module: '.$module->name.'?'"
                                            :confirm-text="$module->is_enabled ? 'Users will lose access to this module and its menu items.' : 'This module and its menu items will become available.'"
                                            :confirm-button="$module->is_enabled ? 'Yes, disable' : 'Yes, enable'" />
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-4 mb-0">
            <i class="ti ti-info-circle me-1"></i>
            <strong>Tip:</strong> Disabling a module hides its sidebar entries and blocks its routes, but data is preserved. Re-enable any time without data loss.
        </div>

    </div>
</div>
@endsection
