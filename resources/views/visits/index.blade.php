@extends('layouts.app')
@section('title', 'Visits / OPD')

@section('content')
<x-page-header title="Visits / OPD" icon="ti-stethoscope">
    <x-slot:actions>
        @can('queue.view')
        <a href="{{ route('admin.queue.board') }}" class="btn btn-outline-info btn-md">
            <i class="ti ti-list-numbers me-1"></i>Queue Board
        </a>
        @endcan
        @can('visits.create')
        <a href="{{ route('admin.visits.create') }}" class="btn btn-primary btn-md">
            <i class="ti ti-plus me-1"></i>New Visit
        </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<!-- Visit Stats -->
<div class="row mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Range Total</p>
                <h4 class="fw-bold mb-0">{{ $stats['total'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-secondary border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Outpatients</p>
                <h4 class="fw-bold mb-0">{{ $stats['outpatient'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Inpatients</p>
                <h4 class="fw-bold mb-0">{{ $stats['inpatient'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-danger border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Emergency</p>
                <h4 class="fw-bold mb-0">{{ $stats['emergency'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Waiting / Consulting</p>
                <h4 class="fw-bold mb-0">{{ $stats['waiting_consulting'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Completed / Cancelled</p>
                <h4 class="fw-bold mb-0">{{ $stats['completed_cancelled'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.visits.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Visit #, patient name, phone..." value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Visit Type</label>
                    <select name="visit_type" class="form-select">
                        <option value="">All Types</option>
                        @foreach(\App\Enums\VisitType::cases() as $type)
                            <option value="{{ $type->value }}" {{ ($filters['visit_type'] ?? '') == $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Active Insurance</label>
                    <select name="insurance_provider_id" class="form-select">
                        <option value="">All Insurance</option>
                        @php
                            $legacyInsuranceOptions = collect($insuranceProviderOptions ?? [['value' => 'cash', 'label' => 'Cash & Carry']]);
                        @endphp
                        @foreach($legacyInsuranceOptions as $provider)
                            <option value="{{ $provider['value'] }}" {{ (string) ($filters['insurance_provider_id'] ?? '') === (string) $provider['value'] ? 'selected' : '' }}>
                                {{ $provider['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    @include('partials.date-range-filter', [
                        'id' => 'visitDateRangePicker',
                        'value' => $filters['date_range'] ?? '',
                    ])
                </div>
                <div class="col-md-auto">
                    <div class="d-flex gap-1">
                        <button aria-label="Filter" title="Filter" type="submit" class="btn btn-primary"><i class="ti ti-filter"></i> Filter</button>
                        <a aria-label="Close" title="Close" href="{{ route('admin.visits.index') }}" class="btn btn-outline-secondary"><i class="ti ti-x"></i></a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Visit List -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Visit #</th>
                        <th>Patient</th>
                        <th>Active Insurance</th>
                        <th>Age</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Doctor</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Duration</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visits as $visit)
                    <tr>
                        <td>
                            <a href="{{ route('admin.visits.show', $visit) }}" class="fw-medium text-primary">
                                {{ $visit->visit_number }}
                            </a>
                        </td>
                        <td>
                            <div>
                                <a href="{{ route('admin.patients.show', $visit->patient) }}" class="fw-medium">{{ $visit->patient->full_name }}</a>
                                <br><small class="text-muted">{{ $visit->patient->patient_number }}</small>
                            </div>
                        </td>
                        <td>
                            @php
                                $visitInsurance = $visit->visitInsurance;
                                $provider = $visitInsurance?->insuranceProvider;
                                $hasInsurance = $visitInsurance
                                    && $visitInsurance->is_active
                                    && ! $visitInsurance->is_expired
                                    && $provider
                                    && ! $provider->is_default;
                            @endphp
                            <span class="badge bg-{{ $hasInsurance ? ($provider->type?->color() ?? 'info') : 'secondary' }}">
                                <i class="ti ti-{{ $hasInsurance ? 'shield-check' : 'cash' }} me-1"></i>{{ $hasInsurance ? $provider->name : ($provider?->name ?? 'Cash & Carry') }}
                            </span>
                            @if($hasInsurance && $visitInsurance->insuranceTier?->name)
                                <small class="text-muted d-block mt-1">{{ $visitInsurance->insuranceTier->name }}</small>
                            @endif
                        </td>
                        <td>{{ $visit->patient_age ?? $visit->patient->age }}y</td>
                        <td>
                            <span class="badge bg-{{ $visit->visit_type === \App\Enums\VisitType::EMERGENCY ? 'danger' : ($visit->visit_type === \App\Enums\VisitType::INPATIENT ? 'info' : 'light text-dark') }}">
                                {{ $visit->visit_type->label() }}
                            </span>
                        </td>
                        <td>
                            <x-status-badge :status="$visit->priority" />
                            @if($visit->triage_score)
                                <x-status-badge :status="$visit->triage_score" class="ms-1" />
                            @endif
                        </td>
                        <td>{{ $visit->currentConsultationDoctor()?->full_name ?? '—' }}</td>
                        <td>
                            <x-status-badge :status="$visit->status" />
                        </td>
                        <td>{{ $visit->visit_date->format('d M Y') }}</td>
                        <td>{{ $visit->duration ?? '—' }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button aria-label="Actions" title="Actions" class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('admin.visits.show', $visit) }}"><i class="ti ti-eye me-2"></i>View Details</a></li>
                                    @can('visits.preview')
                                    <li><a class="dropdown-item" href="{{ route('admin.visits.preview', $visit) }}"><i class="ti ti-eye-search me-2"></i>Preview Visit</a></li>
                                    @endcan
                                    {{-- @can('visits.edit')
                                    <li><a class="dropdown-item" href="{{ route('admin.visits.edit', $visit) }}"><i class="ti ti-pencil me-2"></i>Edit Visit</a></li>
                                    @endcan --}}
                                    @if($visit->status->allowedTransitions())
                                    <li><hr class="dropdown-divider"></li>
                                    @foreach($visit->status->allowedTransitions() as $nextStatus)
                                        <li>
                                            <form method="POST" action="{{ route('admin.visits.transition', $visit) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti ti-arrow-right me-2"></i>{{ $nextStatus->label() }}
                                                </button>
                                            </form>
                                        </li>
                                    @endforeach
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11">
                            <x-empty-state icon="ti-calendar-off" title="No visits found" message="No visits match the selected filters." />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($visits->hasPages())
    <div class="card-footer">
        {{ $visits->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
    @include('partials.date-range-filter-scripts')
@endpush
