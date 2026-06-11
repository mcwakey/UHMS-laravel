@extends('layouts.app')
@section('title', __('reports.hr.payroll_title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('reports.hr.payroll_title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('common.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('reports.hr.payroll_title') }}</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.payroll', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
            <i class="ti ti-file-spreadsheet me-1"></i>{{ __('reports.actions.excel') }}
        </a>
        <a href="{{ route('admin.reports.payroll', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger btn-sm">
            <i class="ti ti-file-type-pdf me-1"></i>{{ __('reports.actions.pdf') }}
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.total_records') }}</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['total_records']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.total_gross') }}</p>
                <h4 class="fw-bold mb-0 text-success">₵{{ number_format($stats['total_gross'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-danger border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.total_deductions') }}</p>
                <h4 class="fw-bold mb-0 text-danger">₵{{ number_format($stats['total_deductions'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.total_net') }}</p>
                <h4 class="fw-bold mb-0 text-info">₵{{ number_format($stats['total_net'], 2) }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Department Breakdown -->
@if($byDepartment->count())
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">{{ __('reports.charts.pay_by_dept') }}</h6></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('reports.department') }}</th>
                    <th class="text-end">{{ __('reports.hr.staff') }}</th>
                    <th class="text-end">{{ __('reports.columns.gross') }}</th>
                    <th class="text-end">{{ __('reports.columns.deductions') }}</th>
                    <th class="text-end">{{ __('reports.hr.net_pay') }}</th>
                </tr>
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
                <label class="form-label">{{ __('reports.hr.pay_period') }}</label>
                <input type="month" name="pay_period" class="form-control" value="{{ $filters['pay_period'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.department') }}</label>
                <select name="department_id" class="form-select">
                    <option value="">{{ __('reports.all_departments') }}</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach(\App\Enums\PayrollStatus::cases() as $ps)
                    <option value="{{ $ps->value }}" {{ ($filters['status'] ?? '') == $ps->value ? 'selected' : '' }}>{{ $ps->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">{{ __('reports.filter') }}</button>
                <a href="{{ route('admin.reports.payroll') }}" class="btn btn-outline-secondary">{{ __('reports.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('reports.columns.employee') }}</th>
                    <th>{{ __('reports.department') }}</th>
                    <th>{{ __('reports.hr.pay_period') }}</th>
                    <th class="text-end">{{ __('reports.columns.basic') }}</th>
                    <th class="text-end">{{ __('reports.columns.allowances') }}</th>
                    <th class="text-end">{{ __('reports.columns.deductions') }}</th>
                    <th class="text-end">{{ __('reports.hr.net_pay') }}</th>
                    <th>{{ __('reports.status') }}</th>
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
                <tr><td colspan="8"><x-empty-state message="{{ __('reports.empty.no_payroll') }}" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
    <div class="card-footer">{{ $records->links() }}</div>
    @endif
</div>
@endsection
