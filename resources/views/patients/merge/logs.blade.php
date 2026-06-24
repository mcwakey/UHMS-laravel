@extends('layouts.app')
@section('title', __('patients.merge_audit_logs'))

@section('content')
<!-- <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ __('patients.merge_audit_logs') }}</h4>
    </div>
   </div> -->
<x-page-header-back
    :title="__('patients.merge_audit_logs')"
    :href="route('admin.patients.merge.index')"
/>

        <!-- <p class="text-muted mb-0">{{ __('patients.merge_audit_subtitle') }}</p> -->
 <!-- <a href="{{ route('admin.patients.merge.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-chevron-left me-1"></i>{{ __('common.back') }}</a> -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ __('patients.merge_history') }}</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>{{ __('patients.col_time') }}</th>
                    <th>{{ __('patients.col_request') }}</th>
                    <th>{{ __('patients.col_action_log') }}</th>
                    <th>{{ __('patients.col_main') }}</th>
                    <th>{{ __('patients.col_duplicate') }}</th>
                    <th>{{ __('patients.col_table') }}</th>
                    <th>{{ __('patients.col_by') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->occurred_at?->format('d M Y H:i:s') }}</td>
                        <td>
                            @if($log->mergeRequest)
                                <a href="{{ route('admin.patients.merge.requests.show', $log->mergeRequest) }}">{{ $log->mergeRequest->request_number }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ str_replace('_', ' ', $log->action) }}</td>
                        <td>{{ $log->mainPatient?->patient_number ?: '-' }}</td>
                        <td>{{ $log->duplicatePatient?->patient_number ?: '-' }}</td>
                        <td>{{ $log->table_name ?: '-' }}</td>
                        <td>{{ $log->performedBy?->name ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state message="{{ __('patients.no_merge_logs') }}" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($logs->hasPages())
    <div class="d-flex justify-content-end mt-3">{{ $logs->links() }}</div>
@endif
@endsection
