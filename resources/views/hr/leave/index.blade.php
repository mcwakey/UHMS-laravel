@extends('layouts.app')
@section('title', __('hr.leave_requests'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('hr.leave_requests') }}
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">{{ __('hr.total') }}: {{ $leaves->total() }}</span>
        </h4>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" action="{{ route('admin.hr.leave.index') }}" class="d-flex gap-2">
            <select name="status" class="form-select" style="width:130px;" onchange="this.form.submit()">
                <option value="">{{ __('hr.all_status') }}</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>{{ $status->translatedLabel() }}</option>
                @endforeach
            </select>
            <select name="leave_type" class="form-select" style="width:150px;" onchange="this.form.submit()">
                <option value="">{{ __('hr.all_types') }}</option>
                @foreach($leaveTypes as $type)
                    <option value="{{ $type->value }}" {{ request('leave_type') == $type->value ? 'selected' : '' }}>{{ $type->translatedLabel() }}</option>
                @endforeach
            </select>
            <select name="employee_id" class="form-select" style="width:180px;" onchange="this.form.submit()">
                <option value="">{{ __('hr.all_employees') }}</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }}</option>
                @endforeach
            </select>
        </form>
        @can('hr.leave.create')
        <a href="{{ route('admin.hr.leave.create') }}" class="btn btn-primary btn-md fs-13">
            <i class="ti ti-plus me-1"></i>{{ __('hr.new_request') }}
        </a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('hr.employee') }}</th>
                        <th>{{ __('hr.leave_type') }}</th>
                        <th>{{ __('hr.period') }}</th>
                        <th class="text-center">{{ __('hr.days') }}</th>
                        <th>{{ __('hr.reason') }}</th>
                        <th>{{ __('hr.status') }}</th>
                        <th class="text-end">{{ __('hr.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaves as $leave)
                    <tr>
                        <td class="fw-medium">{{ $leave->employee->full_name }}</td>
                        <td><x-status-badge :status="$leave->leave_type" /></td>
                        <td>{{ $leave->start_date->format('d M') }} — {{ $leave->end_date->format('d M Y') }}</td>
                        <td class="text-center">{{ $leave->days }}</td>
                        <td>{{ Str::limit($leave->reason, 40) ?? '-' }}</td>
                        <td><x-status-badge :status="$leave->status" /></td>
                        <td class="text-end">
                            @if($leave->status->value === 'pending')
                                @can('hr.leave.approve')
                                <form method="POST" action="{{ route('admin.hr.leave.approve', $leave) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="{{ __('hr.approve') }}"><i class="ti ti-check"></i></button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-danger" title="{{ __('hr.reject') }}" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $leave->id }}"><i class="ti ti-x"></i></button>

                                <!-- Reject Modal -->
                                <div class="modal fade" id="rejectModal{{ $leave->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('admin.hr.leave.reject', $leave) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">{{ __('hr.reject_leave') }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>{{ __('hr.rejecting_leave_for', ['employee' => $leave->employee->full_name, 'type' => $leave->leave_type->translatedLabel(), 'days' => $leave->days]) }}</p>
                                                    <div class="mb-3">
                                                        <label class="form-label">{{ __('hr.rejection_reason') }} <span class="text-danger">*</span></label>
                                                        <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                                                    <button type="submit" class="btn btn-danger">{{ __('hr.reject') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endcan
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7"><x-empty-state :message="__('hr.no_leave_requests')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $leaves->withQueryString()->links() }}</div>
@endsection
