@extends('layouts.app')
@section('title', __('maternity.anc_history'))

@section('content')
<x-page-header :title="__('maternity.anc_history')" :subtitle="$profile->patient?->full_name" icon="ti-list-details">
    <x-slot:actions>
        <a href="{{ route('admin.maternity.pregnancies.show', $profile) }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
        @can('maternity.anc.record')
        <a href="{{ route('admin.maternity.pregnancies.antenatal.create', $profile) }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-plus me-1"></i>{{ __('maternity.record_anc_visit') }}</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ __('maternity.anc_visit_count') }}</small><div class="fs-3 fw-bold">{{ $ancOverview['visit_count'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ __('maternity.latest_anc_visit') }}</small><div class="fw-semibold">{{ $ancOverview['latest_visit']?->visit_date?->format('d M Y') ?? __('common.none') }}</div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ __('maternity.next_visit_date') }}</small><div class="fw-semibold">{{ $ancOverview['next_visit_date']?->format('d M Y') ?? __('common.none') }}</div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ __('maternity.high_risk_anc') }}</small><div><span class="badge bg-{{ $ancOverview['high_risk'] ? 'danger' : 'success' }}">{{ $ancOverview['high_risk'] ? __('common.yes') : __('common.no') }}</span></div></div></div></div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>{{ __('maternity.visit_number') }}</th><th>{{ __('maternity.visit_date') }}</th><th>{{ __('maternity.gestational_age') }}</th><th>{{ __('maternity.blood_pressure') }}</th><th>{{ __('maternity.weight') }}</th><th>{{ __('maternity.fundal_height') }}</th><th>{{ __('maternity.fetal_heart_rate') }}</th><th>{{ __('maternity.danger_signs') }}</th><th>{{ __('maternity.status') }}</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visits as $visit)
                    <tr>
                        <td>{{ $visit->visit_number ?? '—' }}</td>
                        <td>{{ $visit->visit_date?->format('d M Y') }}</td>
                        <td>{{ $visit->gestational_age_weeks !== null ? $visit->gestational_age_weeks.'w '.($visit->gestational_age_days ?? 0).'d' : '—' }}</td>
                        <td>{{ $visit->blood_pressure_systolic && $visit->blood_pressure_diastolic ? $visit->blood_pressure_systolic.'/'.$visit->blood_pressure_diastolic : '—' }}</td>
                        <td>{{ $visit->weight_kg ? $visit->weight_kg.' kg' : '—' }}</td>
                        <td>{{ $visit->fundal_height_cm ? $visit->fundal_height_cm.' cm' : '—' }}</td>
                        <td>{{ $visit->fetal_heart_rate ?: '—' }}</td>
                        <td><span class="badge bg-{{ count($visit->danger_signs ?? []) ? 'danger' : 'secondary' }}">{{ count($visit->danger_signs ?? []) }}</span></td>
                        <td><span class="badge bg-{{ $visit->status?->color() ?? 'secondary' }}">{{ $visit->status?->label() }}</span></td>
                        <td class="text-end"><a href="{{ route('admin.maternity.antenatal.show', $visit) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="10"><x-empty-state icon="ti-stethoscope" :title="__('maternity.no_anc_visits_yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($visits->hasPages())<div class="card-footer">{{ $visits->links() }}</div>@endif
</div>
@endsection
