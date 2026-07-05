@extends('layouts.app')
@section('title', $orderSet->exists ? __('consultation_specialties.admin.edit') : __('consultation_specialties.admin.create'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1"><h4 class="fw-bold mb-0"><i class="ti ti-packages me-2"></i>{{ __('consultation_specialties.admin.order_sets') }} · {{ $profile->name }}</h4></div>
    <a href="{{ route('admin.consultation-specialties.order-sets.index', $profile) }}" class="btn btn-outline-secondary btn-sm">{{ __('consultation_specialties.admin.back') }}</a>
</div>
@include('admin.consultation-specialties.partials.flash')
@include('admin.consultation-specialties.partials.nav')

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $orderSet->exists ? route('admin.consultation-specialties.order-sets.update', [$profile, $orderSet]) : route('admin.consultation-specialties.order-sets.store', $profile) }}" class="row g-3">
            @csrf
            @if($orderSet->exists) @method('PATCH') @endif
            <div class="col-md-4"><label class="form-label">{{ __('consultation_specialties.admin.code') }}</label><input name="code" class="form-control" value="{{ old('code', $orderSet->code) }}" required></div>
            <div class="col-md-8"><label class="form-label">{{ __('consultation_specialties.admin.name') }}</label><input name="name" class="form-control" value="{{ old('name', $orderSet->name) }}" required></div>
            <div class="col-md-3"><label class="form-label">{{ __('consultation_specialties.admin.category') }}</label><input name="category" class="form-control" value="{{ old('category', $orderSet->category) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('consultation_specialties.admin.icon') }}</label><input name="icon" class="form-control" value="{{ old('icon', $orderSet->icon) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('consultation_specialties.admin.color') }}</label><input name="color" class="form-control" value="{{ old('color', $orderSet->color) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('consultation_specialties.admin.sort_order') }}</label><input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', $orderSet->sort_order ?? 0) }}"></div>
            <div class="col-12"><label class="form-label">{{ __('consultation_specialties.admin.description') }}</label><textarea name="description" class="form-control" rows="3">{{ old('description', $orderSet->description) }}</textarea></div>
            <div class="col-12"><label class="form-label">{{ __('consultation_specialties.admin.metadata') }}</label><textarea name="metadata_json" class="form-control font-monospace" rows="4">{{ old('metadata_json', $orderSet->metadata ? json_encode($orderSet->metadata, JSON_PRETTY_PRINT) : '') }}</textarea></div>
            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $orderSet->is_active ?? true))><label class="form-check-label">{{ __('consultation_specialties.admin.active') }}</label></div></div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="{{ route('admin.consultation-specialties.order-sets.index', $profile) }}" class="btn btn-outline-secondary">{{ __('consultation_specialties.admin.cancel') }}</a>
                <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('consultation_specialties.admin.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
