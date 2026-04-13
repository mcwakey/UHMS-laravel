@extends('layouts.app')
@section('title', 'Payroll Report')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Payroll Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Payroll Report</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.payroll', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
            <i class="ti ti-file-spreadsheet me-1"></i>Excel
        </a>
        <a href="{{ route('admin.reports.payroll', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger btn-sm">
            <i class="ti ti-file-type-pdf me-1"></i>PDF
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Total Records</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['total_records']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Total Gross Pay</p>
                <h4 class="fw-bold mb-0 text-success">₵{{ number_format($stats['total_gross'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-danger border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Total Deductions</p>
                <h4 class="fw-bold mb-0 text-danger">₵{{ number_format($stats['total_deductions'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Total Net Pay</p>
                <h4 class="fw-bold mb-0 text-info">₵{{ number_format($stats['total_net'], 2) }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Department Breakdown -->
@if($byDepartment->count())
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Pay by Department</h6></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr><th>Department</th><th class="text-end">Staff</th><th class="text-end">Gross</th><th class="text-end">Deductions</th><th class="text-end">Net</th></tr>
            </thead>
            <tbody>
                @foreach($byDepartment as $dept)
                <tr>
                    <td>{{ $dept->name }}</td>
                    <td class="text-end">{{ number_format($dept->staff_count) }}</td>
                    <td class="text-end">₵{{ number_format($dept->total_gross, 2) }}</td>
                    <td class="text-end text-danger">₵{{ number_format($dept->total_deductions, 2) }}</td>
                    <td class="text-end fw-semibold">₵{{ number_format($dept->total_net, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.payroll') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Pay Period</label>
                <input type="month" name="pay_period" class="form-control" value="{{ $filters['pay_period'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(\App\Enums\PayrollStatus::cases() as $ps)
                    <option value="{{ $ps->value }}" {{ ($filters['status'] ?? '') == $ps->value ? 'selected' : '' }}>{{ $ps->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.reports.payroll') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Pay Period</th>
                    <th class="text-end">Basic</th>
                    <th class="text-end">Allowances</th>
                    <th class="text-end">Deductions</th>
                    <th class="text-end">Net Pay</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                <tr>
                    <td>{{ $rec->employee?->user?->name ?? '—' }}</td>
                    <td>{{ $rec->employee?->department?->name ?? '—' }}</td>
                    <td>{{ $rec->pay_period }}</td>
                    <td class="text-end">₵{{ number_format($rec->basic_salary, 2) }}</td>
                    <td class="text-end">₵{{ number_format($rec->total_allowances, 2) }}</td>
                    <td class="text-end text-danger">₵{{ number_format($rec->total_deductions, 2) }}</td>
                    <td class="text-end fw-semibold">₵{{ number_format($rec->net_salary, 2) }}</td>
                    <td>
                        @php
                            $statusColors = ['pending' => 'warning', 'processed' => 'info', 'paid' => 'success'];
                            $sv = $rec->status instanceof \App\Enums\PayrollStatus ? $rec->status->value : $rec->status;
                        @endphp
                        <span class="badge bg-{{ $statusColors[$sv] ?? 'secondary' }}">{{ $rec->status instanceof \App\Enums\PayrollStatus ? $rec->status->label() : ucfirst($sv) }}</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No payroll records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
    <div class="card-footer">{{ $records->links() }}</div>
    @endif
</div>
@endsection
