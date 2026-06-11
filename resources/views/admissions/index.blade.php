@extends('layouts.app')
@section('title', __('admissions.title'))

@section('content')
<x-page-header :title="__('admissions.title')" icon="ti-bed">
    <span class="badge badge-soft-primary fw-medium border py-1 px-2 border-primary fs-13 ms-1">{{ __('admissions.total') }}: {{ $admissions->total() }}</span>
    <x-slot:actions>
        @can('ward.admit')
        <a href="{{ route('admin.admissions.create') }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-plus me-1"></i>{{ __('admissions.new_admission') }}</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<!-- Stats Cards -->
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary bg-opacity-10 rounded me-3">
                        <i class="ti ti-bed fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $stats['total_admitted'] }}</h4>
                        <small class="text-muted">{{ __('admissions.currently_admitted') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-success bg-opacity-10 rounded me-3">
                        <i class="ti ti-login fs-4 text-success"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $stats['admitted_today'] }}</h4>
                        <small class="text-muted">{{ __('admissions.admitted_today') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-warning bg-opacity-10 rounded me-3">
                        <i class="ti ti-logout fs-4 text-warning"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $stats['discharged_today'] }}</h4>
                        <small class="text-muted">{{ __('admissions.discharged_today') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.admissions.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="{{ __('admissions.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">{{ __('admissions.all_status') }}</option>
                    @foreach(\App\Enums\AdmissionStatus::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="ward_id" class="form-select">
                    <option value="">{{ __('admissions.all_wards') }}</option>
                    @foreach($wards as $ward)
                        <option value="{{ $ward->id }}" {{ request('ward_id') == $ward->id ? 'selected' : '' }}>{{ $ward->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-outline-primary btn-md"><i class="ti ti-filter me-1"></i>{{ __('common.filter') }}</button>
                <a href="{{ route('admin.admissions.index') }}" class="btn btn-outline-secondary btn-md ms-1"><i class="ti ti-x me-1"></i>{{ __('common.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<!-- Admissions Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>{{ __('admissions.admission_no') }}</th>
                        <th>{{ __('admissions.patient') }}</th>
                        <th>{{ __('admissions.ward_bed') }}</th>
                        <th>{{ __('admissions.admitted_on') }}</th>
                        <th>{{ __('admissions.days') }}</th>
                        <th>{{ __('admissions.admitted_by') }}</th>
                        <th>{{ __('admissions.status') }}</th>
                        <th class="text-end">{{ __('admissions.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($admissions as $admission)
                    <tr>
                        <td>
                            <a href="{{ route('admin.admissions.show', $admission) }}" class="fw-medium text-decoration-none">
                                {{ $admission->admission_number }}
                            </a>
                        </td>
                        <td>
                            <div>
                                <a href="{{ route('admin.patients.show', $admission->patient) }}" class="text-decoration-none">
                                    {{ $admission->patient->full_name }}
                                </a>
                            </div>
                            <small class="text-muted">{{ $admission->patient->patient_number }}</small>
                        </td>
                        <td>
                            <div>{{ $admission->bed->ward->name }}</div>
                            <small class="text-muted">{{ __('admissions.bed') }}: {{ $admission->bed->bed_number }}</small>
                        </td>
                        <td>{{ $admission->admission_date->format('d M Y, H:i') }}</td>
                        <td><span class="badge badge-soft-secondary">{{ $admission->length_of_stay }} {{ __('admissions.days') }}</span></td>
                        <td>{{ $admission->admittedBy->name ?? '—' }}</td>
                        <td><span class="badge badge-soft-{{ $admission->status->color() }}">{{ $admission->status->label() }}</span></td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button aria-label="{{ __('admissions.actions') }}" title="{{ __('admissions.actions') }}" class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.admissions.show', $admission) }}">
                                            <i class="ti ti-eye me-1"></i>{{ __('admissions.view_details') }}
                                        </a>
                                    </li>
                                    @if($admission->status->value === 'admitted')
                                    @can('ward.discharge')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.admissions.discharge', $admission) }}">
                                            <i class="ti ti-logout me-1"></i>{{ __('admissions.discharge') }}
                                        </a>
                                    </li>
                                    @endcan
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="ti ti-bed-off fs-1 d-block mb-2"></i>
                            {{ __('admissions.no_admissions') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($admissions->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $admissions->withQueryString()->links() }}
</div>
@endif
@endsection
