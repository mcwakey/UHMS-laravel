@extends('layouts.app')
@section('title', __('medication_administration.admission_medication_board'))

@section('content')
<x-page-header :title="__('medication_administration.admission_medication_board')" :description="__('medication_administration.admission_board_description')" icon="ti-pill">
    <x-slot:actions>
        <form method="GET" class="d-flex gap-2">
            <select name="ward_id" class="form-select form-select-sm">
                <option value="">{{ __('medication_administration.all_wards') }}</option>
                @foreach($wards as $ward)
                    <option value="{{ $ward->id }}" @selected(($filters['ward_id'] ?? '') == $ward->id)>{{ $ward->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline-primary btn-sm"><i class="ti ti-filter me-1"></i>{{ __('common.filter') }}</button>
        </form>
    </x-slot:actions>
</x-page-header>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>{{ __('common.patient') }}</th>
                        <th>{{ __('medication_administration.ward_bed') }}</th>
                        <th class="text-center">{{ __('medication_administration.active_meds') }}</th>
                        <th class="text-center">{{ __('medication_administration.due_now') }}</th>
                        <th class="text-center">{{ __('statuses.default.overdue') }}</th>
                        <th class="text-center">{{ __('medication_administration.upcoming') }}</th>
                        <th class="text-center">{{ __('medication_administration.completed_today') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    @php $admission = $row['admission']; $counts = $row['counts']; @endphp
                    <tr class="{{ $counts['overdue'] > 0 ? 'table-danger' : ($counts['due_now'] > 0 ? 'table-info' : '') }}">
                        <td>
                            <div class="fw-semibold">{{ $admission->patient->full_name }}</div>
                            <small class="text-muted">{{ $admission->patient->patient_number }}</small>
                        </td>
                        <td>
                            <div>{{ $admission->bed->ward->name ?? __('medication_administration.ward') }}</div>
                            <small class="text-muted">Bed {{ $admission->bed->bed_number ?? '—' }}</small>
                        </td>
                        <td class="text-center"><span class="badge bg-primary">{{ $row['active_medication_count'] }}</span></td>
                        <td class="text-center"><span class="badge bg-info">{{ $counts['due_now'] }}</span></td>
                        <td class="text-center"><span class="badge bg-danger">{{ $counts['overdue'] }}</span></td>
                        <td class="text-center"><span class="badge bg-secondary">{{ $counts['upcoming'] }}</span></td>
                        <td class="text-center"><span class="badge bg-success">{{ $counts['completed_today'] }}</span></td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1 flex-wrap">
                                @can('admission.mar_chart.view')
                                <a href="{{ route('admin.admissions.mar-chart', $admission) }}" class="btn btn-sm btn-primary">
                                    <i class="ti ti-layout-grid me-1"></i>{{ __('medication_administration.view_mar') }}
                                </a>
                                @endcan
                                <a href="{{ route('admin.admissions.medications.show', $admission) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-list-details me-1"></i>{{ __('medication_administration.board') }}
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8"><x-empty-state icon="ti-pill-off" :title="__('medication_administration.no_medication_tasks')" :message="__('medication_administration.no_admitted_patients_with_tasks')" /></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    setTimeout(function () {
        if (window.UhmsInertia) {
            window.UhmsInertia.reload({ preserveScroll: true, preserveState: true });
        }
    }, 60000);
</script>
@endpush
