@extends('layouts.app')
@section('title', __('maternity.dashboard'))

@section('content')
<x-page-header :title="__('maternity.dashboard')" icon="ti-baby-carriage">
    <x-slot:actions>
        @can('maternity.pregnancy.create')
        <a href="{{ route('admin.maternity.pregnancies.create') }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-plus me-1"></i>{{ __('maternity.new_pregnancy_profile') }}</a>
        @endcan
        <a href="{{ route('admin.maternity.pregnancies.index') }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-list me-1"></i>{{ __('maternity.pregnancy_profiles') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-3">
    @foreach([
        ['label' => __('maternity.active_pregnancies'), 'value' => $overview['active_pregnancies'], 'icon' => 'ti-heartbeat', 'class' => 'success'],
        ['label' => __('maternity.high_risk_pregnancies'), 'value' => $overview['high_risk_pregnancies'], 'icon' => 'ti-alert-triangle', 'class' => 'danger'],
        ['label' => __('maternity.open_maternity_cases'), 'value' => $overview['open_cases'], 'icon' => 'ti-folder-open', 'class' => 'primary'],
        ['label' => __('maternity.maternity_admissions'), 'value' => $overview['maternity_admissions'], 'icon' => 'ti-bed', 'class' => 'info'],
        ['label' => __('maternity.expected_delivery_this_month'), 'value' => $overview['expected_delivery_this_month'], 'icon' => 'ti-calendar-due', 'class' => 'warning'],
        ['label' => __('maternity.anc_visits_today'), 'value' => $overview['anc_today'], 'icon' => 'ti-stethoscope', 'class' => 'primary'],
        ['label' => __('maternity.missed_anc_visits'), 'value' => $overview['missed_anc'], 'icon' => 'ti-calendar-x', 'class' => 'danger'],
        ['label' => __('maternity.profiles_without_anc'), 'value' => $overview['profiles_without_anc'], 'icon' => 'ti-clipboard-off', 'class' => 'secondary'],
    ] as $card)
    <div class="col-6 col-md-4 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-md bg-{{ $card['class'] }} bg-opacity-10 rounded"><i class="ti {{ $card['icon'] }} fs-4 text-{{ $card['class'] }}"></i></div>
                    <div><div class="fs-4 fw-bold">{{ $card['value'] }}</div><small class="text-muted">{{ $card['label'] }}</small></div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-activity me-1"></i>{{ __('maternity.recent_cases') }}</h5></div>
            <div class="card-body p-0">
                @if($overview['recent_cases']->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light"><tr><th>{{ __('maternity.patient') }}</th><th>{{ __('maternity.case_type') }}</th><th>{{ __('maternity.risk_level') }}</th><th>{{ __('maternity.case_status') }}</th><th></th></tr></thead>
                        <tbody>
                            @foreach($overview['recent_cases'] as $case)
                            <tr>
                                <td><strong>{{ $case->patient?->full_name }}</strong><br><small class="text-muted">{{ $case->patient?->patient_number }}</small></td>
                                <td>{{ $case->case_type?->label() ?? '—' }}</td>
                                <td><span class="badge badge-soft-{{ $case->risk_level?->color() ?? 'secondary' }}">{{ $case->risk_level?->label() ?? '—' }}</span></td>
                                <td><span class="badge bg-{{ $case->status?->color() }}">{{ $case->status?->label() }}</span></td>
                                <td class="text-end"><a href="{{ route('admin.maternity.cases.show', $case) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <x-empty-state icon="ti-baby-carriage" :title="__('maternity.no_recent_cases')" />
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-stethoscope me-1"></i>{{ __('maternity.recent_anc_visits') }}</h5></div>
            <div class="card-body p-0">
                @if($overview['recent_anc_visits']->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light"><tr><th>{{ __('maternity.patient') }}</th><th>{{ __('maternity.visit_date') }}</th><th>{{ __('maternity.gestational_age') }}</th><th>{{ __('maternity.status') }}</th><th></th></tr></thead>
                        <tbody>
                            @foreach($overview['recent_anc_visits'] as $visit)
                            <tr>
                                <td><strong>{{ $visit->patient?->full_name }}</strong><br><small class="text-muted">{{ $visit->patient?->patient_number }}</small></td>
                                <td>{{ $visit->visit_date?->format('d M Y') }}</td>
                                <td>{{ $visit->gestational_age_weeks !== null ? $visit->gestational_age_weeks.'w '.($visit->gestational_age_days ?? 0).'d' : '—' }}</td>
                                <td><span class="badge bg-{{ $visit->status?->color() ?? 'secondary' }}">{{ $visit->status?->label() }}</span></td>
                                <td class="text-end"><a href="{{ route('admin.maternity.antenatal.show', $visit) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <x-empty-state icon="ti-stethoscope" :title="__('maternity.no_anc_visits_yet')" />
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-info-circle me-1"></i>{{ __('maternity.foundation_scope') }}</h5></div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @foreach([
                        __('maternity.future_labor_placeholder'),
                        __('maternity.future_delivery_placeholder'),
                        __('maternity.future_newborn_placeholder'),
                        __('maternity.future_postnatal_placeholder'),
                    ] as $placeholder)
                    <div class="list-group-item px-0"><i class="ti ti-clock text-muted me-1"></i>{{ $placeholder }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
