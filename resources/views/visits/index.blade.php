@extends('layouts.app')
@section('title', __('visits.title'))

@section('content')
<x-page-header :title="__('visits.title')" :description="__('visits.description')" icon="ti-calendar-check">
    <x-slot:actions>
        @can('queue.view')
        <a href="{{ route('admin.queue.board') }}" class="btn btn-outline-info btn-md">
            <i class="ti ti-list-numbers me-1"></i>{{ __('visits.queue_board') }}
        </a>
        @endcan
        @can('visits.create')
        <a href="{{ route('admin.visits.create') }}" class="btn btn-primary btn-md">
            <i class="ti ti-plus me-1"></i>{{ __('visits.new_visit') }}
        </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<!-- Visit Stats -->
<div class="row mb-1">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">{{ __('visits.range_total') }}</p>
                <h4 class="fw-bold mb-0">{{ $stats['total'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-secondary border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">{{ __('visits.outpatients') }}</p>
                <h4 class="fw-bold mb-0">{{ $stats['outpatient'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">{{ __('visits.inpatients') }}</p>
                <h4 class="fw-bold mb-0">{{ $stats['inpatient'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-danger border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">{{ __('visits.emergency') }}</p>
                <h4 class="fw-bold mb-0">{{ $stats['emergency'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">{{ __('visits.waiting_consulting') }}</p>
                <h4 class="fw-bold mb-0">{{ $stats['waiting_consulting'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">{{ __('visits.completed_cancelled') }}</p>
                <h4 class="fw-bold mb-0">{{ $stats['completed_cancelled'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<x-filter-bar
    :action="route('admin.visits.index')"
    :reset-url="route('admin.visits.index')"
    class="mb-2"
    ajax
    ajax-target="#visitsIndexResults"
>
    <input type="hidden" name="per_page" value="{{ $filters['per_page'] ?? $visits->perPage() }}" data-filter-per-page-input>

    <div class="col-md-3">
        <label class="form-label">{{ __('common.search') }}</label>
        <input type="text" name="search" class="form-control" placeholder="{{ __('visits.search_placeholder') }}" value="{{ $filters['search'] ?? '' }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('visits.visit_type') }}</label>
        <select name="visit_type" class="form-select">
            <option value="">{{ __('visits.all_types') }}</option>
            @foreach(\App\Enums\VisitType::cases() as $type)
                <option value="{{ $type->value }}" {{ ($filters['visit_type'] ?? '') == $type->value ? 'selected' : '' }}>{{ $type->translatedLabel() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('visits.active_insurance') }}</label>
        <select name="insurance_provider_id" class="form-select">
            <option value="">{{ __('visits.all_insurance') }}</option>
            @php
                $legacyInsuranceOptions = collect($insuranceProviderOptions ?? [['value' => 'cash', 'label' => __('visits.cash_and_carry')]]);
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
            'submitOnApply' => true,
        ])
    </div>
    <x-slot:actions>
        <!-- <button aria-label="{{ __('common.filter') }}" title="{{ __('common.filter') }}" type="submit" class="btn btn-primary"><i class="ti ti-filter"></i>{{ __('common.filter') }}</button> -->
        <a aria-label="{{ __('common.reset') }}" title="{{ __('common.reset') }}" href="{{ route('admin.visits.index') }}" class="btn btn-outline-secondary btn-icon" data-filter-reset><i class="ti ti-x"></i></a>
    </x-slot:actions>
</x-filter-bar>

<!-- Visit List -->
<div id="visitsIndexResults">
<x-data-table
    id="visitsDataTable"
    :paginator="$visits"
    show-summary
    show-per-page
    :current-per-page="$filters['per_page'] ?? $visits->perPage()"
    :per-page-options="[10, 15, 25, 50, 100]"
>
    <x-slot:head>
        <tr>
            <th>{{ __('common.visit_number_short') }}</th>
            <th>{{ __('common.patient') }}</th>
            <th>{{ __('visits.active_insurance') }}</th>
            <!-- <th>{{ __('common.age') }}</th> -->
            <th>{{ __('common.type') }}</th>
            <th>{{ __('common.priority') }}</th>
            <!-- <th>{{ __('common.doctor') }}</th> -->
            <th>{{ __('common.status') }}</th>
            <th>{{ __('common.date') }}</th>
            <th>{{ __('visits.duration') }}</th>
            <th class="text-end">{{ __('common.actions') }}</th>
        </tr>
    </x-slot:head>

                    @forelse($visits as $visit)
                    <tr>
                        <td>
                            <a href="{{ route('admin.visits.show', $visit) }}" class="fw-medium text-primary">
                                {{ $visit->visit_number }}
                            </a>
                            <div class="small text-muted">{{ $visit->patient->patient_number }}</div>
                        </td>
                        <td>
                            <div>
                                <a href="{{ route('admin.patients.show', $visit->patient) }}" class="fw-medium">{{ $visit->patient->full_name }}</a>
                                <div class="small text-muted">{{ $visit->patient->gender }} · {{ $visit->patient->age }}y</div>
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
                                <i class="ti ti-{{ $hasInsurance ? 'shield-check' : 'cash' }} me-1"></i>{{ $hasInsurance ? $provider->short_name  . " - " . $visitInsurance->insuranceTier->name : ($provider?->short_name ?? __('visits.cash_and_carry')) }}
                            </span>
                            <!-- @if($hasInsurance && $visitInsurance->insuranceTier?->name)
                                <small class="text-muted d-block mt-1">{{ $visitInsurance->insuranceTier->name }}</small>
                            @endif -->
                        </td>
                        <!-- <td>{{ $visit->patient_age ?? $visit->patient->age }}y</td> -->
                        <td>
                            <span class="badge bg-{{ $visit->visit_type === \App\Enums\VisitType::EMERGENCY ? 'danger' : ($visit->visit_type === \App\Enums\VisitType::INPATIENT ? 'info' : 'light text-dark') }}">
                                {{ $visit->visit_type->translatedLabel() }}
                            </span>
                        </td>
                        <td>
                            <x-status-badge :status="$visit->priority" />
                            @if($visit->triage_score)
                                <x-status-badge :status="$visit->triage_score" class="ms-1" />
                            @endif
                        </td>
                        <!-- <td>{{ $visit->currentConsultationDoctor()?->full_name ?? '—' }}</td> -->
                        <td>
                            <x-status-badge :status="$visit->status" />
                            @if($visit->visit_source || $visit->attendance_class)
                                <div class="mt-1 d-flex flex-wrap gap-1">
                                    @if($visit->visit_source)
                                        <span class="badge bg-light text-dark border" title="{{ __('visit_flow.ui.source_label') }}">
                                            <i class="ti ti-arrow-guide me-1"></i>{{ __('visit_flow.source.'.$visit->visit_source) }}
                                        </span>
                                    @endif
                                    @if($visit->attendance_class)
                                        <span class="badge bg-light text-dark border" title="{{ __('visit_flow.ui.attendance_label') }}">
                                            <i class="ti ti-user-check me-1"></i>{{ __('visit_flow.attendance_class.'.$visit->attendance_class) }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>{{ $visit->visit_date->translatedFormat('d M Y') }}</td>
                        <td>{{ $visit->duration ?? '—' }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button aria-label="Actions" title="Actions" class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('admin.visits.show', $visit) }}"><i class="ti ti-eye me-2"></i>{{ __('visits.view_details') }}</a></li>
                                    @can('visits.preview')
                                    <li><a class="dropdown-item" href="{{ route('admin.visits.preview', $visit) }}"><i class="ti ti-eye-search me-2"></i>{{ __('visits.preview_visit') }}</a></li>
                                    @endcan
                                    {{-- @can('visits.edit')
                                    <li><a class="dropdown-item" href="{{ route('admin.visits.edit', $visit) }}"><i class="ti ti-pencil me-2"></i>Edit Visit</a></li>
                                    @endcan --}}
                                    <!-- @if($visit->status->allowedTransitions())
                                    <li><hr class="dropdown-divider"></li>
                                    @foreach($visit->status->allowedTransitions() as $nextStatus)
                                        <li>
                                            <form method="POST" action="{{ route('admin.visits.transition', $visit) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti ti-arrow-right me-2"></i>{{ $nextStatus->translatedLabel() }}
                                                </button>
                                            </form>
                                        </li>
                                    @endforeach
                                    @endif -->
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11">
                            <x-empty-state icon="ti-calendar-off" :title="__('visits.no_visits_found')" :message="__('visits.no_visits_match_filters')" />
                        </td>
                    </tr>
                    @endforelse
</x-data-table>
</div>
@endsection

@push('scripts')
    @include('partials.date-range-filter-scripts')
@endpush
