@extends('layouts.app')
@section('title', __('users.department_assignments'))

@section('content')
<x-page-header-back
    :title="__('users.department_assignments') . ' - ' . $user->name"
    :href="route('admin.users.edit', $user)"
/>

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="fw-bold mb-0"><i class="ti ti-building-hospital me-1"></i>{{ __('users.assigned_departments') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('users.department') }}</th>
                                <th>{{ __('common.type') }}</th>
                                <th>{{ __('users.role_context') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th class="text-end">{{ __('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($user->departments as $department)
                                @php
                                    $starts = $department->pivot?->starts_at ? \Carbon\Carbon::parse($department->pivot->starts_at) : null;
                                    $ends = $department->pivot?->ends_at ? \Carbon\Carbon::parse($department->pivot->ends_at) : null;
                                    $isFuture = $starts && $starts->isFuture();
                                    $isExpired = $ends && $ends->isPast();
                                    $isPrimary = (bool) ($department->pivot?->is_primary ?? false) || (int) $user->department_id === (int) $department->id;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $department->name }}</div>
                                        @if($isPrimary)
                                            <span class="badge bg-primary">{{ __('users.primary_department') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $department->type?->translatedLabel() ?? __('dashboards.department.generic_type') }}</td>
                                    <td>{{ $department->pivot?->role_context ?: __('common.not_available') }}</td>
                                    <td>
                                        @if($isExpired)
                                            <span class="badge bg-secondary">{{ __('users.expired_assignment') }}</span>
                                        @elseif($isFuture)
                                            <span class="badge bg-info">{{ __('users.future_assignment') }}</span>
                                        @else
                                            <span class="badge bg-success">{{ __('users.active_assignment') }}</span>
                                        @endif
                                        <div class="small text-muted">
                                            {{ $starts ? $starts->toDateString() : __('common.not_available') }}
                                            -
                                            {{ $ends ? $ends->toDateString() : __('common.not_available') }}
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            @can('users.departments.manage')
                                                @unless($isPrimary)
                                                    <form method="POST" action="{{ route('admin.users.departments.primary', [$user, $department]) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="btn btn-sm btn-outline-primary" type="submit">
                                                            <i class="ti ti-star me-1"></i>{{ __('users.set_primary_department') }}
                                                        </button>
                                                    </form>
                                                @endunless
                                                <form method="POST" action="{{ route('admin.users.departments.destroy', [$user, $department]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">
                                                        <i class="ti ti-trash me-1"></i>{{ __('users.remove_department_assignment') }}
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <x-empty-state icon="ti-building-off" :title="__('users.no_department_assignments')" :message="__('users.assign_department')" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @can('users.departments.manage')
        <div class="card">
            <div class="card-header">
                <h5 class="fw-bold mb-0"><i class="ti ti-plus me-1"></i>{{ __('users.assign_department') }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.departments.store', $user) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('users.department') }} <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select" required>
                            <option value="">{{ __('users.select_department') }}</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }} - {{ $department->type?->translatedLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('users.role_context') }}</label>
                        <input type="text" name="role_context" class="form-control" value="{{ old('role_context') }}" maxlength="100">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('users.starts_at') }}</label>
                            <input type="date" name="starts_at" class="form-control" value="{{ old('starts_at') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('users.ends_at') }}</label>
                            <input type="date" name="ends_at" class="form-control" value="{{ old('ends_at') }}">
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="assignmentIsPrimary">
                        <label class="form-check-label" for="assignmentIsPrimary">{{ __('users.set_primary_department') }}</label>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="ti ti-device-floppy me-1"></i>{{ __('common.save') }}
                    </button>
                </form>
            </div>
        </div>
        @endcan
    </div>
</div>
@endsection
