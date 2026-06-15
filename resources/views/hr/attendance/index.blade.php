@extends('layouts.app')
@section('title', __('hr.staff_attendance'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('hr.staff_attendance') }}</h4>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" action="{{ route('admin.hr.attendance.index') }}" class="d-flex gap-2">
            <select name="employee_id" class="form-select" style="width:180px;" onchange="this.form.submit()">
                <option value="">{{ __('hr.all_employees') }}</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" style="width:140px;">
            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" style="width:140px;">
            <button aria-label="{{ __('common.filter') }}" title="{{ __('common.filter') }}" type="submit" class="btn btn-outline-primary"><i class="ti ti-filter"></i></button>
        </form>
        <a href="{{ route('admin.hr.attendance.summary') }}" class="btn btn-outline-info"><i class="ti ti-chart-bar me-1"></i>{{ __('hr.summary') }}</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row">
    <!-- Record Attendance -->
    @can('hr.attendance.manage')
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-clock-record me-1"></i>{{ __('hr.record_attendance') }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.hr.attendance.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('hr.employee') }} <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">{{ __('hr.select_employee') }}</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('hr.date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">{{ __('hr.clock_in') }}</label>
                            <input type="time" name="clock_in" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">{{ __('hr.clock_out') }}</label>
                            <input type="time" name="clock_out" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('hr.status') }} <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="present">{{ __('hr.present') }}</option>
                            <option value="absent">{{ __('hr.absent') }}</option>
                            <option value="late">{{ __('hr.late') }}</option>
                            <option value="half_day">{{ __('hr.half_day') }}</option>
                            <option value="holiday">{{ __('hr.holiday') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('hr.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="ti ti-check me-1"></i>{{ __('stock.save') }}</button>
                </form>
            </div>
        </div>
    </div>
    @endcan

    <!-- Attendance Records -->
    <div class="{{ auth()->user()->can('hr.attendance.manage') ? 'col-lg-8' : 'col-12' }}">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('hr.date') }}</th>
                                <th>{{ __('hr.employee') }}</th>
                                <th>{{ __('hr.clock_in') }}</th>
                                <th>{{ __('hr.clock_out') }}</th>
                                <th>{{ __('hr.hours') }}</th>
                                <th>{{ __('hr.status') }}</th>
                                <th>{{ __('hr.review_status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendance as $rec)
                            <tr>
                                <td>{{ $rec->date->format('d M Y') }}</td>
                                <td class="fw-medium">{{ $rec->employee->full_name }}</td>
                                <td>{{ $rec->clock_in ?? '-' }}</td>
                                <td>{{ $rec->clock_out ?? '-' }}</td>
                                <td>{{ $rec->hours_worked ? number_format($rec->hours_worked, 1) . ' ' . __('hr.hrs') : '-' }}</td>
                                <td>
                                    @php
                                        $statusColors = ['present' => 'success', 'absent' => 'danger', 'late' => 'warning', 'half_day' => 'info', 'holiday' => 'secondary'];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$rec->status] ?? 'secondary' }}">{{ __('hr.' . $rec->status) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $rec->review_status === 'approved' ? 'success' : 'warning' }}">{{ __('hr.'.$rec->review_status) }}</span>
                                    @can('hr.attendance.approve')
                                    @if($rec->review_status !== 'approved')
                                    <form method="POST" action="{{ route('admin.hr.attendance.approve', $rec) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success" title="{{ __('hr.approve') }}"><i class="ti ti-check"></i></button></form>
                                    @endif
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7"><x-empty-state :message="__('hr.no_attendance_records')" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="mt-3">{{ $attendance->withQueryString()->links() }}</div>
    </div>
</div>
@endsection
