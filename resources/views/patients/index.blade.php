@extends('layouts.app')
@section('title', __('patients.title'))

@section('content')
<x-page-header :title="__('patients.title')" icon="ti-users">
    <!-- <span class="badge badge-soft-primary fw-medium border py-1 px-2 border-primary fs-13 ms-1">{{ __('common.total') }}: {{ $patients->total() }}</span> -->
    <x-slot:actions>
        @can('patients.merge.view')
        <a href="{{ route('admin.patients.merge.index') }}" class="btn btn-outline-primary btn-md fs-13"><i class="ti ti-git-merge me-1"></i>{{ __('menu.folder_merge') }}</a>
        @endcan
        @can('patients.create')
        <a href="{{ route('admin.patients.create') }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-plus me-1"></i>{{ __('patients.new_patient') }}</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<!-- Filters -->
<x-filter-bar
    :action="route('admin.patients.index')"
    :reset-url="route('admin.patients.index')"
>
    <div class="col-md-3">
        <label class="form-label small">{{ __('common.search') }}</label>
        <input type="text" name="search" class="form-control" placeholder="{{ __('patients.search_placeholder') }}" value="{{ $filters['search'] ?? '' }}">
    </div>
    <div class="col-md-2">
        <label class="form-label small">{{ __('patients.insurance_provider') }}</label>
        <select name="insurance_provider_id" class="form-select">
            <option value="">{{ __('patients.all_insurances') }}</option>
            @foreach($insuranceProviders as $provider)
                <option value="{{ $provider->id }}" {{ ($filters['insurance_provider_id'] ?? '') == $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small">{{ __('common.status') }}</label>
        <select name="status" class="form-select">
            <option value="">{{ __('patients.all_status') }}</option>
            <option value="active" {{ ($filters['status'] ?? '') == 'active' ? 'selected' : '' }}>{{ __('common.active') }}</option>
            <option value="inactive" {{ ($filters['status'] ?? '') == 'inactive' ? 'selected' : '' }}>{{ __('common.inactive') }}</option>
            <option value="deceased" {{ ($filters['status'] ?? '') == 'deceased' ? 'selected' : '' }}>{{ __('patients.deceased') }}</option>
        </select>
    </div>
    <div class="col-md-3">
        @include('partials.date-range-filter', [
            'id' => 'patientLastVisitDateRangePicker',
            'value' => $filters['date_range'] ?? '',
            'label' => __('patients.last_visit_range'),
            'labelClass' => 'small',
            'submitOnApply' => true,
        ])
    </div>
    <x-slot:actions>
        <!-- <button type="submit" class="btn btn-outline-primary btn-md"><i class="ti ti-filter me-1"></i>{{ __('common.filter') }}</button> -->
        <a aria-label="{{ __('common.reset') }}" title="{{ __('common.reset') }}" href="{{ route('admin.patients.index') }}" class="btn btn-outline-secondary btn-icon"><i class="ti ti-x"></i></a>
    </x-slot:actions>
</x-filter-bar>

<!-- Patients Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>{{ __('patients.patient_id') }}</th>
                        <th>{{ __('patients.patient_name') }}</th>
                        <th>{{ __('common.phone') }}</th>
                        <th>{{ __('common.gender') }}</th>
                        <th>{{ __('common.date_of_birth') }}</th>
                        <th>{{ __('patients.city') }}</th>
                        <th>{{ __('patients.insurance') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('patients.last_visit') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                    <tr>
                        <td>
                            <a href="{{ route('admin.patients.show', $patient) }}" class="text-primary fw-medium">{{ $patient->patient_number }}</a>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-md rounded-circle bg-light text-dark me-2 flex-shrink-0">
                                    @if($patient->avatar)
                                        <img src="{{ Storage::url($patient->avatar) }}" alt="{{ $patient->full_name }}" class="rounded-circle">
                                    @else
                                        {{ strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)) }}
                                    @endif
                                </span>
                                <div>
                                    <a href="{{ route('admin.patients.show', $patient) }}" class="fw-medium text-dark">{{ $patient->full_name }}</a>
                                    @if($patient->email)
                                    <br><small class="text-muted">{{ $patient->email }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $patient->phone }}</td>
                        <td>{{ $patient->gender?->label() }}</td>
                        {{-- <td>{{ $patient->age }} yrs</td> --}}
                        <td>{{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->translatedFormat('d M Y') : '—' }}</td>
                        <td>{{ $patient->city ?? '—' }}</td>
                        <td>
                            @if($patient->primaryInsurance?->insuranceProvider)
                                <span class="badge bg-{{ $patient->primaryInsurance->insuranceProvider->type?->color() ?? 'secondary' }}">
                                    {{ $patient->primaryInsurance->insuranceProvider->name }}
                                </span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            @if($patient->isMerged())
                                <span class="badge badge-soft-dark">{{ __('patients.merged') }}</span>
                            @elseif($patient->status === 'active')
                                <span class="badge badge-soft-success">{{ __('common.active') }}</span>
                            @elseif($patient->status === 'inactive')
                                <span class="badge badge-soft-warning">{{ __('common.inactive') }}</span>
                            @else
                                <span class="badge badge-soft-dark">{{ __('patients.deceased') }}</span>
                            @endif
                        </td>
                        <td>{{ $patient->last_visit_date ? \Carbon\Carbon::parse($patient->last_visit_date)->translatedFormat('d M Y') : '—' }}</td>
                        <td class="text-end">
                            <div class="d-flex align-items-center justify-content-end gap-1">
                                @can('visits.create')
                                @if($patient->isMerged() || $patient->status === 'deceased')
                                <button type="button" class="btn btn-sm btn-outline-secondary" title="{{ __('patients.merged_folder_no_visits') }}" disabled>
                                    <i class="ti ti-lock"></i>
                                </button>
                                @else
                                <a href="{{ route('admin.visits.create', ['patient_id' => $patient->id]) }}" class="btn btn-sm btn-outline-success" title="{{ __('patients.new_visit') }}">
                                    <i class="ti ti-stethoscope"></i>
                                </a>
                                @endif
                                @endcan
                                <div class="dropdown">
                                    <a aria-label="Actions" title="Actions" href="javascript:void(0);" class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('admin.patients.show', $patient) }}"><i class="ti ti-eye me-2"></i>{{ __('patients.view_profile') }}</a></li>
                                        @can('patients.edit')
                                        @if(!$patient->isMerged() && $patient->status !== 'deceased')
                                        <li><a class="dropdown-item" href="{{ route('admin.patients.edit', $patient) }}"><i class="ti ti-edit me-2"></i>{{ __('common.edit') }}</a></li>
                                        <li>
                                            <form method="POST" action="{{ route('admin.patients.toggle-status', $patient) }}" class="d-inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti ti-toggle-{{ $patient->status === 'active' ? 'right' : 'left' }} me-2"></i>
                                                    {{ $patient->status === 'active' ? __('patients.deactivate') : __('patients.activate') }}
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                        @endcan
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">
                            <i class="ti ti-user-off fs-1 d-block mb-2"></i>
                            {{ __('patients.no_patients_found') }}
                            @can('patients.create')
                            <br><a href="{{ route('admin.patients.create') }}">{{ __('patients.register_new_patient') }}</a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
@if($patients->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $patients->withQueryString()->links() }}
</div>
@endif
@endsection

@push('scripts')
    @include('partials.date-range-filter-scripts')
@endpush
