@extends('layouts.app')
@section('title', 'Employee - ' . $employee->full_name)

@section('content')
<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ $employee->full_name }}
            <x-status-badge :status="$employee->status" class="ms-2" />
        </h4>
        <small class="text-muted">{{ $employee->employee_number }} &bull; {{ $employee->position }}</small>
    </div>
    <div class="d-flex gap-2">
        @can('hr.employees.edit')
        <a href="{{ route('admin.hr.employees.edit', $employee) }}" class="btn btn-outline-primary"><i class="ti ti-edit me-1"></i>{{ __('hr.edit') }}</a>
        @endcan
        <a href="{{ route('admin.hr.employees.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('stock.back') }}</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('hr.personal_information') }}</h5></div>
            <div class="card-body">
                <div class="table-responsive"><table class="table table-borderless table-sm mb-0">
                    <tr><td class="text-muted" width="40%">{{ __('hr.gender') }}</td><td>{{ $employee->gender?->translatedLabel() ?? '-' }}</td></tr>
                    <tr><td class="text-muted">{{ __('hr.date_of_birth') }}</td><td>{{ $employee->date_of_birth?->format('d M Y') ?? '-' }}</td></tr>
                    <tr><td class="text-muted">{{ __('hr.phone') }}</td><td>{{ $employee->phone }}</td></tr>
                    <tr><td class="text-muted">{{ __('hr.email') }}</td><td>{{ $employee->email ?? '-' }}</td></tr>
                    <tr><td class="text-muted">{{ __('hr.address') }}</td><td>{{ $employee->address ?? '-' }}</td></tr>
                </table></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('hr.emergency_contact') }}</h5></div>
            <div class="card-body">
                <div class="table-responsive"><table class="table table-borderless table-sm mb-0">
                    <tr><td class="text-muted" width="40%">{{ __('hr.name') }}</td><td>{{ $employee->emergency_contact_name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">{{ __('hr.phone') }}</td><td>{{ $employee->emergency_contact_phone ?? '-' }}</td></tr>
                </table></div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('hr.employment_details') }}</h5></div>
            <div class="card-body">
                <div class="table-responsive"><table class="table table-borderless table-sm mb-0">
                    <tr><td class="text-muted" width="40%">{{ __('hr.department') }}</td><td>{{ $employee->department?->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">{{ __('hr.position') }}</td><td>{{ $employee->position }}</td></tr>
                    <tr><td class="text-muted">{{ __('hr.hire_date') }}</td><td>{{ $employee->hire_date->format('d M Y') }}</td></tr>
                    <tr><td class="text-muted">{{ __('hr.basic_salary') }}</td><td>GH₵ {{ number_format($employee->basic_salary, 2) }}</td></tr>
                    <tr><td class="text-muted">{{ __('hr.system_user') }}</td><td>{{ $employee->user?->name ?? __('hr.not_linked') }}</td></tr>
                </table></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('hr.bank_tax') }}</h5></div>
            <div class="card-body">
                <div class="table-responsive"><table class="table table-borderless table-sm mb-0">
                    <tr><td class="text-muted" width="40%">{{ __('hr.bank') }}</td><td>{{ $employee->bank_name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">{{ __('hr.account_number') }}</td><td>{{ $employee->bank_account ?? '-' }}</td></tr>
                    <tr><td class="text-muted">{{ __('hr.branch') }}</td><td>{{ $employee->bank_branch ?? '-' }}</td></tr>
                    <tr><td class="text-muted">{{ __('hr.ssnit_number') }}</td><td>{{ $employee->ssnit_number ?? '-' }}</td></tr>
                    <tr><td class="text-muted">{{ __('hr.tin_number') }}</td><td>{{ $employee->tin_number ?? '-' }}</td></tr>
                </table></div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-primary">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-calendar-off me-1"></i>{{ __('hr.leave_balance') }} ({{ now()->year }})</h5></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>{{ __('hr.annual_allocation') }}</span>
                    <strong>{{ $leaveBalance['annual_allocation'] }} days</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>{{ __('hr.used') }}</span>
                    <strong class="text-danger">{{ $leaveBalance['used'] }} days</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>{{ __('hr.remaining') }}</span>
                    <strong class="text-success">{{ $leaveBalance['remaining'] }} days</strong>
                </div>
                <div class="progress mt-3" style="height: 8px;">
                    @php $pct = $leaveBalance['annual_allocation'] > 0 ? ($leaveBalance['used'] / $leaveBalance['annual_allocation']) * 100 : 0; @endphp
                    <div class="progress-bar bg-{{ $pct > 80 ? 'danger' : ($pct > 50 ? 'warning' : 'success') }}" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('hr.recent_leave_requests') }}</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @forelse($employee->leaveRequests as $leave)
                            <tr>
                                <td><x-status-badge :status="$leave->leave_type" /></td>
                                <td>{{ $leave->start_date->format('d M') }} - {{ $leave->end_date->format('d M') }}</td>
                                <td><x-status-badge :status="$leave->status" /></td>
                            </tr>
                            @empty
                            <tr><td class="text-center text-muted py-3">{{ __('hr.no_leave_requests_short') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
