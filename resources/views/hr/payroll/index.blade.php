@extends('layouts.app')
@section('title', 'Payroll')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Payroll — {{ \Carbon\Carbon::parse($payPeriod . '-01')->format('F Y') }}</h4>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" action="{{ route('admin.hr.payroll.index') }}" class="d-flex gap-2">
            <input type="month" name="pay_period" class="form-control" value="{{ $payPeriod }}" onchange="this.form.submit()">
            <select name="status" class="form-select" style="width:130px;" onchange="this.form.submit()">
                <option value="">All Status</option>
                @foreach($statuses as $s)
                    <option value="{{ $s->value }}" {{ request('status') == $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<!-- Summary Cards -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary bg-opacity-10 rounded me-3">
                        <i class="ti ti-users fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $summary['total_employees'] }}</h4>
                        <small class="text-muted">Employees</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-info bg-opacity-10 rounded me-3">
                        <i class="ti ti-cash fs-4 text-info"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">GH₵ {{ number_format($summary['total_gross'], 2) }}</h4>
                        <small class="text-muted">Total Gross</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-danger bg-opacity-10 rounded me-3">
                        <i class="ti ti-minus fs-4 text-danger"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">GH₵ {{ number_format($summary['total_ssnit_employee'] + $summary['total_tax'] + $summary['total_deductions'], 2) }}</h4>
                        <small class="text-muted">Total Deductions</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-success bg-opacity-10 rounded me-3">
                        <i class="ti ti-wallet fs-4 text-success"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">GH₵ {{ number_format($summary['total_net'], 2) }}</h4>
                        <small class="text-muted">Total Net Pay</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
@can('hr.payroll.process')
<div class="card mb-3">
    <div class="card-body d-flex gap-2 align-items-center py-2">
        <form method="POST" action="{{ route('admin.hr.payroll.process') }}" class="d-flex gap-2 align-items-center">
            @csrf
            <input type="hidden" name="pay_period" value="{{ $payPeriod }}">
            <div class="d-flex gap-2 align-items-center">
                <label class="form-label mb-0 text-nowrap">Allowances:</label>
                <input type="number" name="allowances" class="form-control form-control-sm" style="width:120px;" step="0.01" value="0">
                <label class="form-label mb-0 text-nowrap ms-2">Other Ded.:</label>
                <input type="number" name="other_deductions" class="form-control form-control-sm" style="width:120px;" step="0.01" value="0">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-calculator me-1"></i>Process Payroll</button>
        </form>
        <form method="POST" action="{{ route('admin.hr.payroll.approve') }}" class="d-inline">
            @csrf
            <input type="hidden" name="pay_period" value="{{ $payPeriod }}">
            <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Approve All</button>
        </form>
        <form method="POST" action="{{ route('admin.hr.payroll.mark-paid') }}" class="d-inline">
            @csrf
            <input type="hidden" name="pay_period" value="{{ $payPeriod }}">
            <button type="submit" class="btn btn-success btn-sm"><i class="ti ti-coin me-1"></i>Mark Paid</button>
        </form>
    </div>
</div>
@endcan

<!-- Payroll Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th class="text-end">Basic</th>
                        <th class="text-end">Allowances</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">SSNIT (5.5%)</th>
                        <th class="text-end">Tax</th>
                        <th class="text-end">Other Ded.</th>
                        <th class="text-end">Net Pay</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payroll as $rec)
                    <tr>
                        <td class="fw-medium">{{ $rec->employee->full_name }}</td>
                        <td>{{ $rec->employee->department?->name ?? '-' }}</td>
                        <td class="text-end">{{ number_format($rec->basic_salary, 2) }}</td>
                        <td class="text-end">{{ number_format($rec->allowances, 2) }}</td>
                        <td class="text-end">{{ number_format($rec->gross_pay, 2) }}</td>
                        <td class="text-end text-danger">{{ number_format($rec->ssnit_employee, 2) }}</td>
                        <td class="text-end text-danger">{{ number_format($rec->tax, 2) }}</td>
                        <td class="text-end text-danger">{{ number_format($rec->other_deductions, 2) }}</td>
                        <td class="text-end fw-bold">{{ number_format($rec->net_pay, 2) }}</td>
                        <td><span class="badge bg-{{ $rec->status->color() }}">{{ $rec->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('admin.hr.payroll.payslip', $rec) }}" class="btn btn-sm btn-outline-info" title="Payslip"><i class="ti ti-file-text"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">No payroll records for this period. Click "Process Payroll" to generate.</td></tr>
                    @endforelse
                </tbody>
                @if($payroll->count() > 0)
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="2" class="text-end">Totals:</td>
                        <td class="text-end">{{ number_format($summary['total_basic'], 2) }}</td>
                        <td class="text-end">{{ number_format($summary['total_allowances'], 2) }}</td>
                        <td class="text-end">{{ number_format($summary['total_gross'], 2) }}</td>
                        <td class="text-end text-danger">{{ number_format($summary['total_ssnit_employee'], 2) }}</td>
                        <td class="text-end text-danger">{{ number_format($summary['total_tax'], 2) }}</td>
                        <td class="text-end text-danger">{{ number_format($summary['total_deductions'], 2) }}</td>
                        <td class="text-end">{{ number_format($summary['total_net'], 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $payroll->withQueryString()->links() }}</div>

<!-- Employer SSNIT Note -->
@if($summary['total_ssnit_employer'] > 0)
<div class="alert alert-info mt-3">
    <i class="ti ti-info-circle me-1"></i>
    <strong>Employer SSNIT Contribution (13%):</strong> GH₵ {{ number_format($summary['total_ssnit_employer'], 2) }}
    — This is not deducted from employees but payable by the organization to SSNIT.
</div>
@endif
@endsection
