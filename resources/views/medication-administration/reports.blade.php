@extends('layouts.app')
@section('title', __('medication_administration.reports_title'))

@section('content')
<x-page-header :title="__('medication_administration.reports_title')" :description="__('medication_administration.reports_description')" icon="ti-checkup-list" />

<form method="GET" class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">{{ __('medication_administration.from') }}</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('medication_administration.to') }}</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div>
            <div class="col-md-3">
                <label class="form-label">{{ __('medication_administration.status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('medication_administration.all_statuses') }}</option>
                    @foreach(['GIVEN','MISSED','HELD','REFUSED','SKIPPED'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ __("statuses.mar.".strtolower($status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><button class="btn btn-primary w-100">{{ __('medication_administration.apply_filters') }}</button></div>
        </div>
    </div>
</form>

<div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('medication_administration.administration_report') }}</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr><th>{{ __('medication_administration.date') }}</th><th>{{ __('medication_administration.patient') }}</th><th>{{ __('medication_administration.medication') }}</th><th>{{ __('medication_administration.status') }}</th><th>{{ __('medication_administration.nurse') }}</th><th>{{ __('medication_administration.reason_reaction') }}</th></tr>
                </thead>
                <tbody>
                    @forelse($administrations as $record)
                    <tr>
                        <td>{{ $record->administered_at?->format('d M Y H:i') }}</td>
                        <td>{{ $record->patient->full_name ?? '—' }}</td>
                        <td>{{ $record->medicationOrder->display_name ?? 'Medication' }}</td>
                        <td><x-status-badge :status="$record->status" domain="mar" soft /></td>
                        <td>{{ $record->administeredBy->name ?? '—' }}</td>
                        <td>{{ $record->reason_not_given ?: $record->reaction ?: $record->notes ?: '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6"><x-empty-state icon="ti-checkup-list" :message="__('medication_administration.no_administration_records')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($administrations->hasPages())
    <div class="card-footer">{{ $administrations->links() }}</div>
    @endif
</div>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0 text-danger">{{ __('medication_administration.overdue_report') }}</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>{{ __('medication_administration.due') }}</th><th>{{ __('medication_administration.patient') }}</th><th>{{ __('medication_administration.ward') }}</th><th>{{ __('medication_administration.medication') }}</th><th>{{ __('medication_administration.escalation') }}</th></tr></thead>
                <tbody>
                    @forelse($overdueTasks as $task)
                    <tr>
                        <td class="text-danger fw-semibold">{{ $task->due_at?->format('d M Y H:i') }}</td>
                        <td>{{ $task->patient->full_name ?? '—' }}</td>
                        <td>{{ $task->admission->bed->ward->name ?? __('medication_administration.emergency_opd') }}</td>
                        <td>{{ $task->schedule->medicationOrder->display_name ?? $task->title }}</td>
                        <td><span class="badge bg-danger">{{ $task->escalation_level }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="5"><x-empty-state icon="ti-circle-check" :message="__('medication_administration.no_overdue_tasks')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
