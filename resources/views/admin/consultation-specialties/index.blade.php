@extends('layouts.app')
@section('title', __('consultation_specialties.admin.title'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-layout-board me-2"></i>{{ __('consultation_specialties.admin.title') }}</h4>
    </div>
    @can('consultation-specialties.create')
        <a href="{{ route('admin.consultation-specialties.create') }}" class="btn btn-primary btn-sm"><i class="ti ti-plus me-1"></i>{{ __('consultation_specialties.admin.create') }}</a>
    @endcan
</div>

@include('admin.consultation-specialties.partials.flash')
@include('admin.consultation-specialties.partials.nav')

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label">{{ __('consultation_specialties.admin.search') }}</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('consultation_specialties.admin.search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('consultation_specialties.admin.status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('consultation_specialties.admin.all') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('consultation_specialties.admin.active') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('consultation_specialties.admin.inactive') }}</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-outline-primary"><i class="ti ti-search me-1"></i>{{ __('consultation_specialties.admin.filters') }}</button>
                <a href="{{ route('admin.consultation-specialties.index') }}" class="btn btn-outline-secondary">{{ __('consultation_specialties.admin.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('consultation_specialties.admin.name') }}</th>
                        <th>{{ __('consultation_specialties.admin.code') }}</th>
                        <th>{{ __('consultation_specialties.admin.department_type') }}</th>
                        <th class="text-center">{{ __('consultation_specialties.admin.sections') }}</th>
                        <th class="text-center">{{ __('consultation_specialties.admin.favorites') }}</th>
                        <th class="text-center">{{ __('consultation_specialties.admin.order_sets') }}</th>
                        <th class="text-center">{{ __('consultation_specialties.admin.mappings') }}</th>
                        <th class="text-center">{{ __('consultation_specialties.admin.status') }}</th>
                        <th class="text-end">{{ __('consultation_specialties.admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($profiles as $profile)
                        <tr>
                            <td class="fw-semibold">{{ $profile->name }}</td>
                            <td><code>{{ $profile->code }}</code></td>
                            <td>{{ $profile->department_type ?: __('consultation_specialties.admin.none') }}</td>
                            <td class="text-center"><span class="badge bg-soft-primary">{{ $profile->sections_count }}</span></td>
                            <td class="text-center"><span class="badge bg-soft-info">{{ $profile->favorites_count }}</span></td>
                            <td class="text-center"><span class="badge bg-soft-success">{{ $profile->order_sets_count }}</span></td>
                            <td class="text-center"><span class="badge bg-soft-secondary">{{ $profile->mappings_count }}</span></td>
                            <td class="text-center">
                                <span class="badge bg-{{ $profile->is_active ? 'success' : 'danger' }}">{{ $profile->is_active ? __('consultation_specialties.admin.active') : __('consultation_specialties.admin.inactive') }}</span>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown" aria-label="{{ __('consultation_specialties.admin.actions') }}"><i class="ti ti-dots-vertical"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('admin.consultation-specialties.show', $profile) }}"><i class="ti ti-eye me-1"></i>{{ __('consultation_specialties.admin.view') }}</a></li>
                                        @can('consultation-specialties.update')
                                            <li><a class="dropdown-item" href="{{ route('admin.consultation-specialties.edit', $profile) }}"><i class="ti ti-edit me-1"></i>{{ __('consultation_specialties.admin.edit') }}</a></li>
                                        @endcan
                                        @can('consultation-specialties.delete')
                                            <li>
                                                <form method="POST" action="{{ route('admin.consultation-specialties.destroy', $profile) }}">
                                                    @csrf @method('DELETE')
                                                    <button class="dropdown-item text-danger" @if($profile->isGeneral()) disabled @endif><i class="ti ti-trash me-1"></i>{{ $profile->is_active ? __('consultation_specialties.admin.deactivate') : __('consultation_specialties.admin.delete') }}</button>
                                                </form>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">{{ __('consultation_specialties.admin.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($profiles->hasPages())
        <div class="card-footer">{{ $profiles->links() }}</div>
    @endif
</div>
@endsection
