@extends('layouts.app')
@section('title', $mergeRequest->request_number)

@section('content')
<!-- <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ $mergeRequest->request_number }}</h4>
        <p class="text-muted mb-0">{{ __('patients.merge_request_subtitle') }}</p>
    </div>
    <a href="{{ route('admin.patients.merge.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-chevron-left me-1"></i>{{ __('common.back') }}</a>
</div> -->
<x-page-header-back
    :title="__('patients.merge_patients') . ' - ' . $mergeRequest->request_number"
    :href="route('admin.patients.merge.index')"
/>

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-3 mb-2">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">{{ __('common.status') }}</div>
                <div class="d-flex justify-content-between align-items-center h5 mb-1">
                    {{ str_replace('_', ' ', $mergeRequest->status) }}
                    @if($mergeRequest->can_execute)
                        @can('patients.merge.execute')
                            <form method="POST" action="{{ route('admin.patients.merge.requests.execute', $mergeRequest) }}">
                                @csrf
                                <button class="btn btn-success"><i class="ti ti-git-merge me-1"></i>{{ __('patients.execute_merge') }}</button>
                            </form>
                        @endcan
                    @endif
                </div>
                <small class="text-muted">{{ __('patients.requested') }} {{ $mergeRequest->created_at?->format('d M Y H:i') }}</small>
                <div class="mt-1"><small ><strong>{{ __('patients.reason_label') }}:</strong> {{ $mergeRequest->reason }}</small></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <x-patient-selection-card
            :selected-patient="$mergeRequest->mainPatient"
            :show-search="false"
            :show-clear-button="false"
            :show-active-admission-warning="false"
            title="{{ __('patients.main_folder') }}"
            icon="ti-check"
            patient-info-id="mergeRequestMainInfo"
            patient-initial-id="mergeRequestMainInitial"
            patient-name-id="mergeRequestMainName"
            patient-number-id="mergeRequestMainNumber"
            patient-phone-id="mergeRequestMainPhone"
            patient-last-visit-id="mergeRequestMainLastVisit"
            deceased-warning-id="mergeRequestMainDeceasedWarning"
            class="border-success"
        />
    </div>
    <div class="col-md-4">
        <x-patient-selection-card
            :selected-patient="$mergeRequest->duplicatePatient"
            :show-search="false"
            :show-clear-button="false"
            :show-active-admission-warning="false"
            title="{{ __('patients.duplicate_folder') }}"
            icon="ti-copy"
            patient-info-id="mergeRequestDuplicateInfo"
            patient-initial-id="mergeRequestDuplicateInitial"
            patient-name-id="mergeRequestDuplicateName"
            patient-number-id="mergeRequestDuplicateNumber"
            patient-phone-id="mergeRequestDuplicatePhone"
            patient-last-visit-id="mergeRequestDuplicateLastVisit"
            deceased-warning-id="mergeRequestDuplicateDeceasedWarning"
            class="border-warning"
        />
    </div>
</div>

<!-- @if($mergeRequest->reason)
    <div class="alert alert-light border"><strong>{{ __('patients.reason_label') }}:</strong> {{ $mergeRequest->reason }}</div>
@endif -->

<!-- @if($mergeRequest->can_execute)
    @can('patients.merge.execute')
        <form method="POST" action="{{ route('admin.patients.merge.requests.execute', $mergeRequest) }}" class="mb-3">
            @csrf
            <button class="btn btn-primary"><i class="ti ti-git-merge me-1"></i>{{ __('patients.execute_merge') }}</button>
        </form>
    @endcan
@endif -->

<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ __('patients.preview_summary') }}</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="bg-light"><tr><th>{{ __('patients.col_area') }}</th><th>{{ __('patients.col_handler') }}</th><th class="text-end">{{ __('patients.col_records') }}</th></tr></thead>
            <tbody>
                @foreach(($mergeRequest->preview_summary['record_counts'] ?? []) as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $row['handler'] }}</span></td>
                        <td class="text-end">{{ $row['count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ __('patients.audit_trail') }}</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="bg-light"><tr><th>{{ __('patients.col_time') }}</th><th>{{ __('patients.col_action_log') }}</th><th>{{ __('patients.col_table') }}</th><th>{{ __('patients.col_by') }}</th></tr></thead>
            <tbody>
                @forelse($mergeRequest->logs as $log)
                    <tr>
                        <td>{{ $log->occurred_at?->format('d M Y H:i:s') }}</td>
                        <td>{{ str_replace('_', ' ', $log->action) }}</td>
                        <td>{{ $log->table_name ?: '-' }}</td>
                        <td>{{ $log->performedBy?->name ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty-state message="{{ __('patients.no_audit_entries') }}" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
