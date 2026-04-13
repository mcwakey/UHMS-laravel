@extends('layouts.app')
@section('title', 'Statement Search')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Patient Statement Search</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Statement Search</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.statement-search') }}" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Search Patient</label>
                <input type="text" name="q" class="form-control" placeholder="Enter patient name, ID or phone number..." value="{{ request('q') }}">
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary"><i class="ti ti-search me-1"></i>Search</button>
            </div>
        </form>
    </div>
</div>

@if(isset($patients))
<div class="card">
    <div class="card-header"><h6 class="mb-0">Search Results ({{ $patients->total() }})</h6></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Patient ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Gender</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($patients as $patient)
                <tr>
                    <td><code>{{ $patient->patient_number }}</code></td>
                    <td>{{ $patient->full_name }}</td>
                    <td>{{ $patient->phone ?? '—' }}</td>
                    <td>{{ $patient->gender?->label() ?? '—' }}</td>
                    <td>
                        <a href="{{ route('admin.reports.patient-statement', $patient) }}" class="btn btn-sm btn-primary">
                            <i class="ti ti-file-invoice me-1"></i>View Statement
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No patients found matching "{{ request('q') }}".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($patients->hasPages())
    <div class="card-footer">{{ $patients->links() }}</div>
    @endif
</div>
@else
<div class="text-center text-muted py-5">
    <i class="ti ti-search fs-1 d-block mb-2"></i>
    <p>Search for a patient to generate their financial statement.</p>
</div>
@endif
@endsection
