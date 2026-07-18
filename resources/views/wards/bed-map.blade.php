@extends('layouts.app')
@section('title', __('wards.bed_map'))

@push('styles')
<style>
.bed-available {
    cursor: pointer;
    transition: transform .15s ease, box-shadow .15s ease;
}
.bed-available:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(39,174,96,.25);
}
.bed-tile {
    min-height: 10.5rem;
}
</style>
@endpush

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('admissions.capacity_board') }}</h4>
    </div>
    <div class="text-end d-flex gap-2">
        <a href="{{ $workspaceRoutes->route('admin.wards.index') }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-building-hospital me-1"></i>{{ __('wards.wards') }}</a>
        <a href="{{ $workspaceRoutes->route('admin.wards.beds') }}" class="btn btn-outline-info btn-md fs-13"><i class="ti ti-bed me-1"></i>{{ __('wards.manage_beds') }}</a>
    </div>
</div>

<div class="row g-2 mb-3">
    @foreach([
        'total' => ['label' => __('wards.total'), 'class' => 'secondary'],
        'available' => ['label' => __('wards.available'), 'class' => 'success'],
        'reserved' => ['label' => __('wards.reserved'), 'class' => 'info'],
        'occupied' => ['label' => __('wards.occupied'), 'class' => 'danger'],
        'cleaning' => ['label' => __('statuses.default.cleaning'), 'class' => 'cyan'],
        'maintenance' => ['label' => __('wards.maintenance'), 'class' => 'warning'],
        'blocked' => ['label' => __('statuses.default.blocked'), 'class' => 'dark'],
        'isolation' => ['label' => __('statuses.default.isolation'), 'class' => 'purple'],
    ] as $key => $meta)
        <div class="col-6 col-md-3 col-xl-2">
            <div class="border rounded bg-white p-2 h-100">
                <div class="text-muted small">{{ $meta['label'] }}</div>
                <div class="fs-4 fw-bold text-{{ $meta['class'] }}">{{ $capacity[$key] ?? 0 }}</div>
            </div>
        </div>
    @endforeach
    <div class="col-12 col-md-6 col-xl-3">
        <div class="border rounded bg-white p-2 h-100">
            <div class="d-flex justify-content-between">
                <span class="text-muted small">{{ __('admissions.occupancy') }}</span>
                <span class="fw-semibold">{{ $capacity['occupancy_percentage'] }}%</span>
            </div>
            <div class="progress mt-2" style="height: 8px;">
                <div class="progress-bar bg-danger" style="width: {{ min(100, $capacity['occupancy_percentage']) }}%"></div>
            </div>
            <div class="small text-muted mt-2">
                {{ __('admissions.active_reservations') }}: {{ $capacity['active_reservations'] }}
                · {{ __('admissions.expiring_reservations') }}: {{ $capacity['expiring_reservations'] }}
                · {{ __('admissions.transfers_today') }}: {{ $capacity['transfers_today'] }}
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ $workspaceRoutes->route('admin.wards.bed-map') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">{{ __('wards.ward') }}</label>
                <select name="ward_id" class="form-select">
                    <option value="">{{ __('wards.all_wards') }}</option>
                    @foreach($allWards as $ward)
                        <option value="{{ $ward->id }}" @selected(($filters['ward_id'] ?? '') == $ward->id)>{{ $ward->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">{{ __('common.status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('common.all_statuses') }}</option>
                    @foreach(\App\Enums\BedStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">{{ __('wards.bed_type') }}</label>
                <select name="bed_type" class="form-select">
                    <option value="">{{ __('wards.all_types') }}</option>
                    @foreach(\App\Enums\BedType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(($filters['bed_type'] ?? '') === $type->value)>{{ $type->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">{{ __('common.search') }}</label>
                <input name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('admissions.search_beds_ph') }}">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-fill"><i class="ti ti-filter"></i></button>
                <a href="{{ $workspaceRoutes->route('admin.wards.bed-map') }}" class="btn btn-outline-secondary"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex flex-wrap gap-3 mb-3 align-items-center">
    <span><i class="ti ti-square-filled text-success"></i> {{ __('wards.available') }}</span>
    <span><i class="ti ti-square-filled text-danger"></i> {{ __('wards.occupied') }}</span>
    <span><i class="ti ti-square-filled text-info"></i> {{ __('wards.reserved') }}</span>
    <span><i class="ti ti-square-filled text-cyan"></i> {{ __('statuses.default.cleaning') }}</span>
    <span><i class="ti ti-square-filled text-warning"></i> {{ __('wards.maintenance') }}</span>
    <span><i class="ti ti-square-filled text-dark"></i> {{ __('statuses.default.blocked') }}</span>
    <span><i class="ti ti-square-filled text-purple"></i> {{ __('statuses.default.isolation') }}</span>
</div>

@forelse($wards as $ward)
@include("wards.partials._ward-census-card", ["ward" => $ward])
@empty
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="ti ti-building-hospital fs-1 d-block mb-2"></i>
        {{ __('wards.no_active_wards_map') }}
    </div>
</div>
@endforelse
@endsection
