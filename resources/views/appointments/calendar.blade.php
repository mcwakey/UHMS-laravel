@extends('layouts.app')

@section('title', __('appointments.calendar_title'))

@section('content')
<x-page-header :title="__('appointments.calendar_title')" icon="ti-calendar-event">
    <x-slot:actions>
        @include('appointments.partials.view-switch', ['active' => 'calendar'])
        @can('appointments.create')
        <a href="{{ route('admin.appointments.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i> {{ __('appointments.new_appointment') }}
        </a>
        @endcan
    </x-slot:actions>
</x-page-header>

    <!-- <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <h3 class="page-title">{{ __('appointments.calendar_title') }}</h3>
            </div>
            <div class="col-sm-6">
                <div class="d-flex justify-content-sm-end align-items-center gap-2 flex-wrap">
                @include('appointments.partials.view-switch', ['active' => 'calendar'])
                @can('appointments.create')
                <a href="{{ route('admin.appointments.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> {{ __('appointments.new_appointment') }}
                </a>
                @endcan
                </div>
            </div>
        </div>
    </div> -->

    {{-- Filters --}}
    @php
        $defaultRangeStart = today()->subDays(3)->toDateString();
        $defaultRangeEnd = today()->addDays(10)->toDateString();
        $activeDateRange = ($from ?? $defaultRangeStart).' to '.($to ?? $defaultRangeEnd);
    @endphp
    <x-filter-bar
        :action="route('admin.appointments.calendar')"
        :reset-url="route('admin.appointments.calendar')"
        class="mb-2"
        :show-apply="false"
        row-class="row g-3 align-items-end"
    >
        <div class="col-md-3">
            <label class="form-label small">{{ __('common.search') }}</label>
            <input type="text" name="search" class="form-control" placeholder="{{ __('appointments.search_placeholder') }}" value="{{ $filters['search'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small">{{ __('common.status') }}</label>
            <select name="status" class="form-select">
                <option value="">{{ __('common.all_statuses') }}</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}" {{ ($filters['status'] ?? '') == $status->value ? 'selected' : '' }}>
                        {{ $status->translatedLabel() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">{{ __('common.doctor') }}</label>
            <select name="doctor_id" class="form-select">
                <option value="">{{ __('appointments.all_doctors') }}</option>
                @foreach($doctors as $doctor)
                    <option value="{{ $doctor->id }}" {{ ($filters['doctor_id'] ?? '') == $doctor->id ? 'selected' : '' }}>
                        Dr. {{ $doctor->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">{{ __('common.department') }}</label>
            <select name="department_id" class="form-select">
                <option value="">{{ __('common.all_departments') }}</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            @include('partials.date-range-filter', [
                'id' => 'appointmentCalendarDateRangePicker',
                'value' => $activeDateRange,
                'submitOnApply' => true,
            ])
        </div>
        <x-slot:actions>
            <a href="{{ route('admin.appointments.calendar') }}" class="btn btn-outline-secondary btn-icon" aria-label="{{ __('common.reset') }}" title="{{ __('common.reset') }}">
                <i class="ti ti-x"></i>
            </a>
        </x-slot:actions>
    </x-filter-bar>

    {{-- Range Navigation --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        @php
            $rangeStart = \Carbon\Carbon::parse($from);
            $rangeEnd = \Carbon\Carbon::parse($to);
            $rangeDays = max(1, (int) $rangeStart->diffInDays($rangeEnd) + 1);
            $prevStart = $rangeStart->copy()->subDays($rangeDays);
            $prevEnd = $rangeEnd->copy()->subDays($rangeDays);
            $nextStart = $rangeStart->copy()->addDays($rangeDays);
            $nextEnd = $rangeEnd->copy()->addDays($rangeDays);
            $rangeQuery = request()->except(['date_range', 'date_from', 'date_to', 'from', 'to']);
            $defaultRangeQuery = array_merge($rangeQuery, ['date_range' => $defaultRangeStart.' to '.$defaultRangeEnd]);
        @endphp
        <a href="{{ route('admin.appointments.calendar', array_merge($rangeQuery, ['date_range' => $prevStart->toDateString().' to '.$prevEnd->toDateString()])) }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-chevron-left me-1"></i> {{ __('appointments.previous_range') }}
        </a>
        <div>
            <h5 class="mb-0">
                {{ $rangeStart->format('d M') }} - {{ $rangeEnd->format('d M Y') }}
            </h5>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.appointments.calendar', $defaultRangeQuery) }}" class="btn btn-outline-primary btn-sm">
                {{ __('appointments.default_range') }}
            </a>
            <a href="{{ route('admin.appointments.calendar', array_merge($rangeQuery, ['date_range' => $nextStart->toDateString().' to '.$nextEnd->toDateString()])) }}" class="btn btn-outline-secondary btn-sm">
                {{ __('appointments.next_range') }} <i class="ti ti-chevron-right ms-1"></i>
            </a>
        </div>
    </div>

    {{-- Calendar Grid --}}
    @php
        $calendarDays = collect(iterator_to_array(\Carbon\CarbonPeriod::create($from, $to)))
            ->map(fn ($day) => $day->copy());
        $calendarWeeks = $calendarDays->chunk(7)->values();
    @endphp

    @foreach($calendarWeeks as $weekIndex => $calendarWeek)
    @php
        $weekStart = $calendarWeek->first()->copy();
        $weekEnd = $calendarWeek->last()->copy();
        $columnWidth = round(100 / max(1, $calendarWeek->count()), 2);
    @endphp
    <div class="card uhms-calendar-week {{ $weekIndex > 0 ? 'mt-3' : '' }}">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">{{ $calendarWeeks->count() > 1 ? __('appointments.range_label', ['number' => $weekIndex + 1]) : __('appointments.selected_range') }}</h5>
            <span class="text-muted fw-medium">{{ $weekStart->format('d M') }} - {{ $weekEnd->format('d M Y') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            @foreach($calendarWeek as $day)
                                @php
                                    $isToday = $day->isToday();
                                @endphp
                                <th class="text-center {{ $isToday ? 'bg-primary bg-opacity-10' : '' }}" style="width: {{ $columnWidth }}%; min-width: 150px;">
                                    <div class="{{ $isToday ? 'text-primary fw-bold' : '' }}">
                                        {{ $day->format('l') }}
                                    </div>
                                    <div class="{{ $isToday ? 'text-primary' : 'text-muted' }}">
                                        {{ $day->format('d M') }}
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @foreach($calendarWeek as $day)
                                @php
                                    $dayKey = $day->format('Y-m-d');
                                    $dayAppointments = $calendarData[$dayKey] ?? collect();
                                    $isToday = $day->isToday();
                                @endphp
                                <td class="calendar-day-cell align-top p-2 {{ $isToday ? 'bg-primary bg-opacity-10' : '' }}">
                                    @forelse($dayAppointments as $apt)
                                    <a href="{{ route('admin.appointments.show', $apt) }}" class="appointment-card card mb-2 border-start border-3 border-{{ $apt->status->color() }} text-decoration-none text-reset d-block">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <small class="fw-medium text-truncate" style="max-width: 120px;">
                                                    {{ $apt->patient->first_name }} {{ $apt->patient->last_name }}
                                                </small>
                                                <span class="badge bg-{{ $apt->status->color() }}" style="font-size: 0.65rem;">
                                                    {{ $apt->status->translatedLabel() }}
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
                                        <small>{{ __('appointments.no_appointments') }}</small>
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
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach

    {{-- Legend --}}
    <div class="card mt-3">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <small class="text-muted fw-medium">{{ __('appointments.status_legend') }}</small>
                @foreach(\App\Enums\AppointmentStatus::cases() as $status)
                <x-status-badge :status="$status" />
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@include('partials.date-range-filter-scripts')
@endpush
