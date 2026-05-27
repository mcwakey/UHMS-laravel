@extends('layouts.app')
@section('title', 'Emergency Bays')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1">Emergency Bays</h4>
        <p class="text-muted mb-0">Short-stay emergency locations for resuscitation, observation, and treatment.</p>
    </div>
    <a href="{{ route('admin.emergency.board') }}" class="btn btn-outline-secondary btn-sm">Emergency Board</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-3">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Create Bay</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.emergency.bays.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">Name</label>
                        <input class="form-control" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Code</label>
                        <input class="form-control" name="code" value="{{ old('code') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            @foreach(['AVAILABLE','CLEANING','OUT_OF_SERVICE','RESERVED'] as $status)
                                <option value="{{ $status }}">{{ str_replace('_', ' ', $status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Bay Type</label>
                        <select class="form-select" name="bay_type" required>
                            @foreach(['RESUSCITATION','OBSERVATION','TREATMENT','MINOR_PROCEDURE','ISOLATION','WAITING_AREA','EMERGENCY_WARD'] as $type)
                                <option value="{{ $type }}">{{ str_replace('_', ' ', $type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary w-100" type="submit">Create Bay</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Bay Status</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Bay</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Active Case</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bays as $bay)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $bay->name }}</div>
                                        <small class="text-muted">{{ $bay->code }}</small>
                                    </td>
                                    <td>{{ str_replace('_', ' ', $bay->bay_type) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $bay->status === 'AVAILABLE' ? 'success' : ($bay->status === 'OCCUPIED' ? 'danger' : 'secondary') }}">
                                            {{ str_replace('_', ' ', $bay->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($bay->activeCase)
                                            <a href="{{ route('admin.emergency.cases.show', $bay->activeCase) }}">{{ $bay->activeCase->emergency_number }}</a>
                                            <div class="small text-muted">{{ $bay->activeCase->patient->full_name ?? 'Patient' }}</div>
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </td>
                                    <td class="text-muted">{{ $bay->notes }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No emergency bays configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
