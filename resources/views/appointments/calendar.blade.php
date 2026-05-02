@extends('layouts.app')

@section('title', 'Appointment Calendar')

@section('content')
<div class="content">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <h3 class="page-title">Appointment Calendar</h3>
            </div>
            <div class="col-sm-6 text-sm-end">
                <a href="{{ route('admin.appointments.index') }}" class="btn btn-outline-secondary me-2">
                    <i class="ti ti-list me-1"></i> List View
                </a>
                @can('appointments.create')
                <a href="{{ route('admin.appointments.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> New Appointment
                </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.appointments.calendar') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Week Starting</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}" id="weekStart">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Doctor</label>
                    <select name="doctor_id" class="form-select">
                        <option value="">All Doctors</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" {{ request('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                Dr. {{ $doctor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="ti ti-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.appointments.calendar') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-x me-1"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Week Navigation --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        @php
            $prevWeek = \Carbon\Carbon::parse($from)->subWeek()->toDateString();
            $nextWeek = \Carbon\Carbon::parse($from)->addWeek()->toDateString();
            $todayWeek = now()->startOfWeek()->toDateString();
        @endphp
        <a href="{{ route('admin.appointments.calendar', array_merge(request()->except('from'), ['from' => $prevWeek])) }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-chevron-left me-1"></i> Previous Week
        </a>
        <div>
            <h5 class="mb-0">
                {{ \Carbon\Carbon::parse($from)->format('d M') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
            </h5>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.appointments.calendar', array_merge(request()->except('from'), ['from' => $todayWeek])) }}" class="btn btn-outline-primary btn-sm">
                Today
            </a>
            <a href="{{ route('admin.appointments.calendar', array_merge(request()->except('from'), ['from' => $nextWeek])) }}" class="btn btn-outline-secondary btn-sm">
                Next Week <i class="ti ti-chevron-right ms-1"></i>
            </a>
        </div>
    </div>

    {{-- Calendar Grid --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            @for($i = 0; $i < 7; $i++)
                                @php
                                    $day = \Carbon\Carbon::parse($from)->addDays($i);
                                    $isToday = $day->isToday();
                                @endphp
                                <th class="text-center {{ $isToday ? 'bg-primary bg-opacity-10' : '' }}" style="width: 14.28%; min-width: 150px;">
                                    <div class="{{ $isToday ? 'text-primary fw-bold' : '' }}">
                                        {{ $day->format('l') }}
                                    </div>
                                    <div class="{{ $isToday ? 'text-primary' : 'text-muted' }}">
                                        {{ $day->format('d M') }}
                                    </div>
                                </th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @for($i = 0; $i < 7; $i++)
                                @php
                                    $day = \Carbon\Carbon::parse($from)->addDays($i);
                                    $dayKey = $day->format('Y-m-d');
                                    $dayAppointments = $calendarData[$dayKey] ?? collect();
                                    $isToday = $day->isToday();
                                @endphp
                                <td class="align-top p-2 {{ $isToday ? 'bg-primary bg-opacity-10' : '' }}" style="min-height: 200px; vertical-align: top;">
                                    @forelse($dayAppointments as $apt)
                                    <a href="{{ route('admin.appointments.show', $apt) }}" class="card mb-2 border-start border-3 border-{{ $apt->status->color() }} text-decoration-none text-reset d-block">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <small class="fw-medium text-truncate" style="max-width: 120px;">
                                                    {{ $apt->patient->first_name }} {{ $apt->patient->last_name }}
                                                </small>
                                                <span class="badge bg-{{ $apt->status->color() }}" style="font-size: 0.65rem;">
                                                    {{ $apt->status->label() }}
                                                </span>
                                            </div>
                                            <small class="text-muted d-block">
                                                <i class="ti ti-clock" style="font-size: 0.7rem;"></i>
                                                {{ \Carbon\Carbon::parse($apt->start_time)->format('h:i A') }}
                                            </small>
                                            @if($apt->doctor)
                                            <small class="text-muted d-block text-truncate">
                                                <i class="ti ti-stethoscope" style="font-size: 0.7rem;"></i>
                                                Dr. {{ $apt->doctor->name }}
                                            </small>
                                            @endif
                                        </div>
                                    </a>
                                    @empty
                                    <div class="text-center text-muted py-4">
                                        <small>No appointments</small>
                                    </div>
                                    @endforelse

                                    @can('appointments.create')
                                    @if($day->gte(today()))
                                    <a href="{{ route('admin.appointments.create', ['date' => $dayKey]) }}" class="btn btn-outline-primary btn-sm w-100 mt-1">
                                        <i class="ti ti-plus" style="font-size: 0.7rem;"></i>
                                    </a>
                                    @endif
                                    @endcan
                                </td>
                            @endfor
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div class="card mt-3">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <small class="text-muted fw-medium">Status Legend:</small>
                @foreach(\App\Enums\AppointmentStatus::cases() as $status)
                <span class="badge bg-{{ $status->color() }}">{{ $status->label() }}</span>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
