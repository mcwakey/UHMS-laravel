@extends('layouts.app')
@section('title', 'Payslip - ' . $record->employee->full_name)

@section('content')
<div class="d-flex align-items-center mb-3 pb-3 border-bottom d-print-none">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Payslip</h4>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-primary"><i class="ti ti-printer me-1"></i>Print</button>
        <a href="{{ route('admin.hr.payroll.index', ['pay_period' => $record->pay_period]) }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <!-- Header -->
        <div class="text-center mb-4">
            <h3 class="mb-1">{{ config('app.name', 'UHMS') }}</h3>
            <h5 class="text-muted">Employee Payslip</h5>
            <p class="mb-0">Pay Period: <strong>{{ \Carbon\Carbon::parse($record->pay_period . '-01')->format('F Y') }}</strong></p>
        </div>

        <hr>

        <!-- Employee Info -->
        <div class="row mb-4">
            <div class="col-md-6">
                <table class="table table-borderless table-sm mb-0">
                    <tr><td class="text-muted" width="40%">Employee Name</td><td class="fw-bold">{{ $record->employee->full_name }}</td></tr>
                    <tr><td class="text-muted">Employee #</td><td>{{ $record->employee->employee_number }}</td></tr>
                    <tr><td class="text-muted">Department</td><td>{{ $record->employee->department?->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Position</td><td>{{ $record->employee->position }}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless table-sm mb-0">
                    <tr><td class="text-muted" width="40%">SSNIT #</td><td>{{ $record->employee->ssnit_number ?? '-' }}</td></tr>
                    <tr><td class="text-muted">TIN #</td><td>{{ $record->employee->tin_number ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Bank</td><td>{{ $record->employee->bank_name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Account #</td><td>{{ $record->employee->bank_account ?? '-' }}</td></tr>
                </table>
            </div>
        </div>

        <!-- Earnings & Deductions -->
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold text-success mb-3"><i class="ti ti-plus me-1"></i>Earnings</h6>
                <table class="table table-sm">
                    <tbody>
                        <tr><td>Basic Salary</td><td class="text-end">GH₵ {{ number_format($record->basic_salary, 2) }}</td></tr>
                        <tr><td>Allowances</td><td class="text-end">GH₵ {{ number_format($record->allowances, 2) }}</td></tr>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold"><td>Gross Pay</td><td class="text-end">GH₵ {{ number_format($record->gross_pay, 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold text-danger mb-3"><i class="ti ti-minus me-1"></i>Deductions</h6>
                <table class="table table-sm">
                    <tbody>
                        <tr><td>SSNIT Employee (5.5%)</td><td class="text-end">GH₵ {{ number_format($record->ssnit_employee, 2) }}</td></tr>
                        <tr><td>PAYE Tax</td><td class="text-end">GH₵ {{ number_format($record->tax, 2) }}</td></tr>
                        <tr><td>Other Deductions</td><td class="text-end">GH₵ {{ number_format($record->other_deductions, 2) }}</td></tr>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold"><td>Total Deductions</td><td class="text-end">GH₵ {{ number_format($record->ssnit_employee + $record->tax + $record->other_deductions, 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <hr>

        <!-- Net Pay -->
        <div class="text-center">
            <h4>Net Pay: <span class="text-success">GH₵ {{ number_format($record->net_pay, 2) }}</span></h4>
            <small class="text-muted">
                Status: <span class="badge bg-{{ $record->status->color() }}">{{ $record->status->label() }}</span>
                @if($record->paid_at)
                    | Paid: {{ $record->paid_at->format('d M Y') }}
                @endif
            </small>
        </div>

        <hr>

        <!-- Employer Note -->
        <div class="text-muted small">
            <strong>Employer SSNIT Contribution (13%):</strong> GH₵ {{ number_format($record->ssnit_employer, 2) }}
            <br>
            <em>Processed by: {{ $record->processedByUser?->name ?? 'System' }} | Generated: {{ now()->format('d M Y H:i') }}</em>
        </div>
    </div>
</div>
@endsection
