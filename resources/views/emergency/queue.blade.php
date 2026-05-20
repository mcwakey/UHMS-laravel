@extends('layouts.app')
@section('title', 'Emergency Queue')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="ti ti-urgent text-danger"></i> Emergency Queue</h1>
        <a href="{{ route('admin.emergency.cases.create') }}" class="btn btn-danger">
            <i class="ti ti-plus"></i> New ER Case
        </a>
    </div>

    @if($awaitingTriage->count())
    <div class="card border-warning mb-3">
        <div class="card-header bg-warning-subtle"><strong>Awaiting Triage ({{ $awaitingTriage->count() }})</strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>ER #</th><th>Patient</th><th>Arrived</th><th>Chief Complaint</th><th></th></tr></thead>
                <tbody>
                @foreach($awaitingTriage as $case)
                    <tr>
                        <td><code>{{ $case->emergency_number }}</code></td>
                        <td>{{ $case->patient->full_name ?? '—' }}</td>
                        <td>{{ optional($case->arrival_time)->diffForHumans() }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($case->chief_complaint, 60) }}</td>
                        <td><a href="{{ route('admin.emergency.cases.show', $case) }}" class="btn btn-sm btn-warning">Triage now</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header"><strong>Open Cases (sorted by triage priority + arrival time)</strong></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ER #</th><th>Patient</th><th>Triage</th>
                        <th>Status</th><th>Treatment Area</th><th>Doctor</th>
                        <th>Wait</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($queue as $case)
                        <tr>
                            <td><code>{{ $case->emergency_number }}</code></td>
                            <td>{{ $case->patient->full_name ?? '—' }}</td>
                            <td>
                                @if($case->triage_category)
                                    <span class="badge bg-{{ $case->triage_category->color() }}">{{ $case->triage_category->label() }}</span>
                                @else
                                    <span class="badge bg-secondary">Untriaged</span>
                                @endif
                            </td>
                            <td><span class="badge bg-light text-dark">{{ $case->status->label() }}</span></td>
                            <td>{{ optional($case->treatmentArea)->name ?? '—' }}</td>
                            <td>{{ optional($case->assignedDoctor)->name ?? '—' }}</td>
                            <td>{{ optional($case->arrival_time)->diffForHumans() }}</td>
                            <td><a href="{{ route('admin.emergency.cases.show', $case) }}" class="btn btn-sm btn-primary">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Queue is clear.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
