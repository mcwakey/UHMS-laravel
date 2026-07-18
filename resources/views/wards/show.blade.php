@extends('layouts.app')
@section('title', __('wards.ward_overview', ['name' => $ward->name]))

@section('content')
<x-page-header :title="__('wards.ward_overview', ['name' => $ward->name])" icon="ti-building-hospital">
    <span class="badge badge-soft-secondary fw-medium border py-1 px-2 fs-13 ms-1">{{ $ward->code }}</span>
    @if($ward->department)
        <span class="badge badge-soft-info fw-medium border py-1 px-2 fs-13 ms-1">{{ $ward->department->name }}</span>
    @endif
    @unless($ward->is_active)
        <span class="badge bg-dark fs-13 ms-1">{{ __('wards.inactive') }}</span>
    @endunless
    <x-slot:actions>
        <a href="{{ $workspaceRoutes->route('admin.wards.index') }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('wards.back_to_wards') }}</a>
        <a href="{{ $workspaceRoutes->route('admin.wards.bed-map', ['ward_id' => $ward->id]) }}" class="btn btn-outline-success btn-md fs-13"><i class="ti ti-map me-1"></i>{{ __('wards.bed_map') }}</a>
    </x-slot:actions>
</x-page-header>

{{-- Capacity summary --}}
@php($occupancyPct = $capacity['total'] > 0 ? (int) round(($capacity['occupied'] / $capacity['total']) * 100) : 0)
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap gap-2">
                <span class="badge badge-soft-secondary fs-13">{{ __('wards.total') }}: {{ $capacity['total'] }}</span>
                <span class="badge badge-soft-success fs-13">{{ __('wards.available') }}: {{ $capacity['available'] }}</span>
                <span class="badge badge-soft-danger fs-13">{{ __('wards.occupied') }}: {{ $capacity['occupied'] }}</span>
                <span class="badge badge-soft-info fs-13">{{ __('wards.reserved') }}: {{ $capacity['reserved'] }}</span>
                @if(($capacity['maintenance'] + $capacity['blocked']) > 0)
                    <span class="badge badge-soft-warning fs-13">{{ __('wards.out_of_service') }}: {{ $capacity['maintenance'] + $capacity['blocked'] }}</span>
                @endif
                @if($ward->floor)
                    <span class="badge bg-light text-dark border fs-13">{{ __('wards.floor') }}: {{ $ward->floor }}</span>
                @endif
            </div>
            <div class="flex-fill" style="max-width: 320px; min-width: 200px;">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>{{ __('wards.occupancy') }}</span><span class="fw-semibold">{{ $occupancyPct }}%</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-{{ $occupancyPct >= 90 ? 'danger' : ($occupancyPct >= 70 ? 'warning' : 'success') }}" role="progressbar" style="width: {{ $occupancyPct }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Bed census (shared with the bed map) --}}
@if($census)
    @include('wards.partials._ward-census-card', ['ward' => $census])
@else
    <div class="card mb-3"><div class="card-body text-center py-5 text-muted">
        <i class="ti ti-bed-off fs-1 d-block mb-2"></i>{{ __('wards.no_beds_configured') }}
    </div></div>
@endif

{{-- Admitted patients --}}
<div class="card">
    <div class="card-header"><h5 class="mb-0">{{ __('wards.admitted_patients') }} <span class="badge badge-soft-danger ms-1">{{ $admissions->count() }}</span></h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr>
                    <th>{{ __('wards.current_patient') }}</th>
                    <th>{{ __('wards.bed_number') }}</th>
                    <th>{{ __('admissions.admitted_on') }}</th>
                    <th>{{ __('admissions.length_of_stay_label') }}</th>
                    <th></th>
                </tr></thead>
                <tbody>
                @forelse($admissions as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['admission']->patient?->full_name }}</td>
                        <td>{{ $row['bed']->bed_number }}</td>
                        <td>{{ $row['admission']->admission_date?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $row['admission']->length_of_stay }}d</td>
                        <td class="text-end pe-3">
                            <a href="{{ $workspaceRoutes->route('admin.admissions.show', $row['admission']) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('wards.no_admitted_patients') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
