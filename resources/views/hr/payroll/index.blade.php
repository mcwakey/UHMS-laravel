@extends('layouts.app')
@section('title', __('payroll.payroll'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('payroll.payroll_for_period', ['period' => \Carbon\Carbon::parse($payPeriod . '-01')->format('F Y')]) }}</h4>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" action="{{ route('admin.hr.payroll.index') }}" class="d-flex gap-2">
            <input type="month" name="pay_period" class="form-control" value="{{ $payPeriod }}" onchange="this.form.submit()">
            <select name="status" class="form-select" style="width:130px;" onchange="this.form.submit()">
                <option value="">{{ __('payroll.all_status') }}</option>
                @foreach($statuses as $s)
                    <option value="{{ $s->value }}" {{ request('status') == $s->value ? 'selected' : '' }}>{{ $s->translatedLabel() }}</option>
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
                        <small class="text-muted">{{ __('payroll.employees') }}</small>
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
                        <small class="text-muted">{{ __('payroll.total_gross') }}</small>
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
                        <small class="text-muted">{{ __('payroll.total_deductions') }}</small>
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
                        <small class="text-muted">{{ __('payroll.total_net_pay') }}</small>
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
                <label class="form-label mb-0 text-nowrap">{{ __('payroll.allowances') }}:</label>
                <input type="number" name="allowances" class="form-control form-control-sm" style="width:120px;" step="0.01" value="0">
                <label class="form-label mb-0 text-nowrap ms-2">{{ __('payroll.other_deductions_short') }}:</label>
                <input type="number" name="other_deductions" class="form-control form-control-sm" style="width:120px;" step="0.01" value="0">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-calculator me-1"></i>{{ __('payroll.process_payroll') }}</button>
        </form>
        <form method="POST" action="{{ route('admin.hr.payroll.approve') }}" class="d-inline">
            @csrf
            <input type="hidden" name="pay_period" value="{{ $payPeriod }}">
            <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>{{ __('payroll.approve_all') }}</button>
        </form>
        <form method="POST" action="{{ route('admin.hr.payroll.mark-paid') }}" class="d-inline">
            @csrf
            <input type="hidden" name="pay_period" value="{{ $payPeriod }}">
            <button type="submit" class="btn btn-success btn-sm"><i class="ti ti-coin me-1"></i>{{ __('payroll.mark_paid') }}</button>
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
                        <th>{{ __('payroll.employee') }}</th>
                        <th>{{ __('payroll.department') }}</th>
                        <th class="text-end">{{ __('payroll.basic') }}</th>
                        <th class="text-end">{{ __('payroll.allowances') }}</th>
                        <th class="text-end">{{ __('payroll.gross') }}</th>
                        <th class="text-end">SSNIT (5.5%)</th>
                        <th class="text-end">{{ __('payroll.tax') }}</th>
                        <th class="text-end">{{ __('payroll.other_deductions_short') }}</th>
                        <th class="text-end">{{ __('payroll.net_pay') }}</th>
                        <th>{{ __('payroll.status') }}</th>
                        <th class="text-end">{{ __('payroll.actions') }}</th>
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
                        <td><x-status-badge :status="$rec->status" /></td>
                        <td class="text-end">
                            <a href="{{ route('admin.hr.payroll.payslip', $rec) }}" class="btn btn-sm btn-outline-info" title="{{ __('payroll.payslip') }}"><i class="ti ti-file-text"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">{{ __('payroll.no_records') }}</td></tr>
                    @endforelse
                </tbody>
                @if($payroll->count() > 0)
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="2" class="text-end">{{ __('payroll.totals') }}:</td>
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
    <strong>{{ __('payroll.employer_ssnit_contribution') }}:</strong> GH₵ {{ number_format($summary['total_ssnit_employer'], 2) }}
    - {{ __('payroll.employer_ssnit_note') }}
</div>
@endif
@endsection
