@extends('layouts.app')

@section('title', 'Theatre / Procedure Workflow')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Theatre / Procedure Workflow</h3>
        <form action="{{ route('admin.theatre.index') }}" method="GET" class="d-flex gap-2">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search request #, patient, visit…" class="form-control form-control-sm" style="min-width:240px;">
            <select name="priority" class="form-select form-select-sm" style="width:140px;">
                <option value="">Any priority</option>
                @foreach (['routine','urgent','emergency'] as $p)
                    <option value="{{ $p }}" @selected(request('priority')===$p)>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-primary">Filter</button>
        </form>
    </div>

    {{-- Stats --}}
    <div class="row g-2 mb-3">
        @php
            $cards = [
                'pending'         => ['Pending Requests',  $stats['pending'],         '#d97706'],
                'accepted'        => ['Accepted',          $stats['accepted'],        '#0ea5e9'],
                'billed'          => ['Billed',            $stats['billed'],          '#8b5cf6'],
                'scheduled'       => ['Scheduled',         $stats['scheduled'],       '#2563eb'],
                'in_theatre'      => ['In Theatre',        $stats['in_theatre'],      '#dc2626'],
                'recovery'        => ['Recovery',          $stats['recovery'],        '#14b8a6'],
                'completed_today' => ['Completed Today',   $stats['completed_today'], '#16a34a'],
            ];
        @endphp
        @foreach ($cards as $k => $c)
            <div class="col-md">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-2 text-center">
                        <div class="text-muted small">{{ $c[0] }}</div>
                        <div class="fs-4 fw-bold" style="color: {{ $c[2] }};">{{ $c[1] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-pills mb-3">
        @foreach ([
            'pending'    => 'Pending',
            'accepted'   => 'Accepted',
            'billed'     => 'Billed',
            'scheduled'  => 'Scheduled',
            'in_theatre' => 'In Theatre',
            'recovery'   => 'Recovery',
            'completed'  => 'Completed',
            'closed'     => 'Cancelled / Rejected',
        ] as $key => $label)
            <li class="nav-item">
                <a class="nav-link {{ $tab===$key?'active':'' }}" href="{{ route('admin.theatre.index', ['tab'=>$key]) }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Request</th>
                        <th>Patient</th>
                        <th>Service</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Schedule</th>
                        <th>Surgeon</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $r)
                        <tr>
                            <td>{{ $loop->iteration + ($requests->firstItem() - 1) }}</td>
                            <td>
                                <a href="{{ route('admin.theatre.show', $r) }}"><strong>{{ $r->request_number }}</strong></a><br>
                                <small class="text-muted">{{ optional($r->requested_at)->format('d M Y H:i') }}</small>
                            </td>
                            <td>
                                {{ $r->patient?->first_name }} {{ $r->patient?->last_name }}<br>
                                <small class="text-muted">{{ $r->patient?->patient_number }} · Visit {{ $r->visit?->visit_number }}</small>
                            </td>
                            <td>
                                {{ $r->service?->name ?? '-' }}<br>
                                <small class="text-muted">{{ $r->department?->name }}</small>
                            </td>
                            <td>
                                @php $pColor = ['emergency'=>'danger','urgent'=>'warning','routine'=>'secondary'][$r->priority] ?? 'secondary'; @endphp
                                <span class="badge bg-{{ $pColor }}">{{ ucfirst($r->priority) }}</span>
                            </td>
                            <td>
                                <span class="badge" style="background-color: {{ $r->status->color() }}; color:#fff;">
                                    {{ $r->status->label() }}
                                </span>
                            </td>
                            <td>
                                @if ($s = $r->schedule)
                                    {{ optional($s->scheduled_start)->format('d M H:i') }}<br>
                                    <small class="text-muted">{{ $s->theatreRoom?->name }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $r->schedule?->surgeon?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.theatre.show', $r) }}">Open</a>
                                @if ($r->status === \App\Enums\ProcedureStatus::COMPLETED)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.theatre.report', $r) }}" target="_blank">Report</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No procedure requests in this queue.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $requests->links() }}</div>
    </div>
</div>
@endsection
