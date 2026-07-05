@extends('layouts.app')
@section('title', $profile->exists ? __('consultation_specialties.admin.edit') : __('consultation_specialties.admin.create'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-layout-board me-2"></i>{{ $profile->exists ? __('consultation_specialties.admin.edit') : __('consultation_specialties.admin.create') }}</h4>
    </div>
    <a href="{{ route('admin.consultation-specialties.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('consultation_specialties.admin.back') }}</a>
</div>

@include('admin.consultation-specialties.partials.flash')
@include('admin.consultation-specialties.partials.nav')

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $profile->exists ? route('admin.consultation-specialties.update', $profile) : route('admin.consultation-specialties.store') }}" class="row g-3">
            @csrf
            @if($profile->exists) @method('PATCH') @endif
            <div class="col-md-4">
                <label class="form-label">{{ __('consultation_specialties.admin.code') }}</label>
                <input name="code" class="form-control" value="{{ old('code', $profile->code) }}" required @if($profile->isGeneral()) readonly @endif>
            </div>
            <div class="col-md-8">
                <label class="form-label">{{ __('consultation_specialties.admin.name') }}</label>
                <input name="name" class="form-control" value="{{ old('name', $profile->name) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('consultation_specialties.admin.department_type') }}</label>
                <select name="department_type" class="form-select">
                    <option value="">{{ __('consultation_specialties.admin.none') }}</option>
                    @foreach($departmentTypes as $type)
                        <option value="{{ $type->value }}" @selected(old('department_type', $profile->department_type) === $type->value)>{{ method_exists($type, 'label') ? $type->label() : $type->value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('consultation_specialties.admin.icon') }}</label>
                <input name="icon" class="form-control" value="{{ old('icon', $profile->icon) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('consultation_specialties.admin.color') }}</label>
                <input name="color" class="form-control" value="{{ old('color', $profile->color) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('consultation_specialties.admin.sort_order') }}</label>
                <input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', $profile->sort_order ?? 0) }}">
            </div>
            <div class="col-12">
                <label class="form-label">{{ __('consultation_specialties.admin.description') }}</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $profile->description) }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">{{ __('consultation_specialties.admin.metadata') }}</label>
                <textarea name="metadata_json" class="form-control font-monospace" rows="4">{{ old('metadata_json', $profile->metadata ? json_encode($profile->metadata, JSON_PRETTY_PRINT) : '') }}</textarea>
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $profile->is_active ?? true)) @if($profile->isGeneral()) disabled @endif>
                    <label class="form-check-label">{{ __('consultation_specialties.admin.active') }}</label>
                </div>
            </div>
            @if($profile->isGeneral())
                <div class="col-12"><div class="alert alert-warning mb-0">{{ __('consultation_specialties.admin.general_profile_locked') }}</div></div>
            @endif
            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="{{ $profile->exists ? route('admin.consultation-specialties.show', $profile) : route('admin.consultation-specialties.index') }}" class="btn btn-outline-secondary">{{ __('consultation_specialties.admin.cancel') }}</a>
                <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('consultation_specialties.admin.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
