@extends('layouts.app')
@section('title', 'Bed Map')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Bed Availability Map</h4>
    </div>
    <div class="text-end d-flex gap-2">
        <a href="{{ route('admin.wards.index') }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-building-hospital me-1"></i>Wards</a>
        <a href="{{ route('admin.wards.beds') }}" class="btn btn-outline-info btn-md fs-13"><i class="ti ti-bed me-1"></i>Manage Beds</a>
    </div>
</div>

<!-- Legend -->
<div class="d-flex gap-3 mb-3">
    <span><i class="ti ti-square-filled text-success"></i> Available</span>
    <span><i class="ti ti-square-filled text-danger"></i> Occupied</span>
    <span><i class="ti ti-square-filled text-warning"></i> Maintenance</span>
    <span><i class="ti ti-square-filled text-info"></i> Reserved</span>
</div>

@forelse($wards as $ward)
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">{{ $ward->name }} <span class="text-muted fs-13">({{ $ward->code }})</span></h5>
            @if($ward->floor)
                <small class="text-muted">Floor: {{ $ward->floor }}</small>
            @endif
        </div>
        <div class="text-end">
            <span class="badge badge-soft-success me-1">{{ $ward->available_beds_count }} Available</span>
            <span class="badge badge-soft-danger me-1">{{ $ward->occupied_beds_count }} Occupied</span>
            <span class="badge badge-soft-secondary">{{ $ward->beds_count }} Total</span>
        </div>
    </div>
    <div class="card-body">
        @if($ward->beds->count() > 0)
        <div class="row g-2">
            @foreach($ward->beds as $bed)
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="border rounded p-2 text-center {{ $bed->status->value === 'occupied' ? 'border-danger bg-danger bg-opacity-10' : ($bed->status->value === 'available' ? 'border-success bg-success bg-opacity-10' : ($bed->status->value === 'maintenance' ? 'border-warning bg-warning bg-opacity-10' : 'border-info bg-info bg-opacity-10')) }}">
                    <i class="ti ti-bed fs-4 d-block mb-1 text-{{ $bed->status->color() }}"></i>
                    <div class="fw-medium fs-13">{{ $bed->bed_number }}</div>
                    <small class="text-muted">{{ $bed->bed_type->label() }}</small>
                    @if($bed->currentAdmission)
                        <div class="mt-1">
                            <a href="{{ route('admin.admissions.show', $bed->currentAdmission) }}" class="text-decoration-none fs-12">
                                {{ Str::limit($bed->currentAdmission->patient->full_name, 15) }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-muted text-center mb-0">No beds configured for this ward.</p>
        @endif
    </div>
</div>
@empty
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="ti ti-building-hospital fs-1 d-block mb-2"></i>
        No active wards found. Create wards and add beds to see the bed map.
    </div>
</div>
@endforelse
@endsection
