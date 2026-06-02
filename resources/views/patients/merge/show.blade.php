@extends('layouts.app')
@section('title', $mergeRequest->request_number)

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-1">{{ $mergeRequest->request_number }}</h4>
        <p class="text-muted mb-0">Patient folder merge request</p>
    </div>
    <a href="{{ route('admin.patients.merge.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-chevron-left me-1"></i>Back</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Status</div>
                <div class="h5 mb-1">{{ str_replace('_', ' ', $mergeRequest->status) }}</div>
                <small class="text-muted">Requested {{ $mergeRequest->created_at?->format('d M Y H:i') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-success">
            <div class="card-body">
                <div class="text-muted small">Main Folder</div>
                <a href="{{ route('admin.patients.show', $mergeRequest->mainPatient) }}" class="h6 d-block mb-1">{{ $mergeRequest->mainPatient?->full_name }}</a>
                <small class="text-muted">{{ $mergeRequest->mainPatient?->patient_number }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-warning">
            <div class="card-body">
                <div class="text-muted small">Duplicate Folder</div>
                <a href="{{ route('admin.patients.show', $mergeRequest->duplicatePatient) }}" class="h6 d-block mb-1">{{ $mergeRequest->duplicatePatient?->full_name }}</a>
                <small class="text-muted">{{ $mergeRequest->duplicatePatient?->patient_number }}</small>
            </div>
        </div>
    </div>
</div>

@if($mergeRequest->reason)
    <div class="alert alert-light border"><strong>Reason:</strong> {{ $mergeRequest->reason }}</div>
@endif

@if($mergeRequest->can_execute)
    @can('patients.merge.execute')
        <form method="POST" action="{{ route('admin.patients.merge.requests.execute', $mergeRequest) }}" class="mb-3">
            @csrf
            <button class="btn btn-primary"><i class="ti ti-git-merge me-1"></i>Execute Merge</button>
        </form>
    @endcan
@endif

<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0">Preview Summary</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="bg-light"><tr><th>Area</th><th>Handler</th><th class="text-end">Records</th></tr></thead>
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
        <h5 class="card-title mb-0">Audit Trail</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="bg-light"><tr><th>Time</th><th>Action</th><th>Table</th><th>By</th></tr></thead>
            <tbody>
                @forelse($mergeRequest->logs as $log)
                    <tr>
                        <td>{{ $log->occurred_at?->format('d M Y H:i:s') }}</td>
                        <td>{{ str_replace('_', ' ', $log->action) }}</td>
                        <td>{{ $log->table_name ?: '-' }}</td>
                        <td>{{ $log->performedBy?->name ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty-state message="No audit entries yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
