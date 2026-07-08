@extends('layouts.app')
@section('title', __('reports.consultation_specialties.title'))

@php
    $filters = $payload['filters'];
    $summary = $payload['summary'];
@endphp

@section('content')
<x-page-header
    :title="__('reports.consultation_specialties.title')"
    :description="__('reports.consultation_specialties.description')"
    icon="ti-stethoscope">
    <x-slot:actions>
        <a href="{{ route('admin.reports.consultation-specialties.export', request()->query() + ['dataset' => 'all']) }}" class="btn btn-outline-success btn-sm">
            <i class="ti ti-file-type-csv me-1"></i>{{ __('reports.export_csv') }}
        </a>
    </x-slot:actions>
</x-page-header>

<form method="GET" class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">{{ __('reports.date_from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('reports.date_to') }}</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('reports.consultation_specialties.specialty') }}</label>
                <select name="specialty_profile_id" class="form-select">
                    <option value="">{{ __('reports.consultation_specialties.all_specialties') }}</option>
                    @foreach($profiles as $profile)
                        <option value="{{ $profile->id }}" @selected((int) ($filters['specialty_profile_id'] ?? 0) === $profile->id)>{{ $profile->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('reports.department') }}</label>
                <select name="department_id" class="form-select">
                    <option value="">{{ __('reports.all_departments') }}</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((int) ($filters['department_id'] ?? 0) === $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('reports.doctor') }}</label>
                <select name="doctor_id" class="form-select">
                    <option value="">{{ __('reports.all_doctors') }}</option>
                    @foreach($doctors as $doctor)
                        <option value="{{ $doctor->id }}" @selected((int) ($filters['doctor_id'] ?? 0) === $doctor->id)>{{ $doctor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('reports.status') }}</label>
                <select name="billing_status" class="form-select">
                    <option value="">{{ __('reports.all_statuses') }}</option>
                    @foreach($billingStatuses as $status)
                        <option value="{{ $status }}" @selected(($filters['billing_status'] ?? null) === $status)>{{ __('reports.consultation_specialties.billing_statuses.'.$status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">
                    <i class="ti ti-filter me-1"></i>{{ __('reports.filter') }}
                </button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.reports.consultation-specialties.index') }}" class="btn btn-outline-secondary w-100">{{ __('reports.clear') }}</a>
            </div>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    @foreach([
        ['label' => __('reports.consultation_specialties.total_specialist_consultations'), 'value' => $summary['total_specialist_consultations'], 'icon' => 'ti-stethoscope', 'color' => 'primary'],
        ['label' => __('reports.consultation_specialties.specialties_used'), 'value' => $summary['specialties_used'], 'icon' => 'ti-category-2', 'color' => 'info'],
        ['label' => __('reports.consultation_specialties.structured_entries'), 'value' => $summary['structured_entries_count'], 'icon' => 'ti-forms', 'color' => 'success'],
        ['label' => __('reports.consultation_specialties.specialty_revenue'), 'value' => 'GHS '.number_format((float) $summary['specialty_revenue'], 2), 'icon' => 'ti-report-money', 'color' => 'warning'],
    ] as $card)
        <div class="col-6 col-xl-3">
            <div class="card h-100 border-start border-{{ $card['color'] }} border-3">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <span class="badge bg-{{ $card['color'] }}-subtle text-{{ $card['color'] }} p-2"><i class="ti {{ $card['icon'] }} fs-5"></i></span>
                    <div>
                        <div class="small text-muted">{{ $card['label'] }}</div>
                        <div class="h4 mb-0">{{ is_numeric($card['value']) ? number_format($card['value']) : $card['value'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between">
                <h5 class="card-title mb-0">{{ __('reports.consultation_specialties.volume_by_specialty') }}</h5>
                <a class="small" href="{{ route('admin.reports.consultation-specialties.export', request()->query() + ['dataset' => 'volume_by_specialty']) }}">{{ __('reports.export_csv') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>{{ __('reports.consultation_specialties.specialty') }}</th><th class="text-end">{{ __('reports.consultation_specialties.consultations') }}</th><th class="text-end">{{ __('reports.consultation_specialties.entries') }}</th></tr></thead>
                    <tbody>
                        @forelse($payload['volume']['by_specialty'] as $row)
                            <tr><td>{{ $row->name }}</td><td class="text-end">{{ number_format($row->consultations_count) }}</td><td class="text-end">{{ number_format($row->entries_count) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">{{ __('reports.no_data') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="card-title mb-0">{{ __('reports.consultation_specialties.doctor_workload') }}</h5></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>{{ __('reports.doctor') }}</th><th class="text-end">{{ __('reports.consultation_specialties.consultations') }}</th><th class="text-end">{{ __('reports.consultation_specialties.entries') }}</th><th class="text-end">{{ __('reports.consultation_specialties.specialties') }}</th></tr></thead>
                    <tbody>
                        @forelse($payload['workload'] as $row)
                            <tr><td>{{ $row->doctor_name }}</td><td class="text-end">{{ number_format($row->consultations_count) }}</td><td class="text-end">{{ number_format($row->entries_count) }}</td><td class="text-end">{{ number_format($row->specialty_count) }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">{{ __('reports.no_data') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="card-title mb-0">{{ __('reports.consultation_specialties.section_completion') }}</h5></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>{{ __('reports.consultation_specialties.specialty') }}</th><th>{{ __('reports.consultation_specialties.section') }}</th><th class="text-end">{{ __('reports.consultation_specialties.entries') }}</th><th class="text-end">{{ __('reports.consultation_specialties.completion_rate') }}</th></tr></thead>
                    <tbody>
                        @forelse($payload['sections']->take(20) as $row)
                            <tr><td>{{ $row->name }}</td><td>{{ $row->section_label }}</td><td class="text-end">{{ number_format($row->entry_count) }}</td><td class="text-end">{{ $row->completion_rate }}%</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">{{ __('reports.no_data') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="card-title mb-0">{{ __('reports.consultation_specialties.readiness_breakdown') }}</h5></div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                    <span class="text-muted">{{ __('reports.consultation_specialties.average_score') }}</span>
                    <strong>{{ $payload['readiness']['average_score'] }}%</strong>
                </div>
                @forelse($payload['readiness']['by_status'] as $row)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>{{ __('reports.consultation_specialties.readiness_statuses.'.$row['status']) }}</span>
                        <strong>{{ number_format($row['count']) }}</strong>
                    </div>
                @empty
                    <div class="text-muted">{{ __('reports.no_data') }}</div>
                @endforelse
                <div class="small text-muted mt-3">{{ __('reports.consultation_specialties.readiness_sample_note', ['count' => $payload['readiness']['sample_size']]) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="card-title mb-0">{{ __('reports.consultation_specialties.order_set_usage') }}</h5></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>{{ __('reports.consultation_specialties.order_set') }}</th><th>{{ __('reports.consultation_specialties.specialty') }}</th><th class="text-end">{{ __('reports.consultation_specialties.applications') }}</th></tr></thead>
                    <tbody>
                        @forelse($payload['order_sets']['applications_by_order_set'] as $row)
                            <tr><td>{{ $row->order_set_name }}</td><td>{{ $row->profile_name }}</td><td class="text-end">{{ number_format($row->applications_count) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">{{ __('reports.no_data') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="card-title mb-0">{{ __('reports.consultation_specialties.billing_health') }}</h5></div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">{{ __('reports.consultation_specialties.billing_mappings') }}</span><strong>{{ number_format($summary['billing_mappings_count']) }}</strong></div>
                <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">{{ __('reports.consultation_specialties.auto_bill_mappings') }}</span><strong>{{ number_format($payload['billing']['auto_bill_mappings']) }}</strong></div>
                <div class="mt-3 small text-muted">{{ __('reports.consultation_specialties.billing_applications') }}</div>
                @forelse($payload['billing']['applications_by_status'] as $row)
                    <div class="d-flex justify-content-between border-top py-2"><span>{{ __('reports.consultation_specialties.billing_statuses.'.$row->status) }}</span><strong>{{ number_format($row->count) }}</strong></div>
                @empty
                    <div class="text-muted small mt-2">{{ __('reports.no_data') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
