@extends('layouts.app')
@section('title', __('payroll.payslip') . ' - ' . $record->employee->full_name)

@section('content')
<div class="d-flex align-items-center mb-3 pb-3 border-bottom d-print-none">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('payroll.payslip') }}</h4>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-primary"><i class="ti ti-printer me-1"></i>{{ __('lab.print_button') }}</button>
        <a href="{{ route('admin.hr.payroll.index', ['pay_period' => $record->pay_period]) }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('stock.back') }}</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <!-- Header -->
        <div class="text-center mb-4">
            <h3 class="mb-1">{{ config('app.name', 'UHMS') }}</h3>
            <h5 class="text-muted">{{ __('payroll.employee_payslip') }}</h5>
            <p class="mb-0">{{ __('payroll.pay_period') }}: <strong>{{ \Carbon\Carbon::parse($record->pay_period . '-01')->format('F Y') }}</strong></p>
        </div>

        <hr>

        <!-- Employee Info -->
        <div class="row mb-4">
            <div class="col-md-6">
                <table class="table table-borderless table-sm mb-0">
                    <tr><td class="text-muted" width="40%">{{ __('payroll.employee_name') }}</td><td class="fw-bold">{{ $record->employee->full_name }}</td></tr>
                    <tr><td class="text-muted">{{ __('payroll.employee_number') }}</td><td>{{ $record->employee->employee_number }}</td></tr>
                    <tr><td class="text-muted">{{ __('payroll.department') }}</td><td>{{ $record->employee->department?->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">{{ __('payroll.position') }}</td><td>{{ $record->employee->position }}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless table-sm mb-0">
                    <tr><td class="text-muted" width="40%">{{ __('payroll.ssnit_number') }}</td><td>{{ $record->employee->ssnit_number ?? '-' }}</td></tr>
                    <tr><td class="text-muted">{{ __('payroll.tin_number') }}</td><td>{{ $record->employee->tin_number ?? '-' }}</td></tr>
                    <tr><td class="text-muted">{{ __('payroll.bank') }}</td><td>{{ $record->employee->bank_name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">{{ __('payroll.account_number') }}</td><td>{{ $record->employee->bank_account ?? '-' }}</td></tr>
                </table>
            </div>
        </div>

        <!-- Earnings & Deductions -->
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold text-success mb-3"><i class="ti ti-plus me-1"></i>{{ __('payroll.earnings') }}</h6>
                <table class="table table-sm">
                    <tbody>
                        <tr><td>{{ __('payroll.basic_salary') }}</td><td class="text-end">GH₵ {{ number_format($record->basic_salary, 2) }}</td></tr>
                        <tr><td>{{ __('payroll.allowances') }}</td><td class="text-end">GH₵ {{ number_format($record->allowances, 2) }}</td></tr>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold"><td>{{ __('payroll.gross_pay') }}</td><td class="text-end">GH₵ {{ number_format($record->gross_pay, 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold text-danger mb-3"><i class="ti ti-minus me-1"></i>{{ __('payroll.deductions') }}</h6>
                <table class="table table-sm">
                    <tbody>
                        <tr><td>{{ __('payroll.ssnit_employee') }}</td><td class="text-end">GH₵ {{ number_format($record->ssnit_employee, 2) }}</td></tr>
                        <tr><td>{{ __('payroll.paye_tax') }}</td><td class="text-end">GH₵ {{ number_format($record->tax, 2) }}</td></tr>
                        <tr><td>{{ __('payroll.other_deductions_short') }}</td><td class="text-end">GH₵ {{ number_format($record->other_deductions, 2) }}</td></tr>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold"><td>{{ __('payroll.total_deductions') }}</td><td class="text-end">GH₵ {{ number_format($record->ssnit_employee + $record->tax + $record->other_deductions, 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <hr>

        <!-- Net Pay -->
        <div class="text-center">
            <h4>{{ __('payroll.net_pay') }}: <span class="text-success">GH₵ {{ number_format($record->net_pay, 2) }}</span></h4>
            <small class="text-muted">
                {{ __('payroll.status') }}: <x-status-badge :status="$record->status" />
                @if($record->paid_at)
                    | {{ __('payroll.paid') }}: {{ $record->paid_at->format('d M Y') }}
                @endif
            </small>
        </div>

        <hr>

        <!-- Employer Note -->
        <div class="text-muted small">
            <strong>{{ __('payroll.employer_ssnit_contribution') }}:</strong> GH₵ {{ number_format($record->ssnit_employer, 2) }}
            <br>
            <em>{{ __('payroll.processed_by') }}: {{ $record->processedByUser?->name ?? __('payroll.system') }} | {{ __('payroll.generated') }}: {{ now()->format('d M Y H:i') }}</em>
        </div>
    </div>
</div>
@endsection
