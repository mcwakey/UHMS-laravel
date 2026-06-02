@extends('layouts.app')

@section('title', 'Theatre / Procedure Workflow')

@section('content')
<div class="container-fluid">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">Theatre Schedule Board</h3>
            <div class="text-muted small">Procedure requests, room assignments, billing state, and theatre workflow status.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.theatre.calendar') }}">
                <i class="ti ti-calendar"></i> Calendar
            </a>
            @can('theatre.rooms.view')
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.theatre.rooms.index') }}">
                    <i class="ti ti-door"></i> Rooms
                </a>
            @endcan
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
        <form action="{{ route('admin.theatre.index') }}" method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="col-md-3">
                <label class="form-label small">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Request #, patient, visit" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Priority</label>
                <select name="priority" class="form-select form-select-sm">
                    <option value="">Any priority</option>
                    @foreach (['routine','urgent','emergency'] as $p)
                        <option value="{{ $p }}" @selected(request('priority')===$p)>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Room</label>
                <select name="room_id" class="form-select form-select-sm">
                    <option value="">Any room</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected((int) request('room_id') === (int) $room->id)>{{ $room->code }} - {{ $room->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Scheduled date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-sm btn-primary flex-fill">Filter</button>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.theatre.index', ['tab' => $tab]) }}">Reset</a>
            </div>
        </form>
        </div>
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
                                    <a data-no-inertia class="btn btn-sm btn-outline-secondary" href="{{ route('admin.theatre.report', $r) }}" target="_blank">Report</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><x-empty-state message="No procedure requests in this queue." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $requests->links() }}</div>
    </div>
</div>
@endsection
