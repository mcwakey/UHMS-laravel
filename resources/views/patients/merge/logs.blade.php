@extends('layouts.app')
@section('title', 'Patient Merge Audit Logs')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-1">Patient Merge Audit Logs</h4>
        <p class="text-muted mb-0">Every merge action is retained for traceability.</p>
    </div>
    <a href="{{ route('admin.patients.merge.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-chevron-left me-1"></i>Back</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Time</th>
                    <th>Request</th>
                    <th>Action</th>
                    <th>Main</th>
                    <th>Duplicate</th>
                    <th>Table</th>
                    <th>By</th>
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
                    <tr><td colspan="7"><x-empty-state message="No merge logs found." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($logs->hasPages())
    <div class="d-flex justify-content-end mt-3">{{ $logs->links() }}</div>
@endif
@endsection
