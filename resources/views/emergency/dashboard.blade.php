@extends('layouts.app')
@section('title', 'Emergency Unit Dashboard')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="ti ti-ambulance text-danger"></i> Emergency Unit Dashboard</h1>
        <a href="{{ route('admin.emergency.cases.create') }}" class="btn btn-danger">
            <i class="ti ti-plus"></i> New ER Case
        </a>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        @php
            $cards = [
                ['label' => 'Open Cases',        'value' => $stats['open']             ?? 0, 'icon' => 'ti-alert-triangle', 'color' => 'danger'],
                ['label' => 'Today',             'value' => $stats['today']            ?? 0, 'icon' => 'ti-calendar',       'color' => 'primary'],
                ['label' => 'Admitted Today',    'value' => $stats['admitted_today']   ?? 0, 'icon' => 'ti-bed',            'color' => 'warning'],
                ['label' => 'Discharged Today',  'value' => $stats['discharged_today'] ?? 0, 'icon' => 'ti-home',           'color' => 'success'],
                ['label' => 'Deceased Today',    'value' => $stats['deceased_today']   ?? 0, 'icon' => 'ti-cross',          'color' => 'dark'],
            ];
        @endphp
        @foreach($cards as $c)
        <div class="col-md col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3"><i class="ti {{ $c['icon'] }} fs-1 text-{{ $c['color'] }}"></i></div>
                    <div>
                        <div class="text-muted small">{{ $c['label'] }}</div>
                        <div class="h3 mb-0">{{ $c['value'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Triage breakdown --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header"><strong>Triage Breakdown (open cases)</strong></div>
        <div class="card-body d-flex flex-wrap gap-2">
            @foreach(\App\Enums\EmergencyTriageCategory::cases() as $cat)
                <span class="badge bg-{{ $cat->color() }} fs-6 px-3 py-2">
                    {{ $cat->label() }}: {{ $triageCounts[$cat->value] ?? 0 }}
                </span>
            @endforeach
            <span class="badge bg-secondary fs-6 px-3 py-2">
                Untriaged: {{ $triageCounts['untriaged'] ?? 0 }}
            </span>
        </div>
    </div>

    {{-- Top of queue --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>ER Queue (top 15)</strong>
            <a href="{{ route('admin.emergency.queue') }}" class="btn btn-sm btn-outline-primary">View Full Queue</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ER #</th><th>Patient</th><th>Triage</th>
                        <th>Arrived</th><th>Status</th><th>Doctor</th><th></th>
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
                            <td>{{ optional($case->arrival_time)->diffForHumans() }}</td>
                            <td><span class="badge bg-light text-dark">{{ $case->status->label() }}</span></td>
                            <td>{{ optional($case->assignedDoctor)->name ?? '—' }}</td>
                            <td>
                                <a href="{{ route('admin.emergency.cases.show', $case) }}" class="btn btn-sm btn-primary">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No open ER cases.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
