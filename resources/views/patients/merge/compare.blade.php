@extends('layouts.app')
@section('title', 'Compare Patient Folders')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-1">Compare Patient Folders</h4>
        <p class="text-muted mb-0">Review demographics and records before creating the merge request.</p>
    </div>
    <a href="{{ route('admin.patients.merge.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-chevron-left me-1"></i>Back</a>
</div>

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

@foreach($preview['warnings'] as $warning)
    <div class="alert alert-warning">{{ $warning }}</div>
@endforeach

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100 border-success">
            <div class="card-header bg-success-subtle text-success fw-semibold">Main Folder</div>
            <div class="card-body">
                <div class="h5 mb-1">{{ $mainPatient->full_name }}</div>
                <div class="text-muted">{{ $mainPatient->patient_number }} · ID {{ $mainPatient->id }}</div>
                <div class="small mt-2">{{ $mainPatient->phone ?: 'No phone' }} · {{ $mainPatient->ghana_card_number ?: 'No Ghana Card' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100 border-warning">
            <div class="card-header bg-warning-subtle text-warning fw-semibold">Duplicate Folder</div>
            <div class="card-body">
                <div class="h5 mb-1">{{ $duplicatePatient->full_name }}</div>
                <div class="text-muted">{{ $duplicatePatient->patient_number }} · ID {{ $duplicatePatient->id }}</div>
                <div class="small mt-2">{{ $duplicatePatient->phone ?: 'No phone' }} · {{ $duplicatePatient->ghana_card_number ?: 'No Ghana Card' }}</div>
                @if($duplicatePatient->is_temporary)
                    <span class="badge bg-warning-subtle text-warning mt-2">Temporary emergency folder</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">Records To Reassign</h5>
        <span class="badge bg-primary">{{ $preview['total_records'] }} records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="bg-light"><tr><th>Area</th><th>Handler</th><th class="text-end">Records</th></tr></thead>
            <tbody>
                @foreach($preview['record_counts'] as $row)
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

<form method="POST" action="{{ route('admin.patients.merge.requests.store') }}">
    @csrf
    <input type="hidden" name="main_patient_id" value="{{ $mainPatient->id }}">
    <input type="hidden" name="duplicate_patient_id" value="{{ $duplicatePatient->id }}">

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">Demographic Resolution</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Field</th>
                        <th>Main Value</th>
                        <th>Duplicate Value</th>
                        <th>Keep</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview['demographic_fields'] as $field)
                        <tr class="{{ $field['differs'] ? '' : 'table-light' }}">
                            <td>{{ $field['label'] }}</td>
                            <td>{{ $field['main'] ?: '-' }}</td>
                            <td>{{ $field['duplicate'] ?: '-' }}</td>
                            <td style="min-width: 180px;">
                                <select name="field_resolution[{{ $field['field'] }}]" class="form-select form-select-sm">
                                    <option value="main" {{ $field['suggested_source'] === 'main' ? 'selected' : '' }}>Main folder value</option>
                                    <option value="duplicate" {{ $field['suggested_source'] === 'duplicate' ? 'selected' : '' }}>Duplicate folder value</option>
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Merge Reason</label>
                <textarea name="reason" class="form-control" rows="3" placeholder="Document why these folders are the same patient"></textarea>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="confirmed" value="1" id="confirmed" required>
                <label class="form-check-label" for="confirmed">I have reviewed both folders and confirm the duplicate should be locked after merge.</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary"><i class="ti ti-file-plus me-1"></i>Create Request</button>
                @can('patients.merge.execute')
                    <button type="submit" name="execute_now" value="1" class="btn btn-primary"><i class="ti ti-git-merge me-1"></i>Create And Execute</button>
                @endcan
            </div>
        </div>
    </div>
</form>
@endsection
