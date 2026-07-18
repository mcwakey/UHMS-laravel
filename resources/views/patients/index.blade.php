@extends('layouts.app')
@section('title', __('patients.title'))

@section('content')
<x-page-header :title="__('patients.title')" :description="__('patients.description')" icon="ti-users">
    <!-- <span class="badge badge-soft-primary fw-medium border py-1 px-2 border-primary fs-13 ms-1">{{ __('common.total') }}: {{ $patients->total() }}</span> -->
    <x-slot:actions>
        @can('patients.merge.view')
        <a href="{{ $workspaceRoutes->route('admin.patients.merge.index') }}" class="btn btn-outline-primary btn-md fs-13"><i class="ti ti-git-merge me-1"></i>{{ __('menu.folder_merge') }}</a>
        @endcan
        @can('patients.create')
        <a href="{{ $workspaceRoutes->route('admin.patients.create') }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-plus me-1"></i>{{ __('patients.new_patient') }}</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<!-- Filters -->
<x-filter-bar
    :action="$workspaceRoutes->route('admin.patients.index')"
    :reset-url="$workspaceRoutes->route('admin.patients.index')"
    ajax
    ajax-target="#patientsIndexResults"
>
    <input type="hidden" name="per_page" value="{{ $filters['per_page'] ?? $patients->perPage() }}" data-filter-per-page-input>

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
            <option value="archived" {{ ($filters['status'] ?? '') == 'archived' ? 'selected' : '' }}>{{ __('patients.archived') }}</option>
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
        <a aria-label="{{ __('common.reset') }}" title="{{ __('common.reset') }}" href="{{ $workspaceRoutes->route('admin.patients.index') }}" class="btn btn-outline-secondary btn-icon" data-filter-reset><i class="ti ti-x"></i></a>
    </x-slot:actions>
</x-filter-bar>

<div id="patientsIndexResults">
<x-data-table
    id="patientsDataTable"
    :paginator="$patients"
    show-summary
    show-per-page
    :current-per-page="$filters['per_page'] ?? $patients->perPage()"
    :per-page-options="[10, 25, 50, 100, 'all']"
