@extends('layouts.app')
@section('title', 'Attendance Summary')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('hr.attendance_summary') }}</h4>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" action="{{ route('admin.hr.attendance.summary') }}" class="d-flex gap-2">
            <input type="month" name="month" class="form-control" value="{{ $month }}" onchange="this.form.submit()">
        </form>
        <a href="{{ route('admin.hr.attendance.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('stock.back') }}</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('hr.employee') }}</th>
                        <th>{{ __('hr.department') }}</th>
                        <th class="text-center">{{ __('hr.working_days') }}</th>
                        <th class="text-center text-success">{{ __('hr.present') }}</th>
                        <th class="text-center text-danger">{{ __('hr.absent') }}</th>
                        <th class="text-center text-warning">{{ __('hr.late') }}</th>
                        <th class="text-center text-info">{{ __('hr.half_day') }}</th>
                        <th class="text-end">{{ __('hr.total_hours') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $emp)
                    @php $s = $summaries[$emp->id]; @endphp
                    <tr>
                        <td class="fw-medium">{{ $emp->full_name }}</td>
                        <td>{{ $emp->department?->name ?? '-' }}</td>
                        <td class="text-center">{{ $s['working_days'] }}</td>
                        <td class="text-center"><span class="badge bg-success">{{ $s['present'] }}</span></td>
                        <td class="text-center"><span class="badge bg-danger">{{ $s['absent'] }}</span></td>
                        <td class="text-center"><span class="badge bg-warning">{{ $s['late'] }}</span></td>
                        <td class="text-center"><span class="badge bg-info">{{ $s['half_day'] }}</span></td>
                        <td class="text-end">{{ number_format($s['total_hours'], 1) }} hrs</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