>
    <x-slot:head>
        <tr>
            <th>{{ __('patients.patient_id') }}</th>
            <th>{{ __('patients.patient_name') }}</th>
            <th>{{ __('common.phone') }}</th>
            <!-- <th>{{ __('common.gender') }}</th> -->
            <th>{{ __('common.date_of_birth') }}</th>
            <th>{{ __('patients.city') }}</th>
            <th>{{ __('patients.insurance') }}</th>
            <th>{{ __('common.status') }}</th>
            <th>{{ __('patients.last_visit') }}</th>
            <th class="text-end">{{ __('common.actions') }}</th>
        </tr>
    </x-slot:head>

                    @php
                        // Outstanding-debt flags for the page (one query).
                        $pbEnabled = config('billing.previous_balance_policy.enabled', true);
                        $pbCanAmount = (bool) auth()->user()?->can('billing.previous_balance.amount.view');
                        $pbCanFlag = $pbCanAmount || (bool) auth()->user()?->can('billing.previous_balance.flag.view');
                        $pbMap = ($pbEnabled && $pbCanFlag)
                            ? app(\App\Services\Billing\PatientOutstandingBalanceService::class)
                                ->totalOutstandingMap($patients->pluck('id')->all())
                            : [];
                    @endphp
                    @forelse($patients as $patient)
                    <tr>
                        <td>
                            <a href="{{ $workspaceRoutes->route('admin.patients.show', $patient) }}" class="text-primary fw-medium">{{ $patient->patient_number }}</a>
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
                                    <a href="{{ $workspaceRoutes->route('admin.patients.show', $patient) }}" class="fw-medium text-dark">{{ $patient->full_name }}</a>
                                    <br><small class="text-muted">{{ $patient->gender?->label() }} · {{ $patient->age }} yrs</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <x-patient-protected-field field="phone" :value="$patient->phone" />
                            @if($patient->email)
                            <br><small class="text-muted"><x-patient-protected-field field="email" :value="$patient->email" /></small>
                            @endif
                        </td>
                        <!-- <td>{{ $patient->gender?->label() }}</td> -->
                        {{-- <td>{{ $patient->age }} yrs</td> --}}
                        <td>{{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->translatedFormat('d M Y') : '—' }}</td>
                        <td>{{ $patient->city ?? '—' }}</td>
                        <td>
                            @php
                                $displayInsurances = $patient->insurances
                                    ->filter(fn ($insurance) => $insurance->insuranceProvider && ! $insurance->insuranceProvider->is_default)
                                    ->sortByDesc('is_primary');
                            @endphp
                            @if($displayInsurances->isNotEmpty())
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($displayInsurances as $insurance)
                                        <span class="badge bg-{{ $insurance->is_valid ? ($insurance->insuranceProvider->type?->color() ?? 'secondary') : 'secondary' }}" title="{{ $insurance->insuranceTier?->name ?? '' }}">
                                            {{ $insurance->insuranceProvider->short_name }}{{ $insurance->is_primary ? ' *' : '' }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                                @if(($pbMap[$patient->id] ?? 0) > 0)
                                <br><x-billing.outstanding-badge :amount="$pbMap[$patient->id]" :show-amount="$pbCanAmount" class="mt-1" />
                                @endif
                        </td>
                        <td>
                            @if($patient->isMerged())
                                <span class="badge badge-soft-dark">{{ __('patients.merged') }}</span>
                            @elseif($patient->status === 'active')
                                <span class="badge badge-soft-success">{{ __('common.active') }}</span>
                            @elseif($patient->status === 'inactive')
                                <span class="badge badge-soft-warning">{{ __('common.inactive') }}</span>
                            @elseif($patient->status === 'archived')
                                <span class="badge badge-soft-secondary">{{ __('patients.archived') }}</span>
                            @else
                                <span class="badge badge-soft-dark">{{ __('patients.deceased') }}</span>
                            @endif
                        </td>
                        <td>{{ $patient->last_visit_date ? \Carbon\Carbon::parse($patient->last_visit_date)->translatedFormat('d M Y') : '—' }}</td>
                        <td class="text-end">

                            @php $lastVisitDate = $patient->last_visit_date; @endphp
                            <div class="d-flex align-items-center justify-content-end gap-1">
                                @if(!$patient->isMerged() && !$patient->is_deceased && $patient->status === 'active')
                                @can('visits.create')
                                @if($lastVisitDate && $lastVisitDate === today()->toDateString())
                                <button type="button" class="btn btn-sm btn-outline-secondary" title="{{ __('patients.merged_folder_no_visits') }}" disabled>
                                    <i class="ti ti-lock"></i>
                                </button>
                                @else
                                <a href="{{ $workspaceRoutes->route('admin.visits.create', ['patient_id' => $patient->id]) }}" class="btn btn-sm btn-outline-success" title="{{ __('patients.new_visit') }}">
                                    <i class="ti ti-stethoscope"></i>
                                </a>
                                @endif
                                @endcan
                                @endif
                                {{-- @can('appointments.create')
                                <a href="{{ $workspaceRoutes->route('admin.appointments.create', ['patient_id' => $patient->id]) }}" class="btn btn-sm btn-outline-primary" title="{{ __('appointments.schedule_appointment') }}">
                                    <i class="ti ti-calendar-plus"></i>
                                </a>
                                @endcan --}}
                                <div class="dropdown">
                                    <a aria-label="Actions" title="Actions" href="javascript:void(0);" class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ $workspaceRoutes->route('admin.patients.show', $patient) }}"><i class="ti ti-eye me-2"></i>{{ __('patients.view_profile') }}</a></li>
                                        @can('appointments.create')
                                        <li><a class="dropdown-item" href="{{ $workspaceRoutes->route('admin.appointments.create', ['patient_id' => $patient->id]) }}"><i class="ti ti-calendar-plus me-2"></i>{{ __('appointments.schedule_appointment') }}</a></li>
                                        @endcan
                                        @can('patients.edit')
                                        @if(!$patient->isMerged() && $patient->status !== 'deceased')
                                        <li><a class="dropdown-item" href="{{ $workspaceRoutes->route('admin.patients.edit', $patient) }}"><i class="ti ti-edit me-2"></i>{{ __('common.edit') }}</a></li>
                                        <li>
                                            <form method="POST" action="{{ $workspaceRoutes->route('admin.patients.toggle-status', $patient) }}" class="d-inline">
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
                            <br><a href="{{ $workspaceRoutes->route('admin.patients.create') }}">{{ __('patients.register_new_patient') }}</a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
</x-data-table>
</div>
@endsection

@push('scripts')
    @include('partials.date-range-filter-scripts')
@endpush
